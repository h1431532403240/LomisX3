<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductCategory;
use App\Jobs\FlushProductCategoryCache;
use Illuminate\Cache\TaggedCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\Tracer;

/**
 * 商品分類快取服務
 *
 * 提供高效能的快取管理策略，包含：
 * - 標籤式快取管理
 * - 分層快取策略（樹狀、麵包屑、統計）
 * - 精準清除和預熱機制
 * - 根節點分片快取策略
 * - Redis 鎖防重機制
 */
class ProductCategoryCacheService
{
    /**
     * 快取標籤常數
     */
    public const TAG = 'product_categories';

    /**
     * 快取時效（秒）
     */
    private const CACHE_TTL = 3600;

    /**
     * Redis 鎖定時間（秒）
     */
    private const LOCK_TIMEOUT = 3;

    /**
     * 快取前綴（支援多環境隔離）
     */
    private string $cachePrefix;

    /**
     * Prometheus 註冊器
     */
    private CollectorRegistry $prometheusRegistry;

    /**
     * 聚合受影響的根分類 ID，避免重複清除
     * 
     * @var array<int>
     */
    protected static array $pendingRootIds = [];
    
    /**
     * 防抖清除統計計數器
     * 
     * @var array{count: int, reasons: array<string>}
     */
    protected static array $fallbackStats = [
        'count' => 0,
        'reasons' => [],
    ];

    /**
     * 建構函式
     */
    public function __construct()
    {
        $this->cachePrefix = 'pc_' . config('app.env', 'production') . '_';
        
        // 初始化 Prometheus（如果不在測試環境）
        if (!app()->runningUnitTests()) {
            $this->prometheusRegistry = new CollectorRegistry(new InMemory());
        }
    }

