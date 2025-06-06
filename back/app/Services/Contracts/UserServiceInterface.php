<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * 使用者服務介面
 * 定義使用者業務邏輯層的標準方法
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
interface UserServiceInterface
{
    /**
     * 取得使用者列表（含分頁）
     */
    public function getList(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * 取得使用者詳情
     */
    public function getDetail(int $id): User;

    /**
     * 建立使用者
     */
    public function create(array $data): User;

    /**
     * 更新使用者
     */
    public function update(int $id, array $data): User;

    /**
     * 刪除使用者（軟刪除）
     */
    public function delete(int $id): bool;

    /**
     * 批次更新使用者狀態
     */
    public function batchUpdateStatus(array $ids, string $status): int;

    /**
     * 重設使用者密碼
     */
    public function resetPassword(int $id, string $password): User;

    /**
     * 同步使用者角色
     */
    public function syncRoles(int $userId, array $roles): bool;

    /**
     * 同步使用者權限
     */
    public function syncPermissions(int $userId, array $permissions): bool;

    /**
     * 取得使用者統計資料
     */
    public function getStatistics(int $storeId): array;

    /**
     * 取得活躍使用者列表
     */
    public function getActiveUsers(int $storeId): Collection;

    /**
     * 驗證密碼強度
     */
    public function validatePasswordStrength(string $password): bool;

    /**
     * 檢查使用者存取權限
     */
    public function checkStoreAccess(int $userId, int $storeId): bool;
} 