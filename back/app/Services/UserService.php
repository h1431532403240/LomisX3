<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Enums\{UserErrorCode, UserStatus};
use App\Exceptions\BusinessException;
use Illuminate\Support\{Collection, Facades\DB, Facades\Hash, Facades\Log, Facades\Cache};
use Illuminate\Pagination\{LengthAwarePaginator, CursorPaginator};
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Activitylog\Models\Activity;

/**
 * 使用者服務類別
 * 
 * 負責使用者相關的業務邏輯處理，包含：
 * - 基本 CRUD 操作
 * - 角色權限管理
 * - 密碼安全處理
 * - 2FA 雙因子驗證
 * - 頭像上傳管理
 * - 門市隔離機制
 * 
 * @package App\Services
 * @author LomisX3 Team
 * @version 6.2
 */
class UserService
{
    /**
     * 建構子
     * 
     * @param UserRepositoryInterface $repository 使用者資料庫介面
     */
    public function __construct(
        protected UserRepositoryInterface $repository
    ) {}

    /**
     * 取得使用者列表
     * 
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @param bool $useCursor 是否使用遊標分頁
     * @return LengthAwarePaginator|CursorPaginator
     * @throws BusinessException
     */
    public function getList(array $filters, int $perPage = 20, bool $useCursor = false): LengthAwarePaginator|CursorPaginator
    {
        try {
            // 應用門市隔離：沒有跨店查看權限的使用者只能查看自己門市的使用者
            if (auth()->user()->cannot('viewAcrossStores', User::class)) {
                $filters['store_id'] = auth()->user()->store_id;
            }

            if ($useCursor) {
                return $this->repository->cursorPaginate($filters, $perPage);
            } else {
                // 提取關鍵字搜尋
                $keyword = $filters['keyword'] ?? '';
                unset($filters['keyword']);
                
                // 如果有關鍵字，使用 search 方法，否則使用 paginate
                if (!empty($keyword)) {
                    return $this->repository->search($keyword, $filters, $perPage);
                } else {
                    return $this->repository->paginate($filters, $perPage);
                }
            }
        } catch (\Exception $e) {
            Log::error('Get user list failed', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            throw new BusinessException(
                '取得使用者列表失敗',
                UserErrorCode::SYSTEM_ERROR->value,
                500
            );
        }
    }

    /**
     * 取得使用者詳情
     * 
     * @param int $id 使用者 ID
     * @return User
     * @throws BusinessException
     */
    public function getDetail(int $id): User
    {
        try {
            $cacheKey = "users:{$id}";
            
            return Cache::remember($cacheKey, 3600, function () use ($id) {
                $user = $this->repository->find($id);
                
                if (!$user) {
                    throw new BusinessException(
                        UserErrorCode::USER_NOT_FOUND->message(),
                        UserErrorCode::USER_NOT_FOUND->value,
                        UserErrorCode::USER_NOT_FOUND->httpStatus()
                    );
                }
                
                // 檢查門市存取權限：沒有跨店查看權限且不是同一門市
                if (auth()->user()->cannot('viewAcrossStores', User::class) && $user->store_id !== auth()->user()->store_id) {
                    throw new BusinessException(
                        UserErrorCode::STORE_ACCESS_DENIED->message(),
                        UserErrorCode::STORE_ACCESS_DENIED->value,
                        UserErrorCode::STORE_ACCESS_DENIED->httpStatus()
                    );
                }
                
                return $user->load(['roles', 'permissions', 'store']);
            });
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Get user detail failed', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            throw new BusinessException(
                '取得使用者詳情失敗',
                UserErrorCode::SYSTEM_ERROR->value,
                500
            );
        }
    }

