<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * 為商品分類表添加多租戶門市隔離支援
 * 
 * 重要更新：
 * - 添加 store_id 欄位實現門市隔離
 * - 修改 slug 唯一約束為門市範圍內唯一
 * - 優化索引結構支援多租戶查詢
 * - 保持向後相容性
 */
return new class extends Migration
{
    /**
     * 執行 Migration
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            // 添加門市隔離欄位
            $table->foreignId('store_id')
                  ->after('id')
                  ->constrained('stores')
                  ->comment('所屬門市ID（多租戶隔離）');
            
            // 添加審計欄位
            $table->foreignId('created_by')->nullable()->after('meta_description')->comment('建立者ID');
            $table->foreignId('updated_by')->nullable()->after('created_by')->comment('更新者ID');
            
            // 添加其他企業級欄位
            $table->string('image')->nullable()->after('updated_by')->comment('分類圖片');
            $table->text('meta_keywords')->nullable()->after('image')->comment('SEO關鍵字');
        });

        // 移除原有的 slug 唯一約束
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        // 建立新的多租戶索引
        Schema::table('product_categories', function (Blueprint $table) {
            // 門市範圍內 slug 唯一
            $table->unique(['store_id', 'slug'], 'uk_category_store_slug');
            
            // 門市隔離索引
            $table->index(['store_id', 'status', 'position'], 'idx_category_store_status');
            $table->index(['store_id', 'parent_id', 'position'], 'idx_category_store_hierarchy');
            $table->index(['store_id', 'depth', 'status'], 'idx_category_store_depth');
            $table->index(['store_id', 'deleted_at'], 'idx_category_store_soft_delete');
            
            // 審計索引
            $table->index(['created_by', 'updated_by'], 'idx_category_audit');
        });

        // 為現有資料設置預設門市（如果有的話）
        $firstStoreId = DB::table('stores')->orderBy('id')->value('id');
        if ($firstStoreId) {
            DB::table('product_categories')->update(['store_id' => $firstStoreId]);
        }
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            // 移除多租戶索引
            $table->dropUnique('uk_category_store_slug');
            $table->dropIndex('idx_category_store_status');
            $table->dropIndex('idx_category_store_hierarchy');
            $table->dropIndex('idx_category_store_depth');
            $table->dropIndex('idx_category_store_soft_delete');
            $table->dropIndex('idx_category_audit');
            
            // 移除新增欄位
            $table->dropForeign(['store_id']);
            $table->dropColumn(['store_id', 'created_by', 'updated_by', 'image', 'meta_keywords']);
            
            // 恢復原始 slug 唯一約束
            $table->unique('slug');
        });
    }
}; 