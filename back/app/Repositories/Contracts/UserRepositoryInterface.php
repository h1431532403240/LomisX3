<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * 使用者 Repository 介面
 * 
 * 定義使用者資料存取層的契約
 * 遵循 LomisX3 架構標準的 Repository Pattern
 */
interface UserRepositoryInterface
{
    /**
     * 基礎查詢方法
     */
    
    /**
     * 根據 ID 查找使用者
     *
     * @param int $id 使用者 ID
     * @param array $relations 預載關聯
     * @return User|null
     */
    public function find(int $id, array $relations = []): ?User;

    /**
     * 根據 ID 查找使用者（找不到拋出例外）
     *
     * @param int $id 使用者 ID
     * @param array $relations 預載關聯
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $relations = []): User;

    /**
     * 根據門市 ID 查找使用者
     *
     * @param int $storeId 門市 ID
     * @param array $filters 篩選條件
     * @return Collection
     */
    public function findByStoreId(int $storeId, array $filters = []): Collection;

    /**
     * 根據使用者名稱查找
     *
     * @param string $username 使用者名稱
     * @return User|null
     */
    public function findByUsername(string $username): ?User;

    /**
     * 根據電子郵件查找
     *
     * @param string $email 電子郵件
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * 根據電子郵件或使用者名稱查找
     *
     * @param string $login 登入帳號（使用者名稱或電子郵件）
     * @return User|null
     */
    public function findByEmailOrUsername(string $login): ?User;

    /**
     * 根據使用者名稱和門市查找
     *
     * @param string $username 使用者名稱
     * @param int $storeId 門市 ID
     * @return User|null
     */
    public function findByUsernameWithStore(string $username, int $storeId): ?User;

    /**
     * 根據欄位值查找多筆記錄
     *
     * @param string $field 欄位名稱
     * @param array $values 值陣列
     * @param array $relations 預載關聯
     * @return Collection
     */
    public function findWhereIn(string $field, array $values, array $relations = []): Collection;

    /**
     * 存在性檢查方法
     */
    
    /**
     * 檢查使用者名稱是否存在
     *
     * @param string $username 使用者名稱
     * @param int|null $excludeId 排除的使用者 ID
     * @return bool
     */
    public function existsByUsername(string $username, ?int $excludeId = null): bool;

    /**
     * 檢查電子郵件是否存在
     *
     * @param string $email 電子郵件
     * @param int|null $excludeId 排除的使用者 ID
     * @return bool
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool;

    /**
     * 查詢與分頁方法
     */
    
    /**
     * 分頁查詢使用者
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * 游標分頁查詢
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁筆數
     * @return CursorPaginator
     */
    public function cursorPaginate(array $filters = [], int $perPage = 20): CursorPaginator;

    /**
     * 搜尋使用者
     *
     * @param string $keyword 搜尋關鍵字
     * @param array $filters 額外篩選條件
     * @param int $perPage 每頁數量
     * @return LengthAwarePaginator
     */
    public function search(string $keyword, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * 角色與權限相關方法
     */
    
    /**
     * 取得具有角色的使用者
     *
     * @param int $storeId 門市 ID
     * @param array $roles 角色名稱陣列
     * @return Collection
     */
    public function getUsersWithRoles(int $storeId, array $roles = []): Collection;

    /**
     * 取得門市內活躍使用者
     *
     * @param int $storeId 門市 ID
     * @return Collection
     */
    public function getActiveUsersInStore(int $storeId): Collection;

    /**
     * 取得具有特定角色的使用者
     *
     * @param array $userIds 使用者 ID 陣列
     * @param string $role 角色名稱
     * @return Collection
     */
    public function getUsersWithRole(array $userIds, string $role): Collection;

    /**
     * 統計方法
     */
    
    /**
     * 取得門市活躍使用者數量
     *
     * @param int $storeId 門市 ID
     * @return int
     */
    public function getActiveUsersCount(int $storeId): int;

    /**
     * 計算符合條件的使用者數量
     *
     * @param array $filters 篩選條件
     * @return int
     */
    public function count(array $filters = []): int;

    /**
     * 取得門市使用者統計
     *
     * @param int $storeId 門市 ID
     * @return array
     */
    public function getStoreUserStatistics(int $storeId): array;

    /**
     * 取得使用者登入歷史
     *
     * @param int $userId 使用者 ID
     * @param int $days 天數
     * @return Collection
     */
    public function getUserLoginHistory(int $userId, int $days = 30): Collection;

    /**
     * 取得最近活躍使用者
     *
     * @param int $days 天數
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getRecentActiveUsers(int $days = 7, int $limit = 100): Collection;

    /**
     * CRUD 操作方法
     */
    
    /**
     * 建立使用者
     *
     * @param array $data 使用者資料
     * @return User
     */
    public function create(array $data): User;

    /**
     * 更新使用者
     *
     * @param int $id 使用者 ID
     * @param array $data 更新資料
     * @return User
     */
    public function update(int $id, array $data): User;

    /**
     * 靜默更新（不觸發事件）
     *
     * @param int $id 使用者 ID
     * @param array $data 更新資料
     * @return bool
     */
    public function updateQuietly(int $id, array $data): bool;

    /**
     * 軟刪除使用者
     *
     * @param int $id 使用者 ID
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * 批次操作方法
     */
    
    /**
     * 批次更新狀態
     *
     * @param array $ids 使用者 ID 陣列
     * @param string $status 狀態值
     * @return int 影響的筆數
     */
    public function batchUpdateStatus(array $ids, string $status): int;

    /**
     * 批次軟刪除
     *
     * @param array $ids 使用者 ID 陣列
     * @return int 影響的筆數
     */
    public function batchDelete(array $ids): int;

    /**
     * 批次分配角色
     *
     * @param array $ids 使用者 ID 陣列
     * @param string $role 角色名稱
     * @return int 影響的筆數
     */
    public function batchAssignRole(array $ids, string $role): int;

    /**
     * 批次更新最後登入時間
     *
     * @param array $userIds 使用者 ID 陣列
     * @param Carbon $loginTime 登入時間
     * @return int 影響的筆數
     */
    public function batchUpdateLastLogin(array $userIds, Carbon $loginTime): int;

    /**
     * 密碼與安全相關方法
     */
    
    /**
     * 取得密碼過期的使用者
     *
     * @param int $days 密碼有效天數
     * @return Collection
     */
    public function getUsersWithExpiredPasswords(int $days = 90): Collection;

    /**
     * 取得被鎖定的使用者
     *
     * @return Collection
     */
    public function getLockedUsers(): Collection;

    /**
     * 取得需要驗證信箱的使用者
     *
     * @param int $days 未驗證天數
     * @return Collection
     */
    public function getUnverifiedUsers(int $days = 7): Collection;

    /**
     * 快取相關方法
     */
    
    /**
     * 清除使用者快取
     *
     * @param int $userId 使用者 ID
     * @return void
     */
    public function clearUserCache(int $userId): void;

    /**
     * 清除門市使用者快取
     *
     * @param int $storeId 門市 ID
     * @return void
     */
    public function clearStoreUsersCache(int $storeId): void;

    /**
     * 預熱活躍使用者快取
     *
     * @param int $days 活躍天數
     * @return int 預熱的使用者數量
     */
    public function warmupActiveUsersCache(int $days = 7): int;
}
