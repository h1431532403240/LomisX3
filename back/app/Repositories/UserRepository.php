<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Traits\HasStoreIsolation;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator, CursorPaginator};
use Illuminate\Support\Facades\{DB, Cache, Log};
use Spatie\QueryBuilder\{QueryBuilder, AllowedFilter, AllowedSort, AllowedInclude};
use Carbon\Carbon;

/**
 * 使用者 Repository 實現類別
 * 
 * 負責：
 * 1. 基礎 CRUD 操作
 * 2. 複雜查詢建構 (Spatie QueryBuilder)
 * 3. 門市隔離機制
 * 4. 快取最佳化
 * 5. 批次操作
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
class UserRepository implements UserRepositoryInterface
{
    use HasStoreIsolation;

    /**
     * Repository 建構函式
     * 
     * @param User $model 使用者模型
     */
    public function __construct(
        protected User $model
    ) {}

    /**
     * 根據 ID 查詢使用者
     *
     * @param int $id 使用者 ID
     * @param array $relations 預載關聯
     * @return User|null
     */
    public function find(int $id, array $relations = []): ?User
    {
        $defaultRelations = ['roles', 'permissions', 'store'];
        $relations = empty($relations) ? $defaultRelations : $relations;
        
        $cacheKey = "users:{$id}:" . md5(serialize($relations));
        
        return Cache::remember($cacheKey, 3600, function () use ($id, $relations) {
            return $this->model
                ->where('id', $id)
                ->with($relations)
                ->first();
        });
    }

    /**
     * 根據 ID 查詢使用者（必須存在）
     *
     * @param int $id 使用者 ID
     * @param array $relations 預載關聯
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $relations = []): User
    {
        $user = $this->find($id, $relations);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "User with ID {$id} not found"
            );
        }
        
        return $user;
    }

    /**
     * 根據門市 ID 查詢使用者列表
     *
     * @param int $storeId 門市 ID
     * @param array $filters 篩選條件
     * @return Collection
     */
    public function findByStoreId(int $storeId, array $filters = []): Collection
    {
        $cacheKey = "users:store:{$storeId}:" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($storeId, $filters) {
            $query = $this->model->where('store_id', $storeId);
            
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['role'])) {
                $query->whereHas('roles', function ($q) use ($filters) {
                    $q->where('name', $filters['role']);
                });
            }
            
            return $query->with(['roles', 'permissions', 'store'])->get();
        });
    }

    /**
     * 根據使用者名稱查詢使用者
     *
     * @param string $username 使用者名稱
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        $cacheKey = "user:username:{$username}";
        
        return Cache::remember($cacheKey, 3600, function () use ($username) {
            return $this->model
                ->where('username', $username)
                ->whereNull('deleted_at')
                ->with(['roles', 'permissions', 'store'])
                ->first();
        });
    }

    /**
     * 根據 Email 查詢使用者
     *
     * @param string $email Email 地址
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $cacheKey = "user:email:" . md5($email);
        
        return Cache::remember($cacheKey, 3600, function () use ($email) {
            return $this->model
                ->where('email', $email)
                ->whereNull('deleted_at')
                ->with(['roles', 'permissions', 'store'])
                ->first();
        });
    }

    /**
     * 根據電子郵件或使用者名稱查找使用者
     * 支援登入時彈性使用 email 或 username
     *
     * @param string $login 登入帳號（使用者名稱或電子郵件）
     * @return User|null
     */
    public function findByEmailOrUsername(string $login): ?User
    {
        $cacheKey = "user:login:" . md5($login);
        
        return Cache::remember($cacheKey, 3600, function () use ($login) {
            return $this->model
                ->where(function ($query) use ($login) {
                    $query->where('email', $login)
                          ->orWhere('username', $login);
                })
                ->whereNull('deleted_at')
                ->with(['roles', 'permissions', 'store'])
                ->first();
        });
    }

    /**
     * 根據使用者名稱和門市 ID 查詢使用者
     *
     * @param string $username 使用者名稱
     * @param int $storeId 門市 ID
     * @return User|null
     */
    public function findByUsernameWithStore(string $username, int $storeId): ?User
    {
        $cacheKey = "user:username_store:{$username}:{$storeId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($username, $storeId) {
            return $this->model
                ->where('username', $username)
                ->where('store_id', $storeId)
                ->whereNull('deleted_at')
                ->with(['roles', 'permissions', 'store'])
                ->first();
        });
    }

    /**
     * 檢查使用者名稱是否已存在
     *
     * @param string $username 使用者名稱
     * @param int|null $excludeId 排除的使用者 ID
     * @return bool
     */
    public function existsByUsername(string $username, ?int $excludeId = null): bool
    {
        $query = $this->model->where('username', $username)->whereNull('deleted_at');
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * 檢查 Email 是否已存在
     *
     * @param string $email Email 地址
     * @param int|null $excludeId 排除的使用者 ID
     * @return bool
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email)->whereNull('deleted_at');
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * 分頁查詢使用者
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query
            ->with(['roles', 'store'])
            ->paginate($perPage);
    }

    /**
     * 搜尋使用者
     *
     * @param string $keyword 搜尋關鍵字
     * @param array $filters 額外篩選條件
     * @param int $perPage 每頁數量
     * @return LengthAwarePaginator
     */
    public function search(string $keyword, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        // 關鍵字搜尋
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('username', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }
        
        // 應用額外篩選條件
        $this->applyFilters($query, $filters);
        
        // 應用門市隔離
        if (auth()->check() && !auth()->user()->hasRole('admin')) {
            $query->where('store_id', auth()->user()->store_id);
        }
        
        return $query
            ->with(['roles', 'store'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Cursor 分頁 (適用於大資料集)
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return CursorPaginator
     */
    public function cursorPaginate(array $filters = [], int $perPage = 20): CursorPaginator
    {
        $query = QueryBuilder::for($this->model)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('store_id'),
                AllowedFilter::callback('active_since', function ($query, $value) {
                    $query->where('last_login_at', '>=', Carbon::parse($value));
                })
            ])
            ->allowedSorts(['id', 'created_at', 'last_login_at'])
            ->with(['roles', 'store']);

        // 應用門市隔離
        if (auth()->check() && !auth()->user()->hasRole('admin')) {
            $query->where('store_id', auth()->user()->store_id);
        }

        return $query->cursorPaginate($perPage);
    }

    /**
     * 取得擁有特定角色的使用者
     *
     * @param int $storeId 門市 ID
     * @param array $roles 角色名稱陣列
     * @return Collection
     */
    public function getUsersWithRoles(int $storeId, array $roles = []): Collection
    {
        $cacheKey = "users_with_roles:store:{$storeId}:" . md5(serialize($roles));
        
        return Cache::remember($cacheKey, 600, function () use ($storeId, $roles) {
            $query = $this->model
                ->where('store_id', $storeId)
                ->whereHas('roles');
                
            if (!empty($roles)) {
                $query->whereHas('roles', function ($q) use ($roles) {
                    $q->whereIn('name', $roles);
                });
            }
                
            return $query
                ->with(['roles', 'permissions'])
                ->get();
        });
    }

    /**
     * 取得門市內啟用狀態的使用者
     *
     * @param int $storeId 門市 ID
     * @return Collection
     */
    public function getActiveUsersInStore(int $storeId): Collection
    {
        $cacheKey = "active_users:store:{$storeId}";
        
        return Cache::remember($cacheKey, 300, function () use ($storeId) {
            return $this->model
                ->where('store_id', $storeId)
                ->where('status', UserStatus::ACTIVE->value)
                ->with(['roles'])
                ->orderBy('last_login_at', 'desc')
                ->get();
        });
    }

    /**
     * 取得具有特定角色的使用者
     *
     * @param array $userIds 使用者 ID 陣列
     * @param string $role 角色名稱
     * @return Collection
     */
    public function getUsersWithRole(array $userIds, string $role): Collection
    {
        return $this->model
            ->whereIn('id', $userIds)
            ->whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })
            ->get();
    }

    /**
     * 取得門市內啟用使用者數量
     *
     * @param int $storeId 門市 ID
     * @return int
     */
    public function getActiveUsersCount(int $storeId): int
    {
        $cacheKey = "active_users_count:store:{$storeId}";
        
        return Cache::remember($cacheKey, 600, function () use ($storeId) {
            return $this->model
                ->where('store_id', $storeId)
                ->where('status', UserStatus::ACTIVE->value)
                ->count();
        });
    }

    /**
     * 計算符合條件的使用者數量
     *
     * @param array $filters 篩選條件
     * @return int
     */
    public function count(array $filters = []): int
    {
        $query = $this->model->newQuery();
        
        // 套用篩選條件
        $this->applyFilters($query, $filters);
        
        return $query->count();
    }

    /**
     * 取得門市使用者統計資訊
     *
     * @param int $storeId 門市 ID
     * @return array
     */
    public function getStoreUserStatistics(int $storeId): array
    {
        $cacheKey = "user_statistics:store:{$storeId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($storeId) {
            $baseQuery = $this->model->where('store_id', $storeId);
            
            return [
                'total' => $baseQuery->count(),
                'active' => $baseQuery->where('status', UserStatus::ACTIVE->value)->count(),
                'inactive' => $baseQuery->where('status', UserStatus::INACTIVE->value)->count(),
                'locked' => $baseQuery->where('status', UserStatus::LOCKED->value)->count(),
                'pending' => $baseQuery->where('status', UserStatus::PENDING->value)->count(),
                'with_2fa' => $baseQuery->whereNotNull('two_factor_confirmed_at')->count(),
                'last_week_logins' => $baseQuery->where('last_login_at', '>=', now()->subWeek())->count(),
                'never_logged_in' => $baseQuery->whereNull('last_login_at')->count(),
            ];
        });
    }

    /**
     * 取得使用者登入歷史
     *
     * @param int $userId 使用者 ID
     * @param int $days 查詢天數
     * @return Collection
     */
    public function getUserLoginHistory(int $userId, int $days = 30): Collection
    {
        return DB::table('activity_log')
            ->where('subject_type', User::class)
            ->where('subject_id', $userId)
            ->where('description', 'login')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 取得最近活躍的使用者
     *
     * @param int $storeId 門市 ID (可選)
     * @param int $days 天數
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getRecentActiveUsers(?int $storeId = null, int $days = 7, int $limit = 50): Collection
    {
        $cacheKey = "recent_active_users:store:" . ($storeId ?? 'all') . ":days:{$days}";
        
        return Cache::remember($cacheKey, 300, function () use ($storeId, $days, $limit) {
            $query = $this->model
                ->where('last_login_at', '>=', now()->subDays($days))
                ->where('status', UserStatus::ACTIVE->value);
                
            if ($storeId) {
                $query->where('store_id', $storeId);
            }
            
            return $query
                ->with(['roles', 'store'])
                ->orderBy('last_login_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * 建立使用者
     *
     * @param array $data 使用者資料
     * @return User
     */
    public function create(array $data): User
    {
        $user = $this->model->create($data);
        
        // 清除相關快取
        $this->clearStoreUsersCache($user->store_id);
        
        return $user->load(['roles', 'permissions', 'store']);
    }

    /**
     * 更新使用者
     *
     * @param int $id 使用者 ID
     * @param array $data 更新資料
     * @return User
     */
    public function update(int $id, array $data): User
    {
        $user = $this->findOrFail($id);
        $user->update($data);
        
        // 清除相關快取
        $this->clearUserCache($id);
        $this->clearStoreUsersCache($user->store_id);
        
        return $user->refresh()->load(['roles', 'permissions', 'store']);
    }

    /**
     * 靜默更新使用者 (不觸發事件)
     *
     * @param int $id 使用者 ID
     * @param array $data 更新資料
     * @return bool
     */
    public function updateQuietly(int $id, array $data): bool
    {
        $user = $this->findOrFail($id);
        $result = $user->updateQuietly($data);
        
        // 清除相關快取
        $this->clearUserCache($id);
        
        return $result;
    }

    /**
     * 刪除使用者 (軟刪除)
     *
     * @param int $id 使用者 ID
     * @return bool
     */
    public function delete(int $id): bool
    {
        $user = $this->findOrFail($id);
        $storeId = $user->store_id;
        
        $result = $user->delete();
        
        if ($result) {
            $this->clearUserCache($id);
            $this->clearStoreUsersCache($storeId);
        }
        
        return $result;
    }

    /**
     * 根據 ID 陣列查找使用者
     *
     * @param string $field 欄位名稱
     * @param array $values 值陣列
     * @param array $relations 關聯載入
     * @return Collection
     */
    public function findWhereIn(string $field, array $values, array $relations = []): Collection
    {
        $query = $this->model->whereIn($field, $values);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->get();
    }

    /**
     * 批次更新使用者狀態
     *
     * @param array $ids 使用者 ID 陣列
     * @param string $status 新狀態
     * @return int 受影響的行數
     */
    public function batchUpdateStatus(array $ids, string $status): int
    {
        $affectedRows = $this->model
            ->whereIn('id', $ids)
            ->update([
                'status' => $status,
                'updated_at' => now(),
                'updated_by' => auth()->id()
            ]);

        // 清除相關快取
        $this->clearBatchCache($ids);
        
        Log::info('Batch user status update', [
            'user_ids' => $ids,
            'status' => $status,
            'affected_rows' => $affectedRows,
            'updated_by' => auth()->id()
        ]);

        return $affectedRows;
    }

    /**
     * 批次刪除使用者 (軟刪除)
     *
     * @param array $ids 使用者 ID 陣列
     * @return int 受影響的行數
     */
    public function batchDelete(array $ids): int
    {
        $affectedRows = $this->model
            ->whereIn('id', $ids)
            ->update([
                'deleted_at' => now(),
                'updated_by' => auth()->id()
            ]);

        // 清除相關快取
        $this->clearBatchCache($ids);
        
        Log::info('Batch user deletion', [
            'user_ids' => $ids,
            'affected_rows' => $affectedRows,
            'deleted_by' => auth()->id()
        ]);

        return $affectedRows;
    }

    /**
     * 批次分配角色
     *
     * @param array $ids 使用者 ID 陣列
     * @param string $role 角色名稱
     * @return int 成功分配的使用者數量
     */
    public function batchAssignRole(array $ids, string $role): int
    {
        $successCount = 0;
        
        DB::transaction(function () use ($ids, $role, &$successCount) {
            $users = $this->model->whereIn('id', $ids)->get();
            
            foreach ($users as $user) {
                try {
                    $user->assignRole($role);
                    $successCount++;
                } catch (\Exception $e) {
                    Log::warning('Failed to assign role', [
                        'user_id' => $user->id,
                        'role' => $role,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        // 清除相關快取
        $this->clearBatchCache($ids);
        
        Log::info('Batch role assignment', [
            'user_ids' => $ids,
            'role' => $role,
            'success_count' => $successCount,
            'assigned_by' => auth()->id()
        ]);

        return $successCount;
    }

    /**
     * 批次更新最後登入時間
     *
     * @param array $userIds 使用者 ID 陣列
     * @param Carbon $loginTime 登入時間
     * @return int 受影響的行數
     */
    public function batchUpdateLastLogin(array $userIds, Carbon $loginTime): int
    {
        return $this->model
            ->whereIn('id', $userIds)
            ->update([
                'last_login_at' => $loginTime,
                'last_login_ip' => request()->ip(),
                'login_attempts' => 0,
                'locked_until' => null
            ]);
    }

    /**
     * 取得密碼已過期的使用者
     *
     * @param int $days 密碼有效天數
     * @return Collection
     */
    public function getUsersWithExpiredPasswords(int $days = 90): Collection
    {
        return $this->model
            ->where('password_changed_at', '<=', now()->subDays($days))
            ->orWhereNull('password_changed_at')
            ->where('status', UserStatus::ACTIVE->value)
            ->with(['store'])
            ->get();
    }

    /**
     * 取得被鎖定的使用者
     *
     * @return Collection
     */
    public function getLockedUsers(): Collection
    {
        return $this->model
            ->where('status', UserStatus::LOCKED->value)
            ->orWhere(function ($query) {
                $query->whereNotNull('locked_until')
                      ->where('locked_until', '>', now());
            })
            ->with(['store'])
            ->get();
    }

    /**
     * 取得未驗證 Email 的使用者
     *
     * @param int $days 未驗證天數
     * @return Collection
     */
    public function getUnverifiedUsers(int $days = 7): Collection
    {
        return $this->model
            ->whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays($days))
            ->where('status', UserStatus::PENDING->value)
            ->with(['store'])
            ->get();
    }

    /**
     * 清除使用者快取
     *
     * @param int $userId 使用者 ID
     * @return void
     */
    public function clearUserCache(int $userId): void
    {
        $user = $this->model->find($userId);
        if (!$user) return;

        $patterns = [
            "users:{$userId}",
            "user:username:{$user->username}",
            "user:email:" . md5($user->email),
            "user:username_store:{$user->username}:{$user->store_id}",
            "user_accessible_stores_{$userId}"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
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
        $patterns = [
            "users:store:{$storeId}:*",
            "users_with_roles:store:{$storeId}",
            "active_users:store:{$storeId}",
            "active_users_count:store:{$storeId}",
            "user_statistics:store:{$storeId}",
            "recent_active_users:store:{$storeId}:*"
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                Cache::tags("store_{$storeId}_users")->flush();
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * 預熱活躍使用者快取
     *
     * @param int $days 活躍天數
     * @return int 預熱的使用者數量
     */
    public function warmupActiveUsersCache(int $days = 7): int
    {
        try {
            // 預熱最近指定天數內活躍的使用者
            $activeUsers = $this->model
                ->where('last_login_at', '>=', now()->subDays($days))
                ->where('status', UserStatus::ACTIVE->value)
                ->with(['roles', 'permissions', 'store'])
                ->get();

            foreach ($activeUsers as $user) {
                // 預熱基本使用者資料
                Cache::put("users:{$user->id}", $user, 3600);
                
                // 預熱使用者名稱查詢
                Cache::put("user:username:{$user->username}", $user, 3600);
                
                // 預熱 Email 查詢
                Cache::put("user:email:" . md5($user->email), $user, 3600);
            }

            // 預熱門市統計
            $storeIds = $activeUsers->pluck('store_id')->unique();
            foreach ($storeIds as $storeId) {
                $this->getStoreUserStatistics($storeId);
                $this->getActiveUsersCount($storeId);
            }

            $userCount = $activeUsers->count();
            
            Log::info('Active users cache warmed up', [
                'users_count' => $userCount,
                'stores_count' => $storeIds->count(),
                'days' => $days
            ]);
            
            return $userCount;
        } catch (\Exception $e) {
            Log::error('Failed to warmup active users cache', [
                'error' => $e->getMessage(),
                'days' => $days
            ]);
            
            return 0;
        }
    }

    /**
     * 應用篩選條件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (isset($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('username', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (isset($filters['last_login_from'])) {
            $query->where('last_login_at', '>=', $filters['last_login_from']);
        }

        if (isset($filters['has_2fa'])) {
            if ($filters['has_2fa']) {
                $query->whereNotNull('two_factor_confirmed_at');
            } else {
                $query->whereNull('two_factor_confirmed_at');
            }
        }
    }

    /**
     * 清除批次操作相關快取
     *
     * @param array $userIds 使用者 ID 陣列
     * @return void
     */
    private function clearBatchCache(array $userIds): void
    {
        $users = $this->model->whereIn('id', $userIds)->get(['id', 'store_id']);
        $storeIds = $users->pluck('store_id')->unique();

        foreach ($userIds as $userId) {
            $this->clearUserCache($userId);
        }

        foreach ($storeIds as $storeId) {
            $this->clearStoreUsersCache($storeId);
        }
    }
}