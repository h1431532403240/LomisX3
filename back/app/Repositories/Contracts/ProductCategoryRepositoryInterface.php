<?php

namespace App\Repositories\Contracts;

use App\Models\ProductCategory;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * 商品分類 Repository 介面
 * 定義商品分類資料存取層的契約
 */
interface ProductCategoryRepositoryInterface
{
    /**
     * 取得樹狀結構
     *
     * @param bool $onlyActive 是否僅取啟用的分類
     */
    public function getTree(bool $onlyActive = true): Collection;

    /**
     * 分頁查詢（支援 cursor pagination）
     *
     * @param int   $perPage   每頁筆數
     * @param array $filters   篩選條件
     * @param bool  $useCursor 是否使用游標分頁
     */
    public function paginate(
        int $perPage = 20,
        array $filters = [],
        bool $useCursor = false
    ): LengthAwarePaginator|CursorPaginator;

    /**
     * 根據 ID 查找分類
     *
     * @param int $id 分類 ID
     */
    public function findById(int $id): ?ProductCategory;

    /**
     * 根據 ID 查找啟用的分類
     *
     * @param int $id 分類 ID
     */
    public function findActiveById(int $id): ?ProductCategory;

    /**
     * 根據 slug 查找分類
     *
     * @param string $slug 分類 slug
     */
    public function findBySlug(string $slug): ?ProductCategory;

    /**
     * 根據 slug 查找啟用的分類
     *
     * @param string $slug 分類 slug
     */
    public function findActiveBySlug(string $slug): ?ProductCategory;

    /**
     * 查詢祖先分類
     *
     * @param int $categoryId 分類 ID
     */
    public function getAncestors(int $categoryId): Collection;

    /**
     * 查詢子孫分類
     *
     * @param int $categoryId 分類 ID
     */
    public function getDescendants(int $categoryId): Collection;

    /**
     * 查詢直接子分類
     *
     * @param int $categoryId 分類 ID
     */
    public function getChildren(int $categoryId): Collection;

    /**
     * 檢查循環引用
     *
     * @param int      $categoryId 分類 ID
     * @param int|null $parentId   父分類 ID
     */
    public function checkCircularReference(int $categoryId, ?int $parentId): bool;

    /**
     * 更新排序位置
     *
     * @param array $positions 位置資料 [['id' => 1, 'position' => 1], ...]
     */
    public function updatePositions(array $positions): bool;

    /**
     * 取得最大排序值
     *
     * @param int|null $parentId 父分類 ID
     */
    public function getMaxPosition(?int $parentId = null): int;

    /**
     * 檢查 slug 是否存在
     *
     * @param string   $slug      要檢查的 slug
     * @param int|null $excludeId 排除的分類 ID
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool;

    /**
     * 檢查分類名稱是否存在
     *
     * @param string   $name      要檢查的名稱
     * @param int|null $excludeId 排除的分類 ID
     */
    public function nameExists(string $name, ?int $excludeId = null): bool;

    /**
     * 批次更新狀態
     *
     * @param array $ids    分類 ID 陣列
     * @param bool  $status 狀態值
     *
     * @return int 影響的筆數
     */
    public function batchUpdateStatus(array $ids, bool $status): int;

    /**
     * 批次刪除
     *
     * @param array $ids 分類 ID 陣列
     *
     * @return int 影響的筆數
     */
    public function batchDelete(array $ids): int;

    /**
     * 批次恢復
     *
     * @param array $ids 分類 ID 陣列
     *
     * @return int 影響的筆數
     */
    public function batchRestore(array $ids): int;

    /**
     * 搜尋分類
     *
     * @param string $query   搜尋關鍵字
     * @param array  $filters 額外篩選條件
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * 取得根分類
     *
     * @param bool $onlyActive 是否僅取啟用的分類
     */
    public function getRootCategories(bool $onlyActive = true): Collection;

    /**
     * 取得指定深度內的分類
     *
     * @param int  $maxDepth   最大深度
     * @param bool $onlyActive 是否僅取啟用的分類
     */
    public function getCategoriesByDepth(int $maxDepth, bool $onlyActive = true): Collection;

    /**
     * 建立分類
     *
     * @param array $data 分類資料
     */
    public function create(array $data): ProductCategory;

    /**
     * 更新分類
     *
     * @param int   $id   分類 ID
     * @param array $data 更新資料
     */
    public function update(int $id, array $data): ProductCategory;

    /**
     * 軟刪除分類
     *
     * @param int $id 分類 ID
     */
    public function delete(int $id): bool;

    /**
     * 恢復分類
     *
     * @param int $id 分類 ID
     */
    public function restore(int $id): bool;

    /**
     * 強制刪除分類
     *
     * @param int $id 分類 ID
     */
    public function forceDelete(int $id): bool;

    /**
     * 取得分類統計資訊
     */
    public function getStatistics(): array;

    /**
     * 取得同層分類的數量
     *
     * @param int|null $parentId 父分類 ID
     */
    public function getSiblingsCount(?int $parentId = null): int;

    /**
     * 取得分類路徑
     *
     * @param int $categoryId 分類 ID
     */
    public function getCategoryPath(int $categoryId): string;

    /**
     * 檢查是否有子分類
     *
     * @param int $categoryId 分類 ID
     */
    public function hasChildren(int $categoryId): bool;

    /**
     * 取得分類層級數量統計
     */
    public function getDepthStatistics(): array;
}
