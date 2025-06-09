<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // 允許查看自己的資料
        if ($user->id === $model->id) {
            return true;
        }

        // 檢查基本權限
        if (!$user->can('users.view')) {
            return false;
        }

        // 檢查門市存取權限
        return $user->canAccessStore($model->store_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // 檢查基本權限
        if (!$user->can('users.update')) {
            return false;
        }

        // 檢查門市存取權限
        return $user->canAccessStore($model->store_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // 規則 1：任何人都不能刪除自己。這條規則的優先級最高。
        if ($user->id === $model->id) {
            return false;
        }

        // 規則 2：檢查基本權限
        if (!$user->can('users.delete')) {
            return false;
        }

        // 規則 3：檢查操作者是否有權限訪問目標用戶所在的門市
        return $user->canAccessStore($model->store_id);
        
        // 注意：Gate::before 會讓 super_admin 在這裡直接返回 true，
        // 但「不能刪除自己」的規則依然會對 super_admin 生效，這是一個好的設計。
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('users.create') && $user->canAccessStore($model->store_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // ✅ V5.1 權限驅動改造：使用權限檢查而非角色檢查
        return $user->can('system.operate_across_stores');
    }

    /**
     * 檢查是否可以批次更新使用者狀態
     */
    public function batchStatus(User $user): bool
    {
        return $user->can('users.batch-status');
    }

    /**
     * 決定一個使用者是否可以跨所有門市查看其他使用者
     * 這是跨域權限判斷的唯一、權威的來源
     *
     * @param \App\Models\User $user 當前操作的使用者
     * @return bool
     */
    public function viewAcrossStores(User $user): bool
    {
        // ✅ V5.1 權限驅動改造：使用語義清晰的跨域權限
        return $user->can('system.operate_across_stores');
    }
}
