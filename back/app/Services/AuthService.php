<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\AuthServiceInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Enums\{UserErrorCode, UserStatus};
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, RateLimiter, Log};
use Carbon\Carbon;

/**
 * 認證服務
 * 處理使用者登入、驗證、Token 管理等認證相關功能
 * 
 * 功能特色：
 * - 安全登入流程
 * - 限流保護
 * - 帳號狀態檢查
 * - 登入資訊更新
 * - 密碼驗證
 */
class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * 使用者登入
     * 
     * @param array $credentials 登入憑證
     * @return array 登入結果
     * @throws BusinessException
     */
    public function login(array $credentials): array
    {
        // 限流檢查
        $key = 'login_attempts:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new BusinessException(
                message: '登入嘗試次數過多，請稍後再試',
                code: 'TOO_MANY_LOGIN_ATTEMPTS',
                httpStatusCode: 429
            );
        }

        // 查找使用者
        $user = $this->findUserByLogin($credentials['login']);
        
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            
            throw new BusinessException(
                message: UserErrorCode::INVALID_CREDENTIALS->message(),
                code: UserErrorCode::INVALID_CREDENTIALS,
                httpStatusCode: UserErrorCode::INVALID_CREDENTIALS->httpStatus()
            );
        }

        // 帳號狀態檢查
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
        $this->updateLoginInfo($user, request());

        // 清除限流記錄
        RateLimiter::clear($key);

        // 建立 Token
        $deviceName = $credentials['device_name'] ?? '未知裝置';
        $token = $user->createToken($deviceName, ['*'], now()->addHours(24));

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => now()->addHours(24)
        ];
    }

    /**
     * 根據登入名稱查找使用者
     * 支援使用者名稱或 Email 登入
     * 
     * @param string $login
     * @return User|null
     */
    private function findUserByLogin(string $login): ?User
    {
        // 判斷是 Email 還是使用者名稱
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
    public function validateUserStatus(User $user): void
    {
        switch ($user->status) {
            case UserStatus::INACTIVE->value:
                throw new BusinessException(
                    message: UserErrorCode::ACCOUNT_INACTIVE->message(),
                    code: UserErrorCode::ACCOUNT_INACTIVE,
                    httpStatusCode: UserErrorCode::ACCOUNT_INACTIVE->httpStatus()
                );
                
            case UserStatus::LOCKED->value:
                if ($user->locked_until && $user->locked_until->isFuture()) {
                    throw new BusinessException(
                        message: UserErrorCode::ACCOUNT_LOCKED->message(),
                        code: UserErrorCode::ACCOUNT_LOCKED,
                        httpStatusCode: UserErrorCode::ACCOUNT_LOCKED->httpStatus()
                    );
                }
                break;
                
            case UserStatus::PENDING->value:
                throw new BusinessException(
                    message: UserErrorCode::ACCOUNT_PENDING->message(),
                    code: UserErrorCode::ACCOUNT_PENDING,
                    httpStatusCode: UserErrorCode::ACCOUNT_PENDING->httpStatus()
                );
        }

        // 檢查 Email 驗證
        if (!$user->email_verified_at) {
            throw new BusinessException(
                message: UserErrorCode::EMAIL_NOT_VERIFIED->message(),
                code: UserErrorCode::EMAIL_NOT_VERIFIED,
                httpStatusCode: UserErrorCode::EMAIL_NOT_VERIFIED->httpStatus()
            );
        }
    }

    /**
     * 更新登入資訊
     * 
     * @param User $user
     * @param Request $request
     */
    public function updateLoginInfo(User $user, Request $request): void
    {
        $this->userRepository->updateQuietly($user->id, [
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'login_attempts' => 0,
            'locked_until' => null
        ]);
    }

    /**
     * 驗證密碼
     * 
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    /**
     * 處理登入失敗
     * 增加失敗次數，必要時鎖定帳號
     * 
     * @param User $user
     */
    public function handleLoginFailure(User $user): void
    {
        $attempts = $user->login_attempts + 1;
        $updateData = ['login_attempts' => $attempts];
        
        // 如果失敗次數達到 5 次，鎖定帳號 1 小時
        if ($attempts >= 5) {
            $updateData['locked_until'] = now()->addHour();
            $updateData['status'] = UserStatus::LOCKED->value;
            
            Log::warning('帳號因登入失敗次數過多被鎖定', [
                'user_id' => $user->id,
                'username' => $user->username,
                'attempts' => $attempts,
                'locked_until' => $updateData['locked_until']
            ]);
        }
        
        $this->userRepository->updateQuietly($user->id, $updateData);
    }

    /**
     * 重設登入失敗次數
     * 
     * @param User $user
     */
    public function resetLoginAttempts(User $user): void
    {
        if ($user->login_attempts > 0) {
            $this->userRepository->updateQuietly($user->id, [
                'login_attempts' => 0,
                'locked_until' => null
            ]);
        }
    }

    /**
     * 檢查帳號是否被鎖定
     * 
     * @param User $user
     * @return bool
     */
    public function isAccountLocked(User $user): bool
    {
        return $user->status === UserStatus::LOCKED->value || 
               ($user->locked_until && $user->locked_until->isFuture());
    }

    /**
     * 解鎖帳號
     * 
     * @param User $user
     */
    public function unlockAccount(User $user): void
    {
        $this->userRepository->update($user->id, [
            'status' => UserStatus::ACTIVE->value,
            'locked_until' => null,
            'login_attempts' => 0
        ]);
        
        Log::info('帳號已解鎖', [
            'user_id' => $user->id,
            'username' => $user->username
        ]);
    }

    /**
     * 取得登入歷史統計
     * 
     * @param int $days
     * @return array
     */
    public function getLoginStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_logins' => $this->userRepository->getLoginCount($startDate),
            'unique_users' => $this->userRepository->getUniqueLoginUsers($startDate),
            'failed_attempts' => $this->userRepository->getFailedLoginAttempts($startDate),
            'locked_accounts' => $this->userRepository->getLockedAccountsCount(),
            'daily_stats' => $this->userRepository->getDailyLoginStats($startDate)
        ];
    }
} 