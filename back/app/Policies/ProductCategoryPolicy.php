<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\ProductCategory;
use App\Models\User;

/**
 * 商品分類權限策略
 *
 * 定義商品分類相關操作的權限控制，包含：
 * - 基於角色的權限矩陣
 * - Sanctum token 權限檢查
 * - 細粒度權限控制
 *
 * 權限矩陣：
 * - ADMIN: 全部權限
 * - MANAGER: 管理權限（除強制刪除外）
 * - STAFF: 基本查看權限
 * - GUEST: 無權限
 */
class ProductCategoryPolicy
{
    /**
     * 權限檢查前置處理
     * 超級管理員或擁有 super-admin token 權限的使用者可以執行所有操作
     */
    public function before(User $user, string $ability): ?bool
    {
        // 檢查使用者角色是否為 ADMIN
        $userRole = Role::fromString($user->role ?? '');
        if ($userRole && $userRole->isAdmin()) {
            return true;
        }

        // 檢查 Sanctum token 是否擁有 super-admin 權限
        if ($user->tokenCan('product-categories:super-admin')) {
            return true;
        }

        // 返回 null 繼續執行後續權限檢查
        return null;
    }

    /**
     * 檢查使用者是否可以查看任何分類
     */
    public function viewAny(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isStaffOrAbove();
    }

    /**
     * 檢查使用者是否可以查看指定分類
     */
    public function view(User $user, ProductCategory $productCategory): bool
    {
        $userRole = Role::fromString($user->role ?? '');
        if (! $userRole) {
            return false;
        }

        // 如果分類已刪除，只有管理員可以查看
        if ($productCategory->trashed()) {
            return $userRole->isAdmin();
        }

        // 如果分類已停用，只有管理員和經理可以查看
        if (! $productCategory->status) {
            return $userRole->isManagerOrAbove();
        }

        // 啟用的分類，所有授權使用者都可以查看
        return $userRole->isStaffOrAbove();
    }

    /**
     * 檢查使用者是否可以建立分類
     */
    public function create(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isManagerOrAbove();
    }

    /**
     * 檢查使用者是否可以更新指定分類
     */
    public function update(User $user, ProductCategory $productCategory): bool
    {
        // 已刪除的分類不能更新
        if ($productCategory->trashed()) {
            return false;
        }

        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isManagerOrAbove();
    }

    /**
     * 檢查使用者是否可以刪除指定分類
     */
    public function delete(User $user, ProductCategory $productCategory): bool
    {
        // 已刪除的分類不能再次刪除
        if ($productCategory->trashed()) {
            return false;
        }

        // 如果分類有子分類，不能刪除
        if ($productCategory->children()->exists()) {
            return false;
        }

        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isAdmin();
    }

    /**
     * 檢查使用者是否可以恢復指定分類
     */
    public function restore(User $user, ProductCategory $productCategory): bool
    {
        // 只有已刪除的分類才能恢復
        if (! $productCategory->trashed()) {
            return false;
        }

        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isAdmin();
    }

    /**
     * 檢查使用者是否可以強制刪除指定分類
     */
    public function forceDelete(User $user, ProductCategory $productCategory): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isAdmin();
    }

    /**
     * 檢查使用者是否可以重新排序分類
     */
    public function reorder(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isManagerOrAbove();
    }

    /**
     * 檢查使用者是否可以批次更新分類
     */
    public function batchUpdate(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isManagerOrAbove();
    }

    /**
     * 檢查使用者是否可以查看分類樹
     */
    public function viewTree(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * 檢查使用者是否可以查看麵包屑
     */
    public function viewBreadcrumbs(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * 檢查使用者是否可以查看分類統計
     */
    public function viewStatistics(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isManagerOrAbove();
    }

    /**
     * 檢查使用者是否可以匯出分類資料
     */
    public function export(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isManagerOrAbove();
    }

    /**
     * 檢查使用者是否可以匯入分類資料
     */
    public function import(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isAdmin();
    }

    /**
     * 檢查使用者是否可以清除快取
     */
    public function clearCache(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isAdmin();
    }

    /**
     * 檢查使用者是否可以查看快取資訊
     */
    public function viewCacheInfo(User $user): bool
    {
        $userRole = Role::fromString($user->role ?? '');

        return $userRole && $userRole->isManagerOrAbove();
    }
}
