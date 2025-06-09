<?php

namespace App\Console\Commands;

use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Back-fill Product Category Paths 命令
 * 
 * 用於為現有的商品分類資料填充 path 欄位（Materialized Path）
 * 支援批次處理和進度顯示，確保大量資料的處理效率
 */
class BackfillProductCategoryPaths extends Command
{
    /**
     * 命令簽名和參數定義
     *
     * @var string
     */
    protected $signature = 'category:backfill-paths 
                            {--chunk=1000 : 批次處理大小，預設1000筆}
                            {--dry-run : 乾跑模式，僅預覽不實際執行}
                            {--force : 強制執行，跳過確認提示}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '為商品分類填充 path 欄位（Materialized Path），用於精準快取清除';

    /**
     * 批次處理大小
     */
    private int $chunkSize;

    /**
     * 乾跑模式標記
     */
    private bool $isDryRun;

    /**
     * 乾跑模式下的路徑暫存
     */
    private array $dryRunPaths = [];

    /**
     * 執行命令主邏輯
     */
    public function handle(): int
    {
        $this->info('🚀 商品分類 Path 回填開始');
        
        // 解析命令參數
        $this->chunkSize = (int) $this->option('chunk');
        $this->isDryRun = $this->option('dry-run');
        
        // 參數驗證
        if ($this->chunkSize < 1 || $this->chunkSize > 10000) {
            $this->error('❌ chunk 參數必須在 1-10000 之間');
            return 1;
        }

        // 檢查現有資料狀況
        $totalCount = ProductCategory::withTrashed()->count();
        $withPathCount = ProductCategory::withTrashed()->whereNotNull('path')->count();
        $needsUpdate = $totalCount - $withPathCount;

        $this->info("📊 資料狀況統計:");
        $this->line("   總分類數量: {$totalCount}");
        $this->line("   已有路徑: {$withPathCount}");
        $this->line("   需要處理: {$needsUpdate}");

        if ($needsUpdate === 0) {
            $this->info('✅ 所有分類已有路徑，無需處理');
            return 0;
        }

        // 非乾跑模式需要確認
        if (!$this->isDryRun && !$this->option('force')) {
            if (!$this->confirm("是否繼續處理 {$needsUpdate} 筆資料？")) {
                $this->info('❌ 操作已取消');
                return 0;
            }
        }

        // 開始處理
        $startTime = microtime(true);
        
        if ($this->isDryRun) {
            $this->warn('🔍 乾跑模式：僅預覽，不實際修改資料');
        }

        // 先處理根分類
        $this->processRootCategories();
        
        // 再按深度順序處理子分類
        $this->processChildCategoriesByDepth();

        $duration = round(microtime(true) - $startTime, 2);
        
        if ($this->isDryRun) {
            $this->info("✅ 乾跑完成，耗時 {$duration} 秒");
        } else {
            $this->info("✅ 路徑回填完成，耗時 {$duration} 秒");
            
            // 驗證結果
            $this->verifyResults();
        }

        return 0;
    }

    /**
     * 處理根分類（parent_id = null）
     */
    private function processRootCategories(): void
    {
        $this->info('🌳 處理根分類...');
        
        $rootCategories = ProductCategory::withTrashed()
            ->whereNull('parent_id')
            ->whereNull('path')
            ->select(['id'])
            ->get();

        $this->withProgressBar($rootCategories, function ($category) {
            $path = "/{$category->id}/";
            
            if ($this->isDryRun) {
                // 乾跑模式：暫存路徑資料供後續處理使用
                $this->dryRunPaths[$category->id] = $path;
                $this->line("  [預覽] ID:{$category->id} => path: {$path}");
            } else {
                ProductCategory::withTrashed()
                    ->where('id', $category->id)
                    ->update(['path' => $path]);
            }
        });

        $this->newLine();
    }

    /**
     * 按深度順序處理子分類
     */
    private function processChildCategoriesByDepth(): void
    {
        $this->info('📊 處理子分類...');
        
        // 取得所有需要處理的深度層級
        $maxDepth = ProductCategory::withTrashed()->max('depth') ?? 0;
        
        for ($depth = 1; $depth <= $maxDepth; $depth++) {
            $this->processChildrenAtDepth($depth);
        }
    }

