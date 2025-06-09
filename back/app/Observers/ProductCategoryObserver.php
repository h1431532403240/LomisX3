<?php

namespace App\Observers;

use App\Models\ProductCategory;
use App\Services\ProductCategoryCacheService;
use App\Services\ProductCategoryService;

/**
 * 商品分類模型觀察者
 * 處理分類模型的生命週期事件，負責自動設定相關屬性和快取管理
 */
class ProductCategoryObserver
{
    /**
     * 建構函式
     *
     * @param ProductCategoryCacheService $cache   快取服務
     * @param ProductCategoryService      $service 分類服務
     */
    public function __construct(
        private ProductCategoryCacheService $cache,
        private ProductCategoryService $service
    ) {}

    /**
     * 建立前處理
     * 自動設定 slug、depth、position、path
     */
    public function creating(ProductCategory $category): void
    {
        // 自動生成 slug（如果未提供）
        if (empty($category->slug)) {
            $category->slug = $this->service->generateUniqueSlug($category->name);
        }

        // 自動計算深度
        $category->depth = $this->service->calculateDepth($category->parent_id);

        // 自動設定排序位置（如果未提供）
        if (empty($category->position)) {
            $category->position = $this->service->getNextPosition($category->parent_id);
        }

        // 設定預設狀態
        if ($category->status === null) {
            $category->status = true;
        }
    }

    /**
     * 建立後處理
     * 生成 path 並清除相關快取
     */
    public function created(ProductCategory $category): void
    {
        // 建立後才有ID，此時生成並儲存 path
        $path = $category->generatePath();
        
        // 直接使用 update 更新 path，避免觸發額外事件
        ProductCategory::withoutEvents(function () use ($category, $path) {
            $category->update(['path' => $path]);
        });

        $this->cache->forgetTree();

        // 如果有父分類，清除父分類的子分類快取
        if ($category->parent_id) {
            $this->cache->forgetCategory($category->parent_id);
        }
    }

    /**
     * 更新前處理
     * 檢查是否需要重新生成 slug 和 path
     */
    public function updating(ProductCategory $category): void
    {
        // 如果名稱有變更且 slug 為空，自動生成新的 slug
        if ($category->isDirty('name') && empty($category->slug)) {
            $category->slug = $this->service->generateUniqueSlug(
                $category->name,
                $category->id
            );
        }

        // 如果父分類有變更，重新計算深度和路徑
        if ($category->isDirty('parent_id')) {
            $category->depth = $this->service->calculateDepth($category->parent_id);
            // 新的路徑將在 updated 事件中處理，因為需要觸發遞迴更新
        }
    }

    /**
     * 更新後處理
     * 處理子孫分類的深度更新、路徑更新和快取清除
     */
    public function updated(ProductCategory $category): void
    {
        // 如果父分類有變更，更新路徑和所有子孫分類的深度、路徑
        if ($category->wasChanged('parent_id')) {
            // 更新路徑（會自動遞迴更新所有子孫）
            $category->updatePathsRecursively();
            
            // 更新所有子孫分類的深度
            $this->service->updateDescendantsDepth($category);

            // 清除舊父分類和新父分類的快取
            $oldParentId = $category->getOriginal('parent_id');
            if ($oldParentId) {
                $this->cache->forgetCategory($oldParentId);
            }
            if ($category->parent_id) {
                $this->cache->forgetCategory($category->parent_id);
            }
        }

        // ── 修改: 精準計算原始根分類ID
        $originalRootId = null;
        if ($category->wasChanged('parent_id')) {
            $originalParentId = $category->getOriginal('parent_id');
            if ($originalParentId) {
                // 嘗試從原始父分類獲取根分類ID
                $originalParent = ProductCategory::find($originalParentId);
                $originalRootId = $originalParent?->getRootAncestorId();
            } else {
                // 如果原本是根分類，則使用該分類自身的ID作為原始根ID
                $originalRootId = $category->id;
            }
        }
        
        $this->cache->forgetAffectedTreeParts($category, $originalRootId);
    }

    /**
     * 刪除前處理
     * 可在此處添加刪除前的檢查邏輯
     */
    public function deleting(ProductCategory $category): void
    {
        // 可在此處添加刪除前的業務邏輯檢查
        // 例如：檢查是否有子分類、是否有商品等
    }

    /**
     * 刪除後處理
     * 清除相關快取
     */
    public function deleted(ProductCategory $category): void
    {
        // ── 修改: 精準計算原始根分類ID
        $originalRootId = null;
        if ($category->parent_id) {
            // 從父分類獲取根分類ID
            $parent = ProductCategory::find($category->parent_id);
            $originalRootId = $parent?->getRootAncestorId();
        } else {
            // 如果被刪除的是根分類，使用該分類自身的ID
            $originalRootId = $category->id;
        }
        
        $this->cache->forgetAffectedTreeParts($category, $originalRootId);
    }

    /**
     * 恢復後處理
     * 清除相關快取
     */
    public function restored(ProductCategory $category): void
    {
        $this->cache->forgetTree();

        // 如果有父分類，清除父分類的子分類快取
        if ($category->parent_id) {
            $this->cache->forgetCategory($category->parent_id);
        }
    }

    /**
     * 強制刪除後處理
     * 清除相關快取
     */
    public function forceDeleted(ProductCategory $category): void
    {
        $this->cache->forgetTree();
        $this->cache->forgetCategory($category->id);

        // 如果有父分類，清除父分類的子分類快取
        if ($category->parent_id) {
            $this->cache->forgetCategory($category->parent_id);
        }
    }
}
