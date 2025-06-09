<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 新增 path 欄位實現 Materialized Path 模式，用於：
     * - 精準快取清除：根據路徑字首快速定位受影響的分片
     * - 查詢最佳化：減少遞迴查詢，改用字串比對
     * - 樹狀結構操作：快速查找祖先和後代節點
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            // 新增 path 欄位：存儲從根節點到當前節點的完整路徑
            // 格式："/1/3/5/" 表示 根分類ID=1 > 子分類ID=3 > 當前分類ID=5
            // 限制長度 500 字元，支援最多約 25 層深度（平均每個ID 20字元）
            $table->string('path', 500)->nullable()->after('parent_id')
                  ->comment('Materialized Path: 從根節點到當前節點的完整路徑，格式: /1/3/5/');
        });

        // ── 修改：使用 Laravel Schema Builder 建立索引，確保資料庫相容性
        Schema::table('product_categories', function (Blueprint $table) {
            // 使用 Laravel 原生方法建立索引，自動處理不同資料庫的語法差異
            $table->index('path', 'idx_product_categories_path');
            $table->index(['path', 'status'], 'idx_product_categories_path_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            // 刪除索引
            $table->dropIndex('idx_product_categories_path_status');
            $table->dropIndex('idx_product_categories_path');
            
            // 刪除欄位
            $table->dropColumn('path');
        });
    }
};
