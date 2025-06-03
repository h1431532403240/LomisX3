<?php

namespace App\Jobs;

use App\Models\ProductCategory;
use App\Services\ProductCategoryCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;

/**
 * 防抖快取清除工作
 * 
 * 實現快取防抖功能，避免短時間內頻繁的快取清除操作
 * 包含 Prometheus 監控和結構化日誌記錄
 */
class FlushProductCategoryCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 建構函式
     * 
     * @param array $affectedRootIds 受影響的根分類ID陣列（用於精準清除）
     * @param string $flushType 清除類型：'tree'|'root_shards'|'partial'
     * @param array $context 額外的上下文資訊
     */
    public function __construct(
        private array $affectedRootIds = [],
        private string $flushType = 'tree',
        private array $context = []
    ) {
        // 設置隊列連線和名稱
        $this->onQueue('cache-operations');
        
        // 設置任務優先級（快取操作為高優先級）
        $this->priority = 10;
        
        // 設置最大重試次數
        $this->tries = 3;
        
        // 設置重試延遲（指數退避）
        $this->backoff = [5, 15, 30];
    }

    /**
     * 執行快取清除工作
     * 包含 OpenTelemetry 手動追蹤
     * 
     * @param ProductCategoryCacheService $cacheService
     * @param CollectorRegistry $prometheus
     * @return void
     */
    public function handle(
        ProductCategoryCacheService $cacheService,
        CollectorRegistry $prometheus
    ): void {
        // 建立 OpenTelemetry 手動 span
        $tracer = \OpenTelemetry\API\Globals::tracerProvider()
            ->getTracer('product-category-jobs');
        
        $span = $tracer->spanBuilder('ProductCategory.FlushCacheJob')
            ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_INTERNAL)
            ->setAttribute('service.name', 'product-category')
            ->setAttribute('operation.name', 'flushCache')
            ->setAttribute('job.name', 'FlushProductCategoryCacheJob')
            ->setAttribute('job.id', $this->job?->getJobId() ?? 'unknown')
            ->setAttribute('job.queue', $this->queue ?? 'default')
            ->setAttribute('job.attempts', $this->attempts())
            ->setAttribute('job.max_tries', $this->tries)
            ->startSpan();
        
        $scope = $span->activate();
        $startTime = microtime(true);
        
        try {
            // 記錄 job 特定屬性
            $span->setAttributes([
                'cache.flush_type' => $this->flushType,
                'cache.affected_root_ids' => $this->affectedRootIds,
                'cache.context' => json_encode($this->context),
                'memory.start_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ]);
            
            // 記錄任務開始
            $this->logJobStart();
            
            // 增加 Prometheus 計數器
            $this->incrementPrometheusCounter($prometheus, 'started');
            
            // 根據清除類型執行不同的快取清除策略
            $this->executeFlushStrategy($cacheService, $span);
            
            $duration = microtime(true) - $startTime;
            
            // 記錄成功完成
            $this->logJobSuccess($duration);
            
            // 增加成功計數器
            $this->incrementPrometheusCounter($prometheus, 'completed');
            
            // 記錄處理時間指標
            $this->recordDurationMetric($prometheus, $duration);
            
            // 記錄成功 span 屬性
            $span->setAttributes([
                'job.status' => 'completed',
                'job.duration_seconds' => round($duration, 4),
                'memory.end_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'prometheus.metrics_recorded' => true,
            ]);
            
        } catch (\Throwable $exception) {
            $duration = microtime(true) - $startTime;
            
            // 記錄失敗日誌（結構化）
            $this->logJobFailure($exception, $duration);
            
            // 增加失敗計數器
            $this->incrementPrometheusCounter($prometheus, 'failed');
            
            // 記錄錯誤到 span
            $span->recordException($exception);
            $span->setStatus(
                \OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR,
                $exception->getMessage()
            );
            
            $span->setAttributes([
                'job.status' => 'failed',
                'job.duration_seconds' => round($duration, 4),
                'error.type' => get_class($exception),
                'error.message' => $exception->getMessage(),
                'error.code' => $exception->getCode(),
                'retry.will_retry' => $this->attempts() < $this->tries,
            ]);
            
            // 重新拋出異常以觸發重試機制
            throw $exception;
            
        } finally {
            // 確保 span 正確結束
            $span->end();
            $scope->detach();
        }
    }

    /**
     * 執行快取清除策略
     * 包含 OpenTelemetry 子 span 追蹤
     * 
     * @param ProductCategoryCacheService $cacheService
     * @param \OpenTelemetry\API\Trace\SpanInterface $parentSpan
     * @return void
     */
    private function executeFlushStrategy(
        ProductCategoryCacheService $cacheService,
        \OpenTelemetry\API\Trace\SpanInterface $parentSpan
    ): void {
        $tracer = \OpenTelemetry\API\Globals::tracerProvider()
            ->getTracer('product-category-jobs');
            
        $strategySpan = $tracer->spanBuilder("cache.flush.{$this->flushType}")
            ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_INTERNAL)
            ->setAttribute('cache.strategy', $this->flushType)
            ->startSpan();
            
        $scope = $strategySpan->activate();
        
        try {
            switch ($this->flushType) {
                case 'root_shards':
                    // 精準根分片清除
                    $strategySpan->setAttribute('cache.operation', 'root_shards_flush');
                    if (empty($this->affectedRootIds)) {
                        $cacheService->forgetTree();
                        $strategySpan->setAttribute('cache.fallback_to_tree', true);
                    } else {
                        $strategySpan->setAttribute('cache.shard_count', count($this->affectedRootIds));
                        foreach ($this->affectedRootIds as $rootId) {
                            $cacheService->forgetRootShard($rootId);
                        }
                    }
                    break;
                    
                case 'partial':
                    // 部分清除（特定分類）
                    $strategySpan->setAttribute('cache.operation', 'partial_flush');
                    if (!empty($this->context['category_ids'])) {
                        $categoryIds = $this->context['category_ids'];
                        $strategySpan->setAttribute('cache.category_count', count($categoryIds));
                        foreach ($categoryIds as $categoryId) {
                            $cacheService->forgetCategory($categoryId);
                        }
                    } else {
                        $strategySpan->setAttribute('cache.no_categories', true);
                    }
                    break;
                    
                case 'tree':
                default:
                    // 完整樹狀結構清除
                    $strategySpan->setAttribute('cache.operation', 'tree_flush');
                    $cacheService->forgetTree();
                    break;
            }
            
            $strategySpan->setAttribute('cache.flush_success', true);
            
        } catch (\Throwable $e) {
            $strategySpan->recordException($e);
            $strategySpan->setStatus(
                \OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR,
                "Cache flush strategy failed: {$e->getMessage()}"
            );
            throw $e;
            
        } finally {
            $strategySpan->end();
            $scope->detach();
        }
    }

    /**
     * 記錄任務開始日誌
     */
    private function logJobStart(): void
    {
        Log::info('[ProductCategory] Cache flush job started', [
            'job_id' => $this->job?->getJobId(),
            'flush_type' => $this->flushType,
            'affected_root_ids' => $this->affectedRootIds,
            'context' => $this->context,
            'queue' => $this->queue,
            'attempts' => $this->attempts(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄任務成功日誌
     * 
     * @param float $duration 執行時間（秒）
     */
    private function logJobSuccess(float $duration): void
    {
        Log::info('[ProductCategory] Cache flush job completed successfully', [
            'job_id' => $this->job?->getJobId(),
            'flush_type' => $this->flushType,
            'affected_root_ids' => $this->affectedRootIds,
            'duration_seconds' => round($duration, 4),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'attempts' => $this->attempts(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 記錄任務失敗日誌（結構化）
     * 
     * @param \Throwable $exception
     * @param float $duration
     */
    private function logJobFailure(\Throwable $exception, float $duration): void
    {
        Log::error('[ProductCategory] Cache flush job failed', [
            'job_id' => $this->job?->getJobId(),
            'flush_type' => $this->flushType,
            'affected_root_ids' => $this->affectedRootIds,
            'context' => $this->context,
            'duration_seconds' => round($duration, 4),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'attempts' => $this->attempts(),
            'max_tries' => $this->tries,
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
            'timestamp' => now()->toISOString(),
            'alert_level' => 'critical', // Grafana 告警標識
        ]);
    }

    /**
     * 增加 Prometheus 計數器
     * 
     * @param CollectorRegistry $prometheus
     * @param string $status 狀態：started|completed|failed
     */
    private function incrementPrometheusCounter(CollectorRegistry $prometheus, string $status): void
    {
        try {
            /** @var Counter $counter */
            $counter = $prometheus->getOrRegisterCounter(
                'product_category',
                'cache_flush_job_total',
                'Total number of product category cache flush jobs by status and type',
                ['status', 'flush_type', 'queue']
            );
            
            $counter->inc([
                'status' => $status,
                'flush_type' => $this->flushType,
                'queue' => $this->queue ?? 'default',
            ]);
            
        } catch (\Throwable $e) {
            // Prometheus 錯誤不應影響主要邏輯
            Log::warning('[ProductCategory] Failed to update Prometheus counter', [
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 記錄執行時間指標
     * 
     * @param CollectorRegistry $prometheus
     * @param float $duration
     */
    private function recordDurationMetric(CollectorRegistry $prometheus, float $duration): void
    {
        try {
            $histogram = $prometheus->getOrRegisterHistogram(
                'product_category',
                'cache_flush_job_duration_seconds',
                'Product category cache flush job execution duration in seconds',
                ['flush_type', 'queue'],
                [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
            );
            
            $histogram->observe($duration, [
                'flush_type' => $this->flushType,
                'queue' => $this->queue ?? 'default',
            ]);
            
        } catch (\Throwable $e) {
            Log::warning('[ProductCategory] Failed to record duration metric', [
                'duration' => $duration,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 任務失敗時的處理
     * 
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        // 記錄最終失敗日誌
        Log::critical('[ProductCategory] Cache flush job permanently failed', [
            'job_id' => $this->job?->getJobId(),
            'flush_type' => $this->flushType,
            'affected_root_ids' => $this->affectedRootIds,
            'context' => $this->context,
            'total_attempts' => $this->attempts(),
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
            'timestamp' => now()->toISOString(),
            'alert_level' => 'critical',
            'requires_manual_intervention' => true,
        ]);
        
        // 可以在此處添加額外的錯誤處理邏輯
        // 例如：發送緊急通知、回滾操作等
    }

    /**
     * 取得任務的唯一標識
     */
    public function uniqueId(): string
    {
        // 防止相同類型的快取清除任務重複排隊
        return 'cache_flush_' . $this->flushType . '_' . md5(serialize($this->affectedRootIds));
    }

    /**
     * 取得任務的標籤（用於監控）
     */
    public function tags(): array
    {
        return [
            'type:cache_flush',
            'flush_type:' . $this->flushType,
            'affected_roots:' . count($this->affectedRootIds),
        ];
    }
}
