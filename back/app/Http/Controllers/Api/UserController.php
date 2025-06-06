<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Requests\User\{IndexUserRequest, StoreUserRequest, UpdateUserRequest, BatchStatusUserRequest};
use App\Http\Resources\User\{UserResource, UserCollection};
use App\Models\User;
use App\Exceptions\BusinessException;
use Illuminate\Http\{JsonResponse, Request};

/**
 * 使用者管理 API 控制器
 * 
 * 提供完整的使用者管理功能，包含門市隔離、角色權限、2FA、頭像管理等企業級功能
 * 
 * @group 使用者管理
 * @package App\Http\Controllers\Api
 * @author LomisX3 Team
 * @version 6.2
 */
class UserController extends Controller
{
    /**
     * 建構子
     * 
     * @param UserService $service 使用者服務
     */
    public function __construct(
        protected UserService $service
    ) {
        // 應用使用者政策
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * 取得使用者列表
     * 
     * 支援分頁、搜尋、篩選、排序等功能。具備門市隔離機制，非管理員僅能查看同門市使用者。
     * 
     * @group 使用者管理
     * 
     * @queryParam page integer 頁碼 Example: 1
     * @queryParam per_page integer 每頁項目數（1-100） Example: 20
     * @queryParam search string 搜尋關鍵字（支援名稱、使用者名稱、信箱） Example: John
     * @queryParam keyword string 關鍵字搜尋 Example: admin
     * @queryParam status string 使用者狀態篩選 No-example
     * @queryParam store_id integer 門市ID篩選 Example: 1
     * @queryParam role string 角色篩選 Example: staff
     * @queryParam has_2fa boolean 是否啟用雙因子驗證 Example: true
     * @queryParam email_verified boolean 信箱是否已驗證 Example: true
     * @queryParam created_from string 建立日期起始 Example: 2025-01-01
     * @queryParam created_to string 建立日期結束 Example: 2025-12-31
     * @queryParam last_login_from string 最後登入起始日期 Example: 2025-01-01
     * @queryParam last_login_to string 最後登入結束日期 Example: 2025-12-31
     * @queryParam sort string 排序欄位 No-example
     * @queryParam order string 排序方向（asc/desc） No-example
     * @queryParam include string 包含關聯資料 Example: roles,store
     * @queryParam with_count boolean 是否包含統計計數 Example: true
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "取得使用者列表成功",
     *   "data": {
     *     "data": [
     *       {
     *         "id": 1,
     *         "username": "admin",
     *         "name": "管理員",
     *         "email": "admin@lomis.com",
     *         "store_id": 1,
     *         "phone": "0912345678",
     *         "status": {
     *           "value": "active",
     *           "label": "啟用",
     *           "color": "success",
     *           "is_active": true
     *         },
     *         "email_verified_at": "2025-01-01T00:00:00.000000Z",
     *         "is_email_verified": true,
     *         "two_factor": {
     *           "enabled": true,
     *           "confirmed_at": "2025-01-01T00:00:00.000000Z"
     *         },
     *         "login_info": {
     *           "last_login_at": "2025-01-07T10:00:00.000000Z",
     *           "is_locked": false,
     *           "locked_until": null
     *         },
     *         "roles": [
     *           {
     *             "id": 1,
     *             "name": "admin",
     *             "display_name": "管理員",
     *             "level": 100,
     *             "color": "primary"
     *           }
     *         ],
     *         "avatar": {
     *           "url": "https://example.com/avatars/1.jpg",
     *           "thumbnail_url": "https://example.com/avatars/1-thumb.jpg",
     *           "has_avatar": true
     *         },
     *         "audit": {
     *           "created_at": "2025-01-01T00:00:00.000000Z",
     *           "updated_at": "2025-01-07T10:00:00.000000Z"
     *         }
     *       }
     *     ],
     *     "links": {
     *       "first": "http://localhost/api/users?page=1",
     *       "last": "http://localhost/api/users?page=10",
     *       "prev": null,
     *       "next": "http://localhost/api/users?page=2"
     *     },
     *     "meta": {
     *       "current_page": 1,
     *       "from": 1,
     *       "last_page": 10,
     *       "per_page": 20,
     *       "to": 20,
     *       "total": 200
     *     }
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "驗證失敗",
     *   "errors": {
     *     "per_page": ["每頁最多只能顯示 100 筆資料"]
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @param IndexUserRequest $request
     * @return JsonResponse
     */
    public function index(IndexUserRequest $request): JsonResponse
    {
        try {
            $filters = $request->getFilters();
            $perPage = $request->integer('per_page', 20);
            
            $result = $this->service->getList($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'message' => '取得使用者列表成功',
                'data' => new UserCollection($result)
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 顯示使用者詳情
     * 
     * 取得單一使用者的詳細資訊，包含角色、權限、門市、頭像、登入歷史等完整資料
     * 
     * @group 使用者管理
     * 
     * @urlParam user integer required 使用者ID Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "取得使用者詳情成功",
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
     *       "last_login_ip": "192.168.1.100",
     *       "login_attempts": 0,
     *       "is_locked": false,
     *       "locked_until": null
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
     *     },
     *     "statistics": {
     *       "media_count": 5,
     *       "tokens_count": 3,
     *       "activities_count": 150,
     *       "created_users_count": 25
     *     },
     *     "audit": {
     *       "created_at": "2025-01-01T00:00:00.000000Z",
     *       "updated_at": "2025-01-07T10:00:00.000000Z",
     *       "created_by": {
     *         "id": 1,
     *         "name": "系統管理員"
     *       }
     *     },
     *     "actions": {
     *       "can_update": true,
     *       "can_delete": false,
     *       "can_reset_password": true,
     *       "can_toggle_2fa": true
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "使用者不存在"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        try {
            $userDetail = $this->service->getDetail($user->id);
            
            return response()->json([
                'success' => true,
                'message' => '取得使用者詳情成功',
                'data' => new UserResource($userDetail)
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 建立使用者
     * 
     * 建立新的使用者帳號。支援自動密碼生成、角色指派、門市隔離等功能。
     * 
     * @group 使用者管理
     * 
     * @bodyParam username string required 使用者名稱（3-50字元，唯一） Example: john_doe
     * @bodyParam name string required 姓名（2-100字元） Example: 約翰
     * @bodyParam email string required 電子信箱（唯一） Example: john@example.com
     * @bodyParam phone string 手機號碼 Example: 0912345678
     * @bodyParam password string required 密碼（8字元以上，包含大小寫字母、數字、符號） Example: SecurePass123!
     * @bodyParam password_confirmation string required 確認密碼 Example: SecurePass123!
     * @bodyParam store_id integer required 所屬門市ID Example: 1
     * @bodyParam roles array required 角色陣列 Example: ["staff", "manager"]
     * @bodyParam status string 使用者狀態（預設active） No-example
     * @bodyParam send_welcome_email boolean 是否發送歡迎信件（預設true） Example: true
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "建立使用者成功",
     *   "data": {
     *     "id": 25,
     *     "username": "john_doe",
     *     "name": "約翰",
     *     "email": "john@example.com",
     *     "phone": "0912345678",
     *     "store_id": 1,
     *     "status": {
     *       "value": "active",
     *       "label": "啟用",
     *       "color": "success",
     *       "is_active": true
     *     },
     *     "email_verified_at": null,
     *     "is_email_verified": false,
     *     "two_factor": {
     *       "enabled": false,
     *       "confirmed_at": null
     *     },
     *     "roles": [
     *       {
     *         "id": 3,
     *         "name": "staff",
     *         "display_name": "員工",
     *         "level": 40,
     *         "color": "info"
     *       }
     *     ],
     *     "avatar": {
     *       "url": null,
     *       "thumbnail_url": null,
     *       "has_avatar": false
     *     },
     *     "audit": {
     *       "created_at": "2025-01-07T10:30:00.000000Z",
     *       "updated_at": "2025-01-07T10:30:00.000000Z"
     *     }
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "驗證失敗",
     *   "errors": {
     *     "username": ["使用者名稱已被使用"],
     *     "email": ["電子信箱已被使用"],
     *     "password": ["密碼強度不足"]
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "建立使用者失敗：角色權限不足",
     *   "error_code": "USER_CREATE_FAILED"
     * }
     * 
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->service->create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => '建立使用者成功',
                'data' => new UserResource($user)
            ], 201);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 更新使用者
     * 
     * 更新使用者資訊。支援部分更新、角色變更、狀態切換等功能。
     * 
     * @group 使用者管理
     * 
     * @urlParam user integer required 使用者ID Example: 1
     * 
     * @bodyParam name string 姓名（2-100字元） Example: 約翰·史密斯
     * @bodyParam email string 電子信箱（唯一） Example: john.smith@example.com
     * @bodyParam phone string 手機號碼 Example: 0987654321
     * @bodyParam password string 新密碼（8字元以上） Example: NewSecurePass123!
     * @bodyParam password_confirmation string 確認新密碼 Example: NewSecurePass123!
     * @bodyParam store_id integer 所屬門市ID Example: 2
     * @bodyParam roles array 角色陣列 Example: ["manager"]
     * @bodyParam status string 使用者狀態 No-example
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "更新使用者成功",
     *   "data": {
     *     "id": 1,
     *     "username": "john_doe",
     *     "name": "約翰·史密斯",
     *     "email": "john.smith@example.com",
     *     "phone": "0987654321",
     *     "store_id": 2,
     *     "status": {
     *       "value": "active",
     *       "label": "啟用",
     *       "color": "success",
     *       "is_active": true
     *     },
     *     "roles": [
     *       {
     *         "id": 2,
     *         "name": "manager",
     *         "display_name": "管理員",
     *         "level": 60,
     *         "color": "warning"
     *       }
     *     ],
     *     "audit": {
     *       "created_at": "2025-01-07T10:30:00.000000Z",
     *       "updated_at": "2025-01-07T11:15:00.000000Z",
     *       "updated_by": {
     *         "id": 1,
     *         "name": "管理員"
     *       }
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "使用者不存在"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "更新失敗：無法修改自己的角色",
     *   "error_code": "USER_UPDATE_FAILED"
     * }
     * 
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->service->update($user->id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => '更新使用者成功',
                'data' => new UserResource($updatedUser)
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 刪除使用者
     * 
     * 軟刪除使用者帳號。刪除後使用者無法登入，但資料保留以供審計。
     * 
     * @group 使用者管理
     * 
     * @urlParam user integer required 使用者ID Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "刪除使用者成功"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "使用者不存在"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "無法刪除自己的帳號",
     *   "error_code": "USER_DELETE_FAILED"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->service->delete($user->id);
            
            return response()->json([
                'success' => true,
                'message' => '刪除使用者成功'
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 批次更新使用者狀態
     * 
     * 批次啟用或停用多個使用者帳號
     * 
     * @group 使用者管理
     * 
     * @bodyParam user_ids array required 使用者ID陣列 Example: [1, 2, 3]
     * @bodyParam status string required 目標狀態 No-example
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "批次更新狀態成功",
     *   "data": {
     *     "affected_count": 3
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "驗證失敗",
     *   "errors": {
     *     "user_ids": ["至少選擇一個使用者"],
     *     "status": ["狀態值無效"]
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @param BatchStatusUserRequest $request
     * @return JsonResponse
     */
    public function batchStatus(BatchStatusUserRequest $request): JsonResponse
    {
        // 手動權限檢查 - 批次狀態更新權限
        $this->authorize('batchStatus', User::class);
        
        try {
            $affectedCount = $this->service->batchUpdateStatus(
                $request->validated('user_ids'),
                $request->validated('status')
            );
            
            return response()->json([
                'success' => true,
                'message' => '批次更新狀態成功',
                'data' => ['affected_count' => $affectedCount]
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 重設密碼
     * 
     * 管理員重設使用者密碼，可選擇自動生成或指定密碼
     * 
     * @group 使用者管理
     * 
     * @urlParam user integer required 使用者ID Example: 1
     * 
     * @bodyParam password string 新密碼（留空則自動生成） Example: NewPassword123!
     * @bodyParam password_confirmation string 確認密碼（當指定密碼時必填） Example: NewPassword123!
     * @bodyParam auto_generate boolean 是否自動生成密碼（預設false） Example: true
     * @bodyParam send_email boolean 是否發送密碼重設通知（預設true） Example: true
     * @bodyParam force_change boolean 是否強制下次登入修改密碼（預設true） Example: true
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "密碼重設成功",
     *   "data": {
     *     "password_sent_to_email": true,
     *     "temporary_password": "TempPass123!",
     *     "force_change_required": true
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "使用者不存在"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "不能重設自己的密碼",
     *   "error_code": "PASSWORD_RESET_FAILED"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @param User $user
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(User $user, Request $request): JsonResponse
    {
        // 手動權限檢查 - 密碼重設權限
        $this->authorize('resetPassword', $user);
        
        try {
            $validated = $request->validate([
                'password' => 'nullable|string|min:8|confirmed',
                'auto_generate' => 'boolean',
                'send_email' => 'boolean',
                'force_change' => 'boolean'
            ]);
            
            $result = $this->service->resetPassword($user->id, $validated);
            
            return response()->json([
                'success' => true,
                'message' => '密碼重設成功',
                'data' => $result
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 使用者統計資料
     * 
     * 取得使用者相關的統計資訊，包含總數、狀態分布、角色分布等
     * 
     * @group 使用者管理
     * 
     * @queryParam store_id integer 特定門市統計 Example: 1
     * @queryParam period string 統計期間（daily/weekly/monthly/yearly） Example: monthly
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "取得統計資料成功",
     *   "data": {
     *     "overview": {
     *       "total_users": 150,
     *       "active_users": 120,
     *       "inactive_users": 20,
     *       "locked_users": 5,
     *       "pending_users": 5
     *     },
     *     "by_store": [
     *       {
     *         "store_id": 1,
     *         "store_name": "總店",
     *         "user_count": 80
     *       },
     *       {
     *         "store_id": 2,
     *         "store_name": "分店A",
     *         "user_count": 45
     *       }
     *     ],
     *     "by_role": [
     *       {
     *         "role_name": "staff",
     *         "role_display_name": "員工",
     *         "user_count": 100
     *       },
     *       {
     *         "role_name": "manager",
     *         "role_display_name": "管理員",
     *         "user_count": 30
     *       }
     *     ],
     *     "trends": {
     *       "new_users_this_month": 15,
     *       "new_users_last_month": 12,
     *       "growth_rate": 25.0
     *     },
     *     "security": {
     *       "users_with_2fa": 75,
     *       "users_without_2fa": 75,
     *       "unverified_emails": 25
     *     }
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        // 手動權限檢查 - 統計查看權限
        $this->authorize('viewStatistics', User::class);
        
        try {
            $statistics = $this->service->getStatistics();
            
            return response()->json([
                'success' => true,
                'message' => '取得統計資料成功',
                'data' => $statistics
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 上傳使用者頭像
     * 
     * 上傳並設定使用者頭像圖片，支援自動壓縮和縮圖生成
     * 
     * @group 使用者管理
     * 
     * @urlParam user integer required 使用者ID Example: 1
     * 
     * @bodyParam avatar file required 頭像圖片檔案（JPG/PNG，最大2MB） Example: avatar.jpg
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "頭像上傳成功",
     *   "data": {
     *     "avatar": {
     *       "url": "https://example.com/avatars/1.jpg",
     *       "thumbnail_url": "https://example.com/avatars/1-thumb.jpg",
     *       "has_avatar": true
     *     }
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "驗證失敗",
     *   "errors": {
     *     "avatar": ["圖片檔案無效或過大"]
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "使用者不存在"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @param User $user
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAvatar(User $user, Request $request): JsonResponse
    {
        // 手動權限檢查 - 頭像上傳權限
        $this->authorize('uploadAvatar', $user);
        
        try {
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ]);
            
            $result = $this->service->uploadAvatar($user->id, $request->file('avatar'));
            
            return response()->json([
                'success' => true,
                'message' => '頭像上傳成功',
                'data' => $result
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 刪除使用者頭像
     * 
     * 移除使用者的頭像圖片
     * 
     * @group 使用者管理
     * 
     * @urlParam user integer required 使用者ID Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "頭像刪除成功"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "使用者不存在"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "使用者沒有設定頭像",
     *   "error_code": "AVATAR_DELETE_FAILED"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @param User $user
     * @return JsonResponse
     */
    public function deleteAvatar(User $user): JsonResponse
    {
        // 手動權限檢查 - 頭像刪除權限
        $this->authorize('deleteAvatar', $user);
        
        try {
            $this->service->deleteAvatar($user->id);
            
            return response()->json([
                'success' => true,
                'message' => '頭像刪除成功'
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }

    /**
     * 使用者活動記錄
     * 
     * 取得使用者的操作活動記錄，支援分頁和篩選
     * 
     * @group 使用者管理
     * 
     * @urlParam user integer required 使用者ID Example: 1
     * 
     * @queryParam page integer 頁碼 Example: 1
     * @queryParam per_page integer 每頁項目數 Example: 20
     * @queryParam log_name string 活動類型篩選 Example: user_login
     * @queryParam from_date string 開始日期 Example: 2025-01-01
     * @queryParam to_date string 結束日期 Example: 2025-01-31
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "取得活動記錄成功",
     *   "data": {
     *     "data": [
     *       {
     *         "id": 1,
     *         "log_name": "user_login",
     *         "description": "使用者登入成功",
     *         "properties": {
     *           "ip_address": "192.168.1.100",
     *           "user_agent": "Mozilla/5.0...",
     *           "device_name": "Chrome on Windows"
     *         },
     *         "created_at": "2025-01-07T10:00:00.000000Z"
     *       },
     *       {
     *         "id": 2,
     *         "log_name": "user_update",
     *         "description": "使用者資料更新",
     *         "properties": {
     *           "updated_fields": ["name", "phone"],
     *           "old_values": {"name": "舊名稱"},
     *           "new_values": {"name": "新名稱"}
     *         },
     *         "created_at": "2025-01-07T09:30:00.000000Z"
     *       }
     *     ],
     *     "links": {
     *       "first": "http://localhost/api/users/1/activities?page=1",
     *       "last": "http://localhost/api/users/1/activities?page=5",
     *       "prev": null,
     *       "next": "http://localhost/api/users/1/activities?page=2"
     *     },
     *     "meta": {
     *       "current_page": 1,
     *       "from": 1,
     *       "last_page": 5,
     *       "per_page": 20,
     *       "to": 20,
     *       "total": 100
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "使用者不存在"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "權限不足"
     * }
     * 
     * @param User $user
     * @param Request $request
     * @return JsonResponse
     */
    public function activities(User $user, Request $request): JsonResponse
    {
        // 手動權限檢查 - 活動記錄查看權限
        $this->authorize('viewActivities', $user);
        
        try {
            $filters = $request->only(['log_name', 'from_date', 'to_date']);
            $perPage = $request->integer('per_page', 20);
            
            $activities = $this->service->getActivities($user->id, $filters, $perPage);
            
            return response()->json([
                'success' => true,
                'message' => '取得活動記錄成功',
                'data' => $activities
            ]);
        } catch (BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode()
            ], $e->getHttpStatus());
        }
    }
} 