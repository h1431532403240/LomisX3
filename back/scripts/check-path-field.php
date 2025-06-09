<?php

/**
 * 檢查 ProductCategory 的 path 欄位狀態
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "🔍 檢查 product_categories 資料表結構...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // 檢查資料表是否存在
    if (!Schema::hasTable('product_categories')) {
        echo "❌ product_categories 資料表不存在\n";
        echo "💡 請先執行：php artisan migrate\n";
        exit(1);
    }
    
    echo "✅ product_categories 資料表存在\n";
    
    // 檢查欄位列表
    $columns = Schema::getColumnListing('product_categories');
    echo "\n📊 資料表欄位列表：\n";
    foreach ($columns as $column) {
        echo "  • {$column}\n";
    }
    
    // 重點檢查 path 欄位
    echo "\n🎯 Path 欄位檢查：\n";
    if (in_array('path', $columns)) {
        echo "✅ path 欄位已存在\n";
        
        // 檢查現有資料
        $totalCount = DB::table('product_categories')->count();
        $withPathCount = DB::table('product_categories')->whereNotNull('path')->count();
        $emptyPathCount = DB::table('product_categories')->where('path', '')->count();
        $nullPathCount = $totalCount - $withPathCount;
        
        echo "\n📈 路徑資料統計：\n";
        echo "  • 總分類數：{$totalCount}\n";
        echo "  • 已有路徑：{$withPathCount}\n";
        echo "  • 空字串路徑：{$emptyPathCount}\n";
        echo "  • NULL 路徑：{$nullPathCount}\n";
        
        if ($nullPathCount > 0) {
            echo "\n⚠️  發現 {$nullPathCount} 筆分類缺少路徑資料\n";
            echo "💡 建議執行：php artisan category:backfill-paths\n";
        } else {
            echo "\n✅ 所有分類都已有路徑資料\n";
        }
        
        // 顯示一些範例路徑
        echo "\n📝 路徑範例：\n";
        $samplePaths = DB::table('product_categories')
            ->whereNotNull('path')
            ->select(['id', 'name', 'parent_id', 'path'])
            ->limit(5)
            ->get();
        
        foreach ($samplePaths as $category) {
            $parentInfo = $category->parent_id ? "父分類:{$category->parent_id}" : "根分類";
            echo "  • ID:{$category->id} ({$parentInfo}) => 路徑: {$category->path}\n";
        }
        
    } else {
        echo "❌ path 欄位不存在\n";
        echo "💡 需要執行 migration：php artisan migrate\n";
        echo "🔍 檢查是否有相關 migration 檔案...\n";
        
        $migrationFiles = glob(__DIR__ . '/../database/migrations/*add_path_to_product_categories*');
        if (!empty($migrationFiles)) {
            echo "✅ 找到 migration 檔案：\n";
            foreach ($migrationFiles as $file) {
                echo "  • " . basename($file) . "\n";
            }
        } else {
            echo "❌ 未找到 path 相關的 migration 檔案\n";
        }
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤：" . $e->getMessage() . "\n";
    echo "📋 堆疊追蹤：\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 