    /**
     * 創建使用者
     * 
     * @param array $data 使用者資料
     * @return User
     * @throws BusinessException
     */
    public function create(array $data): User
    {
        return $this->executeInTransaction(function () use ($data) {
            // 驗證資料
            $this->validateUserData($data);
            
            // 檢查門市存取權限
            if (!auth()->user()->canAccessStore($data['store_id'])) {
                throw new BusinessException(
                    UserErrorCode::STORE_ACCESS_DENIED->message(),
                    UserErrorCode::STORE_ACCESS_DENIED->value,
                    UserErrorCode::STORE_ACCESS_DENIED->httpStatus()
                );
            }
            
            // 處理密碼
            if (isset($data['password'])) {
                if (!$this->validatePasswordStrength($data['password'])) {
                    throw new BusinessException(
                        UserErrorCode::WEAK_PASSWORD->message(),
                        UserErrorCode::WEAK_PASSWORD->value,
                        UserErrorCode::WEAK_PASSWORD->httpStatus()
                    );
                }
                $data['password'] = Hash::make($data['password']);
            }
            
            $data['created_by'] = auth()->id();
            
            // 創建使用者
            $user = $this->repository->create($data);
            
            // 同步角色
            if (isset($data['roles'])) {
                $this->syncUserRoles($user->id, $data['roles']);
            }
            
            // 同步權限
            if (isset($data['permissions'])) {
                $this->syncUserPermissions($user->id, $data['permissions']);
            }
            
            Log::info('User created', [
                'user_id' => $user->id,
                'created_by' => auth()->id()
            ]);
            
            return $user->fresh(['roles', 'permissions']);
        });
    }

    /**
     * 更新使用者
     * 
     * @param int $id 使用者 ID
     * @param array $data 更新資料
     * @return User
     * @throws BusinessException
     */
    public function update(int $id, array $data): User
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $user = $this->getDetail($id);
            
            // 驗證資料（排除自己）
            $this->validateUserData($data, $id);
            
            // 處理密碼
            if (isset($data['password'])) {
                if (!$this->validatePasswordStrength($data['password'])) {
                    throw new BusinessException(
                        UserErrorCode::WEAK_PASSWORD->message(),
                        UserErrorCode::WEAK_PASSWORD->value,
                        UserErrorCode::WEAK_PASSWORD->httpStatus()
                    );
                }
                $data['password'] = Hash::make($data['password']);
            }
            
            $data['updated_by'] = auth()->id();
            
            // 更新使用者
            $updatedUser = $this->repository->update($id, $data);
            
            // 同步角色
            if (isset($data['roles'])) {
                $this->syncUserRoles($id, $data['roles']);
            }
            
            // 同步權限
            if (isset($data['permissions'])) {
                $this->syncUserPermissions($id, $data['permissions']);
            }
            
            // 清除快取
            Cache::forget("users:{$id}");
            Cache::forget("user_accessible_stores_{$id}");
            
            Log::info('User updated', [
                'user_id' => $id,
                'updated_by' => auth()->id()
            ]);
            
