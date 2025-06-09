<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{User, Store};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};
// ✅ SRP 修復：移除 Role 引用，UserSeeder 不再管理角色

/**
 * 使用者種子檔案
 * 
 * 建立 LomisX3 系統所需的使用者資料
 * 包含各種角色的示範使用者，方便開發和測試
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */
class UserSeeder extends Seeder
{
    /**
     * 預設密碼
     * 生產環境請務必修改
     * 
     * @var string
     */
    private string $defaultPassword = 'password123';

    /**
     * 使用者資料定義
     * 包含不同角色和門市的示範使用者
     * 
     * @var array<string, array<string, mixed>>
     */
    private array $userData = [
        // 系統管理員
        'system_admin' => [
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@lomisx3.com',
            'role' => 'admin',
            'store_code' => 'HQ001', // 總公司
            'avatar' => 'avatars/admin.jpg',
            'description' => '系統超級管理員，擁有所有權限',
            'settings' => [
                'language' => 'zh-TW',
                'timezone' => 'Asia/Taipei',
                'notifications' => true,
                'theme' => 'light',
            ],
        ],
        
        // 總公司管理人員
        'hq_manager' => [
            'username' => 'hq.manager',
            'name' => '總部經理',
            'email' => 'hq.manager@lomisx3.com',
            'role' => 'store_admin',
            'store_code' => 'HQ001',
            'description' => '總公司營運管理經理',
        ],
        
        // 區域經理
        'north_manager' => [
            'username' => 'north.manager',
            'name' => '北區經理',
            'email' => 'north.manager@lomisx3.com',
            'role' => 'store_admin',
            'store_code' => 'NOR001',
            'description' => '北區營運中心經理',
        ],
        'central_manager' => [
            'username' => 'central.manager',
            'name' => '中區經理',
            'email' => 'central.manager@lomisx3.com',
            'role' => 'store_admin',
            'store_code' => 'CEN001',
            'description' => '中區營運中心經理',
        ],
        'south_manager' => [
            'username' => 'south.manager',
            'name' => '南區經理',
            'email' => 'south.manager@lomisx3.com',
            'role' => 'store_admin',
            'store_code' => 'SOU001',
            'description' => '南區營運中心經理',
        ],
        
        // 門市店長
        'taipei_manager' => [
            'username' => 'taipei.manager',
            'name' => '台北店長',
            'email' => 'taipei.manager@lomisx3.com',
            'role' => 'manager',
            'store_code' => 'TP001',
            'description' => '台北旗艦店店長',
        ],
        'xinyi_manager' => [
            'username' => 'xinyi.manager',
            'name' => '信義店長',
            'email' => 'xinyi.manager@lomisx3.com',
            'role' => 'manager',
            'store_code' => 'TP002',
            'description' => '信義分店店長',
        ],
        'taichung_manager' => [
            'username' => 'taichung.manager',
            'name' => '台中店長',
            'email' => 'taichung.manager@lomisx3.com',
            'role' => 'manager',
            'store_code' => 'TC001',
            'description' => '台中逢甲店店長',
        ],
        'kaohsiung_manager' => [
            'username' => 'kaohsiung.manager',
            'name' => '高雄店長',
            'email' => 'kaohsiung.manager@lomisx3.com',
            'role' => 'manager',
            'store_code' => 'KH001',
            'description' => '高雄夢時代店店長',
        ],
        
        // 一般員工
        'taipei_staff1' => [
            'username' => 'taipei.staff1',
            'name' => '台北員工A',
            'email' => 'taipei.staff1@lomisx3.com',
            'role' => 'staff',
            'store_code' => 'TP001',
            'description' => '台北旗艦店銷售員工',
        ],
        'taipei_staff2' => [
            'username' => 'taipei.staff2',
            'name' => '台北員工B',
            'email' => 'taipei.staff2@lomisx3.com',
            'role' => 'staff',
            'store_code' => 'TP001',
            'description' => '台北旗艦店庫存員工',
        ],
        'xinyi_staff' => [
            'username' => 'xinyi.staff',
            'name' => '信義員工',
            'email' => 'xinyi.staff@lomisx3.com',
            'role' => 'staff',
            'store_code' => 'TP002',
            'description' => '信義分店銷售員工',
        ],
        'taichung_staff' => [
            'username' => 'taichung.staff',
            'name' => '台中員工',
            'email' => 'taichung.staff@lomisx3.com',
            'role' => 'staff',
            'store_code' => 'TC001',
            'description' => '台中逢甲店銷售員工',
        ],
        
        // 測試用戶
        'test_user' => [
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'role' => 'guest',
            'store_code' => 'TP001',
            'description' => '系統測試專用帳號',
        ],
        
        // 示範訪客
        'demo_guest' => [
            'username' => 'demo.guest',
            'name' => '示範訪客',
            'email' => 'demo@lomisx3.com',
            'role' => 'guest',
            'store_code' => 'TP001',
            'description' => '系統展示用訪客帳號',
        ],
    ];

    /**
     * 執行使用者種子資料建立
     * 
     * @return void
     */
    public function run(): void
    {
        $this->command->info('👥 開始建立使用者種子資料...');
        
        // 檢查必要的門市是否存在
        $this->validateStoresExist();
        
        // ✅ SRP 修復：移除角色驗證，UserSeeder 不再負責角色管理
        // 原：$this->validateRolesExist(); // 已移除
        
        DB::transaction(function () {
            $this->createUsers();
        });
        
        $this->command->info('✅ 使用者種子資料建立完成！');
        $this->printSummary();
    }

