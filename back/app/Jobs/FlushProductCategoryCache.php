<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 商品分類快取清除任務
 * 
 * 此 Job 負責執行商品分類相關快取的清除作業，
 * 支援指定分類 ID 的精準清除或全面清除
 */
class FlushProductCategoryCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任務執行的最大嘗試次數
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 任務執行超時時間（秒）
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * 重試前的等待時間（秒）
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * 要清除快取的分類 ID 列表
     *
     * @var array<int>
     */
    public array $categoryIds;

    /**
     * 是否強制全面清除
     *
     * @var bool
     */
    public bool $forceFullFlush;

    /**
     * 建立新的任務實例
     *
     * @param array<int> $categoryIds 要清除快取的分類 ID，空陣列表示全面清除
     * @param bool $forceFullFlush 是否強制執行全面快取清除
     */
    public function __construct(array $categoryIds = [], bool $forceFullFlush = false)
    {
        $this->categoryIds = $categoryIds;
        $this->forceFullFlush = $forceFullFlush;
        
        // 設定預設佇列為 low 優先級
        $this->onQueue(config('custom_queues.product_category_flush', 'low'));
    }

    /**
     * 執行任務
     * 
     * 根據建構時傳入的參數決定執行精準清除或全面清除
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            if ($this->forceFullFlush || empty($this->categoryIds)) {
                $this->performFullFlush();
            } else {
                $this->performSelectiveFlush();
            }
            
            $duration = microtime(true) - $startTime;
            $this->logSuccess($duration);
            
        } catch (\Throwable $exception) {
            $duration = microtime(true) - $startTime;
            $this->logFailure($exception, $duration);
            throw $exception;
        }
    }

    /**
     * 執行全面快取清除
     * 
     * 清除所有商品分類相關的快取標籤
     */
    protected function performFullFlush(): void
    {
        Cache::tags(['product_categories'])->flush();
        
        Log::info('Product category cache full flush completed', [
            'job_id' => $this->job?->getJobId(),
            'category_ids' => $this->categoryIds,
            'force_full_flush' => $this->forceFullFlush,
        ]);
    }

    /**
     * 執行選擇性快取清除
     * 
     * 根據指定的分類 ID 清除相關的快取項目
     */
    protected function performSelectiveFlush(): void
    {
        $keysToForget = [];
        
        foreach ($this->categoryIds as $categoryId) {
            // 產生該分類相關的快取鍵
            $keysToForget[] = "product_category_tree:{$categoryId}";
            $keysToForget[] = "product_category_children:{$categoryId}";
            $keysToForget[] = "product_category_ancestors:{$categoryId}";
        }
        
        // 批次清除快取鍵
        foreach ($keysToForget as $key) {
            Cache::forget($key);
        }
        
        Log::info('Product category cache selective flush completed', [
            'job_id' => $this->job?->getJobId(),
            'category_ids' => $this->categoryIds,
            'keys_forgotten' => count($keysToForget),
            'cache_keys' => $keysToForget,
        ]);
    }

    /**
     * 記錄成功執行的日誌
     *
     * @param float $duration 執行時間（秒）
     */
    protected function logSuccess(float $duration): void
    {
        Log::info('FlushProductCategoryCache job completed successfully', [
            'job_id' => $this->job?->getJobId(),
            'category_ids' => $this->categoryIds,
            'force_full_flush' => $this->forceFullFlush,
            'duration_ms' => round($duration * 1000, 2),
            'queue' => $this->queue,
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * 記錄失敗執行的日誌
     *
     * @param \Throwable $exception 例外物件
     * @param float $duration 執行時間（秒）
     */
    protected function logFailure(\Throwable $exception, float $duration): void
    {
        Log::error('FlushProductCategoryCache job failed', [
            'job_id' => $this->job?->getJobId(),
            'category_ids' => $this->categoryIds,
            'force_full_flush' => $this->forceFullFlush,
            'duration_ms' => round($duration * 1000, 2),
            'queue' => $this->queue,
            'attempts' => $this->attempts(),
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * 處理任務失敗
     * 
     * 當任務達到最大重試次數後仍然失敗時執行
     *
     * @param \Throwable|null $exception 導致失敗的例外
     */
    public function failed(?\Throwable $exception = null): void
    {
        Log::critical('FlushProductCategoryCache job permanently failed', [
            'job_id' => $this->job?->getJobId(),
            'category_ids' => $this->categoryIds,
            'force_full_flush' => $this->forceFullFlush,
            'max_attempts' => $this->tries,
            'queue' => $this->queue,
            'exception_class' => $exception ? get_class($exception) : null,
            'exception_message' => $exception?->getMessage(),
        ]);
        
        // 可以在這裡加入額外的失敗處理邏輯，例如：
        // - 發送告警通知
        // - 記錄到監控系統
        // - 觸發備援機制
    }

    /**
     * 取得任務的唯一識別符
     * 
     * 用於避免重複的任務排隊
     *
     * @return string
     */
    public function uniqueId(): string
    {
        $categoryIds = $this->categoryIds;
        sort($categoryIds);
        $categoryIdHash = md5(json_encode($categoryIds));
        return sprintf(
            'flush_product_category_cache:%s:%s',
            $categoryIdHash,
            $this->forceFullFlush ? 'full' : 'selective'
        );
    }

    /**
     * 取得任務的標籤（用於 Laravel Horizon 監控）
     *
     * @return array<string>
     */
    public function tags(): array
    {
        $tags = ['product_category_cache', 'cache_flush'];
        
        if ($this->forceFullFlush) {
            $tags[] = 'full_flush';
        } else {
            $tags[] = 'selective_flush';
        }
        
        if (count($this->categoryIds) === 1) {
            $tags[] = "category_id:{$this->categoryIds[0]}";
        } elseif (count($this->categoryIds) > 1) {
            $tags[] = 'batch_flush';
            $tags[] = "category_count:" . count($this->categoryIds);
        }
        
        return $tags;
    }
} 