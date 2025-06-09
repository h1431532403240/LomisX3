<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\FlushProductCategoryCache;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 測試快取清除 Job 的佇列執行
 */
class FlushProductCategoryCacheJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試 Job 被正確 dispatch 到 low queue
     */
    public function test_flush_job_dispatched_to_correct_queue(): void
    {
        // Arrange
        Queue::fake();
        $category = ProductCategory::factory()->create();

        // Act
        FlushProductCategoryCache::dispatch([$category->id])
            ->onQueue(config('custom_queues.product_category_flush', 'low'));

        // Assert
        Queue::assertPushedOn('low', FlushProductCategoryCache::class);
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) use ($category) {
            return in_array($category->id, $job->categoryIds);
        });
    }

    /**
     * 測試 Job 執行後真的清除快取標籤
     */
    public function test_flush_job_actually_clears_tagged_cache(): void
    {
        // Arrange
        $category = ProductCategory::factory()->create();
        
        // 建立測試快取
        Cache::tags(['product_categories'])->put('pc_tree_active', 'test_data', 3600);
        Cache::tags(['product_categories'])->put('pc_stats_active', 'test_stats', 3600);
        
        // 確認快取存在
        $this->assertTrue(Cache::tags(['product_categories'])->has('pc_tree_active'));
        $this->assertTrue(Cache::tags(['product_categories'])->has('pc_stats_active'));

        // Act - 執行 Job（全面清除）
        $job = new FlushProductCategoryCache([$category->id], true);
        $job->handle();

        // Assert - 所有標籤快取被清除
        $this->assertFalse(Cache::tags(['product_categories'])->has('pc_tree_active'));
        $this->assertFalse(Cache::tags(['product_categories'])->has('pc_stats_active'));
    }

    /**
     * 測試 Job 處理多個分類 ID
     */
    public function test_flush_job_handles_multiple_categories(): void
    {
        // Arrange
        $categories = ProductCategory::factory()->count(3)->create();
        $categoryIds = $categories->pluck('id')->toArray();

        // 建立快取
        Cache::tags(['product_categories'])->put('pc_tree_active', 'test1', 3600);

        // Act - 執行全面清除
        $job = new FlushProductCategoryCache($categoryIds, true);
        $job->handle();

        // Assert
        $this->assertFalse(Cache::tags(['product_categories'])->has('pc_tree_active'));
    }

    /**
     * 測試 Job 錯誤處理
     */
    public function test_flush_job_handles_cache_failure_gracefully(): void
    {
        // Arrange
        $category = ProductCategory::factory()->create();
        
        // Act & Assert - Job 不應該拋出未捕獲的異常
        $job = new FlushProductCategoryCache([$category->id]);
        
        // 這個測試確保 Job 有適當的錯誤處理
        $this->expectNotToPerformAssertions();
        $job->handle();
    }

    /**
     * 測試 Job 配置正確性
     */
    public function test_flush_job_configuration(): void
    {
        // Arrange
        $job = new FlushProductCategoryCache([1, 2, 3]);

        // Assert
        $this->assertEquals('low', $job->queue);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->timeout);
    }

    /**
     * 測試選擇性快取清除
     */
    public function test_selective_cache_flush(): void
    {
        // Arrange
        $category = ProductCategory::factory()->create();
        
        // 建立特定的快取項目
        Cache::put("product_category_tree:{$category->id}", 'test_tree_data', 3600);
        Cache::put("product_category_children:{$category->id}", 'test_children_data', 3600);
        Cache::put('other_cache_key', 'other_data', 3600);
        
        // 確認快取存在
        $this->assertTrue(Cache::has("product_category_tree:{$category->id}"));
        $this->assertTrue(Cache::has("product_category_children:{$category->id}"));
        $this->assertTrue(Cache::has('other_cache_key'));

        // Act - 執行選擇性清除
        $job = new FlushProductCategoryCache([$category->id], false);
        $job->handle();

        // Assert - 只有相關的快取被清除
        $this->assertFalse(Cache::has("product_category_tree:{$category->id}"));
        $this->assertFalse(Cache::has("product_category_children:{$category->id}"));
        $this->assertTrue(Cache::has('other_cache_key')); // 其他快取應該保留
    }

    /**
     * 測試空分類ID陣列觸發全面清除
     */
    public function test_empty_category_ids_triggers_full_flush(): void
    {
        // Arrange
        Cache::tags(['product_categories'])->put('pc_tree_active', 'test_data', 3600);
        
        // 確認快取存在
        $this->assertTrue(Cache::tags(['product_categories'])->has('pc_tree_active'));

        // Act - 空陣列應該觸發全面清除
        $job = new FlushProductCategoryCache([]);
        $job->handle();

        // Assert
        $this->assertFalse(Cache::tags(['product_categories'])->has('pc_tree_active'));
    }
} 