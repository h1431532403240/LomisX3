<?php

namespace App\Services;

use App\Enums\ProductCategoryErrorCode;
use App\Exceptions\BusinessException;
use App\Models\ProductCategory;
use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Activity;

/**
 * 商品分類服務層
 * 處理商品分類的業務邏輯和複雜操作
 */
class ProductCategoryService
{
    /**
     * 最大深度限制
     */
    private const MAX_DEPTH = 3;

    /**
     * 建構函式
     */
    public function __construct(
        private ProductCategoryRepositoryInterface $repository,
        private ProductCategoryCacheService $cacheService
    ) {}

    /**
     * 建立分類
     *
     * @param array $data 分類資料
     *
     * @throws BusinessException
     */
    public function createCategory(array $data): ProductCategory
    {
        // 驗證父分類深度
        if (isset($data['parent_id'])) {
            $this->validateDepth($data['parent_id']);
            $this->validateParentCategory($data['parent_id']);
        }

        // 生成唯一 slug（如果未提供）
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlugForCreate($data);
        }

        // 檢查 slug 唯一性
        if ($this->repository->slugExists($data['slug'])) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::DUPLICATE_SLUG,
                "URL 別名 '{$data['slug']}' 已存在"
            );
        }

        try {
            $category = $this->repository->create($data);
            $this->cacheService->forgetTree();

            return $category;
        } catch (\Exception $e) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::BATCH_OPERATION_FAILED,
                '分類建立失敗：' . $e->getMessage()
            );
        }
    }

    /**
     * 更新分類
     *
     * @param int   $id   分類 ID
     * @param array $data 更新資料
     *
     * @throws BusinessException
     */
    public function updateCategory(int $id, array $data): ProductCategory
    {
        $category = $this->repository->findById($id);
        if (! $category) {
            throw BusinessException::fromErrorCode(ProductCategoryErrorCode::CATEGORY_NOT_FOUND);
        }

        // 檢查循環引用
        if (isset($data['parent_id'])) {
            if (! $this->repository->checkCircularReference($id, $data['parent_id'])) {
                throw BusinessException::fromErrorCode(
                    ProductCategoryErrorCode::CIRCULAR_REFERENCE_DETECTED
                );
            }

            // 驗證父分類深度
            if ($data['parent_id']) {
                $this->validateDepth($data['parent_id']);
                $this->validateParentCategory($data['parent_id']);
            }
        }

        // 生成唯一 slug（如果名稱有變更且未提供 slug）
        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = $this->generateSlugForUpdate($data, $id);
        }

        // 檢查 slug 唯一性
        if (isset($data['slug']) && $this->repository->slugExists($data['slug'], $id)) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::DUPLICATE_SLUG,
                "URL 別名 '{$data['slug']}' 已存在"
            );
        }

        try {
            $updatedCategory = $this->repository->update($id, $data);
            $this->cacheService->forgetTree();

            return $updatedCategory;
        } catch (\Exception $e) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::BATCH_OPERATION_FAILED,
                '分類更新失敗：' . $e->getMessage()
            );
        }
    }

    /**
     * 刪除分類
     *
     * @param int $id 分類 ID
     *
     * @throws BusinessException
     */
    public function deleteCategory(int $id): bool
    {
        $category = $this->repository->findById($id);
        if (! $category) {
            throw BusinessException::fromErrorCode(ProductCategoryErrorCode::CATEGORY_NOT_FOUND);
        }

        // 檢查是否有子分類
        if ($this->repository->hasChildren($id)) {
            throw BusinessException::fromErrorCode(ProductCategoryErrorCode::CATEGORY_HAS_CHILDREN);
        }

        // TODO: 檢查是否有商品綁定此分類
        // if ($this->hasProducts($id)) {
        //     throw BusinessException::fromErrorCode(ProductCategoryErrorCode::CATEGORY_HAS_PRODUCTS);
        // }

        try {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->cacheService->forgetTree();
            }

            return $result;
        } catch (\Exception $e) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::BATCH_OPERATION_FAILED,
                '分類刪除失敗：' . $e->getMessage()
            );
        }
    }

    /**
     * 更新排序位置
     *
     * @param array $positions 位置資料
     *
     * @throws BusinessException
     */
    public function updatePositions(array $positions): bool
    {
        try {
            return DB::transaction(function () use ($positions) {
                $result = $this->repository->updatePositions($positions);
                if ($result) {
                    $this->cacheService->forgetTree();
                }

                return $result;
            });
        } catch (\Exception $e) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::BATCH_OPERATION_FAILED,
                '排序更新失敗：' . $e->getMessage()
            );
        }
    }

    /**
     * 批次更新狀態
     *
     * @param array $ids    分類 ID 陣列
     * @param bool  $status 狀態值
     *
     * @throws BusinessException
     */
    public function batchUpdateStatus(array $ids, bool $status): int
    {
        if (empty($ids)) {
            return 0;
        }

        // 驗證所有分類是否存在
        $categories = $this->repository->findByIds($ids);
        if ($categories->count() !== count($ids)) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::CATEGORY_NOT_FOUND,
                '部分分類不存在'
            );
        }

        try {
            return DB::transaction(function () use ($ids, $status, $categories) {
                // 使用批次 UUID 記錄活動日誌
                $batchUuid = \Illuminate\Support\Str::uuid()->toString();
                
                // 記錄批次操作開始
                activity()
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'batch_uuid' => $batchUuid,
                        'operation_type' => 'batch_status_update',
                        'target_ids' => $ids,
                        'new_status' => $status,
                        'affected_count' => count($ids),
                        'categories_info' => $categories->map(function ($category) {
                            return [
                                'id' => $category->id,
                                'name' => $category->name,
                                'current_status' => $category->status,
                                'depth' => $category->depth,
                            ];
                        })->toArray()
                    ])
                    ->useLog('product_categories')
                    ->log('開始批次更新商品分類狀態');

                $result = $this->repository->batchUpdateStatus($ids, $status);
                
                if ($result > 0) {
                    $this->cacheService->forgetTree();
                    
                    // 記錄每個分類的狀態變更
                    foreach ($categories as $category) {
                        if ($category->status !== $status) {
                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($category)
                                ->withProperties([
                                    'batch_uuid' => $batchUuid,
                                    'operation_type' => 'status_change',
                                    'old_status' => $category->status,
                                    'new_status' => $status,
                                    'status_label' => $status ? '啟用' : '停用',
                                ])
                                ->useLog('product_categories')
                                ->event('status_changed')
                                ->log('批次更新狀態');
                        }
                    }
                    
                    // 記錄批次操作完成
                    activity()
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'batch_uuid' => $batchUuid,
                            'operation_type' => 'batch_status_update_completed',
                            'success_count' => $result,
                            'new_status' => $status,
                        ])
                        ->useLog('product_categories')
                        ->log("批次狀態更新完成，成功更新 {$result} 個分類");
                }

                return $result;
            });
        } catch (\Exception $e) {
            // 記錄批次操作失敗
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'operation_type' => 'batch_status_update_failed',
                    'target_ids' => $ids,
                    'error_message' => $e->getMessage(),
                ])
                ->useLog('product_categories')
                ->log('批次狀態更新失敗');
                
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::BATCH_OPERATION_FAILED,
                '批次狀態更新失敗：' . $e->getMessage()
            );
        }
    }

    /**
     * 批次刪除分類
     *
     * @param array $ids 分類 ID 陣列
     *
     * @throws BusinessException
     */
    public function batchDelete(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        // 驗證所有分類是否存在
        $categories = $this->repository->findByIds($ids);
        if ($categories->count() !== count($ids)) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::CATEGORY_NOT_FOUND,
                '部分分類不存在'
            );
        }

        // 檢查是否有分類包含子分類
        foreach ($categories as $category) {
            if ($this->repository->hasChildren($category->id)) {
                throw BusinessException::fromErrorCode(
                    ProductCategoryErrorCode::CATEGORY_HAS_CHILDREN,
                    "分類 '{$category->name}' 包含子分類，無法刪除"
                );
            }
        }

        try {
            return DB::transaction(function () use ($ids, $categories) {
                // 使用批次 UUID 記錄活動日誌
                $batchUuid = \Illuminate\Support\Str::uuid()->toString();
                
                // 記錄批次操作開始
                activity()
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'batch_uuid' => $batchUuid,
                        'operation_type' => 'batch_delete',
                        'target_ids' => $ids,
                        'affected_count' => count($ids),
                        'categories_info' => $categories->map(function ($category) {
                            return [
                                'id' => $category->id,
                                'name' => $category->name,
                                'slug' => $category->slug,
                                'depth' => $category->depth,
                                'parent_id' => $category->parent_id,
                            ];
                        })->toArray()
                    ])
                    ->useLog('product_categories')
                    ->log('開始批次刪除商品分類');

                $result = $this->repository->batchDelete($ids);
                
                if ($result > 0) {
                    $this->cacheService->forgetTree();
                    
                    // 記錄每個分類的刪除
                    foreach ($categories as $category) {
                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($category)
                            ->withProperties([
                                'batch_uuid' => $batchUuid,
                                'operation_type' => 'soft_delete',
                                'deletion_reason' => 'batch_operation',
                            ])
                            ->useLog('product_categories')
                            ->event('deleted')
                            ->log('批次刪除操作');
                    }
                    
                    // 記錄批次操作完成
                    activity()
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'batch_uuid' => $batchUuid,
                            'operation_type' => 'batch_delete_completed',
                            'success_count' => $result,
                        ])
                        ->useLog('product_categories')
                        ->log("批次刪除完成，成功刪除 {$result} 個分類");
                }

                return $result;
            });
        } catch (\Exception $e) {
            // 記錄批次操作失敗
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'operation_type' => 'batch_delete_failed',
                    'target_ids' => $ids,
                    'error_message' => $e->getMessage(),
                ])
                ->useLog('product_categories')
                ->log('批次刪除失敗');
                
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::BATCH_OPERATION_FAILED,
                '批次刪除失敗：' . $e->getMessage()
            );
        }
    }

    /**
     * 產生唯一的 slug（SEO 優化版本）
     * 
     * 使用智能混合策略避免破壞 SEO：
     * 1. 基本重試機制（最多3次）
     * 2. 啟用分類：使用日期碼+隨機確保 SEO 友善 
     * 3. 草稿/隱藏分類：可使用純隨機字串
     *
     * @param string   $name      分類名稱
     * @param int|null $excludeId 排除的 ID
     * @param bool     $isActive  是否為啟用狀態（預設 true）
     */
    public function generateUniqueSlug(string $name, ?int $excludeId = null, bool $isActive = true): string
    {
        // 處理空字串和只有特殊字元的情況
        $baseSlug = Str::slug($name);
        
        // 如果轉換後的 slug 是空的，使用預設 slug
        if (empty($baseSlug)) {
            $baseSlug = 'category';
        }
        
        $slug = $baseSlug;
        $counter = 2;
        $maxAttempts = config('product_category.slug_retry_max', 3);

        // 基本重試機制
        $attempts = 1;
        while ($attempts <= $maxAttempts && $this->repository->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            $attempts++;
        }

        // Phase 3.1 SEO 智能策略：根據分類狀態選擇後備方案
        if ($attempts > $maxAttempts && $this->repository->slugExists($slug, $excludeId)) {
            return $this->generateFallbackSlug($baseSlug, $isActive, $excludeId);
        }

        return $slug;
    }

    /**
     * 產生後備 slug（根據分類狀態智能選擇策略）
     * 
     * @param string   $baseSlug  基礎 slug
     * @param bool     $isActive  是否為啟用狀態
     * @param int|null $excludeId 排除的 ID
     * @return string
     */
    private function generateFallbackSlug(string $baseSlug, bool $isActive, ?int $excludeId = null): string
    {
        if (!$isActive) {
            // 草稿或隱藏分類：可使用隨機字串
            return $this->generateRandomSlug($baseSlug, $excludeId);
        }
        
        // 啟用分類：使用 SEO 友善的日期碼格式
        return $this->generateSeoFriendlySlug($baseSlug, $excludeId);
    }

    /**
     * 產生 SEO 友善的日期碼 slug
     * 
     * 格式：baseSlug-yyyyMMdd-xxxx
     * 兼具可讀性和唯一性
     * 
     * @param string   $baseSlug  基礎 slug
     * @param int|null $excludeId 排除的 ID
     * @return string
     */
    private function generateSeoFriendlySlug(string $baseSlug, ?int $excludeId = null): string
    {
        $dateCode = date('Ymd'); // 20250107 格式
        $randomSuffix = strtolower(Str::random(4));
        
        $slug = "{$baseSlug}-{$dateCode}-{$randomSuffix}";
        
        // 最終檢查：極低機率的衝突處理
        $retryCount = 0;
        while ($this->repository->slugExists($slug, $excludeId) && $retryCount < 5) {
            $randomSuffix = strtolower(Str::random(4));
            $slug = "{$baseSlug}-{$dateCode}-{$randomSuffix}";
            $retryCount++;
        }
        
        // 如果還是衝突，加入時間戳作為最終保障
        if ($this->repository->slugExists($slug, $excludeId)) {
            $timeStamp = substr((string) time(), -4); // 取時間戳後4位
            $slug = "{$baseSlug}-{$dateCode}-{$timeStamp}";
        }

        Log::info('生成 SEO 友善的 slug', [
            'base_slug' => $baseSlug,
            'final_slug' => $slug,
            'date_code' => $dateCode,
            'random_suffix' => $randomSuffix,
            'seo_impact' => 'minimal - 保持可讀性',
        ]);

        return $slug;
    }

    /**
     * 產生隨機 slug（僅用於草稿/隱藏分類）
     * 
     * @param string   $baseSlug  基礎 slug
     * @param int|null $excludeId 排除的 ID
     * @return string
     */
    private function generateRandomSlug(string $baseSlug, ?int $excludeId = null): string
    {
        $randomSuffix = Str::random(4);
        $slug = "{$baseSlug}-{$randomSuffix}";
        
        // 最後檢查：如果還是衝突，使用時間戳作為最終保障
        if ($this->repository->slugExists($slug, $excludeId)) {
            $slug = "{$baseSlug}-" . time() . '-' . Str::random(2);
        }

        Log::info('生成隨機 slug（非啟用分類）', [
            'base_slug' => $baseSlug,
            'final_slug' => $slug,
            'random_suffix' => $randomSuffix,
            'seo_impact' => 'none - 隱藏分類',
        ]);

        return $slug;
    }

    /**
     * 更新 createCategory 方法中的 slug 生成邏輯
     * 
     * @param array<string, mixed> $data 分類資料
     * @return string
     */
    private function generateSlugForCreate(array $data): string
    {
        $isActive = $data['status'] ?? true;
        return $this->generateUniqueSlug($data['name'], null, $isActive);
    }

    /**
     * 更新 updateCategory 方法中的 slug 生成邏輯
     * 
     * @param array<string, mixed> $data 分類資料
     * @param int $excludeId 排除的 ID
     * @return string
     */
    private function generateSlugForUpdate(array $data, int $excludeId): string
    {
        $isActive = $data['status'] ?? true;
        return $this->generateUniqueSlug($data['name'], $excludeId, $isActive);
    }

    /**
     * 取得下一個排序位置
     *
     * @param int|null $parentId 父分類 ID
     */
    public function getNextPosition(?int $parentId = null): int
    {
        return $this->repository->getMaxPosition($parentId) + 1;
    }

    /**
     * 計算深度
     *
     * @param int|null $parentId 父分類 ID
     */
    public function calculateDepth(?int $parentId): int
    {
        if (! $parentId) {
            return 0;
        }

        $parent = $this->repository->findActiveById($parentId);

        return $parent ? $parent->depth + 1 : 0;
    }

    /**
     * 更新子孫分類的深度
     *
     * @param ProductCategory $category 分類實例
     */
    public function updateDescendantsDepth(ProductCategory $category): void
    {
        $descendants = $category->descendants();

        foreach ($descendants as $descendant) {
            $newDepth = $this->calculateDepth($descendant->parent_id);
            if ($descendant->depth !== $newDepth) {
                $descendant->update(['depth' => $newDepth]);
            }
        }
    }

    /**
     * 驗證分類深度
     *
     * @param int|null $parentId 父分類 ID
     *
     * @throws BusinessException
     */
    private function validateDepth(?int $parentId): void
    {
        if (! $parentId) {
            return;
        }

        $parent = $this->repository->findById($parentId);
        if (! $parent) {
            throw BusinessException::fromErrorCode(ProductCategoryErrorCode::PARENT_NOT_FOUND);
        }

        if ($parent->depth >= self::MAX_DEPTH) {
            throw BusinessException::fromErrorCode(
                ProductCategoryErrorCode::MAX_DEPTH_EXCEEDED,
                '分類層級不能超過 ' . self::MAX_DEPTH . ' 層'
            );
        }
    }

    /**
     * 驗證父分類
     *
     * @param int $parentId 父分類 ID
     *
     * @throws BusinessException
     */
    private function validateParentCategory(int $parentId): void
    {
        $parent = $this->repository->findById($parentId);
        if (! $parent) {
            throw BusinessException::fromErrorCode(ProductCategoryErrorCode::PARENT_NOT_FOUND);
        }

        if (! $parent->status) {
            throw BusinessException::fromErrorCode(ProductCategoryErrorCode::PARENT_INACTIVE);
        }
    }

    /**
     * 清除相關快取
     */
    public function clearCache(): void
    {
        $this->cacheService->forgetTree();
    }

    /**
     * 取得完整的分類樹
     * 支援快取、深度限制和根節點過濾
     * 包含 OpenTelemetry 手動追蹤
     *
     * @param array $options 樹狀結構選項
     *                      - root_id: 根節點ID (null 表示所有根節點)
     *                      - max_depth: 最大深度 (null 表示無限制)
     *                      - include_inactive: 是否包含停用分類 (預設 false)
     * @return Collection<ProductCategory>
     */
    public function getTree(array $options = []): Collection
    {
        // 建立 OpenTelemetry 手動 span
        $tracer = \OpenTelemetry\API\Globals::tracerProvider()
            ->getTracer('product-category-service');
        
        $span = $tracer->spanBuilder('ProductCategory.getTree')
            ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_INTERNAL)
            ->setAttribute('service.name', 'product-category')
            ->setAttribute('operation.name', 'getTree')
            ->startSpan();
        
        $scope = $span->activate();
        
        try {
            // 標準化選項參數
            $options = array_merge([
                'root_id' => null,
                'max_depth' => null,
                'include_inactive' => false,
            ], $options);
            
            // 記錄 span 屬性
            $span->setAttributes([
                'tree.root_id' => $options['root_id'],
                'tree.max_depth' => $options['max_depth'],
                'tree.include_inactive' => $options['include_inactive'],
            ]);
            
            // 使用簡化的參數調用快取服務
            $onlyActive = !$options['include_inactive'];
            
            // 嘗試從快取取得（使用 ProductCategoryCacheService 的現有方法）
            $cached = $this->cacheService->getTree($onlyActive);
            if ($cached !== null && $cached->isNotEmpty()) {
                $span->setAttributes([
                    'result' => 'hit',
                    'cache.hit' => true,
                    'tree.nodes_count' => $cached->count(),
                ]);
                
                return $cached;
            }
            
            $span->setAttributes([
                'result' => 'miss',
                'cache.hit' => false,
            ]);
            
            // 建立查詢 - 使用簡化的邏輯
            $query = ProductCategory::query();
            
            // 根節點過濾
            if ($options['root_id'] !== null) {
                $query->where('parent_id', $options['root_id']);
            } else {
                $query->whereNull('parent_id');
            }
            
            // 狀態過濾
            if ($onlyActive) {
                $query->where('status', true);
            }
            
            // 深度限制
            if ($options['max_depth'] !== null) {
                $query->where('depth', '<=', $options['max_depth']);
            }
            
            // 執行查詢並建立樹狀結構
            $tree = $query->with(['children' => function ($childQuery) use ($onlyActive, $options) {
                if ($onlyActive) {
                    $childQuery->where('status', true);
                }
                if ($options['max_depth'] !== null) {
                    $childQuery->where('depth', '<=', $options['max_depth']);
                }
                $childQuery->orderBy('position');
            }])
            ->orderBy('position')
            ->get();
            
            // 記錄結果統計
            $span->setAttributes([
                'tree.nodes_count' => $tree->count(),
                'db.query.executed' => true,
            ]);
            
            return $tree;
            
        } catch (\Throwable $e) {
            // 記錄錯誤資訊
            $span->recordException($e);
            $span->setStatus(
                \OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR,
                $e->getMessage()
            );
            
            throw $e;
            
        } finally {
            // 確保 span 正確結束
            $span->end();
            $scope->detach();
        }
    }

    /**
     * 取得快取的麵包屑
     *
     * @param int $categoryId 分類 ID
     *
     * @return mixed
     */
    public function getCachedBreadcrumbs(int $categoryId)
    {
        return $this->cacheService->getBreadcrumbs($categoryId);
    }

    /**
     * 取得快取的子分類
     *
     * @param int  $parentId   父分類 ID
     * @param bool $onlyActive 是否僅取啟用的分類
     *
     * @return mixed
     */
    public function getCachedChildren(int $parentId, bool $onlyActive = true)
    {
        return $this->cacheService->getChildren($parentId, $onlyActive);
    }
}
