<?php

namespace App\Repositories;

use App\Models\ProductCategory;
use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * 商品分類 Repository 實作
 * 處理商品分類的資料存取邏輯
 */
class ProductCategoryRepository implements ProductCategoryRepositoryInterface
{
    /**
     * 建構函式
     */
    public function __construct(
        protected ProductCategory $model
    ) {}

    /**
     * 取得樹狀結構
     */
    public function getTree(bool $onlyActive = true): Collection
    {
        $query = $this->model->with(['children' => function ($q) use ($onlyActive) {
            if ($onlyActive) {
                $q->active();
            }
            $q->ordered();
        }])
            ->root()
            ->ordered();

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * 分頁查詢（支援 cursor pagination）
     */
    public function paginate(
        int $perPage = 20,
        array $filters = [],
        bool $useCursor = false
    ): LengthAwarePaginator|CursorPaginator {
        $query = $this->model->query();

        // 應用篩選條件
        $this->applyFilters($query, $filters);

        $query->ordered()->with(['parent']);

        return $useCursor
            ? $query->cursorPaginate($perPage)
            : $query->paginate($perPage);
    }

    /**
     * 根據 ID 查找分類
     */
    public function findById(int $id): ?ProductCategory
    {
        return $this->model->find($id);
    }

    /**
     * 根據 ID 查找啟用的分類
     */
    public function findActiveById(int $id): ?ProductCategory
    {
        return $this->model->active()
            ->where('id', $id)
            ->first();
    }

    /**
     * 根據 slug 查找分類
     */
    public function findBySlug(string $slug): ?ProductCategory
    {
        return $this->model->where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * 根據 slug 查找啟用的分類
     */
    public function findActiveBySlug(string $slug): ?ProductCategory
    {
        return $this->model->active()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * 查詢祖先分類
     */
    public function getAncestors(int $categoryId): Collection
    {
        $category = $this->findById($categoryId);

        if (! $category) {
            return new Collection;
        }

        return $category->ancestors();
    }

    /**
     * 查詢子孫分類
     */
    public function getDescendants(int $categoryId): Collection
    {
        $category = $this->findById($categoryId);

        if (! $category) {
            return new Collection;
        }

        return $category->descendants();
    }

    /**
     * 查詢直接子分類
     */
    public function getChildren(int $categoryId): Collection
    {
        return $this->model->where('parent_id', $categoryId)
            ->ordered()
            ->get();
    }

    /**
     * 檢查循環引用
     */
    public function checkCircularReference(int $categoryId, ?int $parentId): bool
    {
        if (! $parentId || $categoryId === $parentId) {
            return $categoryId !== $parentId; // 自己不能是自己的父分類
        }

        $parent = $this->findById($parentId);
        if (! $parent) {
            return false; // 父分類不存在
        }

        $category = $this->findById($categoryId);
        if (! $category) {
            return false; // 分類不存在
        }

        // 檢查父分類是否為當前分類的子孫
        return ! $parent->isDescendantOf($category);
    }

    /**
     * 更新排序位置
     */
    public function updatePositions(array $positions): bool
    {
        try {
            foreach ($positions as $item) {
                if (isset($item['id']) && isset($item['position'])) {
                    $updateData = ['position' => $item['position']];

                    // 如果提供了 parent_id，也一併更新
                    if (isset($item['parent_id'])) {
                        $updateData['parent_id'] = $item['parent_id'];
                    }

                    $this->model->where('id', $item['id'])
                        ->update($updateData);
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update positions: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * 取得最大排序值
     */
    public function getMaxPosition(?int $parentId = null): int
    {
        return $this->model->where('parent_id', $parentId)->max('position') ?? 0;
    }

    /**
     * 檢查 slug 是否存在
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug)
            ->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * 檢查分類名稱是否存在
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = $this->model->where('name', $name)
            ->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * 批次更新狀態
     */
    public function batchUpdateStatus(array $ids, bool $status): int
    {
        return $this->model->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    /**
     * 批次刪除
     */
    public function batchDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * 批次恢復
     */
    public function batchRestore(array $ids): int
    {
        return $this->model->onlyTrashed()
            ->whereIn('id', $ids)
            ->restore();
    }

    /**
     * 搜尋分類
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->search($query);

        $this->applyFilters($queryBuilder, $filters);

        return $queryBuilder->ordered()->get();
    }

    /**
     * 取得根分類
     */
    public function getRootCategories(bool $onlyActive = true): Collection
    {
        $query = $this->model->root()->ordered();

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * 取得指定深度內的分類
     */
    public function getCategoriesByDepth(int $maxDepth, bool $onlyActive = true): Collection
    {
        $query = $this->model->withDepth($maxDepth)->ordered();

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * 建立分類
     */
    public function create(array $data): ProductCategory
    {
        return $this->model->create($data);
    }

    /**
     * 更新分類
     */
    public function update(int $id, array $data): ProductCategory
    {
        $category = $this->model->findOrFail($id);
        $category->update($data);

        return $category->fresh();
    }

    /**
     * 軟刪除分類
     */
    public function delete(int $id): bool
    {
        $category = $this->findById($id);

        if (! $category) {
            return false;
        }

        return $category->delete();
    }

    /**
     * 恢復分類
     */
    public function restore(int $id): bool
    {
        $category = $this->model->onlyTrashed()->find($id);

        if (! $category) {
            return false;
        }

        return $category->restore();
    }

    /**
     * 強制刪除分類
     */
    public function forceDelete(int $id): bool
    {
        $category = $this->model->withTrashed()->find($id);

        if (! $category) {
            return false;
        }

        return $category->forceDelete();
    }

    /**
     * 取得分類統計資訊
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->active()->count(),
            'inactive' => $this->model->where('status', false)->count(),
            'deleted' => $this->model->onlyTrashed()->count(),
            'root_categories' => $this->model->root()->count(),
            'max_depth' => $this->model->max('depth') ?? 0,
            'by_depth' => $this->getDepthStatistics(),
        ];
    }

    /**
     * 取得同層分類的數量
     */
    public function getSiblingsCount(?int $parentId = null): int
    {
        return $this->model->where('parent_id', $parentId)->count();
    }

    /**
     * 取得分類路徑
     */
    public function getCategoryPath(int $categoryId): string
    {
        $category = $this->findById($categoryId);

        if (! $category) {
            return '';
        }

        return $category->full_path;
    }

    /**
     * 檢查是否有子分類
     */
    public function hasChildren(int $categoryId): bool
    {
        return $this->model->where('parent_id', $categoryId)->exists();
    }

    /**
     * 取得分類層級數量統計
     */
    public function getDepthStatistics(): array
    {
        return $this->model->selectRaw('depth, COUNT(*) as count')
            ->groupBy('depth')
            ->pluck('count', 'depth')
            ->toArray();
    }

    /**
     * 應用查詢篩選條件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'null' || is_null($filters['parent_id'])) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['depth'])) {
            $query->where('depth', $filters['depth']);
        }

        if (isset($filters['max_depth'])) {
            $query->withDepth($filters['max_depth']);
        }

        if (! empty($filters['with_trashed']) && $filters['with_trashed']) {
            $query->withTrashed();
        }
    }
}
