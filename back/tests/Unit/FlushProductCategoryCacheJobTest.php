<?php

namespace Tests\Unit;

use App\Jobs\FlushProductCategoryCacheJob;
use App\Models\ProductCategory;
use App\Services\ProductCategoryCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;
use Tests\TestCase;

/**
 * FlushProductCategoryCacheJob 單元測試
 * 
 * 測試快取清除工作的各種情境，包含：
 * - Prometheus 監控指標
 * - 結構化日誌記錄  
 * - 不同清除策略
 * - 錯誤處理和重試機制
 */
class FlushProductCategoryCacheJobTest extends TestCase
{
    use RefreshDatabase;

    private $mockCacheService;
    private $mockPrometheus;
    private $mockCounter;
    private $mockHistogram;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立 Mock 物件
        $this->mockCacheService = Mockery::mock(ProductCategoryCacheService::class);
        $this->mockPrometheus = Mockery::mock(CollectorRegistry::class);
        $this->mockCounter = Mockery::mock(Counter::class);
        $this->mockHistogram = Mockery::mock(Histogram::class);
        
        // 綁定到容器
        $this->app->instance(ProductCategoryCacheService::class, $this->mockCacheService);
        $this->app->instance(CollectorRegistry::class, $this->mockPrometheus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試樹狀結構清除工作成功執行
     */
    public function test_tree_flush_job_executes_successfully(): void
    {
        // Arrange
        Log::shouldReceive('info')->twice();
        
        $this->mockPrometheus->shouldReceive('getOrRegisterCounter')
            ->twice()
            ->andReturn($this->mockCounter);
        
        $this->mockPrometheus->shouldReceive('getOrRegisterHistogram')
            ->once()
            ->andReturn($this->mockHistogram);
        
        $this->mockCounter->shouldReceive('inc')->twice();
        $this->mockHistogram->shouldReceive('observe')->once();
        
        $this->mockCacheService->shouldReceive('forgetTree')->once();

        $job = new FlushProductCategoryCacheJob([], 'tree');

        // Act
        $job->handle($this->mockCacheService, $this->mockPrometheus);

        // Assert - 由 Mock 驗證方法調用
        $this->assertTrue(true);
    }

    /**
     * 測試根分片清除工作
     */
    public function test_root_shards_flush_job_executes_successfully(): void
    {
        // Arrange
        $affectedRootIds = [1, 2, 3];
        
        Log::shouldReceive('info')->twice();
        
        $this->mockPrometheus->shouldReceive('getOrRegisterCounter')
            ->twice()
            ->andReturn($this->mockCounter);
        
        $this->mockPrometheus->shouldReceive('getOrRegisterHistogram')
            ->once()
            ->andReturn($this->mockHistogram);
        
        $this->mockCounter->shouldReceive('inc')->twice();
        $this->mockHistogram->shouldReceive('observe')->once();
        
        // 應該為每個根 ID 調用 forgetRootShard
        foreach ($affectedRootIds as $rootId) {
            $this->mockCacheService->shouldReceive('forgetRootShard')
                ->with($rootId)
                ->once();
        }

        $job = new FlushProductCategoryCacheJob($affectedRootIds, 'root_shards');

        // Act
        $job->handle($this->mockCacheService, $this->mockPrometheus);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * 測試部分清除工作
     */
    public function test_partial_flush_job_executes_successfully(): void
    {
        // Arrange
        $categoryIds = [10, 20, 30];
        $context = ['category_ids' => $categoryIds];
        
        Log::shouldReceive('info')->twice();
        
        $this->mockPrometheus->shouldReceive('getOrRegisterCounter')
            ->twice()
            ->andReturn($this->mockCounter);
        
        $this->mockPrometheus->shouldReceive('getOrRegisterHistogram')
            ->once()
            ->andReturn($this->mockHistogram);
        
        $this->mockCounter->shouldReceive('inc')->twice();
        $this->mockHistogram->shouldReceive('observe')->once();
        
        // 應該為每個分類 ID 調用 forgetCategory
        foreach ($categoryIds as $categoryId) {
            $this->mockCacheService->shouldReceive('forgetCategory')
                ->with($categoryId)
                ->once();
        }

        $job = new FlushProductCategoryCacheJob([], 'partial', $context);

        // Act
        $job->handle($this->mockCacheService, $this->mockPrometheus);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * 測試工作執行時 Prometheus 指標記錄
     */
    public function test_prometheus_metrics_are_recorded(): void
    {
        // Arrange
        Log::shouldReceive('info')->twice();
        
        // 驗證計數器指標
        $this->mockPrometheus->shouldReceive('getOrRegisterCounter')
            ->with(
                'product_category',
                'cache_flush_job_total',
                'Total number of product category cache flush jobs by status and type',
                ['status', 'flush_type', 'queue']
            )
            ->twice()
            ->andReturn($this->mockCounter);
        
        $this->mockCounter->shouldReceive('inc')
            ->with([
                'status' => 'started',
                'flush_type' => 'tree',
                'queue' => 'cache-operations',
            ])
            ->once();
        
        $this->mockCounter->shouldReceive('inc')
            ->with([
                'status' => 'completed',
                'flush_type' => 'tree',
                'queue' => 'cache-operations',
            ])
            ->once();
        
        // 驗證直方圖指標
        $this->mockPrometheus->shouldReceive('getOrRegisterHistogram')
            ->with(
                'product_category',
                'cache_flush_job_duration_seconds',
                'Product category cache flush job execution duration in seconds',
                ['flush_type', 'queue'],
                [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
            )
            ->once()
            ->andReturn($this->mockHistogram);
        
        $this->mockHistogram->shouldReceive('observe')
            ->withArgs(function ($duration, $labels) {
                return is_float($duration) 
                    && $duration >= 0 
                    && $labels['flush_type'] === 'tree'
                    && $labels['queue'] === 'cache-operations';
            })
            ->once();
        
        $this->mockCacheService->shouldReceive('forgetTree')->once();

        $job = new FlushProductCategoryCacheJob([], 'tree');

        // Act
        $job->handle($this->mockCacheService, $this->mockPrometheus);

        // Assert - 由 Mock 驗證
        $this->assertTrue(true);
    }

    /**
     * 測試工作失敗時的錯誤處理
     */
    public function test_job_handles_failure_correctly(): void
    {
        // Arrange
        $exception = new \RuntimeException('Cache service error');
        
        Log::shouldReceive('info')->once(); // 開始日誌
        Log::shouldReceive('error')->once(); // 失敗日誌
        
        $this->mockPrometheus->shouldReceive('getOrRegisterCounter')
            ->twice()
            ->andReturn($this->mockCounter);
        
        $this->mockCounter->shouldReceive('inc')
            ->with([
                'status' => 'started',
                'flush_type' => 'tree',
                'queue' => 'cache-operations',
            ])
            ->once();
        
        $this->mockCounter->shouldReceive('inc')
            ->with([
                'status' => 'failed',
                'flush_type' => 'tree',
                'queue' => 'cache-operations',
            ])
            ->once();
        
        // Cache service 拋出異常
        $this->mockCacheService->shouldReceive('forgetTree')
            ->once()
            ->andThrow($exception);

        $job = new FlushProductCategoryCacheJob([], 'tree');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cache service error');
        
        $job->handle($this->mockCacheService, $this->mockPrometheus);
    }

    /**
     * 測試 Prometheus 錯誤不影響主要邏輯
     */
    public function test_prometheus_errors_do_not_affect_main_logic(): void
    {
        // Arrange
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('warning')->twice(); // Prometheus 錯誤警告
        
        // Prometheus 拋出異常
        $this->mockPrometheus->shouldReceive('getOrRegisterCounter')
            ->twice()
            ->andThrow(new \Exception('Prometheus error'));
        
        $this->mockPrometheus->shouldReceive('getOrRegisterHistogram')
            ->once()
            ->andThrow(new \Exception('Prometheus error'));
        
        $this->mockCacheService->shouldReceive('forgetTree')->once();

        $job = new FlushProductCategoryCacheJob([], 'tree');

        // Act - 應該正常執行，不拋出異常
        $job->handle($this->mockCacheService, $this->mockPrometheus);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * 測試工作的唯一標識生成
     */
    public function test_unique_id_generation(): void
    {
        // Arrange & Act
        $job1 = new FlushProductCategoryCacheJob([1, 2, 3], 'root_shards');
        $job2 = new FlushProductCategoryCacheJob([1, 2, 3], 'root_shards');
        $job3 = new FlushProductCategoryCacheJob([1, 2, 4], 'root_shards');
        $job4 = new FlushProductCategoryCacheJob([1, 2, 3], 'tree');

        // Assert
        $this->assertEquals($job1->uniqueId(), $job2->uniqueId());
        $this->assertNotEquals($job1->uniqueId(), $job3->uniqueId());
        $this->assertNotEquals($job1->uniqueId(), $job4->uniqueId());
    }

    /**
     * 測試工作標籤
     */
    public function test_job_tags(): void
    {
        // Arrange & Act
        $job = new FlushProductCategoryCacheJob([1, 2, 3], 'root_shards');
        $tags = $job->tags();

        // Assert
        $this->assertContains('type:cache_flush', $tags);
        $this->assertContains('flush_type:root_shards', $tags);
        $this->assertContains('affected_roots:3', $tags);
    }

    /**
     * 測試 failed 方法記錄最終失敗
     */
    public function test_failed_method_logs_permanent_failure(): void
    {
        // Arrange
        $exception = new \RuntimeException('Permanent failure');
        
        Log::shouldReceive('critical')
            ->with(
                '[ProductCategory] Cache flush job permanently failed',
                Mockery::type('array')
            )
            ->once();

        $job = new FlushProductCategoryCacheJob([1, 2], 'root_shards');

        // Act
        $job->failed($exception);

        // Assert - 由 Mock 驗證
        $this->assertTrue(true);
    }
}
