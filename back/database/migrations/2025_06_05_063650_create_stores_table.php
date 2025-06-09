<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 建立門市表
     * 支援多層次門市架構與基本門市管理功能
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('門市名稱');
            $table->string('code', 20)->unique()->comment('門市代碼');
            $table->text('description')->nullable()->comment('門市描述');
            $table->string('address')->nullable()->comment('門市地址');
            $table->string('phone', 20)->nullable()->comment('聯絡電話');
            $table->string('email', 100)->nullable()->comment('聯絡信箱');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('門市狀態');
            $table->json('settings')->nullable()->comment('門市設定');
            
            // 樹狀結構支援
            $table->foreignId('parent_id')->nullable()->constrained('stores')->onDelete('set null')->comment('上層門市ID');
            $table->integer('sort_order')->default(0)->comment('排序順序');
            
            // 審計欄位
            $table->foreignId('created_by')->nullable()->comment('建立者');
            $table->foreignId('updated_by')->nullable()->comment('更新者');
            $table->timestamps();
            $table->softDeletes();
            
            // 索引
            $table->index(['parent_id', 'sort_order']);
            $table->index(['status', 'created_at']);
            $table->index(['created_by', 'updated_by']);
        });
    }

    /**
     * 回滾遷移
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
