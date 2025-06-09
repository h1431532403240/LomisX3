<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UserCacheService;
use Illuminate\Support\Facades\Log;

/**
 * 使用者快取預熱命令
 * 預熱活躍使用者的快取資料以提升系統響應速度
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
class UserCacheWarmupCommand extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'cache:warmup-users 
                            {--active-days=7 : 預熱最近幾天活躍的使用者}
                            {--chunk=100 : 每次處理的資料量}
                            {--dry-run : 乾跑模式，不實際執行}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '預熱活躍使用者的快取資料';

    /**
     * UserCacheService 依賴注入
     */
    public function __construct(
        protected UserCacheService $cacheService
    ) {
        parent::__construct();
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $activeDays = (int) $this->option('active-days');
        $chunk = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        $this->info("🔥 開始預熱使用者快取...");
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  乾跑模式：不會實際執行快取操作');
            $this->newLine();
        }

        $startTime = now();

        try {
            // 顯示參數資訊
            $this->table([
                '參數', '值'
            ], [
                ['活躍天數', $activeDays . ' 天'],
                ['處理區塊大小', $chunk],
                ['模式', $dryRun ? '乾跑模式' : '實際執行'],
                ['開始時間', $startTime->format('Y-m-d H:i:s')]
            ]);

            $this->newLine();

            if (!$dryRun) {
                // 執行快取預熱
                $this->cacheService->warmupActiveUsersCache();
                
                // 執行健康檢查
                $healthCheck = $this->cacheService->healthCheck();
                
                $this->displayResults($healthCheck, $startTime);
            } else {
                $this->info('✅ 乾跑模式完成，沒有實際執行快取操作');
            }

            Log::info('User cache warmup completed', [
                'active_days' => $activeDays,
                'chunk' => $chunk,
                'dry_run' => $dryRun,
                'duration_seconds' => now()->diffInSeconds($startTime)
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ 快取預熱失敗: {$e->getMessage()}");
            
            Log::error('User cache warmup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 顯示執行結果
     */
    private function displayResults(array $healthCheck, $startTime): void
    {
        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);

        $this->newLine();
        $this->info('📊 執行結果：');
        
        $this->table([
            '項目', '狀態'
        ], [
            ['快取連線', $healthCheck['cache_connection'] ? '✅ 正常' : '❌ 異常'],
            ['Redis 連線', $healthCheck['redis_connection'] ? '✅ 正常' : '❌ 異常'],
            ['快取可用空間', $healthCheck['memory_usage'] ?? 'N/A'],
            ['執行時間', $duration . ' 秒'],
            ['完成時間', $endTime->format('Y-m-d H:i:s')]
        ]);

        if (isset($healthCheck['warmed_users_count'])) {
            $this->info("🔥 預熱使用者數量: {$healthCheck['warmed_users_count']}");
        }

        $this->newLine();
        $this->info('✅ 使用者快取預熱完成！');
    }
} 