            return $updatedUser->fresh(['roles', 'permissions']);
        });
    }

    /**
     * 刪除使用者（軟刪除）
     * 
     * @param int $id 使用者 ID
     * @return bool
     * @throws BusinessException
     */
    /**
     * 刪除使用者（軟刪除）
     * 
     * ✅✅✅ V4.0 標準修復：信任鏈重構 ✅✅✅
     * 不再重新獲取模型，直接信任從 Controller 傳來的、已授權的 User 模型實例
     * 權限檢查已在 Controller 層完成，Service 層專注於「刪除」業務邏輯本身
     * 
     * @param User $user 已授權的使用者模型實例
     * @return bool
     * @throws BusinessException
     */
    public function delete(User $user): bool
    {
        return $this->executeInTransaction(function () use ($user) {
            // ✅ 正確：直接使用已授權的 User 模型實例，無需重複驗證
            // 權限檢查（包含「不能刪除自己」、「不能刪除管理員」等規則）已在 Controller@authorize() 完成
            
            $result = $this->repository->delete($user);
            
            if ($result) {
                // 撤銷所有 Token
                $user->tokens()->delete();
                
                // 清除快取
                Cache::forget("users:{$user->id}");
                Cache::forget("user_accessible_stores_{$user->id}");
                
                Log::info('User deleted', [
                    'user_id' => $user->id,
                    'deleted_by' => auth()->id()
                ]);
            }
            
            return $result;
        });
    }

    /**
     * 批量更新使用者狀態
     * 
     * @param array $ids 使用者 ID 列表
     * @param string $status 新狀態
     * @return int 影響行數
     * @throws BusinessException
     */
    public function batchUpdateStatus(array $ids, string $status): int
    {
        if (!UserStatus::tryFrom($status)) {
            throw new BusinessException(
                '無效的使用者狀態',
                'INVALID_USER_STATUS',
                400
            );
        }
        
        // 檢查是否包含管理員帳號
        if ($status === UserStatus::INACTIVE->value) {
            $adminUsers = $this->repository->findWhereIn('id', $ids)
                ->filter(fn($user) => $user->hasRole('admin'));
                
            if ($adminUsers->isNotEmpty()) {
                throw new BusinessException(
                    UserErrorCode::CANNOT_DELETE_ADMIN->message(),
                    UserErrorCode::CANNOT_DELETE_ADMIN->value,
                    UserErrorCode::CANNOT_DELETE_ADMIN->httpStatus()
                );
            }
        }
        
        return $this->executeInTransaction(function () use ($ids, $status) {
            $affectedRows = $this->repository->batchUpdateStatus($ids, $status);
            
            // 清除快取
            foreach ($ids as $id) {
                Cache::forget("users:{$id}");
                Cache::forget("user_accessible_stores_{$id}");
            }
            
            Log::info('Batch status update', [
                'user_ids' => $ids,
                'status' => $status,
                'affected_rows' => $affectedRows,
                'updated_by' => auth()->id()
            ]);
            
            return $affectedRows;
        });
    }

    /**
     * 重設密碼
     * 
     * @param int $id 使用者 ID
     * @param string $password 新密碼
     * @return User
     * @throws BusinessException
     */
    public function resetPassword(int $id, string $password): User
    {
        return $this->executeInTransaction(function () use ($id, $password) {
            $user = $this->getDetail($id);
            
            if (!$this->validatePasswordStrength($password)) {
                throw new BusinessException(
                    UserErrorCode::WEAK_PASSWORD->message(),
                    UserErrorCode::WEAK_PASSWORD->value,
                    UserErrorCode::WEAK_PASSWORD->httpStatus()
                );
            }
            
            $updatedUser = $this->repository->update($id, [
                'password' => Hash::make($password),
                'updated_by' => auth()->id()
            ]);
            
            // 重設密碼後清除所有 Token
            $user->tokens()->delete();
            
            Log::info('Password reset', [
                'user_id' => $id,
                'reset_by' => auth()->id()
            ]);
            
            return $updatedUser;
        });
    }

    /**
     * 同步使用者角色
     * 
     * @param int $userId 使用者 ID
     * @param array $roles 角色名稱列表
     * @return void
     * @throws BusinessException
     */
    public function syncUserRoles(int $userId, array $roles): void
    {
        try {
            $user = $this->repository->findOrFail($userId);
            $user->syncRoles($roles);
            
            // 清除快取
            Cache::forget("users:{$userId}");
            Cache::forget("user_accessible_stores_{$userId}");
            
            Log::info('User roles synced', [
                'user_id' => $userId,
                'roles' => $roles,
                'synced_by' => auth()->id()
            ]);
        } catch (\Exception $e) {
            Log::error('Sync user roles failed', [
                'user_id' => $userId,
                'roles' => $roles,
                'error' => $e->getMessage()
            ]);
            
            throw new BusinessException(
                '同步使用者角色失敗',
                UserErrorCode::SYSTEM_ERROR->value,
                500
            );
        }
    }

    /**
     * 同步使用者權限
     * 
     * @param int $userId 使用者 ID
     * @param array $permissions 權限名稱列表
     * @return void
     * @throws BusinessException
     */
    public function syncUserPermissions(int $userId, array $permissions): void
    {
        try {
            $user = $this->repository->findOrFail($userId);
            $user->syncPermissions($permissions);
            
            // 清除快取
            Cache::forget("users:{$userId}");
            Cache::forget("user_accessible_stores_{$userId}");
            
            Log::info('User permissions synced', [
                'user_id' => $userId,
                'permissions' => $permissions,
                'synced_by' => auth()->id()
            ]);
        } catch (\Exception $e) {
            Log::error('Sync user permissions failed', [
                'user_id' => $userId,
                'permissions' => $permissions,
                'error' => $e->getMessage()
            ]);
            
            throw new BusinessException(
                '同步使用者權限失敗',
                UserErrorCode::SYSTEM_ERROR->value,
                500
            );
        }
    }

    /**
     * 取得門市統計資料
     * 
     * @param int $storeId 門市 ID
     * @return array
     */
    public function getStoreStatistics(int $storeId): array
    {
        return Cache::remember("store_user_stats:{$storeId}", 3600, function () use ($storeId) {
            return $this->repository->getStoreUserStatistics($storeId);
        });
    }

    /**
     * 取得活躍使用者
     * 
     * @param int $days 天數
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getActiveUsers(int $days = 7, int $limit = 50): Collection
    {
        return Cache::remember("active_users:{$days}:{$limit}", 1800, function () use ($days, $limit) {
            return $this->repository->getActiveUsersInStore(auth()->user()->store_id)
                ->where('last_login_at', '>=', now()->subDays($days))
                ->take($limit);
        });
    }

    /**
     * 驗證使用者資料
     * 
     * @param array $data 使用者資料
     * @param int|null $excludeId 排除的使用者 ID
     * @return void
     * @throws BusinessException
     */
    private function validateUserData(array $data, ?int $excludeId = null): void
    {
        // 檢查使用者名稱重複
        if (isset($data['username'])) {
            $existing = $this->repository->findByUsername($data['username']);
            if ($existing && $existing->id !== $excludeId) {
                throw new BusinessException(
                    UserErrorCode::USERNAME_EXISTS->message(),
                    UserErrorCode::USERNAME_EXISTS->value,
                    UserErrorCode::USERNAME_EXISTS->httpStatus()
                );
            }
        }
        
        // 檢查郵件重複
        if (isset($data['email'])) {
            $existing = $this->repository->findByEmail($data['email']);
            if ($existing && $existing->id !== $excludeId) {
                throw new BusinessException(
                    UserErrorCode::EMAIL_EXISTS->message(),
                    UserErrorCode::EMAIL_EXISTS->value,
                    UserErrorCode::EMAIL_EXISTS->httpStatus()
                );
            }
        }
    }

    /**
     * 驗證密碼強度
     * 
     * @param string $password 密碼
     * @return bool
     */
    private function validatePasswordStrength(string $password): bool
    {
        return strlen($password) >= 8 &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^a-zA-Z0-9]/', $password);
    }

    /**
     * 取得整體統計資料
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        return Cache::remember('user_statistics', 3600, function () {
            $user = auth()->user();
            $isAdmin = $user->hasRole('admin');
            $storeId = $isAdmin ? null : $user->store_id;
            
            // 基礎統計
            $filters = $storeId ? ['store_id' => $storeId] : [];
            $totalUsers = $this->repository->count($filters);
            $activeUsers = $this->repository->count(array_merge($filters, ['status' => UserStatus::ACTIVE->value]));
            $inactiveUsers = $this->repository->count(array_merge($filters, ['status' => UserStatus::INACTIVE->value]));
            $pendingUsers = $this->repository->count(array_merge($filters, ['status' => UserStatus::PENDING->value]));
            $lockedUsers = $this->repository->count(array_merge($filters, ['status' => UserStatus::LOCKED->value]));
            
            // 狀態分佈統計
            $statusDistribution = [];
            $statuses = [
                ['status' => 'active', 'count' => $activeUsers, 'color' => '#10B981'],
                ['status' => 'inactive', 'count' => $inactiveUsers, 'color' => '#6B7280'],
                ['status' => 'pending', 'count' => $pendingUsers, 'color' => '#F59E0B'],
                ['status' => 'locked', 'count' => $lockedUsers, 'color' => '#EF4444'],
            ];
            
            foreach ($statuses as $statusData) {
                $percentage = $totalUsers > 0 ? round(($statusData['count'] / $totalUsers) * 100, 1) : 0;
                $statusDistribution[] = [
                    'status' => $statusData['status'],
                    'count' => $statusData['count'],
                    'percentage' => $percentage,
                    'color' => $statusData['color']
                ];
            }
            
            // 角色分佈統計
            $roleDistribution = $this->getRoleDistribution($storeId);
            
            // 門市分佈統計（僅管理員可見）
            $storeDistribution = $isAdmin ? $this->getStoreDistribution() : [];
            
            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'status_distribution' => $statusDistribution,
                'role_distribution' => $roleDistribution,
                'store_distribution' => $storeDistribution
            ];
        });
    }

    /**
     * 取得角色分佈統計
     * 
     * @param int|null $storeId 門市 ID
     * @return array
     */
    private function getRoleDistribution(?int $storeId): array
    {
        $query = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', '=', User::class)
            ->whereNull('users.deleted_at');
            
        if ($storeId) {
            $query->where('users.store_id', $storeId);
        }
        
        $roleStats = $query
            ->select('roles.name as role', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->get();
            
        $total = $roleStats->sum('count');
        
        return $roleStats->map(function ($role) use ($total) {
            return [
                'role' => $role->role,
                'count' => $role->count,
                'percentage' => $total > 0 ? round(($role->count / $total) * 100, 1) : 0
            ];
        })->toArray();
    }

    /**
     * 取得門市分佈統計
     * 
     * @return array
     */
    private function getStoreDistribution(): array
    {
        $storeStats = DB::table('users')
            ->join('stores', 'users.store_id', '=', 'stores.id')
            ->whereNull('users.deleted_at')
            ->select('stores.name as store_name', 'stores.id as store_id', DB::raw('COUNT(*) as count'))
            ->groupBy('stores.id', 'stores.name')
            ->get();
            
        $total = $storeStats->sum('count');
        
        return $storeStats->map(function ($store) use ($total) {
            return [
                'store_id' => $store->store_id,
                'store_name' => $store->store_name,
                'count' => $store->count,
                'percentage' => $total > 0 ? round(($store->count / $total) * 100, 1) : 0
            ];
        })->toArray();
    }

    /**
     * 管理員重設密碼
     * 
     * @param int $id 使用者 ID
     * @param User $operator 操作者
     * @return string 新密碼
     * @throws BusinessException
     */
    public function resetPasswordByAdmin(int $id, User $operator): string
    {
        $user = $this->getDetail($id);
        
        // 產生隨機密碼
        $newPassword = \Str::random(12) . '!';
        
        $this->repository->update($id, [
            'password' => Hash::make($newPassword),
            'updated_by' => $operator->id
        ]);
        
        // 重設密碼後清除所有 Token
        $user->tokens()->delete();
        
        Log::info('Password reset by admin', [
            'user_id' => $id,
            'admin_id' => $operator->id
        ]);
        
        return $newPassword;
    }

    /**
     * 分頁查詢使用者列表
     * 
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        // 門市隔離篩選
        if (!auth()->user()->hasRole('admin')) {
            $filters['store_id'] = auth()->user()->store_id;
        }

        return $this->repository->search($filters, $perPage);
    }

    /**
     * 啟用 2FA
     * 
     * @param User $user
     * @return array
     * @throws BusinessException
     */
    public function enable2FA(User $user): array
    {
        $google2fa = app('pragmarx.google2fa');
        $secretKey = $google2fa->generateSecretKey();
        
        $this->repository->update($user->id, [
            'two_factor_secret' => encrypt($secretKey),
            'two_factor_confirmed_at' => now()
        ]);

        return [
            'secret_key' => $secretKey,
            'qr_code_url' => $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secretKey
            )
        ];
    }

    /**
     * 停用 2FA
     * 
     * @param User $user
     * @return bool
     */
    public function disable2FA(User $user): bool
    {
        return $this->repository->update($user->id, [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null
        ]) !== null;
    }

    /**
     * 更新使用者登入資訊
     * 
     * @param User $user 使用者實例
     * @param Request $request 請求物件
     * @return void
     */
    public function updateLoginInfo(User $user, Request $request): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'login_attempts' => 0, // 成功登入後重置嘗試次數
        ]);
        
        Log::info('用戶登入資訊已更新', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }

    /**
     * 驗證使用者是否可以登入
     * 檢查使用者狀態、帳號鎖定等條件
     *
     * @param User $user 使用者實例
     * @return void
     * @throws BusinessException
     */
    public function validateUserCanLogin(User $user): void
    {
        // 檢查使用者狀態
        if ($user->status !== UserStatus::ACTIVE) {
            $statusLabel = match ($user->status) {
                UserStatus::INACTIVE => '停用',
                UserStatus::LOCKED => '鎖定',
                UserStatus::PENDING => '待啟用',
                default => '未知'
            };
            
            throw new BusinessException(
                "帳號狀態為「{$statusLabel}」，無法登入",
                UserErrorCode::ACCOUNT_STATUS_INVALID->value,
                403
            );
        }

        // 檢查帳號是否被鎖定
        if ($user->locked_until && $user->locked_until->isFuture()) {
            throw new BusinessException(
                '帳號已被鎖定，請於 ' . $user->locked_until->format('Y-m-d H:i:s') . ' 後再試',
                UserErrorCode::ACCOUNT_LOCKED->value,
                423
            );
        }

        // 檢查登入嘗試次數
        if ($user->login_attempts >= 5) {
            // 自動鎖定帳號 30 分鐘
            $user->update([
                'locked_until' => now()->addMinutes(30)
            ]);
            
            throw new BusinessException(
                '登入嘗試次數過多，帳號已被鎖定 30 分鐘',
                UserErrorCode::TOO_MANY_ATTEMPTS->value,
                423
            );
        }

        // 檢查電子郵件是否已驗證（如果需要）
        if (config('auth.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            throw new BusinessException(
                '請先驗證您的電子郵件地址',
                UserErrorCode::EMAIL_NOT_VERIFIED->value,
                403
            );
        }

        Log::info('使用者登入狀態驗證通過', [
            'user_id' => $user->id,
            'username' => $user->username,
            'status' => $user->status
        ]);
    }

    /**
     * 記錄成功登入
     * 更新使用者的登入資訊並重置登入嘗試次數
     *
     * @param User $user 使用者實例
     * @param string $ip 登入 IP 地址
     * @return void
     */
    public function recordSuccessfulLogin(User $user, string $ip): void
    {
        try {
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $ip,
                'login_attempts' => 0, // 重置登入嘗試次數
                'locked_until' => null, // 清除鎖定狀態
            ]);

            // 記錄活動日誌
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties([
                    'ip' => $ip,
                    'user_agent' => request()->userAgent(),
                    'action' => 'login'
                ])
                ->log('使用者登入');

            Log::info('成功登入記錄已更新', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $ip,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            // 登入記錄失敗不應該影響登入流程，僅記錄錯誤
            Log::warning('記錄登入資訊失敗', [
                'user_id' => $user->id,
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 執行事務處理
     * 
     * @param callable $callback 回調函數
     * @return mixed
     * @throws BusinessException
     */
    private function executeInTransaction(callable $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (BusinessException $e) {
            // 重新拋出業務異常
            throw $e;
        } catch (\Exception $e) {
            Log::error('Transaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new BusinessException(
                '操作失敗，請稍後重試',
                'TRANSACTION_FAILED',
                500
            );
        }
    }
}