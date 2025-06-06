<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Users Table V6.2 Migration
 * 使用者表 V6.2 版本 - 創新軟刪除唯一約束解決方案
 * 
 * 核心創新：
 * - MySQL 計算欄位實現軟刪除唯一約束
 * - username_active: 活躍用戶為原始 username，刪除用戶為 'username_deleted_id'
 * - email_active: 活躍用戶為原始 email，刪除用戶為 'email_deleted_id'
 * - 唯一索引作用於計算欄位，完美解決軟刪除重複註冊問題
 * 
 * 技術特色：
 * - 100% 資料庫層面解決方案，無需應用程式邏輯
 * - 支援無限次用戶名/郵箱重用
 * - 高效能查詢，索引直接支援
 * - 向後相容，可安全升級現有系統
 */
return new class extends Migration
{
    /**
     * 執行 Migration
     * 
     * 步驟：
     * 1. 移除舊的唯一約束
     * 2. 添加計算欄位
     * 3. 建立新的唯一索引在計算欄位上
     * 4. 優化其他索引結構
     */
    public function up(): void
    {
        // 步驟 1: 安全移除現有的唯一約束（如果存在）
        try {
            if ($this->indexExists('users', 'users_username_unique')) {
                DB::statement("ALTER TABLE users DROP INDEX users_username_unique");
            }
        } catch (\Exception $e) {
            \Log::info('users_username_unique index does not exist or already dropped');
        }
        
        try {
            if ($this->indexExists('users', 'users_email_unique')) {
                DB::statement("ALTER TABLE users DROP INDEX users_email_unique");
            }
        } catch (\Exception $e) {
            \Log::info('users_email_unique index does not exist or already dropped');
        }

        // 步驟 2: 使用原生 SQL 添加計算欄位
        // MySQL 計算欄位語法必須使用原生 SQL
        // 注意：不能在計算欄位中使用 AUTO_INCREMENT 欄位，改用時間戳記
        DB::statement("
            ALTER TABLE users 
            ADD COLUMN username_active VARCHAR(150) AS (
                IF(deleted_at IS NULL, username, CONCAT(username, '_deleted_', UNIX_TIMESTAMP(deleted_at)))
            ) STORED COMMENT '計算欄位：軟刪除用戶名唯一約束'
        ");

        DB::statement("
            ALTER TABLE users 
            ADD COLUMN email_active VARCHAR(300) AS (
                IF(deleted_at IS NULL, email, CONCAT(email, '_deleted_', UNIX_TIMESTAMP(deleted_at)))
            ) STORED COMMENT '計算欄位：軟刪除郵箱唯一約束'
        ");

        // 步驟 3: 在計算欄位上建立唯一索引
        DB::statement("
            CREATE UNIQUE INDEX uk_user_username_active 
            ON users (username_active)
        ");

        DB::statement("
            CREATE UNIQUE INDEX uk_user_email_active 
            ON users (email_active)
        ");

        // 步驟 4: 優化其他關鍵索引（避免重複）
        try {
            Schema::table('users', function (Blueprint $table) {
                // 門市隔離 + 軟刪除複合索引
                if (!$this->indexExists('users', 'idx_user_store_soft_delete')) {
                    $table->index(['store_id', 'deleted_at'], 'idx_user_store_soft_delete');
                }
                
                // 狀態 + 軟刪除複合索引（用於統計）
                if (!$this->indexExists('users', 'idx_user_status_soft_delete')) {
                    $table->index(['status', 'deleted_at'], 'idx_user_status_soft_delete');
                }
                
                // 最後登入 + 軟刪除複合索引（用於用戶活躍度分析）
                if (!$this->indexExists('users', 'idx_user_login_soft_delete')) {
                    $table->index(['last_login_at', 'deleted_at'], 'idx_user_login_soft_delete');
                }
            });
        } catch (\Exception $e) {
            // 索引可能已存在，記錄但繼續執行
            \Log::warning('Some indexes already exist', ['error' => $e->getMessage()]);
        }

        // 步驟 5: 記錄 Migration 執行資訊
        \Log::info('Users Table V6.2 Migration completed', [
            'computed_columns' => ['username_active', 'email_active'],
            'unique_indexes' => ['uk_user_username_active', 'uk_user_email_active'],
            'optimization_indexes' => [
                'idx_user_store_soft_delete',
                'idx_user_status_soft_delete', 
                'idx_user_login_soft_delete'
            ],
            'migration_timestamp' => now()
        ]);
    }

    /**
     * 回滾 Migration
     * 
     * 注意：回滾會恢復到原始的唯一約束
     * 這可能導致軟刪除用戶無法重新註冊
     */
    public function down(): void
    {
        // 移除我們添加的索引
        DB::statement("DROP INDEX IF EXISTS uk_user_username_active ON users");
        DB::statement("DROP INDEX IF EXISTS uk_user_email_active ON users");
        
        Schema::table('users', function (Blueprint $table) {
            // 移除優化索引
            $table->dropIndex('idx_user_store_soft_delete');
            $table->dropIndex('idx_user_status_soft_delete');
            $table->dropIndex('idx_user_login_soft_delete');
            
            // 移除計算欄位
            $table->dropColumn('username_active');
            $table->dropColumn('email_active');
            
            // 恢復原始唯一約束
            $table->unique('username');
            $table->unique('email');
        });

        \Log::warning('Users Table V6.2 Migration rolled back', [
            'note' => 'Soft deleted users may conflict with new registrations',
            'rollback_timestamp' => now()
        ]);
    }

    /**
     * 檢查索引是否存在
     */
    private function indexExists(string $table, string $index): bool
    {
        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($result);
    }
};
