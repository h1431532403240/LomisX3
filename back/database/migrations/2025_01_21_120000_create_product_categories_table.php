<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 商品分類資料表 Migration
 * 建立完整的階層式商品分類系統，支援巢狀結構、軟刪除、SEO 等企業級功能
 */
return new class extends Migration
{
    /**
     * 執行 migration
     */
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            // 主鍵
            $table->id()->comment('主鍵');

            // 基本資訊
            $table->string('name', 100)->comment('分類名稱');
            $table->string('slug', 100)->unique()->comment('URL 別名（唯一）');

            // 階層結構
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('product_categories')
                ->cascadeOnDelete()
                ->comment('上層分類 ID');

            // 排序與狀態
            $table->integer('position')->default(0)->comment('排序用（預設 0）');
            $table->boolean('status')->default(true)->comment('是否啟用（預設 true）');
            $table->integer('depth')->default(0)->comment('層級深度（第幾層，從 0 開始）');

            // 描述與 SEO
            $table->text('description')->nullable()->comment('分類描述');
            $table->string('meta_title', 100)->nullable()->comment('SEO 標題');
            $table->string('meta_description', 255)->nullable()->comment('SEO 描述');

            // 時間戳記
            $table->timestamps();

            // 軟刪除
            $table->softDeletes();

            // 索引設計
            $table->index(['parent_id', 'position', 'status'], 'idx_category_hierarchy');
            $table->index(['slug', 'deleted_at'], 'idx_category_slug');
            $table->index(['depth', 'status'], 'idx_category_depth');
            $table->index('status');
            $table->index('position');
        });
    }

    /**
     * 回滾 migration
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
