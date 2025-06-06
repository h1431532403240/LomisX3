<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 增強使用者表以支援使用者管理模組 V6.2
     * 包含門市隔離、軟刪除唯一約束等企業級功能
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 門市關聯與基本資訊 (檢查是否已存在)
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->after('id')->comment('使用者名稱');
            }
            if (!Schema::hasColumn('users', 'store_id')) {
                $table->foreignId('store_id')->after('username')->constrained('stores')->comment('所屬門市ID');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email')->comment('聯絡電話');
            }
            
            // 使用者狀態管理
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'locked', 'pending'])
                      ->default('pending')->after('phone')->comment('使用者狀態');
            }
                      
            // 登入安全機制
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('email_verified_at')->comment('最後登入時間');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at')->comment('最後登入IP');
            }
            if (!Schema::hasColumn('users', 'login_attempts')) {
                $table->integer('login_attempts')->default(0)->after('last_login_ip')->comment('登入嘗試次數');
            }
            if (!Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('login_attempts')->comment('鎖定到期時間');
            }
            
            // 使用者偏好設定 (2FA 欄位已由 Fortify 處理)
            if (!Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable()->after('two_factor_confirmed_at')->comment('使用者偏好設定');
            }
            
            // 審計欄位
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('preferences')->comment('建立者ID');
            }
            if (!Schema::hasColumn('users', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->comment('更新者ID');
            }
            
            // 軟刪除
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
        
        // 建立基本索引
        Schema::table('users', function (Blueprint $table) {
            // 業務查詢索引
            if (!$this->indexExists('users', 'idx_user_store_status')) {
                $table->index(['store_id', 'status'], 'idx_user_store_status');
            }
            if (!$this->indexExists('users', 'idx_user_login_attempts')) {
                $table->index(['login_attempts', 'locked_until'], 'idx_user_login_attempts');
            }
            if (!$this->indexExists('users', 'idx_users_audit')) {
                $table->index(['created_by', 'updated_by'], 'idx_users_audit');
            }
            if (!$this->indexExists('users', 'idx_user_status_login')) {
                $table->index(['status', 'last_login_at'], 'idx_user_status_login');
            }
            if (!$this->indexExists('users', 'idx_user_soft_delete_store')) {
                $table->index(['deleted_at', 'store_id'], 'idx_user_soft_delete_store');
            }
            
            // 基本唯一約束 (不使用軟刪除，先建立基本約束)
            if (!$this->indexExists('users', 'users_username_unique')) {
                $table->unique('username', 'users_username_unique');
            }
        });
    }

    /**
     * 檢查索引是否存在
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'");
        return count($indexes) > 0;
    }

    /**
     * 回滾遷移
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 移除索引 (如果存在)
            if ($this->indexExists('users', 'users_username_unique')) {
                $table->dropUnique('users_username_unique');
            }
            if ($this->indexExists('users', 'idx_user_store_status')) {
                $table->dropIndex('idx_user_store_status');
            }
            if ($this->indexExists('users', 'idx_user_login_attempts')) {
                $table->dropIndex('idx_user_login_attempts');
            }
            if ($this->indexExists('users', 'idx_users_audit')) {
                $table->dropIndex('idx_users_audit');
            }
            if ($this->indexExists('users', 'idx_user_status_login')) {
                $table->dropIndex('idx_user_status_login');
            }
            if ($this->indexExists('users', 'idx_user_soft_delete_store')) {
                $table->dropIndex('idx_user_soft_delete_store');
            }
        });
        
        Schema::table('users', function (Blueprint $table) {
            // 移除新增欄位 (如果存在)
            $columnsToRemove = [
                'username', 'store_id', 'phone', 'status',
                'last_login_at', 'last_login_ip', 'login_attempts', 'locked_until',
                'preferences', 'created_by', 'updated_by', 'deleted_at'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
