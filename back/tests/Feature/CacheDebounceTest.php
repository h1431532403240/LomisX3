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
use Mockery;

/**
 * 商品分類快取防抖功能測試
 * 
 * 測試快取清除的防抖機制，確保在短時間內多次呼叫時
 * 能夠有效避免重複的快取清除作業
 */
class CacheDebounceTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategoryCacheService $cacheService;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(ProductCategoryCacheService::class);
    }

    /**
     * 測試防抖動機制 - 3個並發flush只觸發1次dispatch
     */
    public function test_debounce_flush_prevents_multiple_dispatches(): void
    {
        // 模擬佇列
        Queue::fake();
        
        // 模擬 Cache::add 行為（Redis 鎖機制）
        $lockCallCount = 0;
        Cache::shouldReceive('add')
            ->with(Mockery::pattern('/debounce_lock/'), true, Mockery::any())
            ->andReturnUsing(function () use (&$lockCallCount) {
                $lockCallCount++;
                // 第一次呼叫返回 true（獲得鎖），後續返回 false（鎖已存在）
                return $lockCallCount === 1;
            });

        // 模擬3個並發的快取清除請求
        $categoryIds = [1, 2, 3];
        
        // 並發執行3次防抖清除
        for ($i = 0; $i < 3; $i++) {
            $this->cacheService->performDebouncedFlush($categoryIds);
        }

        // 驗證只有1個 Job 被分派到佇列
        Queue::assertPushed(FlushProductCategoryCache::class, 1);
        
        // 驗證 Job 包含正確的分類 ID
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) use ($categoryIds) {
            return $job->categoryIds === $categoryIds;
        });
    }

    /**
     * 測試防抖動鎖過期後可以重新觸發
     */
    public function test_debounce_lock_expiry_allows_new_dispatch(): void
    {
        Queue::fake();
        
        // 第一次呼叫：鎖成功
        Cache::shouldReceive('add')
            ->once()
            ->with(Mockery::pattern('/debounce_lock/'), true, Mockery::any())
            ->andReturn(true);
            
        // 第一次防抖清除
        $this->cacheService->performDebouncedFlush([1]);
        
        // 模擬鎖過期，第二次呼叫也能成功
        Cache::shouldReceive('add')
            ->once()
            ->with(Mockery::pattern('/debounce_lock/'), true, Mockery::any())
            ->andReturn(true);
            
        // 第二次防抖清除（模擬鎖過期後）
        $this->cacheService->performDebouncedFlush([2]);

        // 驗證兩次都成功分派了 Job
        Queue::assertPushed(FlushProductCategoryCache::class, 2);
    }

    /**
     * 測試不同分類ID的防抖動處理
     */
    public function test_debounce_with_different_category_ids(): void
    {
        Queue::fake();
        
        // 模擬鎖機制：每次都能獲得鎖（不同的分類組合）
        Cache::shouldReceive('add')
            ->andReturn(true);

        // 測試不同的分類ID組合
        $this->cacheService->performDebouncedFlush([1, 2]);
        $this->cacheService->performDebouncedFlush([3, 4]);
        $this->cacheService->performDebouncedFlush([5]);

        // 驗證每個不同的組合都觸發了 Job
        Queue::assertPushed(FlushProductCategoryCache::class, 3);
    }

    /**
     * 測試空陣列觸發全面清除
     */
    public function test_empty_array_triggers_full_flush(): void
    {
        Queue::fake();
        
        Cache::shouldReceive('add')
            ->andReturn(true);

        // 空陣列應該觸發全面清除
        $this->cacheService->performDebouncedFlush([]);

        // 驗證 Job 被分派且包含空陣列
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) {
            return empty($job->categoryIds);
        });
    }

    /**
     * 測試佇列配置正確性
     */
    public function test_queue_configuration(): void
    {
        Queue::fake();
        
        Cache::shouldReceive('add')
            ->andReturn(true);

        // 設定測試用的佇列名稱
        config(['custom_queues.product_category_flush' => 'test_queue']);

        $this->cacheService->performDebouncedFlush([1]);

        // 驗證 Job 被分派到正確的佇列
        Queue::assertPushedOn('test_queue', FlushProductCategoryCache::class);
    }

    /**
     * 測試延遲執行配置
     */
    public function test_delayed_execution(): void
    {
        Queue::fake();
        
        Cache::shouldReceive('add')
            ->andReturn(true);

        $this->cacheService->performDebouncedFlush([1]);

        // 驗證 Job 有延遲執行（5秒）
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) {
            // 檢查 Job 是否有設定延遲
            return $job->delay !== null;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 