    /**
     * 取得完整分類樹狀結構（支援根節點分片）
     *
     * @param bool $onlyActive 是否僅取得啟用狀態的分類
     */
    public function getTree(bool $onlyActive = true): Collection
    {
        $start = microtime(true);
        $cacheKey = $this->cachePrefix . 'tree_' . ($onlyActive ? 'active' : 'all');
        $cacheHit = false;
        $result = null;
        $span = null;
        $scope = null;

        try {
            // 建立 OpenTelemetry 手動 span
            $tracer = \OpenTelemetry\API\Globals::tracerProvider()
                ->getTracer('product-category-service');
            
            $span = $tracer->spanBuilder('ProductCategory.getTree')
                ->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_INTERNAL)
                ->setAttribute('service.name', 'product-category')
                ->setAttribute('operation.name', 'getTree')
                ->startSpan();
            
            $scope = $span->activate();

            $result = $this->getTaggedCache()->remember($cacheKey, self::CACHE_TTL, function () use ($onlyActive, &$cacheHit) {
                $cacheHit = false; // 快取未命中
                Log::info('快取未命中，重新查詢分類樹狀結構', ['only_active' => $onlyActive]);

                $query = ProductCategory::with(['children' => function ($q) use ($onlyActive) {
                    $q->orderBy('position');
                    if ($onlyActive) {
                        $q->where('status', true);
                    }
                }]);

                if ($onlyActive) {
                    $query->where('status', true);
                }

                return $query->whereNull('parent_id')
                    ->orderBy('position')
                    ->get();
            });

            // 檢查快取結果
            if ($result->isNotEmpty()) {
                $cacheHit = true; // 快取命中
            }

            return $result;

        } catch (\Throwable $e) {
            // 記錄錯誤資訊
            if ($span) {
                $span->recordException($e);
                $span->setStatus(
                    \OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR,
                    $e->getMessage()
                );
            }
            
            throw $e;
            
        } finally {
            // 記錄 Prometheus 指標
            $this->recordPrometheusMetrics($start, $cacheHit, $onlyActive, $result ?? collect());
            
            // 確保 span 正確結束
            if ($span) {
                $span->end();
            }
            if ($scope) {
                $scope->detach();
            }
        }
    }

    /**
     * 記錄 Prometheus 指標（優化版本，控制 cardinality）
     * 
     * 避免高基數 label，使用多個獨立指標替代複合 label
     * 
     * @param float $startTime 開始時間
     * @param bool $cacheHit 是否快取命中
     * @param bool $onlyActive 是否僅查詢啟用分類
     * @param \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductCategory>|\Illuminate\Support\Collection<int|string, mixed> $result 查詢結果
     */
    private function recordPrometheusMetrics(float $startTime, bool $cacheHit, bool $onlyActive, $result): void
    {
        if (!$this->shouldRecordMetrics()) {
            return;
        }

        try {
            $executionTime = microtime(true) - $startTime;
            $resultCount = $result->count();
            
            // 基礎標籤（低基數）
            $baseLabels = [
                'method' => 'get_tree',
                'cache_hit' => $cacheHit ? 'true' : 'false',
                'status_filter' => $onlyActive ? 'active_only' : 'include_inactive',
            ];

            // 執行時間直方圖（移除 result 相關 label）
            $histogram = $this->prometheusRegistry->getOrRegisterHistogram(
                'pc_cache',
                'get_tree_duration_seconds',
                'PC cache get_tree operation duration',
                array_keys($baseLabels),
                [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
            );
            $histogram->observe($executionTime, array_values($baseLabels));

            // 獨立的操作計數器
            $operationCounter = $this->prometheusRegistry->getOrRegisterCounter(
                'pc_cache',
                'operations_total',
                'Total PC cache operations',
                array_keys($baseLabels)
            );
            $operationCounter->incBy(1, array_values($baseLabels));

            // 獨立的結果大小計數器（避免作為 label）
            $resultSizeCounter = $this->prometheusRegistry->getOrRegisterCounter(
                'pc_cache',
                'result_items_total',
                'Total items returned by PC cache operations',
                ['method', 'status_filter']
            );
            $resultSizeCounter->incBy($resultCount, [
                $baseLabels['method'],
                $baseLabels['status_filter']
            ]);

            // 快取效率指標
            if ($cacheHit) {
                $cacheHitCounter = $this->prometheusRegistry->getOrRegisterCounter(
                    'pc_cache',
                    'hits_total',
                    'Total PC cache hits',
                    ['method', 'status_filter']
                );
                $cacheHitCounter->incBy(1, [
                    $baseLabels['method'],
                    $baseLabels['status_filter']
                ]);
            } else {
                $cacheMissCounter = $this->prometheusRegistry->getOrRegisterCounter(
                    'pc_cache',
                    'misses_total',
                    'Total PC cache misses',
                    ['method', 'status_filter']
                );
                $cacheMissCounter->incBy(1, [
                    $baseLabels['method'],
                    $baseLabels['status_filter']
                ]);
            }

            // 錯誤率監控（根據結果判斷）
            if ($resultCount === 0 && !$cacheHit) {
                $emptyResultCounter = $this->prometheusRegistry->getOrRegisterCounter(
                    'pc_cache',
                    'empty_results_total',
                    'Total empty results from PC cache operations',
                    ['method', 'status_filter']
                );
                $emptyResultCounter->incBy(1, [
                    $baseLabels['method'],
                    $baseLabels['status_filter']
                ]);
            }

            // 記憶體使用量（如果可用）
            if (function_exists('memory_get_usage')) {
                $memoryGauge = $this->prometheusRegistry->getOrRegisterGauge(
                    'pc_cache',
                    'memory_usage_bytes',
                    'Memory usage during PC cache operations',
                    ['method']
                );
                $memoryGauge->set(memory_get_usage(true), [$baseLabels['method']]);
            }

            // 每小時監控 TSDB cardinality（取樣）
            if (rand(1, 3600) === 1) { // 1/3600 機率
                $this->monitorTsdbCardinality();
            }

        } catch (\Throwable $e) {
            // 靜默處理 Prometheus 錯誤，避免影響業務邏輯
            Log::debug('Prometheus 指標記錄失敗', [
                'error' => $e->getMessage(),
                'method' => 'recordPrometheusMetrics',
            ]);
        }
    }

    /**
     * 監控 Prometheus TSDB cardinality
     */
    private function monitorTsdbCardinality(): void
    {
        try {
            // 記錄當前指標數量
            $cardinalityGauge = $this->prometheusRegistry->getOrRegisterGauge(
                'pc_cache',
                'prometheus_metrics_count',
                'Number of PC cache related Prometheus metrics',
                ['component']
            );

            // 統計我們的指標數量（動態計算以支援擴展）
            $ourMetrics = [
                'histograms' => 1, // get_tree_duration_seconds
                'counters' => 5,   // operations_total, result_items_total, hits_total, misses_total, empty_results_total
                'gauges' => 2,     // memory_usage_bytes, prometheus_metrics_count
            ];

            foreach ($ourMetrics as $type => $count) {
                $cardinalityGauge->set($count, [$type]);
            }

            // 動態計算總指標數並檢查閾值
            $totalMetrics = array_sum($ourMetrics);
            $maxAllowedMetrics = config('prometheus.cache_metrics_limit', 10);
            
            if ($totalMetrics > $maxAllowedMetrics) {
                Log::warning('PC cache Prometheus 指標數量過多', [
                    'total_metrics' => $totalMetrics,
                    'max_allowed' => $maxAllowedMetrics,
                    'breakdown' => $ourMetrics,
                    'suggestion' => '考慮減少標籤維度或合併相似指標',
                ]);
            }

        } catch (\Throwable $e) {
            Log::debug('TSDB cardinality 監控失敗', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 取得分類麵包屑路徑
     *
     * @param int $categoryId 分類ID
     */
    public function getBreadcrumbs(int $categoryId): Collection
    {
        $cacheKey = $this->cachePrefix . "breadcrumbs_{$categoryId}";

        return $this->getTaggedCache()->remember($cacheKey, self::CACHE_TTL, function () use ($categoryId) {
            $category = ProductCategory::find($categoryId);
            
            if (!$category) {
                return collect();
            }

            return $category->getAncestors()->reverse()->values();
        });
    }

    /**
     * 取得子分類
     *
     * @param int  $parentId   父分類ID
     * @param bool $onlyActive 是否僅取得啟用狀態的分類
     */
    public function getChildren(int $parentId, bool $onlyActive = true): Collection
    {
        $activeFlag = $onlyActive ? 'active' : 'all';
        $cacheKey = $this->cachePrefix . "children_{$parentId}_{$activeFlag}";

        return $this->getTaggedCache()->remember($cacheKey, self::CACHE_TTL, function () use ($parentId, $onlyActive) {
            $query = ProductCategory::where('parent_id', $parentId)->orderBy('position');
            
            if ($onlyActive) {
                $query->where('status', true);
            }
            
            return $query->get();
        });
    }

    /**
     * 取得所有子孫分類
     *
     * @param int  $parentId   父分類ID
     * @param bool $onlyActive 是否僅取得啟用狀態的分類
     */
    public function getDescendants(int $parentId, bool $onlyActive = true): Collection
    {
        $activeFlag = $onlyActive ? 'active' : 'all';
        $cacheKey = $this->cachePrefix . "descendants_{$parentId}_{$activeFlag}";

        return $this->getTaggedCache()->remember($cacheKey, self::CACHE_TTL, function () use ($parentId, $onlyActive) {
            $category = ProductCategory::find($parentId);
            
            if (!$category) {
                return collect();
            }

            $query = $category->descendants();
            
            if ($onlyActive) {
                $query->where('status', true);
            }
            
            return $query->orderBy('depth')->orderBy('position')->get();
        });
    }

    /**
     * 取得分類統計資訊
     */
    public function getStatistics(): array
    {
        $cacheKey = $this->cachePrefix . 'statistics';

        return $this->getTaggedCache()->remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'total' => ProductCategory::count(),
                'active' => ProductCategory::where('status', true)->count(),
                'root_categories' => ProductCategory::whereNull('parent_id')->count(),
                'max_depth' => ProductCategory::max('depth') ?? 0,
                'avg_children' => ProductCategory::selectRaw('AVG(children_count) as avg')
                    ->whereHas('children')
                    ->value('avg') ?? 0,
            ];
        });
    }

    /**
     * 清除樹狀結構快取
     */
    public function forgetTree(): void
    {
        $this->getTaggedCache()->forget($this->cachePrefix . 'tree_active');
        $this->getTaggedCache()->forget($this->cachePrefix . 'tree_all');
        
        Log::info('已清除分類樹狀結構快取');
    }

    /**
     * 清除特定分類的相關快取
     *
     * @param int $categoryId 分類ID
     */
    public function forgetCategory(int $categoryId): void
    {
        $patterns = [
            $this->cachePrefix . "breadcrumbs_{$categoryId}",
            $this->cachePrefix . "children_{$categoryId}_active",
            $this->cachePrefix . "children_{$categoryId}_all",
            $this->cachePrefix . "descendants_{$categoryId}_active",
            $this->cachePrefix . "descendants_{$categoryId}_all",
        ];

        foreach ($patterns as $pattern) {
            $this->getTaggedCache()->forget($pattern);
        }

        Log::info('已清除分類相關快取', ['category_id' => $categoryId]);
    }

    /**
     * 清除受影響的樹狀結構部分（聚合優化版本）
     * 
     * 在同一請求週期內聚合所有受影響的根分類ID，最後統一清除
     * 避免多次呼叫導致的重複操作和全域flush
     * 
     * @param ProductCategory $category 發生變更的分類
     * @param int|null $originalRootId 原始根分類ID（用於移動操作）
     */
    public function forgetAffectedTreeParts(ProductCategory $category, ?int $originalRootId = null): void
    {
        try {
            // 嘗試精準清除受影響的根分類快取
            $currentRoot = $category->getRootAncestorId();
            $affectedRootIds = array_filter(array_unique([$currentRoot, $originalRootId]));
            
            if (empty($affectedRootIds)) {
                $this->recordFallbackReason('empty_root_ids', [
                    'category_id' => $category->id,
                    'current_root' => $currentRoot,
                    'original_root' => $originalRootId,
                ]);
                $this->performDebouncedFlush([$category->id]);
                return;
            }
            
            // 聚合受影響的根分類ID
            self::$pendingRootIds = array_merge(self::$pendingRootIds, $affectedRootIds);
            self::$pendingRootIds = array_unique(self::$pendingRootIds);
            
            // 註冊請求結束時的清除回調
            $this->registerShutdownClearance();
            
            Log::info('已聚合受影響的根分類', [
                'category_id' => $category->id,
                'current_root' => $currentRoot,
                'original_root' => $originalRootId,
                'new_affected_roots' => $affectedRootIds,
                'total_pending_roots' => count(self::$pendingRootIds),
                'pending_roots' => self::$pendingRootIds,
            ]);
            
        } catch (\Throwable $e) {
            $this->recordFallbackReason('exception', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            Log::error('聚合快取清除發生異常，執行防抖清除', [
                'category_id' => $category->id,
                'current_root' => $currentRoot ?? null,
                'original_root' => $originalRootId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // 異常情況下的 fallback 策略
            $this->performDebouncedFlush([$category->id]);
        }
    }

    /**
     * 註冊請求結束時的清除回調
     */
    private function registerShutdownClearance(): void
    {
        static $registered = false;
        
        if (!$registered) {
            register_shutdown_function(function () {
                $this->executePendingClearance();
            });
            $registered = true;
        }
    }

    /**
     * 執行聚合的快取清除
     */
    private function executePendingClearance(): void
    {
        if (empty(self::$pendingRootIds)) {
            return;
        }
        
        $startTime = microtime(true);
        $successfulClears = 0;
        $failedClears = 0;
        
        foreach (self::$pendingRootIds as $rootId) {
            if ($this->clearRootShard($rootId)) {
                $successfulClears++;
            } else {
                $failedClears++;
            }
        }
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // 如果有失敗的清除，記錄警告並執行防抖清除
        if ($failedClears > 0) {
            $this->recordFallbackReason('partial_failure', [
                'successful_clears' => $successfulClears,
                'failed_clears' => $failedClears,
                'affected_roots' => self::$pendingRootIds,
            ]);
            
            Log::warning('部分精準快取清除失敗，執行防抖清除 fallback', [
                'total_roots' => count(self::$pendingRootIds),
                'successful_clears' => $successfulClears,
                'failed_clears' => $failedClears,
                'execution_time_ms' => $executionTime,
                'fallback_stats' => self::$fallbackStats,
            ]);
            
            // 對失敗的根分類執行防抖清除
            $this->performDebouncedFlush(array_keys(array_filter(self::$pendingRootIds, function($rootId) {
                return !$this->clearRootShard($rootId);
            })));
        } else {
            Log::info('聚合快取清除成功完成', [
                'total_roots' => count(self::$pendingRootIds),
                'successful_clears' => $successfulClears,
                'execution_time_ms' => $executionTime,
                'affected_roots' => self::$pendingRootIds,
                'efficiency_gain' => $this->calculateEfficiencyGain(),
            ]);
        }
        
        // 清理狀態
        self::$pendingRootIds = [];
        
        // 輸出防抖統計（如果有的話）
        if (self::$fallbackStats['count'] > 0) {
            Log::warning('快取清除 fallback 統計', [
                'total_fallbacks' => self::$fallbackStats['count'],
                'reasons' => array_count_values(self::$fallbackStats['reasons']),
                'suggestion' => '考慮優化精準清除邏輯或增加根分類快取',
            ]);
            
            // 重置統計
            self::$fallbackStats = ['count' => 0, 'reasons' => []];
        }
    }

    /**
     * 記錄 fallback 原因，用於後續分析優化
     * 
     * @param string $reason 原因代碼
     * @param array<string, mixed> $context 上下文資料
     */
    private function recordFallbackReason(string $reason, array $context = []): void
    {
        self::$fallbackStats['count']++;
        self::$fallbackStats['reasons'][] = $reason;
        
        Log::warning("快取清除 fallback 觸發: {$reason}", array_merge($context, [
            'fallback_count' => self::$fallbackStats['count'],
            'optimization_hint' => $this->getFallbackOptimizationHint($reason),
        ]));
    }

    /**
     * 取得 fallback 原因的優化建議
     * 
     * @param string $reason
     * @return string
     */
    private function getFallbackOptimizationHint(string $reason): string
    {
        return match($reason) {
            'empty_root_ids' => '建議檢查 getRootAncestorId() 方法的實作',
            'partial_failure' => '建議檢查 clearRootShard() 方法的可靠性',
            'exception' => '建議增加異常處理和錯誤復原機制',
            default => '建議檢查快取清除邏輯的整體架構',
        };
    }

    /**
     * 計算聚合清除的效率提升
     * 
     * @return array<string, mixed>
     */
    private function calculateEfficiencyGain(): array
    {
        $totalRoots = count(self::$pendingRootIds);
        $assumedIndividualCalls = $totalRoots * 2; // 假設每次變更平均影響2個根分類
        
        return [
            'batch_size' => $totalRoots,
            'estimated_individual_calls' => $assumedIndividualCalls,
            'efficiency_improvement' => $assumedIndividualCalls > 0 
                ? round((($assumedIndividualCalls - 1) / $assumedIndividualCalls) * 100, 2) . '%'
                : '0%',
        ];
    }

    /**
     * 取得樹狀分片快取鍵
     *
     * @param int  $rootId     根分類ID
     * @param bool $onlyActive 是否僅包含啟用分類
     */
    private function getTreeShardCacheKey(int $rootId, bool $onlyActive): string
    {
        $activeFlag = $onlyActive ? 'active' : 'all';
        return $this->cachePrefix . "tree_shard_{$activeFlag}_{$rootId}";
    }

    /**
     * 取得根分類ID列表快取鍵
     *
     * @param bool $onlyActive 是否僅包含啟用分類
     */
    private function getRootIdsCacheKey(bool $onlyActive): string
    {
        $activeFlag = $onlyActive ? 'active' : 'all';
        return $this->cachePrefix . "root_ids_{$activeFlag}";
    }

    /**
     * 執行防抖動的快取清除（佇列處理）
     *
     * @param array $categoryIds 需要清除快取的分類 ID 陣列
     */
    public function performDebouncedFlush(array $categoryIds): void
    {
        // 生成防抖動鎖的鍵名
        $lockKey = 'debounce_lock:product_category_flush:' . md5(serialize($categoryIds));
        $lockTtl = 2; // 2 秒防抖動視窗
        
        // 嘗試獲得防抖動鎖
        if (!Cache::add($lockKey, true, $lockTtl)) {
            // 鎖已存在，表示在防抖動視窗內，跳過此次執行
            Log::debug('防抖動機制生效，跳過重複的快取清除請求', [
                'category_ids' => $categoryIds,
                'lock_key' => $lockKey,
                'lock_ttl' => $lockTtl,
            ]);
            return;
        }

        // 獲得鎖成功，執行快取清除
        $queueName = config('custom_queues.product_category_flush', 'low');

        // 使用正式的 Job 類別取代 Closure
        FlushProductCategoryCache::dispatch($categoryIds)
            ->onQueue($queueName)
            ->delay(now()->addSeconds(5));
            
        Log::info('Product category cache debounced flush scheduled', [
            'category_ids' => $categoryIds,
            'queue' => $queueName,
            'delay_seconds' => 5,
            'job_class' => FlushProductCategoryCache::class,
            'lock_key' => $lockKey,
            'debounce_window' => $lockTtl,
        ]);
    }

    /**
     * 快取預熱
     *
     * @param bool $force 是否強制重新預熱
     */
    public function warmup(bool $force = false): void
    {
        if ($force) {
            $this->flush();
        }

        Log::info('開始快取預熱');

        // 預載入常用快取
        $this->getTree(true);
        $this->getTree(false);
        $this->getStatistics();

        // 預載入根分類的子分類
        $rootCategories = ProductCategory::root()->pluck('id');
        foreach ($rootCategories as $rootId) {
            $this->getChildren($rootId, true);
            $this->getChildren($rootId, false);
        }

        Log::info('快取預熱完成');
    }

    /**
     * 取得帶標籤的快取實例（支援 fallback）
     * 
     * @return \Illuminate\Cache\TaggedCache|\Illuminate\Contracts\Cache\Repository
     */
    private function getTaggedCache()
    {
        // 檢查當前快取驅動是否支援標籤
        $cacheStore = Cache::getStore();
        
        // 如果快取驅動不支援標籤（如 array, database），回退到普通快取
        if (!method_exists($cacheStore, 'tags')) {
            Log::warning('快取驅動不支援標籤功能，使用普通快取', [
                'driver' => config('cache.default'),
                'store_class' => get_class($cacheStore),
            ]);
            return Cache::store();
        }
        
        // 使用標籤式快取
        return Cache::tags([self::TAG]);
    }

    /**
     * 清除所有相關快取（支援非標籤式快取）
     */
    public function flush(): void
    {
        $cacheInstance = $this->getTaggedCache();
        
        // 檢查是否為 TaggedCache 實例
        if ($cacheInstance instanceof \Illuminate\Cache\TaggedCache) {
            $cacheInstance->flush();
        } else {
            // 如果不支援標籤，手動清除已知的快取鍵
            $this->flushWithoutTags();
        }
        
        Log::info('已清除所有商品分類快取');
    }

    /**
     * 不使用標籤的快取清除方法
     */
    private function flushWithoutTags(): void
    {
        $patterns = [
            $this->cachePrefix . 'tree_active',
            $this->cachePrefix . 'tree_all',
            $this->cachePrefix . 'statistics',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // 清除可能的分片快取
        for ($i = 1; $i <= 100; $i++) {
            Cache::forget($this->getTreeShardCacheKey($i, true));
            Cache::forget($this->getTreeShardCacheKey($i, false));
        }
    }

    /**
     * 清除單一根分類的快取分片（支援非標籤式快取）
     * 
     * @param int $rootId 根分類ID
     * @return bool 清除是否成功
     */
    private function clearRootShard(int $rootId): bool
    {
        try {
            $activeKey = $this->getTreeShardCacheKey($rootId, true);
            $allKey = $this->getTreeShardCacheKey($rootId, false);
            
            // 嘗試使用標籤式清除
            try {
                $activeResult = Cache::tags([self::TAG])->forget($activeKey);
                $allResult = Cache::tags([self::TAG])->forget($allKey);
            } catch (\BadMethodCallException $e) {
                // 回退到普通快取清除
                $activeResult = Cache::forget($activeKey);
                $allResult = Cache::forget($allKey);
            }
            
            Log::debug('根分類快取分片清除', [
                'root_id' => $rootId,
                'active_key' => $activeKey,
                'all_key' => $allKey,
                'active_cleared' => $activeResult,
                'all_cleared' => $allResult,
            ]);
            
            return $activeResult || $allResult;
            
        } catch (\Throwable $e) {
            Log::warning('根分類快取分片清除失敗', [
                'root_id' => $rootId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 產生補齊的根分類 ID（用於分片）
     *
     * @param int $rootId  根分類 ID
     * @param int $padding 補齊位數，預設 4 位
     */
    private function paddedRootId(int $rootId, int $padding = 4): string
    {
        return str_pad((string) $rootId, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * 取得快取資訊（用於監控和除錯）
     */
    public function getCacheInfo(): array
    {
        return [
            'tag' => self::TAG,
            'prefix' => $this->cachePrefix,
            'ttl' => self::CACHE_TTL,
            'lock_timeout' => self::LOCK_TIMEOUT,
            'driver' => config('cache.default'),
            'redis_connection' => config('database.redis.default.host'),
        ];
    }

    /**
     * ── 新增: 生成樹狀結構快取鍵
     * 支援 P1.3 OpenTelemetry 測試追蹤器需求
     */
    public function generateTreeCacheKey(?int $rootId = null, ?int $maxDepth = null, bool $includeInactive = false): string
    {
        $parts = ['tree'];
        
        if ($rootId !== null) {
            $parts[] = "root_{$rootId}";
        } else {
            $parts[] = 'all_roots';
        }
        
        if ($maxDepth !== null) {
            $parts[] = "depth_{$maxDepth}";
        } else {
            $parts[] = 'unlimited_depth';
        }
        
        $parts[] = $includeInactive ? 'with_inactive' : 'active_only';
        
        return $this->cachePrefix . implode('_', $parts);
    }

    /**
     * 是否應該記錄 Prometheus 指標
     */
    private function shouldRecordMetrics(): bool
    {
        return isset($this->prometheusRegistry);
    }
} 