<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use App\Models\ProductCategory;
use App\Services\ProductCategoryCacheService;

/**
 * 商品分類快取熱身命令
 * 
 * 用於預先載入分類樹狀結構到快取中，提升系統效能
 * 支援僅載入啟用分類或全部分類
 */
class CategoryCacheWarmup extends Command
{
    /**
     * 命令簽名
     */
    protected $signature = 'category:cache-warmup 
                            {--active : 僅熱身啟用的分類}
                            {--dry-run : 模擬執行，不實際修改快取}';

    /**
     * 命令描述
     */
    protected $description = '預熱商品分類快取，提升系統效能';

    /**
     * 快取服務
     */
    private ProductCategoryCacheService $cacheService;

    /**
     * 建構函式
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
        $onlyActive = $this->option('active');
        $dryRun = $this->option('dry-run');
        $startTime = microtime(true);

        $this->info('🔥 開始商品分類快取熱身...');
        $this->info('模式: ' . ($onlyActive ? '僅啟用分類' : '全部分類'));
        
        if ($dryRun) {
            $this->warn('🧪 DRY RUN 模式 - 不會實際修改快取');
        }

        // 檢查佇列配置
        $queueName = config('queue.product_category_flush', 'low');
        $this->info("佇列: {$queueName}");

        try {
            // 建立進度條
            $progressBar = $this->output->createProgressBar($dryRun ? 1 : 2);
            $progressBar->setFormat('verbose');
            $progressBar->start();

            if ($dryRun) {
                // DRY RUN 模式：僅模擬操作
                $this->line("\n🔍 模擬操作 - 檢查分類數據...");
                
                // 查詢分類數據但不載入到快取
                $query = ProductCategory::query();
                if ($onlyActive) {
                    $query->where('status', true);
                }
                
                $categoryCount = $query->count();
                $rootCount = $query->whereNull('parent_id')->count();
                
                $this->line("   - 總分類數量: {$categoryCount}");
                $this->line("   - 根分類數量: {$rootCount}");
                $this->line("   - 預估快取大小: " . round($categoryCount * 0.5, 2) . " KB");
                
                $progressBar->advance();
                
            } else {
                // 正常模式：實際執行快取操作
                
                // 步驟1: 清除現有快取
                $this->line("\n🧹 清除現有快取...");
                $this->cacheService->forgetTree();
                $progressBar->advance();

                // 步驟2: 預載入分類樹
                $this->line("\n📦 載入分類樹到快取...");
                $tree = $this->cacheService->getTree($onlyActive);
                $progressBar->advance();
                
                // 計算實際載入的分類數量
                $categoryCount = $this->countCategories($tree);
            }

            $progressBar->finish();

            // 計算統計資訊
            $elapsedTime = round(microtime(true) - $startTime, 3);
            $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

            // 顯示完成資訊
            $this->newLine(2);
            
            if ($dryRun) {
                $this->info('✅ DRY RUN 完成！（未實際修改快取）');
            } else {
                $this->info('✅ 快取熱身完成！');
            }
            
            $this->table(
                ['項目', '數值'],
                [
                    [$dryRun ? '預估分類數量' : '載入分類數量', $categoryCount],
                    ['執行時間', "{$elapsedTime} 秒"],
                    ['記憶體使用', "{$memoryUsage} MB"],
                    ['快取模式', $onlyActive ? '僅啟用' : '全部'],
                    ['佇列名稱', $queueName],
                    ['執行模式', $dryRun ? 'DRY RUN' : '實際執行'],
                ]
            );

            // 驗證快取是否成功（僅在非 DRY RUN 模式）
            if (!$dryRun) {
                $this->verifyCache($onlyActive);
            } else {
                $this->line("\n💡 提示: 移除 --dry-run 選項以實際執行快取熱身");
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌ 快取熱身失敗: ' . $e->getMessage());
            $this->error('錯誤位置: ' . $e->getFile() . ':' . $e->getLine());
            
            if ($this->option('verbose')) {
                $this->error('完整錯誤堆疊:');
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * 計算分類數量（遞迴）
     *
     * @param array<int, mixed>|Collection<int, ProductCategory> $tree 分類樹
     */
    private function countCategories($tree): int
    {
        if ($tree instanceof Collection) {
            $tree = $tree->toArray();
        }
        
        $count = count($tree);
        
        foreach ($tree as $category) {
            if (isset($category['children']) && is_array($category['children'])) {
                $count += $this->countCategories($category['children']);
            }
        }
        
        return $count;
    }

    /**
     * 驗證快取是否成功載入
     */
    private function verifyCache(bool $onlyActive): void
    {
        $this->line("\n🔍 驗證快取狀態...");
        
        try {
            // 取得快取資訊
            $cacheInfo = $this->cacheService->getCacheInfo();
            
            if ($cacheInfo['tree_cache_exists']) {
                $this->info('✅ 分類樹快取已成功載入');
                
                // 顯示快取統計
                if (isset($cacheInfo['cache_stats'])) {
                    $stats = $cacheInfo['cache_stats'];
                    $this->line("   - 快取大小: " . ($stats['size'] ?? 'N/A'));
                    $this->line("   - 快取時間: " . ($stats['created_at'] ?? 'N/A'));
                }
            } else {
                $this->warn('⚠️  分類樹快取可能未正確載入');
            }
            
        } catch (\Throwable $e) {
            $this->warn('⚠️  無法驗證快取狀態: ' . $e->getMessage());
        }
    }
} 