<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 主要資料庫種子檔案
 * 
 * 統一管理 LomisX3 系統的所有種子資料
 * 按照正確順序執行各種子檔案，確保資料完整性
 * 
 * @author LomisX3 開發團隊
 * @version 2.0.0
 */
class DatabaseSeeder extends Seeder
{
    /**
     * 執行資料庫種子資料建立
     * 
     * 執行順序說明：
     * 1. 角色權限系統 - 建立權限和角色架構
     * 2. 門市系統 - 建立組織架構和門市資料
     * 3. 使用者系統 - 建立使用者並指派角色
     * 4. 示範資料 - 建立商品分類等業務資料
     * 
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🚀 LomisX3 系統種子資料建立開始...');
        $this->command->info('═══════════════════════════════════════');
        
        $startTime = microtime(true);
        
        try {
            // 第一階段：基礎架構
            $this->command->info('');
            $this->command->info('📋 第一階段：建立基礎架構');
            $this->command->info('─────────────────────────────');
            
            $this->call(RoleAndPermissionSeeder::class);
            
            // 第二階段：組織架構
            $this->command->info('');
            $this->command->info('🏢 第二階段：建立組織架構');
            $this->command->info('─────────────────────────────');
            
            $this->call(StoreSeeder::class);
            
            // 第三階段：使用者系統
            $this->command->info('');
            $this->command->info('👥 第三階段：建立使用者系統');
            $this->command->info('─────────────────────────────');
            
            $this->call(UserSeeder::class);
            
            // 第四階段：示範資料
            $this->command->info('');
            $this->command->info('🎯 第四階段：建立示範資料');
            $this->command->info('─────────────────────────────');
            
            $this->call(DemoDataSeeder::class);
            
            // 完成統計
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            $this->printCompletionSummary($duration);
            
        } catch (\Exception $e) {
            $this->command->error('');
            $this->command->error('❌ 種子資料建立失敗！');
            $this->command->error("錯誤訊息: {$e->getMessage()}");
            $this->command->error('');
            $this->command->error('🔧 解決方案：');
            $this->command->error('1. 檢查資料庫連線是否正常');
            $this->command->error('2. 確認 Migration 是否已執行：php artisan migrate');
            $this->command->error('3. 檢查 Spatie Permission 套件是否已發布：php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"');
            $this->command->error('4. 重置資料庫後重新執行：php artisan migrate:fresh --seed');
            
            throw $e;
        }
    }

    /**
     * 顯示完成摘要
     * 
     * @param float $duration 執行時間（秒）
     * @return void
     */
    private function printCompletionSummary(float $duration): void
    {
        $this->command->info('');
        $this->command->info('🎉 LomisX3 系統種子資料建立完成！');
        $this->command->info('═══════════════════════════════════════');
        
        // 統計資料
        $stats = $this->getSystemStats();
        
        $this->command->table(
            ['系統模組', '資料數量', '狀態', '說明'],
            [
                ['權限系統', $stats['permissions'] . ' 個權限', '✅ 完成', '模組化權限管理'],
                ['角色系統', $stats['roles'] . ' 個角色', '✅ 完成', '企業級階層架構'],
                ['門市系統', $stats['stores'] . ' 個門市', '✅ 完成', '樹狀組織架構'],
                ['使用者系統', $stats['users'] . ' 位使用者', '✅ 完成', '完整角色指派'],
                ['商品分類', $stats['categories'] . ' 個分類', '✅ 完成', '多門市示範資料'],
            ]
        );
        
        $this->command->info('');
        $this->command->info("⏱️  總執行時間: {$duration} 秒");
        $this->command->info('');
        
        // 重要提醒
        $this->command->warn('🔐 重要安全提醒:');
        $this->command->warn('   📌 預設管理員帳號: admin');
        $this->command->warn('   📌 預設密碼: password123');
        $this->command->warn('   📌 生產環境請立即修改預設密碼！');
        
        $this->command->info('');
        $this->command->info('🚀 快速開始:');
        $this->command->line('   1. 啟動後端服務: cd back && php artisan serve');
        $this->command->line('   2. 啟動前端服務: cd front && npm run dev');
        $this->command->line('   3. 使用 admin / password123 登入系統');
        $this->command->line('   4. 瀏覽示範資料和功能模組');
        
        $this->command->info('');
        $this->command->info('📚 開發參考:');
        $this->command->line('   - 架構文檔: LOMIS_X3_專案架構標準手冊.md');
        $this->command->line('   - 測試指令: php artisan test');
        $this->command->line('   - 快取清除: php artisan cache:clear');
        $this->command->line('   - 重新種子: php artisan migrate:fresh --seed');
        
        $this->command->info('');
        $this->command->info('🎯 系統功能測試建議:');
        $this->command->line('   ✅ 登入系統並檢查儀表板');
        $this->command->line('   ✅ 測試使用者管理功能');
        $this->command->line('   ✅ 測試權限控制機制');
        $this->command->line('   ✅ 測試商品分類管理');
        $this->command->line('   ✅ 測試門市隔離功能');
        
        $this->command->info('');
        $this->command->success('🎊 歡迎使用 LomisX3 企業級管理系統！');
    }

    /**
     * 取得系統統計資料
     * 
     * @return array<string, int>
     */
    private function getSystemStats(): array
    {
        try {
            return [
                'permissions' => \Spatie\Permission\Models\Permission::count(),
                'roles' => \Spatie\Permission\Models\Role::count(),
                'stores' => \App\Models\Store::count(),
                'users' => \App\Models\User::count(),
                'categories' => \App\Models\ProductCategory::count(),
            ];
        } catch (\Exception $e) {
            // 如果某些模型不存在，返回預設值
            return [
                'permissions' => 0,
                'roles' => 0,
                'stores' => 0,
                'users' => 0,
                'categories' => 0,
            ];
        }
    }
}
