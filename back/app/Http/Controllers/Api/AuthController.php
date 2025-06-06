<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use App\Services\{UserService, TwoFactorService};
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;
use App\Enums\UserErrorCode;
use App\Exceptions\BusinessException;
use App\Traits\HasApiResponse;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Hash, Log, RateLimiter};
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * 認證 API 控制器
 * 
 * 提供完整的企業級認證系統，包含安全登入、2FA雙因子驗證、Token管理等功能
 * 
 * @group 認證管理
 * @package App\Http\Controllers\Api
 * @author LomisX3 Team
 * @version 6.2
 * 
 * 功能特色：
 * - 安全登入 (節流保護)
 * - 2FA 雙因子驗證
 * - Token 管理
 * - 登入歷史追蹤
 * - 密碼重設
 * - 帳號鎖定機制
 */
class AuthController extends Controller
{
    use HasApiResponse;
    /**
     * 建構函式
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserService $userService,
        private readonly TwoFactorService $twoFactorService
    ) {
        // 認證中介軟體設定
        $this->middleware('auth:sanctum')->except(['login', 'twoFactorChallenge']);
        $this->middleware('throttle:5,1')->only(['login', 'twoFactorChallenge']);
    }

    /**
     * 使用者登入
     * 
     * 處理使用者登入驗證，支援使用者名稱或信箱登入。具備節流保護、門市隔離、2FA檢查等安全機制。
     * 
     * @group 認證管理
     * 
     * @bodyParam login string required 登入帳號（使用者名稱或信箱） Example: admin@lomis.com
     * @bodyParam password string required 密碼 Example: SecurePass123!
     * @bodyParam device_name string 裝置名稱（用於Token識別） Example: Chrome on Windows
     * @bodyParam remember boolean 記住我（延長Token有效期） Example: true
     * @bodyParam store_code string 門市代碼（可選，用於門市特定登入） Example: STORE001
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "登入成功",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "username": "admin",
     *       "name": "管理員",
     *       "email": "admin@lomis.com",
     *       "store_id": 1,
     *       "status": {
     *         "value": "active",
     *         "label": "啟用",
     *         "color": "success",
     *         "is_active": true
     *       },
     *       "roles": [
     *         {
     *           "id": 1,
     *           "name": "admin",
     *           "display_name": "管理員",
     *           "level": 100,
     *           "color": "primary"
     *         }
     *       ],
     *       "avatar": {
     *         "url": "https://example.com/avatars/1.jpg",
     *         "thumbnail_url": "https://example.com/avatars/1-thumb.jpg",
     *         "has_avatar": true
     *       }
     *     },
     *     "token": "1|abcdef123456...",
     *     "expires_at": "2025-01-08T10:00:00.000000Z",
     *     "permissions": ["users.view", "users.create", "users.update"],
     *     "store": {
     *       "id": 1,
     *       "name": "總店"
     *     }
     *   }
     * }
     * 
     * @response 428 {
     *   "success": false,
     *   "message": "需要雙因子驗證",
     *   "requires_2fa": true,
     *   "user_id": 1,
     *   "error_code": "TWO_FACTOR_REQUIRED"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "登入失敗：帳號或密碼錯誤",
     *   "error_code": "INVALID_CREDENTIALS"
     * }
     * 
     * @response 423 {
     *   "success": false,
     *   "message": "帳號已被鎖定",
     *   "error_code": "ACCOUNT_LOCKED",
     *   "locked_until": "2025-01-07T11:00:00.000000Z"
     * }
     * 
     * @response 429 {
     *   "success": false,
     *   "message": "登入嘗試過於頻繁，請稍後再試",
     *   "error_code": "TOO_MANY_ATTEMPTS"
     * }
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // 🛡️ 輸入驗證
            $request->validate([
                'login' => ['required', 'string'],
                'password' => ['required', 'string'],
                'device_name' => ['nullable', 'string', 'max:255'],
                'remember' => ['boolean'],
            ]);

            // 🔍 使用 Repository 層查找使用者（符合架構標準）
            $user = $this->userRepository->findByEmailOrUsername($request->input('login'));

            // ❌ 驗證使用者存在且密碼正確
            if (!$user || !Hash::check($request->input('password'), $user->password)) {
                Log::warning('登入失敗：無效憑證', [
                    'login' => $request->input('login'),
                    'ip' => $request->ip()
                ]);
                
                throw BusinessException::fromErrorCode(UserErrorCode::INVALID_CREDENTIALS);
            }

            // 🔒 使用 Service 層驗證使用者狀態（符合架構標準）
            $this->userService->validateUserCanLogin($user);

            // 📱 檢查 2FA 需求
            if ($user->requiresTwoFactorAuthentication()) {
                return $this->apiSuccess([
                    'requires_2fa' => true,
                    'user_id' => $user->id,
                ], '需要雙因子驗證', 428);
            }

            // ✅ 建立 Sanctum Token
            $deviceName = $request->input('device_name', 'unknown-device');
            $rememberMe = $request->boolean('remember', false);
            
            // 設定 Token 能力（基於角色的細粒度權限）
            $abilities = $user->getAllPermissions()->pluck('name')->toArray();
            $token = $user->createToken($deviceName, $abilities);

            // 📝 記錄成功登入
            $this->userService->recordSuccessfulLogin($user, $request->ip());

            // 🎯 載入完整的使用者關聯資料
            $user->load(['roles', 'permissions', 'store']);

            // 🔧 使用標準 UserResource 格式化回應（符合架構標準）
            $userData = new UserResource($user);

            // 🎉 回傳完整的認證資訊
            return $this->apiSuccess([
                'user' => $userData,
                'token' => $token->plainTextToken,
                'expires_at' => $rememberMe ? 
                    now()->addDays(30)->toISOString() : 
                    now()->addHours(8)->toISOString(),
                'permissions' => $abilities,
                'store' => $user->store ? [
                    'id' => $user->store->id,
                    'name' => $user->store->name,
                    'code' => $user->store->code,
                ] : null,
            ], '登入成功');

        } catch (BusinessException $e) {
            // 企業級錯誤處理 - 使用正確的 HTTP 狀態碼和錯誤代碼
            return $this->apiError($e->getMessage(), $e->getHttpStatus(), $e->getErrorCode());
        } catch (\Throwable $e) {
            // 記錄未預期錯誤
            Log::error('登入系統錯誤', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->only(['login', 'device_name'])
            ]);
            
            return $this->apiError('系統暫時無法處理登入請求，請稍後再試', 500, 'SYSTEM_ERROR');
        }
    }

    /**
     * 2FA 挑戰驗證
     * 
     * 處理雙因子驗證代碼驗證，支援 TOTP 代碼和恢復代碼兩種驗證方式
     * 
     * @group 認證管理
     * 
     * @bodyParam user_id integer required 使用者ID（來自登入回應） Example: 1
     * @bodyParam code string 6位數TOTP驗證碼（與recovery_code二選一） Example: 123456
     * @bodyParam recovery_code string 10字元恢復代碼（與code二選一） Example: abc123defg
     * @bodyParam device_name string 裝置名稱 Example: Chrome on Windows
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "雙因子驗證成功",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "username": "admin",
     *       "name": "管理員",
     *       "email": "admin@lomis.com",
     *       "store_id": 1,
     *       "roles": [
     *         {
     *           "id": 1,
     *           "name": "admin",
     *           "display_name": "管理員"
     *         }
     *       ]
     *     },
     *     "token": "2|xyz789...",
     *     "expires_at": "2025-01-08T10:00:00.000000Z",
     *     "permissions": ["users.view", "users.create"],
     *     "store": {
     *       "id": 1,
     *       "name": "總店"
     *     }
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "驗證代碼錯誤",
     *   "error_code": "INVALID_2FA_CODE"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "使用者不存在",
     *   "error_code": "USER_NOT_FOUND"
     * }
     * 
     * @response 429 {
     *   "success": false,
     *   "message": "驗證嘗試過於頻繁",
     *   "error_code": "TOO_MANY_ATTEMPTS"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function twoFactorChallenge(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'code' => 'required_without:recovery_code|string|size:6',
            'recovery_code' => 'required_without:code|string|size:10',
            'device_name' => 'nullable|string|max:255'
        ]);
        
        try {
            $user = User::findOrFail($request->user_id);
            
            // 驗證 2FA 代碼
            if ($request->filled('code')) {
                $isValid = $this->twoFactorService->verifyCode($user, $request->code);
            } else {
                $isValid = $this->twoFactorService->verifyRecoveryCode($user, $request->recovery_code);
            }
            
            if (!$isValid) {
                // 記錄失敗嘗試
                activity('2fa_verification_failed')
                    ->causedBy($user)
                    ->withProperties([
                        'ip_address' => $request->ip(),
                        'verification_type' => $request->filled('code') ? 'totp' : 'recovery'
                    ])
                    ->log('2FA 驗證失敗');
                
                throw new BusinessException(
                    message: '驗證代碼錯誤',
                    code: UserErrorCode::INVALID_2FA_CODE
                );
            }
            
            // 2FA 驗證成功，建立 Token
            $deviceName = $request->device_name ?? '未知裝置';
            $token = $user->createToken($deviceName, ['*'], now()->addHours(24));
            
            // 更新登入資訊
            $this->userService->updateLoginInfo($user, $request);
            
            // 記錄成功活動
            activity('2fa_verification_success')
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'device_name' => $deviceName,
                    'verification_type' => $request->filled('code') ? 'totp' : 'recovery'
                ])
                ->log('2FA 驗證成功');
            
            return response()->json([
                'success' => true,
                'message' => '雙因子驗證成功',
                'data' => [
                    'user' => new UserResource($user->load(['roles', 'permissions', 'store'])),
                    'token' => $token->plainTextToken,
                    'expires_at' => $token->accessToken->expires_at->toISOString(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'store' => [
                        'id' => $user->store_id,
                        'name' => $user->store->name ?? null
                    ]
                ]
            ]);
            
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], $e->getHttpStatusCode());
        }
    }

    /**
     * 啟用雙因子驗證
     * 
     * 為使用者帳號啟用2FA功能，生成QR Code供驗證器應用程式掃描
     * 
     * @group 認證管理
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "2FA 已啟用，請掃描 QR Code",
     *   "data": {
     *     "qr_code": "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCI+...",
     *     "secret": "ABCDEFGHIJKLMNOP",
     *     "recovery_codes": [
     *       "abc123defg",
     *       "hij456klmn",
     *       "opq789rstu"
     *     ],
     *     "manual_entry_key": "ABCD EFGH IJKL MNOP"
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "雙因子驗證已啟用",
     *   "error_code": "2FA_ALREADY_ENABLED"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "未授權"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function enable2FA(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // 檢查是否已啟用 2FA
            if ($user->two_factor_confirmed_at) {
                return response()->json([
                    'success' => false,
                    'message' => '雙因子驗證已啟用',
                    'error_code' => UserErrorCode::TWO_FACTOR_ALREADY_ENABLED->value
                ], 422);
            }
            
            // 生成 2FA 密鑰和 QR Code
            $result = $this->twoFactorService->enable($user);
            
            // 記錄活動
            activity('2fa_enabled')
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ])
                ->log('啟用雙因子驗證');
            
            return response()->json([
                'success' => true,
                'message' => '2FA 已啟用，請掃描 QR Code',
                'data' => $result
            ]);
            
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], $e->getHttpStatusCode());
        }
    }

    /**
     * 確認雙因子驗證
     * 
     * 使用驗證器應用程式產生的代碼確認2FA設定
     * 
     * @group 認證管理
     * 
     * @bodyParam code string required 6位數驗證碼 Example: 123456
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "雙因子驗證確認成功",
     *   "data": {
     *     "confirmed_at": "2025-01-07T10:00:00.000000Z",
     *     "recovery_codes": [
     *       "abc123defg",
     *       "hij456klmn",
     *       "opq789rstu"
     *     ]
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "驗證碼錯誤",
     *   "error_code": "INVALID_2FA_CODE"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "雙因子驗證尚未啟用",
     *   "error_code": "2FA_NOT_ENABLED"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function confirm2FA(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);
        
        try {
            $user = Auth::user();
            
            // 確認 2FA 代碼
            $result = $this->twoFactorService->confirm($user, $request->code);
            
            // 記錄活動
            activity('2fa_confirmed')
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ])
                ->log('確認雙因子驗證');
            
            return response()->json([
                'success' => true,
                'message' => '雙因子驗證確認成功',
                'data' => $result
            ]);
            
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], $e->getHttpStatusCode());
        }
    }

    /**
     * 停用雙因子驗證
     * 
     * 關閉使用者的2FA功能，需要密碼確認
     * 
     * @group 認證管理
     * 
     * @bodyParam password string required 使用者密碼 Example: CurrentPassword123!
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "雙因子驗證已停用"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "密碼錯誤",
     *   "error_code": "INVALID_PASSWORD"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "雙因子驗證尚未啟用",
     *   "error_code": "2FA_NOT_ENABLED"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function disable2FA(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string'
        ]);
        
        try {
            $user = Auth::user();
            
            // 停用 2FA
            $this->twoFactorService->disable($user, $request->password);
            
            // 記錄活動
            activity('2fa_disabled')
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ])
                ->log('停用雙因子驗證');
            
            return response()->json([
                'success' => true,
                'message' => '雙因子驗證已停用'
            ]);
            
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], $e->getHttpStatusCode());
        }
    }

    /**
     * 使用者登出
     * 
     * 登出當前使用者，撤銷當前的 API Token
     * 
     * @group 認證管理
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "登出成功"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "未授權"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * 登出
     * 
     * 撤銷當前使用者的 Sanctum Token 並記錄登出活動
     * 
     * @group 認證管理
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "登出成功"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "未授權",
     *   "error_code": "UNAUTHENTICATED"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // 🔒 檢查是否有認證的使用者
            $user = $request->user();
            
            // 如果沒有認證的使用者，直接返回成功（冪等性）
            if (!$user) {
                return $this->apiSuccess([], '登出成功');
            }
            
            // 🗝️ 安全撤銷當前 Token
            $currentToken = $request->user()->currentAccessToken();
            if ($currentToken) {
                $currentToken->delete();
            }
            
            // 📝 記錄登出活動（只有在使用者存在時）
            try {
                activity('user_logout')
                    ->causedBy($user)
                    ->withProperties([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'token_name' => $currentToken?->name ?? 'unknown'
                    ])
                    ->log('使用者登出');
            } catch (\Exception $logError) {
                // 活動記錄失敗不應該影響登出流程
                Log::warning('登出活動記錄失敗', [
                    'user_id' => $user->id,
                    'error' => $logError->getMessage()
                ]);
            }
            
            return $this->apiSuccess([], '登出成功');
            
        } catch (\Exception $e) {
            // 📊 記錄錯誤但不影響用戶體驗
            Log::error('登出系統錯誤', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
                'ip_address' => $request->ip(),
                'has_token' => !is_null($request->bearerToken())
            ]);
            
            // 🎯 即使發生錯誤，也返回成功以保證用戶體驗
            // 客戶端已經清除了本地認證狀態，後端錯誤不應該影響用戶
            return $this->apiSuccess([], '登出成功');
        }
    }

    /**
     * 取得當前使用者資訊
     * 
     * 取得當前認證使用者的詳細資訊
     * 
     * @group 認證管理
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "取得使用者資訊成功",
     *   "data": {
     *     "id": 1,
     *     "username": "admin",
     *     "name": "管理員",
     *     "email": "admin@lomis.com",
     *     "store_id": 1,
     *     "store": {
     *       "id": 1,
     *       "name": "總店",
     *       "code": "STORE001"
     *     },
     *     "phone": "0912345678",
     *     "status": {
     *       "value": "active",
     *       "label": "啟用",
     *       "color": "success",
     *       "is_active": true
     *     },
     *     "email_verified_at": "2025-01-01T00:00:00.000000Z",
     *     "is_email_verified": true,
     *     "two_factor": {
     *       "enabled": true,
     *       "confirmed_at": "2025-01-01T00:00:00.000000Z"
     *     },
     *     "login_info": {
     *       "last_login_at": "2025-01-07T10:00:00.000000Z",
     *       "last_login_ip": "192.168.1.100"
     *     },
     *     "roles": [
     *       {
     *         "id": 1,
     *         "name": "admin",
     *         "display_name": "管理員",
     *         "level": 100,
     *         "color": "primary"
     *       }
     *     ],
     *     "permissions": ["users.view", "users.create", "users.update"],
     *     "avatar": {
     *       "url": "https://example.com/avatars/1.jpg",
     *       "thumbnail_url": "https://example.com/avatars/1-thumb.jpg",
     *       "has_avatar": true
     *     }
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "未授權"
     * }
     * 
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = Auth::user();
            $user->load(['roles', 'permissions', 'store']);
            
            return response()->json([
                'success' => true,
                'message' => '取得使用者資訊成功',
                'data' => new UserResource($user)
            ]);
            
        } catch (\Exception $e) {
            Log::error('取得使用者資訊錯誤', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '取得使用者資訊失敗',
                'error_code' => UserErrorCode::USER_INFO_FAILED->value
            ], 500);
        }
    }

    /**
     * 刷新 Token
     * 
     * 刷新當前的 API Token，延長有效期限
     * 
     * @group 認證管理
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Token 刷新成功",
     *   "data": {
     *     "token": "3|newtoken123...",
     *     "expires_at": "2025-01-08T10:00:00.000000Z"
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "未授權"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // 撤銷當前 Token
            $request->user()->currentAccessToken()->delete();
            
            // 建立新 Token
            $deviceName = $request->user()->currentAccessToken()->name ?? '未知裝置';
            $newToken = $user->createToken($deviceName, ['*'], now()->addHours(24));
            
            // 記錄活動
            activity('token_refreshed')
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $request->ip(),
                    'device_name' => $deviceName
                ])
                ->log('Token 刷新');
            
            return response()->json([
                'success' => true,
                'message' => 'Token 刷新成功',
                'data' => [
                    'token' => $newToken->plainTextToken,
                    'expires_at' => $newToken->accessToken->expires_at->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Token 刷新錯誤', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip_address' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Token 刷新失敗',
                'error_code' => UserErrorCode::TOKEN_REFRESH_FAILED->value
            ], 500);
        }
    }

    /**
     * 處理登入邏輯
     * 
     * @param array $loginData
     * @param Request $request
     * @return array
     */
    private function processLogin(array $loginData, Request $request): array
    {
        // 找尋使用者
        $user = $this->findUserByLogin($loginData['login']);
        
        if (!$user || !password_verify($loginData['password'], $user->password)) {
            throw new BusinessException(
                UserErrorCode::INVALID_CREDENTIALS->message(),
                UserErrorCode::INVALID_CREDENTIALS->value,
                UserErrorCode::INVALID_CREDENTIALS->httpStatus()
            );
        }

        // 驗證使用者狀態
        $this->validateUserStatus($user);

        // 檢查是否需要 2FA
        if ($user->has_2fa) {
            return [
                'requires_2fa' => true,
                'user_id' => $user->id,
                'message' => UserErrorCode::TWO_FACTOR_REQUIRED->message()
            ];
        }

        // 更新登入資訊
        $this->userRepository->updateQuietly($user->id, [
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'login_attempts' => 0,
            'locked_until' => null
        ]);

        // 建立 Token
        $deviceName = $loginData['device_name'] ?? '未知裝置';
        $token = $user->createToken($deviceName, ['*'], now()->addHours(24));

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => now()->addHours(24)
        ];
    }

    /**
     * 根據登入名稱查找使用者
     * 
     * @param string $login
     * @return User|null
     */
    private function findUserByLogin(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return $this->userRepository->findByEmail($login);
        }
        
        return $this->userRepository->findByUsername($login);
    }

    /**
     * 驗證使用者狀態
     * 
     * @param User $user
     * @throws BusinessException
     */
    private function validateUserStatus(User $user): void
    {
        switch ($user->status) {
            case 'inactive':
                throw new BusinessException(
                    UserErrorCode::ACCOUNT_INACTIVE->message(),
                    UserErrorCode::ACCOUNT_INACTIVE->value,
                    UserErrorCode::ACCOUNT_INACTIVE->httpStatus()
                );
                
            case 'locked':
                if ($user->locked_until && $user->locked_until->isFuture()) {
                    throw new BusinessException(
                        UserErrorCode::ACCOUNT_LOCKED->message(),
                        UserErrorCode::ACCOUNT_LOCKED->value,
                        UserErrorCode::ACCOUNT_LOCKED->httpStatus()
                    );
                }
                break;
                
            case 'pending':
                throw new BusinessException(
                    UserErrorCode::ACCOUNT_PENDING->message(),
                    UserErrorCode::ACCOUNT_PENDING->value,
                    UserErrorCode::ACCOUNT_PENDING->httpStatus()
                );
        }

        if (!$user->email_verified_at) {
            throw new BusinessException(
                UserErrorCode::EMAIL_NOT_VERIFIED->message(),
                UserErrorCode::EMAIL_NOT_VERIFIED->value,
                UserErrorCode::EMAIL_NOT_VERIFIED->httpStatus()
            );
        }
    }
}
