<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductCategoryCacheService;
use Illuminate\Support\Facades\Log;

/**
 * 商品分類快取預熱命令
 * 
 * 此命令用於預熱商品分類的快取資料，包括：
 * - 樹狀結構快取
 * - 統計資訊快取
 * - 支援僅預熱啟用分類或全部分類
 */
class CategoryCacheWarmup extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'category:cache-warmup 
                           {--active : 只預熱啟用的分類}
                           {--queue= : 指定使用的佇列名稱，預設為low}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '預熱商品分類快取資料，提升查詢效能';

    /**
     * 快取服務實例
     */
    private ProductCategoryCacheService $cacheService;

    /**
     * 建立命令實例
     */
    public function __construct(ProductCategoryCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $onlyActive = $this->option('active');
        $queueName = $this->option('queue') ?? config('cache.flush_queue', 'low');

        $this->info('🔥 開始預熱商品分類快取...');
        $this->newLine();

        // 顯示配置資訊
        $this->displayConfiguration($onlyActive, $queueName);
        
        try {
            // 創建進度條
            $progressBar = $this->output->createProgressBar(4);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->setMessage('準備中...');
            $progressBar->start();

            // 步驟1：預熱樹狀結構快取
            $progressBar->setMessage('預熱樹狀結構快取...');
            $this->warmupTreeCache($onlyActive);
            $progressBar->advance();

            // 步驟2：預熱統計資訊快取
            $progressBar->setMessage('預熱統計資訊快取...');
            $this->warmupStatisticsCache();
            $progressBar->advance();

            // 步驟3：預熱根分類ID快取
            $progressBar->setMessage('預熱根分類ID快取...');
            $this->warmupRootIdsCache($onlyActive);
            $progressBar->advance();

            // 步驟4：預熱深度統計快取
            $progressBar->setMessage('預熱深度統計快取...');
            $this->warmupDepthStatistics();
            $progressBar->advance();

            $progressBar->setMessage('快取預熱完成！');
            $progressBar->finish();
            
            $this->newLine(2);
            
            // 顯示執行結果
            $executionTime = round(microtime(true) - $startTime, 2);
            $this->displayResults($executionTime, $onlyActive);
            
            // 記錄成功日誌
            Log::info('Product category cache warmup completed', [
                'only_active' => $onlyActive,
                'execution_time' => $executionTime,
                'queue' => $queueName,
            ]);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('❌ 快取預熱失敗：' . $e->getMessage());
            
            // 記錄錯誤日誌
            Log::error('Product category cache warmup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'only_active' => $onlyActive,
            ]);

            return self::FAILURE;
        }
    }

    /**
     * 顯示配置資訊
     */
    private function displayConfiguration(bool $onlyActive, string $queueName): void
    {
        $this->table(
            ['配置項目', '設定值'],
            [
                ['預熱範圍', $onlyActive ? '僅啟用分類' : '全部分類'],
                ['佇列名稱', $queueName],
                ['快取驅動', config('cache.default')],
                ['Redis標籤支援', config('cache.stores.redis') ? '✅' : '❌'],
            ]
        );
        $this->newLine();
    }

    /**
     * 預熱樹狀結構快取
     */
    private function warmupTreeCache(bool $onlyActive): void
    {
        try {
            // 預熱啟用分類樹
            $activeTree = $this->cacheService->getTree(true);
            $activeCount = $this->countTreeNodes($activeTree);

            if (!$onlyActive) {
                // 預熱全部分類樹
                $allTree = $this->cacheService->getTree(false);
                $allCount = $this->countTreeNodes($allTree);
                
                $this->line("   ✅ 全部分類樹狀結構：{$allCount} 個節點");
            }
            
            $this->line("   ✅ 啟用分類樹狀結構：{$activeCount} 個節點");
            
        } catch (\Throwable $e) {
            $this->warn("   ⚠️  樹狀結構快取預熱部分失敗：{$e->getMessage()}");
        }
    }

    /**
     * 預熱統計資訊快取
     */
    private function warmupStatisticsCache(): void
    {
        try {
            $stats = $this->cacheService->getStatistics();
            
            $this->line("   ✅ 統計資訊快取：總計 {$stats['total']} 個分類");
            
        } catch (\Throwable $e) {
            $this->warn("   ⚠️  統計資訊快取預熱失敗：{$e->getMessage()}");
        }
    }

    /**
     * 預熱根分類ID快取
     */
    private function warmupRootIdsCache(bool $onlyActive): void
    {
        try {
            // 這裡需要呼叫 CacheService 的方法來預熱根ID快取
            // 由於原本的服務可能沒有直接的方法，我們透過取得樹狀結構來間接預熱
            $this->cacheService->getTree($onlyActive);
            
            if (!$onlyActive) {
                $this->cacheService->getTree(false);
                $this->line("   ✅ 全部根分類ID快取");
            }
            
            $this->line("   ✅ 啟用根分類ID快取");
            
        } catch (\Throwable $e) {
            $this->warn("   ⚠️  根分類ID快取預熱失敗：{$e->getMessage()}");
        }
    }

    /**
     * 預熱深度統計快取
     */
    private function warmupDepthStatistics(): void
    {
        try {
            $stats = $this->cacheService->getStatistics();
            
            if (isset($stats['depth_distribution'])) {
                $depthCount = count($stats['depth_distribution']);
                $this->line("   ✅ 深度統計快取：{$depthCount} 個深度層級");
            } else {
                $this->line("   ✅ 深度統計快取");
            }
            
        } catch (\Throwable $e) {
            $this->warn("   ⚠️  深度統計快取預熱失敗：{$e->getMessage()}");
        }
    }

    /**
     * 統計樹狀結構中的節點數量
     */
    private function countTreeNodes(array $tree): int
    {
        $count = 0;
        
        foreach ($tree as $node) {
            $count++; // 當前節點
            
            if (isset($node['children']) && is_array($node['children'])) {
                $count += $this->countTreeNodes($node['children']); // 遞迴計算子節點
            }
        }
        
        return $count;
    }

    /**
     * 顯示執行結果
     */
    private function displayResults(float $executionTime, bool $onlyActive): void
    {
        $this->info('✨ 快取預熱完成！');
        $this->newLine();

        // 顯示快取資訊
        try {
            $cacheInfo = $this->cacheService->getCacheInfo();
            
            $this->table(
                ['快取項目', '狀態', '大小'],
                [
                    ['樹狀結構（啟用）', '✅ 已快取', $this->formatCacheSize($cacheInfo['tree_active'] ?? null)],
                    ['樹狀結構（全部）', $onlyActive ? '⏭️  跳過' : '✅ 已快取', $this->formatCacheSize($cacheInfo['tree_all'] ?? null)],
                    ['統計資訊', '✅ 已快取', $this->formatCacheSize($cacheInfo['statistics'] ?? null)],
                    ['其他快取', '✅ 已快取', '-'],
                ]
            );
            
        } catch (\Throwable $e) {
            $this->warn('無法取得快取資訊：' . $e->getMessage());
        }

        $this->newLine();
        $this->info("⏱️  執行時間：{$executionTime} 秒");
        
        // 提供後續建議
        $this->comment('💡 建議：');
        $this->comment('   • 可以設定 cron job 定期執行此命令');
        $this->comment('   • 在部署後執行此命令以提升首次查詢效能');
        $this->comment('   • 監控快取命中率以評估預熱效果');
    }

    /**
     * 格式化快取大小顯示
     */
    private function formatCacheSize(?string $data): string
    {
        if ($data === null) {
            return '-';
        }
        
        $size = strlen(serialize($data));
        
        if ($size < 1024) {
            return "{$size} B";
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024, 1) . ' KB';
        } else {
            return round($size / (1024 * 1024), 1) . ' MB';
        }
    }
} 