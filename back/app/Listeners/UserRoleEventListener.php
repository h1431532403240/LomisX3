<?php

namespace App\Listeners;

use App\Services\UserCacheService;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Events\{RoleAssigned, RoleRemoved, PermissionAssigned, PermissionRemoved};

/**
 * 使用者角色權限事件監聽器
 * 處理 Spatie Permission 套件的角色權限變更事件
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
class UserRoleEventListener
{
    /**
     * UserCacheService 依賴注入
     */
    public function __construct(
        protected UserCacheService $cacheService
    ) {}

    /**
     * 處理角色分配事件
     *
     * @param RoleAssigned $event
     * @return void
     */
    public function handleRoleAssigned(RoleAssigned $event): void
    {
        if ($event->getModel() instanceof User) {
            $user = $event->getModel();
            
            // 記錄角色分配活動
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'role_assigned' => $event->getRole()->name,
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'store_id' => $user->store_id,
                    'ip_address' => request()->ip()
                ])
                ->log('分配使用者角色');

            // 清除相關快取
            $this->cacheService->clearUserCache($user->id);
            $this->cacheService->clearStoreUsersCache($user->store_id);

            // 清除權限快取
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            Log::info('Role assigned to user', [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $event->getRole()->name,
                'assigned_by' => auth()->id()
            ]);
        }
    }

    /**
     * 處理角色移除事件
     *
     * @param RoleRemoved $event
     * @return void
     */
    public function handleRoleRemoved(RoleRemoved $event): void
    {
        if ($event->getModel() instanceof User) {
            $user = $event->getModel();
            
            // 記錄角色移除活動
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'role_removed' => $event->getRole()->name,
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'store_id' => $user->store_id,
                    'ip_address' => request()->ip()
                ])
                ->log('移除使用者角色');

            // 清除相關快取
            $this->cacheService->clearUserCache($user->id);
            $this->cacheService->clearStoreUsersCache($user->store_id);

            // 清除權限快取
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            Log::info('Role removed from user', [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $event->getRole()->name,
                'removed_by' => auth()->id()
            ]);
        }
    }

    /**
     * 處理權限分配事件
     *
     * @param PermissionAssigned $event
     * @return void
     */
    public function handlePermissionAssigned(PermissionAssigned $event): void
    {
        if ($event->getModel() instanceof User) {
            $user = $event->getModel();
            
            // 記錄權限分配活動
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'permission_assigned' => $event->getPermission()->name,
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'store_id' => $user->store_id,
                    'ip_address' => request()->ip()
                ])
                ->log('分配使用者權限');

            // 清除相關快取
            $this->cacheService->clearUserCache($user->id);
            $this->cacheService->clearStoreUsersCache($user->store_id);

            // 清除權限快取
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            Log::info('Permission assigned to user', [
                'user_id' => $user->id,
                'username' => $user->username,
                'permission' => $event->getPermission()->name,
                'assigned_by' => auth()->id()
            ]);
        }
    }

    /**
     * 處理權限移除事件
     *
     * @param PermissionRemoved $event
     * @return void
     */
    public function handlePermissionRemoved(PermissionRemoved $event): void
    {
        if ($event->getModel() instanceof User) {
            $user = $event->getModel();
            
            // 記錄權限移除活動
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'permission_removed' => $event->getPermission()->name,
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'store_id' => $user->store_id,
                    'ip_address' => request()->ip()
                ])
                ->log('移除使用者權限');

            // 清除相關快取
            $this->cacheService->clearUserCache($user->id);
            $this->cacheService->clearStoreUsersCache($user->store_id);

            // 清除權限快取
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            Log::info('Permission removed from user', [
                'user_id' => $user->id,
                'username' => $user->username,
                'permission' => $event->getPermission()->name,
                'removed_by' => auth()->id()
            ]);
        }
    }
} 