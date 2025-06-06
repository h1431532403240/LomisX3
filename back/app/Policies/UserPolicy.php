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
        // 檢查基本權限
        if (!$user->can('users.delete')) {
            return false;
        }

        // 防止刪除自己
        if ($user->id === $model->id) {
            return false;
        }

        // 檢查門市存取權限
        return $user->canAccessStore($model->store_id);
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
        return $user->hasRole('admin');
    }

    /**
     * 檢查是否可以批次更新使用者狀態
     */
    public function batchStatus(User $user): bool
    {
        return $user->can('users.batch-status');
    }
}
