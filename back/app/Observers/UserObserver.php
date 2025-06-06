<?php

namespace App\Observers;

use App\Models\User;
use App\Services\UserCacheService;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\{Hash, Log};
use Illuminate\Support\Str;

/**
 * 使用者模型觀察者
 * 
 * 負責：
 * 1. 密碼雜湊處理
 * 2. 快取清理
 * 3. Token 清理
 * 4. 活動日誌記錄
 * 5. 相關業務邏輯觸發
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
class UserObserver
{
    /**
     * UserCacheService 依賴注入
     */
    public function __construct(
        protected UserCacheService $cacheService
    ) {}

    /**
     * 模型儲存前事件
     * 
     * @param User $user
     * @return void
     */
    public function saving(User $user): void
    {
        // V6.2 優化：正確的密碼雜湊檢查
        if ($user->isDirty('password') && !empty($user->password)) {
            // 檢查密碼是否已經是雜湊格式
            if (!Str::startsWith($user->password, '$2y$')) {
                $user->password = Hash::make($user->password);
                Log::info('Password hashed for user', ['user_id' => $user->id ?? 'new']);
            }
        }

        // 設定創建/更新者
        if (auth()->check()) {
            if (!$user->exists) {
                $user->created_by = auth()->id();
            }
            $user->updated_by = auth()->id();
        }

        // 軟刪除唯一約束將由資料庫計算欄位自動處理
        // 無需在模型層面手動設定
    }

    /**
     * 模型建立後事件
     * 
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        // 記錄活動日誌
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'store_id' => $user->store_id,
                'username' => $user->username,
                'email' => $user->email,
                'status' => $user->status,
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent')
            ])
            ->log('建立使用者帳號');

        // 清除門市相關快取
        $this->cacheService->clearStoreUsersCache($user->store_id);

        Log::info('User created successfully', [
            'user_id' => $user->id,
            'username' => $user->username,
            'store_id' => $user->store_id,
            'created_by' => auth()->id()
        ]);
    }

    /**
     * 模型更新後事件
     * 
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        $original = $user->getOriginal();

        // 記錄重要變更的活動日誌
        $this->logSignificantChanges($user, $changes, $original);

        // 清除相關快取
        $this->clearUserCaches($user, $changes);

        // V6.2: 密碼變更時清除所有 Token
        if (isset($changes['password'])) {
            $user->tokens()->delete();
            
            Log::info('All tokens revoked due to password change', [
                'user_id' => $user->id,
                'username' => $user->username,
                'changed_by' => auth()->id()
            ]);
        }

        // V6.2: 狀態變更時的特殊處理
        if (isset($changes['status'])) {
            $oldStatus = $original['status'] ?? '';
            $newStatus = $changes['status'];
            
            // 確保狀態值是字串格式
            $oldStatusValue = $oldStatus instanceof \App\Enums\UserStatus ? $oldStatus->value : (string) $oldStatus;
            $newStatusValue = $newStatus instanceof \App\Enums\UserStatus ? $newStatus->value : (string) $newStatus;
            
            $this->handleStatusChange($user, $oldStatusValue, $newStatusValue);
        }

        // V6.2: 門市變更時的處理
        if (isset($changes['store_id'])) {
            $this->handleStoreChange($user, $original['store_id'], $changes['store_id']);
        }

        Log::info('User updated successfully', [
            'user_id' => $user->id,
            'username' => $user->username,
            'changes' => array_keys($changes),
            'updated_by' => auth()->id()
        ]);
    }

    /**
     * 模型刪除前事件
     * 
     * @param User $user
     * @return void
     */
    public function deleting(User $user): void
    {
        // 記錄刪除活動
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'username' => $user->username,
                'email' => $user->email,
                'store_id' => $user->store_id,
                'original_data' => $user->only([
                    'name', 'phone', 'status', 'last_login_at'
                ]),
                'ip_address' => request()->ip()
            ])
            ->log('刪除使用者帳號');

        Log::warning('User deletion initiated', [
            'user_id' => $user->id,
            'username' => $user->username,
            'store_id' => $user->store_id,
            'deleted_by' => auth()->id()
        ]);
    }

    /**
     * 模型刪除後事件
     * 
     * @param User $user
     * @return void
     */
    public function deleted(User $user): void
    {
        // 清除所有相關 Token
        $user->tokens()->delete();

        // 清除相關快取
        $this->cacheService->clearUserCache($user->id);
        $this->cacheService->clearStoreUsersCache($user->store_id);

        Log::info('User deleted and cleaned up', [
            'user_id' => $user->id,
            'username' => $user->username,
            'tokens_deleted' => true,
            'cache_cleared' => true
        ]);
    }

    /**
     * 模型恢復後事件
     * 
     * @param User $user
     * @return void
     */
    public function restored(User $user): void
    {
        // 記錄恢復活動
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'username' => $user->username,
                'email' => $user->email,
                'store_id' => $user->store_id,
                'ip_address' => request()->ip()
            ])
            ->log('恢復使用者帳號');

        // 清除相關快取
        $this->cacheService->clearUserCache($user->id);
        $this->cacheService->clearStoreUsersCache($user->store_id);

        Log::info('User restored successfully', [
            'user_id' => $user->id,
            'username' => $user->username,
            'restored_by' => auth()->id()
        ]);
    }

    /**
     * 記錄重要變更的活動日誌
     * 
     * @param User $user
     * @param array $changes
     * @param array $original
     * @return void
     */
    private function logSignificantChanges(User $user, array $changes, array $original): void
    {
        $significantFields = [
            'username', 'email', 'status', 'store_id', 
            'password', 'two_factor_confirmed_at'
        ];

        $significantChanges = array_intersect_key($changes, array_flip($significantFields));

        if (!empty($significantChanges)) {
            $logProperties = [
                'user_id' => $user->id,
                'changes' => [],
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent')
            ];

            foreach ($significantChanges as $field => $newValue) {
                $oldValue = $original[$field] ?? null;
                
                // 隱藏敏感資訊
                if ($field === 'password') {
                    $logProperties['changes'][$field] = [
                        'old' => '***隱藏***',
                        'new' => '***隱藏***'
                    ];
                } elseif ($field === 'two_factor_confirmed_at') {
                    $logProperties['changes'][$field] = [
                        'old' => $oldValue ? '已啟用' : '未啟用',
                        'new' => $newValue ? '已啟用' : '未啟用'
                    ];
                } else {
                    $logProperties['changes'][$field] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }

            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties($logProperties)
                ->log('更新使用者資料');
        }
    }

    /**
     * 清除使用者相關快取
     * 
     * @param User $user
     * @param array $changes
     * @return void
     */
    private function clearUserCaches(User $user, array $changes): void
    {
        // 總是清除基本使用者快取
        $this->cacheService->clearUserCache($user->id);

        // 如果門市、狀態或角色相關欄位變更，清除門市快取
        $fieldsAffectingStoreCache = ['store_id', 'status'];
        if (array_intersect_key($changes, array_flip($fieldsAffectingStoreCache))) {
            $this->cacheService->clearStoreUsersCache($user->store_id);
            
            // 如果門市變更，也要清除原門市的快取
            if (isset($changes['store_id'])) {
                $originalStoreId = $user->getOriginal('store_id');
                if ($originalStoreId) {
                    $this->cacheService->clearStoreUsersCache($originalStoreId);
                }
            }
        }
    }

    /**
     * 處理使用者狀態變更
     * 
     * @param User $user
     * @param mixed $oldStatus
     * @param mixed $newStatus
     * @return void
     */
    private function handleStatusChange(User $user, mixed $oldStatus, mixed $newStatus): void
    {
        // 轉換狀態為字串值以便比較
        $oldStatusValue = $oldStatus instanceof \App\Enums\UserStatus ? $oldStatus->value : (string) $oldStatus;
        $newStatusValue = $newStatus instanceof \App\Enums\UserStatus ? $newStatus->value : (string) $newStatus;
        
        // 記錄狀態變更的詳細日誌
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'status_change' => [
                    'from' => $oldStatusValue,
                    'to' => $newStatusValue
                ],
                'username' => $user->username,
                'store_id' => $user->store_id,
                'ip_address' => request()->ip()
            ])
            ->log('變更使用者狀態');

        // 如果帳號被鎖定或停用，清除所有 Token
        if (in_array($newStatusValue, [UserStatus::LOCKED->value, UserStatus::INACTIVE->value])) {
            $tokenCount = $user->tokens()->count();
            $user->tokens()->delete();
            
            Log::warning('User tokens revoked due to status change', [
                'user_id' => $user->id,
                'status_from' => $oldStatusValue,
                'status_to' => $newStatusValue,
                'tokens_revoked' => $tokenCount
            ]);
        }
    }

    /**
     * 處理使用者門市變更
     * 
     * @param User $user
     * @param int $oldStoreId
     * @param int $newStoreId
     * @return void
     */
    private function handleStoreChange(User $user, int $oldStoreId, int $newStoreId): void
    {
        // 記錄門市變更
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'store_change' => [
                    'from_store_id' => $oldStoreId,
                    'to_store_id' => $newStoreId
                ],
                'username' => $user->username,
                'ip_address' => request()->ip()
            ])
            ->log('變更使用者門市');

        // 清除角色相關快取（Spatie Permission 的門市隔離）
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Log::info('User store changed', [
            'user_id' => $user->id,
            'username' => $user->username,
            'from_store' => $oldStoreId,
            'to_store' => $newStoreId,
            'changed_by' => auth()->id()
        ]);
    }
} 