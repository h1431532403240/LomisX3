<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\{Cache, Log};
use Carbon\Carbon;

/**
 * Token 清理命令
 * 清理過期的 API Token 並精準清理相關快取
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
class TokenCleanupCommand extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup 
                            {--chunk=1000 : 每次處理的 Token 數量}
                            {--precise-cache : 啟用精準快取清理}
                            {--dry-run : 乾跑模式，不實際刪除}
                            {--days= : 清理幾天前過期的 Token}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '清理過期的 API Token 並精準清理相關快取';

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $preciseCacheClearing = $this->option('precise-cache');
        $dryRun = $this->option('dry-run');
        $days = $this->option('days') ? (int) $this->option('days') : null;

        $this->info("🧹 開始清理過期 Token...");
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  乾跑模式：不會實際刪除 Token');
            $this->newLine();
        }

        $startTime = now();
        $totalDeleted = 0;
        $affectedUserIds = [];

        try {
            // 建立查詢條件
            $query = PersonalAccessToken::whereNotNull('expires_at');
            
            if ($days) {
                // 清理指定天數前過期的 Token
                $cutoffDate = now()->subDays($days);
                $query->where('expires_at', '<', $cutoffDate);
                $this->info("📅 清理 {$days} 天前過期的 Token");
            } else {
                // 清理所有過期的 Token
                $query->where('expires_at', '<', now());
                $this->info("📅 清理所有過期的 Token");
            }

            // 顯示統計資訊
            $totalExpiredTokens = $query->count();
            
            $this->table([
                '項目', '數量'
            ], [
                ['待清理 Token 總數', number_format($totalExpiredTokens)],
                ['處理區塊大小', number_format($chunk)],
                ['精準快取清理', $preciseCacheClearing ? '✅ 啟用' : '❌ 停用'],
                ['模式', $dryRun ? '乾跑模式' : '實際執行']
            ]);

            $this->newLine();

            if ($totalExpiredTokens === 0) {
                $this->info('✅ 沒有需要清理的過期 Token');
                return Command::SUCCESS;
            }

            // 使用進度條
            $progressBar = $this->output->createProgressBar($totalExpiredTokens);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

            // 分批處理過期 Token
            $query->chunkById($chunk, function ($tokens) use (&$totalDeleted, &$affectedUserIds, $dryRun, $preciseCacheClearing, $progressBar) {
                
                if ($preciseCacheClearing) {
                    // 收集受影響的使用者 ID
                    $userIds = $tokens->pluck('tokenable_id')->unique()->toArray();
                    $affectedUserIds = array_merge($affectedUserIds, $userIds);
                }

                if (!$dryRun) {
                    // 實際刪除 Token
                    $deletedCount = PersonalAccessToken::whereIn('id', $tokens->pluck('id'))->delete();
                    $totalDeleted += $deletedCount;
                }

                $progressBar->advance($tokens->count());
            });

            $progressBar->finish();
            $this->newLine(2);

            // 精準快取清理
            if ($preciseCacheClearing && !$dryRun && !empty($affectedUserIds)) {
                $this->info("🧹 清理受影響使用者的快取...");
                
                $uniqueUserIds = array_unique($affectedUserIds);
                $cacheCleared = 0;
                
                foreach ($uniqueUserIds as $userId) {
                    Cache::forget("users:{$userId}");
                    Cache::forget("user_accessible_stores_{$userId}");
                    $cacheCleared++;
                }
                
                $this->info("✅ 已清理 {$cacheCleared} 個使用者的快取");
            }

            // 顯示結果
            $this->displayResults($totalDeleted, $totalExpiredTokens, $startTime, $dryRun);

            Log::info('Token cleanup completed', [
                'total_deleted' => $totalDeleted,
                'total_expired' => $totalExpiredTokens,
                'affected_users' => count(array_unique($affectedUserIds)),
                'dry_run' => $dryRun,
                'duration_seconds' => now()->diffInSeconds($startTime)
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Token 清理失敗: {$e->getMessage()}");
            
            Log::error('Token cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 顯示清理結果
     */
    private function displayResults(int $totalDeleted, int $totalExpired, Carbon $startTime, bool $dryRun): void
    {
        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);

        $this->newLine();
        $this->info('📊 清理結果：');
        
        $results = [
            ['總過期 Token 數', number_format($totalExpired)],
            ['執行時間', $duration . ' 秒'],
            ['完成時間', $endTime->format('Y-m-d H:i:s')]
        ];

        if ($dryRun) {
            $results[] = ['模擬清理數量', number_format($totalExpired)];
            $results[] = ['實際刪除', '0 (乾跑模式)'];
        } else {
            $results[] = ['實際清理數量', number_format($totalDeleted)];
        }

        $this->table(['項目', '結果'], $results);

        $this->newLine();
        
        if ($dryRun) {
            $this->info('✅ 乾跑模式完成！');
        } else {
            $this->info('✅ Token 清理完成！');
            
            if ($totalDeleted > 0) {
                $this->comment("💡 建議：可以考慮設定定期排程來自動清理過期 Token");
            }
        }
    }
} 