    /**
     * 處理指定深度的子分類
     */
    private function processChildrenAtDepth(int $depth): void
    {
        $this->line("  處理深度 {$depth} 的分類...");
        
        $categories = ProductCategory::withTrashed()
            ->whereNotNull('parent_id')
            ->where('depth', $depth)
            ->whereNull('path')
            ->select(['id', 'parent_id'])
            ->get();

        if ($categories->isEmpty()) {
            return;
        }

        $this->withProgressBar($categories, function ($category) {
            // 取得父分類的路徑
            $parentPath = $this->getParentPath($category->parent_id);

            if (!$parentPath) {
                $this->error("❌ 找不到父分類 {$category->parent_id} 的路徑");
                return;
            }

            // 構建當前分類的路徑
            $path = $parentPath . $category->id . '/';
            
            if ($this->isDryRun) {
                // 乾跑模式：暫存路徑資料
                $this->dryRunPaths[$category->id] = $path;
                $this->line("  [預覽] ID:{$category->id} (父:{$category->parent_id}) => path: {$path}");
            } else {
                ProductCategory::withTrashed()
                    ->where('id', $category->id)
                    ->update(['path' => $path]);
            }
        });

        $this->newLine();
    }

    /**
     * 取得父分類的路徑（支援乾跑模式）
     */
    private function getParentPath(int $parentId): ?string
    {
        if ($this->isDryRun) {
            // 乾跑模式：從暫存中取得
            return $this->dryRunPaths[$parentId] ?? null;
        } else {
            // 正常模式：從資料庫取得
            return ProductCategory::withTrashed()
                ->where('id', $parentId)
                ->value('path');
        }
    }

    /**
     * 驗證回填結果
     */
    private function verifyResults(): void
    {
        $this->info('🔍 驗證回填結果...');
        
        // 檢查是否還有未處理的分類
        $missingPathCount = ProductCategory::withTrashed()
            ->whereNull('path')
            ->count();

        if ($missingPathCount > 0) {
            $this->error("❌ 仍有 {$missingPathCount} 筆分類缺少路徑");
            
            // 顯示缺少路徑的分類 ID
            $missingIds = ProductCategory::withTrashed()
                ->whereNull('path')
                ->pluck('id')
                ->take(10);
            
            $this->line("缺少路徑的分類 ID：" . $missingIds->implode(', '));
            if ($missingPathCount > 10) {
                $this->line("...還有 " . ($missingPathCount - 10) . " 筆");
            }
            return;
        }

        // 檢查路徑格式正確性
        $invalidPathCount = ProductCategory::withTrashed()
            ->where(function ($query) {
                $query->where('path', 'not like', '/%/')
                      ->orWhere('path', 'like', '%//%');
            })
            ->count();

        if ($invalidPathCount > 0) {
            $this->error("❌ 有 {$invalidPathCount} 筆分類的路徑格式不正確");
            return;
        }

        // 檢查根分類路徑
        $invalidRootPaths = ProductCategory::withTrashed()
            ->whereNull('parent_id')
            ->where('path', 'not regexp', '^/[0-9]+/$')
            ->count();

        if ($invalidRootPaths > 0) {
            $this->error("❌ 有 {$invalidRootPaths} 筆根分類的路徑格式不正確");
            return;
        }

        $this->info('✅ 所有路徑驗證通過');
        
        // 輸出統計資訊
        $rootCount = ProductCategory::withTrashed()
            ->whereNull('parent_id')
            ->count();
        
        $childCount = ProductCategory::withTrashed()
            ->whereNotNull('parent_id')
            ->count();

        $this->info("📈 最終統計:");
        $this->line("   根分類: {$rootCount} 筆");
        $this->line("   子分類: {$childCount} 筆");
        $this->line("   總計: " . ($rootCount + $childCount) . " 筆");
        
        // 顯示一些路徑範例
        $this->info("📝 路徑範例:");
        $samplePaths = ProductCategory::withTrashed()
            ->whereNotNull('path')
            ->select(['id', 'name', 'parent_id', 'path', 'depth'])
            ->orderBy('depth')
            ->orderBy('id')
            ->limit(8)
            ->get();
        
        foreach ($samplePaths as $category) {
            $parentInfo = $category->parent_id ? "父:{$category->parent_id}" : "根分類";
            $this->line("   • D{$category->depth} ID:{$category->id} ({$parentInfo}) => {$category->path}");
        }
    }
}
