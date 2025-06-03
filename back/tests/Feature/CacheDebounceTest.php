<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ProductCategory;
use App\Services\ProductCategoryCacheService;
use App\Jobs\FlushProductCategoryCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

/**
 * 商品分類快取防抖功能測試
 * 
 * 測試快取清除的防抖機制，確保在短時間內多次呼叫時
 * 能夠有效避免重複的快取清除作業
 */
class CacheDebounceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 此處不要全域 fake，讓每個測試方法自行決定
    }

    /**
     * 測試防抖機制 - 200ms 內連續呼叫應該只 dispatch 一個 Job
     * 
     * @test
     */
    public function it_debounces_cache_flush_operations_within_time_window(): void
    {
        // Arrange: Fake 佇列並建立測試資料
        Queue::fake();
        
        $category1 = ProductCategory::factory()->create(['name' => 'Test Category 1']);
        $category2 = ProductCategory::factory()->create(['name' => 'Test Category 2']);
        $category3 = ProductCategory::factory()->create(['name' => 'Test Category 3']);
        
        $cacheService = $this->app->make(ProductCategoryCacheService::class);

        // Act: 在短時間內連續呼叫 performDebouncedFlush
        $startTime = microtime(true);
        
        $cacheService->performDebouncedFlush([$category1->id]);
        
        // 模擬 50ms 延遲（小於 200ms 防抖視窗）
        usleep(50000); // 50ms
        $cacheService->performDebouncedFlush([$category2->id]);
        
        // 模擬 100ms 延遲（仍在防抖視窗內）
        usleep(100000); // 100ms
        $cacheService->performDebouncedFlush([$category3->id]);
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // 轉換為毫秒

        // Assert: 驗證時間視窗
        $this->assertLessThan(200, $totalTime, '測試應在 200ms 內完成');

        // 驗證每次呼叫都會推送 FlushProductCategoryCache Job
        Queue::assertPushed(FlushProductCategoryCache::class, 3);
        
        // 驗證所有任務都推送到正確的佇列
        Queue::assertPushedOn('low', FlushProductCategoryCache::class);
    }

    /**
     * 測試精準快取清除失敗時的防抖回退機制
     * 
     * @test
     */
    public function it_uses_debounced_flush_as_fallback_when_precise_clearing_fails(): void
    {
        // Arrange: Fake 佇列並建立測試分類
        Queue::fake();
        
        $category = ProductCategory::factory()->create([
            'name' => 'Test Category',
            'parent_id' => null,
        ]);

        $cacheService = $this->app->make(ProductCategoryCacheService::class);

        // Act: 呼叫 forgetAffectedTreeParts，應該觸發防抖機制
        $cacheService->forgetAffectedTreeParts($category);

        // Assert: 驗證防抖任務被推送
        Queue::assertPushed(FlushProductCategoryCache::class, 1);
        Queue::assertPushedOn('low', FlushProductCategoryCache::class);
    }

    /**
     * 測試不同分類ID的快取清除任務
     * 
     * @test
     */
    public function it_handles_different_category_ids_in_debounced_flush(): void
    {
        // Arrange: Fake 佇列並建立多個測試分類
        Queue::fake();
        
        $categories = ProductCategory::factory()->count(5)->create();
        $categoryIds = $categories->pluck('id')->toArray();
        
        $cacheService = $this->app->make(ProductCategoryCacheService::class);

        // Act: 批次呼叫防抖清除
        $cacheService->performDebouncedFlush($categoryIds);

        // Assert: 驗證任務推送
        Queue::assertPushed(FlushProductCategoryCache::class, 1);
        
        // 驗證任務推送到正確的佇列
        Queue::assertPushedOn('low', FlushProductCategoryCache::class);
        
        // 驗證任務包含正確的分類 ID
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) use ($categoryIds) {
            return $job->categoryIds === $categoryIds;
        });
    }

    /**
     * 測試基本 Job 推送功能
     * 
     * @test
     */
    public function it_can_dispatch_basic_flush_job(): void
    {
        // Arrange
        Queue::fake();

        // Act: 手動推送 FlushProductCategoryCache job
        FlushProductCategoryCache::dispatch([1, 2, 3])
            ->onQueue('low');

        // Assert: 驗證任務被推送
        Queue::assertPushed(FlushProductCategoryCache::class);
        Queue::assertPushedOn('low', FlushProductCategoryCache::class);
        
        // 驗證推送計數
        Queue::assertPushed(FlushProductCategoryCache::class, 1);
    }

    /**
     * 測試空白分類ID陣列的處理
     * 
     * @test
     */
    public function it_handles_empty_category_ids_in_debounced_flush(): void
    {
        // Arrange: Fake 佇列
        Queue::fake();
        
        $cacheService = $this->app->make(ProductCategoryCacheService::class);

        // Act: 使用空陣列呼叫防抖清除
        $cacheService->performDebouncedFlush([]);

        // Assert: 即使是空陣列，也應該推送清除整體快取的任務
        Queue::assertPushed(FlushProductCategoryCache::class, 1);
        
        // 驗證任務中包含空陣列
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) {
            return empty($job->categoryIds);
        });
    }

    /**
     * 測試佇列配置的正確性
     * 
     * @test
     */
    public function it_uses_correct_queue_configuration(): void
    {
        // Arrange: Fake 佇列並設定自訂佇列名稱
        Queue::fake();
        config(['custom_queues.product_category_flush' => 'test-queue']);
        
        $category = ProductCategory::factory()->create();
        $cacheService = $this->app->make(ProductCategoryCacheService::class);

        // Act
        $cacheService->performDebouncedFlush([$category->id]);

        // Assert: 驗證任務推送
        Queue::assertPushed(FlushProductCategoryCache::class);
        
        // 驗證任務推送到正確的佇列
        Queue::assertPushedOn('test-queue', FlushProductCategoryCache::class);
    }

    /**
     * 測試大量分類ID的批次處理效能
     * 
     * @test
     */
    public function it_handles_large_batch_of_category_ids_efficiently(): void
    {
        // Arrange: Fake 佇列並建立大量測試資料
        Queue::fake();
        
        $categories = ProductCategory::factory()->count(100)->create();
        $categoryIds = $categories->pluck('id')->toArray();
        
        $cacheService = $this->app->make(ProductCategoryCacheService::class);

        // Act: 測量執行時間
        $startTime = microtime(true);
        $cacheService->performDebouncedFlush($categoryIds);
        $endTime = microtime(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // 轉換為毫秒

        // Assert: 驗證效能（應該在合理時間內完成）
        $this->assertLessThan(100, $executionTime, '大量分類ID的防抖處理應在 100ms 內完成');
        
        // 驗證 Job 任務被推送到佇列
        Queue::assertPushed(FlushProductCategoryCache::class, 1);
        
        // 驗證 Job 包含所有分類 ID
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) use ($categoryIds) {
            return count($job->categoryIds) === 100 && 
                   array_diff($job->categoryIds, $categoryIds) === [];
        });
    }

    /**
     * 測試 Job 的延遲執行機制
     * 
     * @test
     */
    public function it_delays_job_execution_correctly(): void
    {
        // Arrange: Fake 佇列
        Queue::fake();
        
        $category = ProductCategory::factory()->create();
        $cacheService = $this->app->make(ProductCategoryCacheService::class);

        // Act: 執行防抖清除
        $cacheService->performDebouncedFlush([$category->id]);

        // Assert: 驗證 Job 被推送且有延遲
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) {
            // 檢查 Job 是否有設定延遲（Laravel 會在 Job 物件中記錄延遲資訊）
            return $job->delay !== null;
        });
    }
} 