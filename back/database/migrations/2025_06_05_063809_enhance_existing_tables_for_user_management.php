<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 增強現有表以支援使用者管理模組 V6.2
     * 包含門市維度擴展、Token 效能優化、媒體可見性等功能
     */
    public function up(): void
    {
        // 擴展 activity_log 表 - 門市維度支援
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->foreignId('store_id')->nullable()->after('causer_id')->comment('門市ID');
                
                // 索引優化
                $table->index(['store_id', 'created_at'], 'idx_activity_store');
                $table->index(['causer_type', 'causer_id', 'store_id'], 'idx_activity_causer_store');
            });
        }
        
        // 擴展 personal_access_tokens 表 - 效能與門市支援
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->foreignId('store_id')->nullable()->after('tokenable_id')->comment('門市ID');
                
                // V6.2 優化：建立過期標記計算欄位
                $table->tinyInteger('expires_flag')->storedAs('(expires_at IS NOT NULL)')->after('expires_at')->comment('過期標記');
                
                // 索引優化
                $table->index(['store_id'], 'idx_token_store');
                $table->index(['expires_flag', 'expires_at'], 'idx_token_active');
                $table->index(['tokenable_type', 'tokenable_id', 'store_id'], 'idx_token_owner_store');
            });
        }
        
        // 擴展 media 表 - 可見性與門市支援
        if (Schema::hasTable('media')) {
            Schema::table('media', function (Blueprint $table) {
                $table->enum('visibility', ['public', 'private'])->default('public')->after('custom_properties')->comment('可見性');
                $table->foreignId('store_id')->nullable()->after('visibility')->comment('門市ID');
                
                // V6.2 優化索引
                $table->index(['store_id', 'visibility'], 'idx_media_store_visibility');
                $table->index(['model_type', 'model_id', 'collection_name', 'visibility'], 'idx_media_model_collection');
                $table->index(['created_at', 'store_id'], 'idx_media_created_store');
            });
        }
        
        // 權限表團隊支援 (如果使用 teams)
        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('model_has_permissions', 'team_id')) {
                    $table->unsignedBigInteger('team_id')->nullable()->after('permission_id')->comment('團隊ID (門市ID)');
                    $table->index(['team_id'], 'idx_model_permissions_team');
                }
            });
        }
        
        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                if (!Schema::hasColumn('model_has_roles', 'team_id')) {
                    $table->unsignedBigInteger('team_id')->nullable()->after('role_id')->comment('團隊ID (門市ID)');
                    $table->index(['team_id'], 'idx_model_roles_team');
                }
            });
        }
    }

    /**
     * 回滾遷移
     */
    public function down(): void
    {
        // 回滾權限表變更
        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                if (Schema::hasColumn('model_has_roles', 'team_id')) {
                    $table->dropIndex('idx_model_roles_team');
                    $table->dropColumn('team_id');
                }
            });
        }
        
        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                if (Schema::hasColumn('model_has_permissions', 'team_id')) {
                    $table->dropIndex('idx_model_permissions_team');
                    $table->dropColumn('team_id');
                }
            });
        }
        
        // 回滾 media 表變更
        if (Schema::hasTable('media')) {
            Schema::table('media', function (Blueprint $table) {
                $table->dropIndex('idx_media_store_visibility');
                $table->dropIndex('idx_media_model_collection');
                $table->dropIndex('idx_media_created_store');
                $table->dropColumn(['visibility', 'store_id']);
            });
        }
        
        // 回滾 personal_access_tokens 表變更
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropIndex('idx_token_store');
                $table->dropIndex('idx_token_active');
                $table->dropIndex('idx_token_owner_store');
                $table->dropColumn(['store_id', 'expires_flag']);
            });
        }
        
        // 回滾 activity_log 表變更
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropIndex('idx_activity_store');
                $table->dropIndex('idx_activity_causer_store');
                $table->dropColumn('store_id');
            });
        }
    }
};
