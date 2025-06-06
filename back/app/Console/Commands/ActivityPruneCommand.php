<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

/**
 * 活動日誌清理命令
 * 清理指定天數前的活動日誌，支援按門市維度清理
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
class ActivityPruneCommand extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'activitylog:prune 
                            {--days=180 : 保留最近幾天的活動日誌}
                            {--by-store : 按門市維度進行清理}
                            {--store-id= : 僅清理指定門市的日誌}
                            {--chunk=1000 : 每次處理的記錄數量}
                            {--dry-run : 乾跑模式，不實際刪除}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '清理指定天數前的活動日誌，支援按門市維度清理';

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $byStore = $this->option('by-store');
        $storeId = $this->option('store-id');
        $chunk = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        $this->info("🗑️ 開始清理活動日誌...");
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  乾跑模式：不會實際刪除記錄');
            $this->newLine();
        }

        $startTime = now();
        $cutoffDate = now()->subDays($days);

        try {
            // 顯示清理參數
            $this->displayParameters($days, $cutoffDate, $byStore, $storeId, $chunk, $dryRun);

            // 建立查詢條件
            $query = Activity::where('created_at', '<', $cutoffDate);
            
            if ($storeId) {
                $query->where('properties->store_id', $storeId);
                $this->info("🏪 僅清理門市 ID: {$storeId} 的活動日誌");
            } elseif ($byStore) {
                $this->info("🏪 按門市維度進行清理");
                return $this->cleanupByStore($query, $chunk, $dryRun, $startTime);
            }

            // 統計待清理記錄
            $totalRecords = $query->count();
            
            if ($totalRecords === 0) {
                $this->info('✅ 沒有需要清理的活動日誌');
                return Command::SUCCESS;
            }

            $this->info("📊 找到 " . number_format($totalRecords) . " 筆待清理記錄");
            $this->newLine();

            // 執行清理
            $deletedCount = $this->performCleanup($query, $chunk, $dryRun, $totalRecords);

            // 顯示結果
            $this->displayResults($deletedCount, $totalRecords, $startTime, $dryRun);

            Log::info('Activity log cleanup completed', [
                'days_retained' => $days,
                'total_records' => $totalRecords,
                'deleted_count' => $deletedCount,
                'store_id' => $storeId,
                'by_store' => $byStore,
                'dry_run' => $dryRun,
                'duration_seconds' => now()->diffInSeconds($startTime)
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ 活動日誌清理失敗: {$e->getMessage()}");
            
            Log::error('Activity log cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 顯示清理參數
     */
    private function displayParameters(int $days, Carbon $cutoffDate, bool $byStore, ?string $storeId, int $chunk, bool $dryRun): void
    {
        $this->table([
            '參數', '值'
        ], [
            ['保留天數', $days . ' 天'],
            ['清理日期界限', $cutoffDate->format('Y-m-d H:i:s')],
            ['按門市清理', $byStore ? '✅ 是' : '❌ 否'],
            ['指定門市', $storeId ?: '全部'],
            ['處理區塊大小', number_format($chunk)],
            ['模式', $dryRun ? '乾跑模式' : '實際執行']
        ]);

        $this->newLine();
    }

    /**
     * 按門市維度進行清理
     */
    private function cleanupByStore($baseQuery, int $chunk, bool $dryRun, Carbon $startTime): int
    {
        // 取得所有有活動日誌的門市
        $storeIds = DB::table('activity_log')
            ->whereRaw("JSON_EXTRACT(properties, '$.store_id') IS NOT NULL")
            ->where('created_at', '<', now()->subDays(180))
            ->selectRaw("DISTINCT JSON_EXTRACT(properties, '$.store_id') as store_id")
            ->pluck('store_id')
            ->filter()
            ->sort();

        if ($storeIds->isEmpty()) {
            $this->info('✅ 沒有門市相關的活動日誌需要清理');
            return Command::SUCCESS;
        }

        $this->info("🏪 找到 {$storeIds->count()} 個門市有活動日誌需要清理");
        $this->newLine();

        $totalDeleted = 0;
        $progressBar = $this->output->createProgressBar($storeIds->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% 門市: %message%');

        foreach ($storeIds as $storeId) {
            $progressBar->setMessage("清理門市 {$storeId}");
            
            $storeQuery = clone $baseQuery;
            $storeQuery->where('properties->store_id', $storeId);
            
            $storeRecords = $storeQuery->count();
            
            if ($storeRecords > 0) {
                $deleted = $this->performCleanup($storeQuery, $chunk, $dryRun, $storeRecords, false);
                $totalDeleted += $deleted;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->displayResults($totalDeleted, $totalDeleted, $startTime, $dryRun, true);
        
        return Command::SUCCESS;
    }

    /**
     * 執行清理操作
     */
    private function performCleanup($query, int $chunk, bool $dryRun, int $totalRecords, bool $showProgress = true): int
    {
        $deletedCount = 0;
        
        if ($showProgress) {
            $progressBar = $this->output->createProgressBar($totalRecords);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        }

        if (!$dryRun) {
            // 實際執行刪除
            $query->chunkById($chunk, function ($activities) use (&$deletedCount, $showProgress, &$progressBar) {
                $ids = $activities->pluck('id');
                $deleted = Activity::whereIn('id', $ids)->delete();
                $deletedCount += $deleted;
                
                if ($showProgress) {
                    $progressBar->advance($activities->count());
                }
            });
        } else {
            // 乾跑模式，只計算數量
            $deletedCount = $totalRecords;
            if ($showProgress) {
                $progressBar->advance($totalRecords);
            }
        }

        if ($showProgress) {
            $progressBar->finish();
            $this->newLine(2);
        }

        return $deletedCount;
    }

    /**
     * 顯示清理結果
     */
    private function displayResults(int $deletedCount, int $totalRecords, Carbon $startTime, bool $dryRun, bool $byStore = false): void
    {
        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);

        $this->newLine();
        $this->info('📊 清理結果：');
        
        $results = [
            ['總記錄數', number_format($totalRecords)],
            ['執行時間', $duration . ' 秒'],
            ['完成時間', $endTime->format('Y-m-d H:i:s')]
        ];

        if ($byStore) {
            $results[] = ['清理方式', '按門市維度'];
        }

        if ($dryRun) {
            $results[] = ['模擬清理數量', number_format($deletedCount)];
            $results[] = ['實際刪除', '0 (乾跑模式)'];
        } else {
            $results[] = ['實際清理數量', number_format($deletedCount)];
        }

        $this->table(['項目', '結果'], $results);

        $this->newLine();
        
        if ($dryRun) {
            $this->info('✅ 乾跑模式完成！');
        } else {
            $this->info('✅ 活動日誌清理完成！');
            
            if ($deletedCount > 0) {
                // 計算節省的空間（估算）
                $savedSpaceMB = round(($deletedCount * 1024) / 1024 / 1024, 2);
                $this->comment("💾 估算節省儲存空間: {$savedSpaceMB} MB");
            }
        }
    }
} 