    /**
     * 驗證門市是否存在
     * 
     * @return void
     */
    private function validateStoresExist(): void
    {
        $this->command->info('🔍 檢查門市資料...');
        
        $requiredStoreCodes = array_unique(array_column($this->userData, 'store_code'));
        $existingStoreCodes = Store::whereIn('code', $requiredStoreCodes)->pluck('code')->toArray();
        $missingStoreCodes = array_diff($requiredStoreCodes, $existingStoreCodes);
        
        if (!empty($missingStoreCodes)) {
            $this->command->error('❌ 缺少必要的門市資料，請先執行 StoreSeeder:');
            foreach ($missingStoreCodes as $code) {
                $this->command->line("  - {$code}");
            }
            throw new \Exception('請先執行 php artisan db:seed --class=StoreSeeder');
        }
        
        $this->command->info("  ✅ 已找到 " . count($existingStoreCodes) . " 個必要門市");
    }

    // ✅ SRP 修復：移除角色驗證方法，UserSeeder 不再負責角色管理

    /**
     * 建立使用者資料
     * 
     * @return void
     */
    private function createUsers(): void
    {
        $this->command->info('👤 建立使用者帳號...');
        
        foreach ($this->userData as $userKey => $userData) {
            $store = Store::where('code', $userData['store_code'])->first();
            
            $this->command->line("  建立使用者: {$userData['name']} ({$userData['username']})");
            
            // 建立或更新使用者 - 只負責用戶實體創建，不分配角色
            $user = User::updateOrCreate(
                [
                    'username' => $userData['username'],
                    'store_id' => $store->id,
                ],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($this->defaultPassword),
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'phone' => $this->generatePhoneNumber(),
                    'last_login_at' => null,
                    'last_login_ip' => null,
                    'login_attempts' => 0,
                    'locked_until' => null,
                    'preferences' => json_encode($this->getDefaultSettings()),
                    'created_by' => 1, // 假設系統管理員ID為1
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            // ✅ SRP 修復：移除角色分配邏輯，由 RoleAndPermissionSeeder 統一管理
            // 原：$user->syncRoles([$role]); // 已移除
            
            $this->command->line("    ✅ 用戶已創建 | 門市: {$store->name}");
        }
    }

    /**
     * 產生隨機手機號碼
     * 
     * @return string
     */
    private function generatePhoneNumber(): string
    {
        $prefixes = ['0912', '0911', '0988', '0987', '0928', '0929'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        return $prefix . $suffix;
    }

    /**
     * 產生隨機生日
     * 
     * @return string
     */
    private function generateBirthday(): string
    {
        $year = rand(1970, 2000);
        $month = rand(1, 12);
        $day = rand(1, 28); // 使用28避免月份天數問題
        
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * 取得預設使用者設定
     * 
     * @return array<string, mixed>
     */
    private function getDefaultSettings(): array
    {
        return [
            'language' => 'zh-TW',
            'timezone' => 'Asia/Taipei',
            'notifications' => true,
            'theme' => 'light',
            'dashboard_widgets' => ['stats', 'recent_activities', 'quick_actions'],
        ];
    }

    /**
     * 顯示建立摘要
     * 
     * @return void
     */
    private function printSummary(): void
    {
        $totalUsers = User::count();
        
        $usersByStore = User::join('stores', 'users.store_id', '=', 'stores.id')
            ->selectRaw('stores.name, COUNT(*) as count')
            ->groupBy('stores.name')
            ->pluck('count', 'name')
            ->toArray();
        
        $this->command->info('');
        $this->command->info('📊 使用者系統摘要:');
        $this->command->table(
            ['項目', '數量', '說明'],
            [
                ['使用者總數', $totalUsers, '完整組織使用者（角色由 RoleAndPermissionSeeder 分配）'],
            ]
        );
        
        $this->command->info('');
        $this->command->info('🏢 門市使用者分布:');
        foreach ($usersByStore as $storeName => $count) {
            $this->command->line("  {$storeName}: {$count} 位使用者");
        }
        
        $this->command->info('');
        $this->command->info('🔑 重要帳號資訊:');
        $this->command->table(
            ['使用者名稱', '帳號', '門市', '說明'],
            [
                ['系統管理員', 'admin', 'LomisX3 總公司', '創始管理員（角色由 RoleAndPermissionSeeder 分配）'],
                ['北區經理', 'north.manager', '北區營運中心', '區域管理'],
                ['台北店長', 'taipei.manager', '台北旗艦店', '門市管理'],
                ['台北員工A', 'taipei.staff1', '台北旗艦店', '日常營運'],
                ['測試使用者', 'testuser', '台北旗艦店', '系統測試'],
            ]
        );
        
        $this->command->info('');
        $this->command->warn('⚠️  重要安全提醒:');
        $this->command->warn("   📌 預設密碼: {$this->defaultPassword}");
        $this->command->warn('   📌 生產環境請立即修改所有預設密碼！');
        $this->command->warn('   📌 建議啟用雙因子驗證 (2FA)');
        $this->command->warn('   📌 定期檢查使用者權限和活動記錄');
        
        $this->command->info('');
        $this->command->info('🚀 快速登入測試:');
        $this->command->line('   php artisan tinker');
        $this->command->line('   User::where("username", "admin")->first()');
        $this->command->line('   Auth::login($user)');
    }
} 