<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserStatus;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\{Cache, Log};
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * 使用者快取服務
 * 
 * 負責：
 * 1. 使用者快取管理
 * 2. 智能快取清理
 * 3. 快取預熱策略
 * 4. 效能優化
 * 5. 容錯機制
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
class UserCacheService
{
    /**
     * 快取標籤前缀
     */
    private const CACHE_TAG_USERS = 'users_cache';
    private const CACHE_TAG_STORE_PREFIX = 'store_';

    /**
     * 快取時間 (秒)
     */
    private const CACHE_TTL_USER = 3600;      // 使用者基本資料：1 小時
    private const CACHE_TTL_PERMISSIONS = 300;  // 權限資料：5 分鐘
    private const CACHE_TTL_STATISTICS = 1800;  // 統計資料：30 分鐘

    /**
     * 建構函式
     */
    public function __construct(
        protected UserRepositoryInterface $repository
    ) {}

    /**
     * 取得使用者快取（包含容錯機制）
     *
     * @param int $userId 使用者 ID
     * @return User|null
     */
    public function getUserWithFallback(int $userId): ?User
    {
        try {
            $cacheKey = $this->generateUserCacheKey($userId);
            
            return Cache::tags(self::CACHE_TAG_USERS)->remember(
                $cacheKey, 
                self::CACHE_TTL_USER, 
                fn() => $this->repository->find($userId)
            );
        } catch (\Exception $e) {
            Log::warning('User cache failed, using database directly', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // 快取失效時直接查詢資料庫
            return $this->repository->find($userId);
        }
    }

    /**
     * 快取使用者資料
     *
     * @param User $user
     * @return bool
     */
    public function cacheUser(User $user): bool
    {
        try {
            $cacheKey = $this->generateUserCacheKey($user->id);
            
            Cache::tags(self::CACHE_TAG_USERS)->put(
                $cacheKey, 
                $user, 
                self::CACHE_TTL_USER
            );
            
            // 同時快取常用的查詢方式
            $this->cacheUserByUsername($user);
            $this->cacheUserByEmail($user);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache user data', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 取得使用者權限快取
     *
     * @param int $userId 使用者 ID
     * @param int $storeId 門市 ID
     * @return array
     */
    public function getUserPermissions(int $userId, int $storeId): array
    {
        $cacheKey = $this->generatePermissionCacheKey($userId, $storeId);
        
        return Cache::tags([self::CACHE_TAG_USERS, "permissions_s{$storeId}"])
            ->remember($cacheKey, self::CACHE_TTL_PERMISSIONS, function () use ($userId) {
                $user = $this->repository->find($userId);
                if (!$user) return [];
                
                return [
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getPermissionNames()->toArray(),
                    'accessible_stores' => $user->getAccessibleStoreIds()->toArray()
                ];
            });
    }

    /**
     * 取得門市統計快取
     *
     * @param int $storeId 門市 ID
     * @return array
     */
    public function getStoreStatistics(int $storeId): array
    {
        $cacheKey = $this->generateStoreStatsCacheKey($storeId);
        
        return Cache::tags($this->getStoreTag($storeId))
            ->remember($cacheKey, self::CACHE_TTL_STATISTICS, function () use ($storeId) {
                return $this->repository->getStoreUserStatistics($storeId);
            });
    }

    /**
     * 清除使用者快取（精準清除）
     *
     * @param int $userId 使用者 ID
     * @return void
     */
    public function clearUserCache(int $userId): void
    {
        try {
            $user = $this->repository->find($userId);
            if (!$user) {
                Log::warning('Cannot clear cache for non-existent user', ['user_id' => $userId]);
                return;
            }

            // 清除基本使用者快取
            $this->clearBasicUserCache($userId, $user);
            
            // 清除權限相關快取
            $this->clearUserPermissionsCache($userId, $user->store_id);
            
            Log::debug('User cache cleared', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('Failed to clear user cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 清除使用者權限快取（包含門市隔離）
     *
     * @param int $userId 使用者 ID
     * @param int $storeId 門市 ID
     * @return void
     */
    public function forgetUserCacheWithPermissions(int $userId, int $storeId): void
    {
        try {
            // 清除使用者基本快取
            $this->clearBasicUserCache($userId);
            
            // 清除權限快取
            $this->clearUserPermissionsCache($userId, $storeId);
            
            // 清除門市權限快取（V6.2：只清除該門市的權限快取）
            Cache::tags("permissions_s{$storeId}")->flush();
            
            Log::debug('User cache with permissions cleared', [
                'user_id' => $userId,
                'store_id' => $storeId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear user cache with permissions', [
                'user_id' => $userId,
                'store_id' => $storeId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 清除門市使用者快取
     *
     * @param int $storeId 門市 ID
     * @return void
     */
    public function clearStoreUsersCache(int $storeId): void
    {
        try {
            // 清除門市專用快取
            Cache::tags($this->getStoreTag($storeId))->flush();
            
            // 清除特定快取鍵
            $patterns = [
                "users:store:{$storeId}",
                "users_with_roles:store:{$storeId}",
                "active_users:store:{$storeId}",
                "active_users_count:store:{$storeId}",
                "user_statistics:store:{$storeId}",
                "recent_active_users:store:{$storeId}"
            ];

            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
            
            Log::debug('Store users cache cleared', ['store_id' => $storeId]);
        } catch (\Exception $e) {
            Log::error('Failed to clear store users cache', [
                'store_id' => $storeId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 清除門市快取（別名方法）
     * 為了向後相容性提供的方法別名
     *
     * @param int $storeId 門市 ID
     * @return void
     */
    public function clearStoreCache(int $storeId): void
    {
        $this->clearStoreUsersCache($storeId);
    }

    /**
     * 智能快取預熱（V6.2 優化版本）
     *
     * @param int $days 活躍天數
     * @return void
     */
    public function warmupActiveUsersCache(int $days = 7): void
    {
        try {
            Log::info('Starting intelligent cache warmup', ['days' => $days]);
            
            // 預熱最近活躍的使用者
            $activeUsers = User::where('last_login_at', '>=', now()->subDays($days))
                ->where('status', UserStatus::ACTIVE->value)
                ->with(['roles', 'permissions', 'store'])
                ->get();

            $warmedUsers = 0;
            $warmedStores = [];

            foreach ($activeUsers as $user) {
                // 預熱使用者基本資料
                $this->cacheUser($user);
                
                // 預熱權限資料
                $this->getUserPermissions($user->id, $user->store_id);
                
                $warmedUsers++;
                $warmedStores[$user->store_id] = true;
            }

            // 預熱門市統計
            foreach (array_keys($warmedStores) as $storeId) {
                $this->getStoreStatistics($storeId);
                $this->repository->getActiveUsersCount($storeId);
            }

            Log::info('Cache warmup completed', [
                'users_warmed' => $warmedUsers,
                'stores_warmed' => count($warmedStores),
                'execution_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to warmup active users cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 批次清除快取（效能優化）
     *
     * @param array $userIds 使用者 ID 陣列
     * @return void
     */
    public function batchClearUserCache(array $userIds): void
    {
        try {
            // 分組獲取使用者資料以減少查詢次數
            $users = User::whereIn('id', $userIds)
                ->select('id', 'store_id', 'username', 'email')
                ->get()
                ->keyBy('id');

            $storeIds = $users->pluck('store_id')->unique();

            // 批次清除使用者快取
            foreach ($userIds as $userId) {
                $user = $users->get($userId);
                if ($user) {
                    $this->clearBasicUserCache($userId, $user);
                }
            }

            // 批次清除門市快取
            foreach ($storeIds as $storeId) {
                $this->clearStoreUsersCache($storeId);
            }

            Log::info('Batch cache clear completed', [
                'user_count' => count($userIds),
                'store_count' => $storeIds->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to batch clear user cache', [
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 快取健康檢查
     *
     * @return array
     */
    public function healthCheck(): array
    {
        try {
            $testKey = 'cache_health_check_' . time();
            $testValue = 'test_value';
            
            // 測試快取寫入
            Cache::put($testKey, $testValue, 10);
            
            // 測試快取讀取
            $retrieved = Cache::get($testKey);
            
            // 清除測試快取
            Cache::forget($testKey);
            
            $isHealthy = $retrieved === $testValue;
            
            return [
                'status' => $isHealthy ? 'healthy' : 'unhealthy',
                'timestamp' => now()->toISOString(),
                'test_passed' => $isHealthy,
                'driver' => config('cache.default'),
                'message' => $isHealthy ? 'Cache is working properly' : 'Cache read/write test failed'
            ];
        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'error',
                'timestamp' => now()->toISOString(),
                'test_passed' => false,
                'error' => $e->getMessage(),
                'message' => 'Cache health check encountered an error'
            ];
        }
    }

    /**
     * 生成使用者快取鍵
     *
     * @param int $userId
     * @return string
     */
    private function generateUserCacheKey(int $userId): string
    {
        return "users:{$userId}";
    }

    /**
     * 生成權限快取鍵
     *
     * @param int $userId
     * @param int $storeId
     * @return string
     */
    private function generatePermissionCacheKey(int $userId, int $storeId): string
    {
        return "user_permissions:{$userId}:store:{$storeId}";
    }

    /**
     * 生成門市統計快取鍵
     *
     * @param int $storeId
     * @return string
     */
    private function generateStoreStatsCacheKey(int $storeId): string
    {
        return "user_statistics:store:{$storeId}";
    }

    /**
     * 生成使用者名稱快取鍵
     *
     * @param string $username
     * @return string
     */
    private function generateUsernameKey(string $username): string
    {
        return "user:username:{$username}";
    }

    /**
     * 生成 Email 快取鍵
     *
     * @param string $email
     * @return string
     */
    private function generateEmailKey(string $email): string
    {
        return "user:email:" . md5($email);
    }

    /**
     * 取得門市標籤
     *
     * @param int $storeId
     * @return string
     */
    private function getStoreTag(int $storeId): string
    {
        return self::CACHE_TAG_STORE_PREFIX . $storeId . '_users';
    }

    /**
     * 快取使用者名稱查詢
     *
     * @param User $user
     * @return void
     */
    private function cacheUserByUsername(User $user): void
    {
        $cacheKey = $this->generateUsernameKey($user->username);
        Cache::tags(self::CACHE_TAG_USERS)->put($cacheKey, $user, self::CACHE_TTL_USER);
    }

    /**
     * 快取 Email 查詢
     *
     * @param User $user
     * @return void
     */
    private function cacheUserByEmail(User $user): void
    {
        $cacheKey = $this->generateEmailKey($user->email);
        Cache::tags(self::CACHE_TAG_USERS)->put($cacheKey, $user, self::CACHE_TTL_USER);
    }

    /**
     * 清除基本使用者快取
     *
     * @param int $userId
     * @param User|null $user
     * @return void
     */
    private function clearBasicUserCache(int $userId, ?User $user = null): void
    {
        $patterns = [
            $this->generateUserCacheKey($userId)
        ];

        if ($user) {
            $patterns[] = $this->generateUsernameKey($user->username);
            $patterns[] = $this->generateEmailKey($user->email);
            $patterns[] = "user:username_store:{$user->username}:{$user->store_id}";
        }

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * 清除使用者權限快取
     *
     * @param int $userId
     * @param int $storeId
     * @return void
     */
    private function clearUserPermissionsCache(int $userId, int $storeId): void
    {
        $patterns = [
            $this->generatePermissionCacheKey($userId, $storeId),
            "user_accessible_stores_{$userId}"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}