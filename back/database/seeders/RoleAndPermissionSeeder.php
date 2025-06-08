<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Role, Permission};

/**
 * 角色權限種子檔案
 * 
 * 建立 LomisX3 系統所需的完整角色權限架構
 * 支援多租戶門市隔離和企業級權限控制
 * 
 * @author LomisX3 開發團隊
 * @version 5.1.0 - 新增跨域操作權限架構
 */
class RoleAndPermissionSeeder extends Seeder
{
    /**
     * 系統權限定義
     * 按模組分組，方便管理和擴展
     * 
     * @var array<string, array<string>>
     */
    private array $permissions = [
        // 使用者管理模組
        'users' => [
            'users.view',           // 檢視使用者
            'users.create',         // 建立使用者
            'users.update',         // 更新使用者
            'users.delete',         // 刪除使用者
            'users.batch-status',   // 批次狀態更新
            'users.reset-password', // 重設密碼
            'users.view_statistics',// 檢視統計資料
            'users.view_security_stats', // 檢視安全統計
            'users.manage_2fa',     // 管理雙因子驗證
            'users.impersonate',    // 使用者模擬登入
        ],
        
        // 角色權限模組
        'roles' => [
            'roles.view',           // 檢視角色
            'roles.create',         // 建立角色
            'roles.update',         // 更新角色
            'roles.delete',         // 刪除角色
            'roles.assign',         // 指派角色
        ],
        
        // 門市管理模組
        'stores' => [
            'stores.view',          // 檢視門市
            'stores.create',        // 建立門市
            'stores.update',        // 更新門市
            'stores.delete',        // 刪除門市
            'stores.view_all',      // 檢視所有門市 (跨門市權限)
        ],
        
        // 商品分類模組
        'categories' => [
            'categories.view',      // 檢視分類
            'categories.create',    // 建立分類
            'categories.update',    // 更新分類
            'categories.delete',    // 刪除分類
            'categories.batch',     // 批次操作
            'categories.statistics',// 檢視統計
        ],
        
        // 系統管理模組
        'system' => [
            'system.view_logs',     // 檢視系統日誌
            'system.manage_cache',  // 管理快取
            'system.view_metrics',  // 檢視系統指標
            'system.backup',        // 系統備份
            'system.maintenance',   // 系統維護
            'system.operate_across_stores', // 跨域操作權限（語義清晰的超級權限）
        ],
    ];

    /**
     * 角色定義
     * 包含角色名稱、描述和對應權限
     * 
     * @var array<string, array<string, mixed>>
     */
    private array $roles = [
        'super_admin' => [
            'display_name' => '超級管理員',
            'description' => '系統最高權限，繞過所有權限檢查，僅限開發者和最高擁有者使用',
            'permissions' => 'all', // 特殊標記：擁有所有權限
        ],
        'admin' => [
            'display_name' => '系統管理員',
            'description' => '擁有系統所有權限，可跨門市操作',
            'permissions' => 'all', // 特殊標記：擁有所有權限
        ],
        'store_admin' => [
            'display_name' => '門市管理員',
            'description' => '門市管理權限，限制在所屬門市範圍內',
            'permissions' => [
                'users.view', 'users.create', 'users.update', 'users.reset-password',
                'roles.view', 'roles.assign',
                'stores.view', 'stores.update',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
                'system.view_logs',
            ],
        ],
        'manager' => [
            'display_name' => '部門主管',
            'description' => '部門管理權限，可管理部門內使用者',
            'permissions' => [
                'users.view', 'users.update', 'users.reset-password',
                'categories.view', 'categories.create', 'categories.update',
                'stores.view',
            ],
        ],
        'staff' => [
            'display_name' => '一般員工',
            'description' => '基本操作權限，日常業務使用',
            'permissions' => [
                'users.view',
                'categories.view',
                'stores.view',
            ],
        ],
        'guest' => [
            'display_name' => '訪客',
            'description' => '最低權限，僅可檢視基本資訊',
            'permissions' => [
                'categories.view',
            ],
        ],
    ];

    /**
     * 執行角色權限種子資料建立
     * 
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🚀 開始建立角色權限種子資料...');
        
        // 1. 重設快取
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // 2. 建立權限
        $this->createPermissions();
        
        // 3. 建立角色
        $this->createRoles();
        
        // 4. 指派權限給角色
        $this->assignPermissionsToRoles();
        
        // 5. 分配超級管理員角色給第一個使用者
        $this->assignSuperAdminRole();
        
        $this->command->info('✅ 角色權限種子資料建立完成！');
        $this->printSummary();
    }

    /**
     * 建立系統權限
     * 
     * @return void
     */
    private function createPermissions(): void
    {
        $this->command->info('📋 建立系統權限...');
        
        $totalPermissions = 0;
        
        foreach ($this->permissions as $module => $modulePermissions) {
            $this->command->line("  建立 {$module} 模組權限...");
            
            foreach ($modulePermissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission],
                    [
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $totalPermissions++;
            }
        }
        
        $this->command->info("  ✅ 共建立 {$totalPermissions} 個權限");
    }

    /**
     * 建立系統角色
     * 
     * @return void
     */
    private function createRoles(): void
    {
        $this->command->info('👥 建立系統角色...');
        
        foreach ($this->roles as $roleName => $roleData) {
            Role::firstOrCreate(
                ['name' => $roleName],
                [
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            $this->command->line("  ✅ 角色: {$roleData['display_name']} ({$roleName})");
        }
    }

    /**
     * 指派權限給角色
     * 
     * @return void
     */
    private function assignPermissionsToRoles(): void
    {
        $this->command->info('🔗 指派權限給角色...');
        
        foreach ($this->roles as $roleName => $roleData) {
            $role = Role::findByName($roleName);
            
            if ($roleData['permissions'] === 'all') {
                // 系統管理員擁有所有權限
                $allPermissions = Permission::all();
                $role->syncPermissions($allPermissions);
                $this->command->line("  ✅ {$roleData['display_name']}: 所有權限 ({$allPermissions->count()} 個)");
            } else {
                // 其他角色指派特定權限
                $role->syncPermissions($roleData['permissions']);
                $permissionCount = count($roleData['permissions']);
                $this->command->line("  ✅ {$roleData['display_name']}: {$permissionCount} 個權限");
            }
        }
    }

    /**
     * 分配超級管理員角色給創始管理員
     * 
     * @return void
     */
    private function assignSuperAdminRole(): void
    {
        $this->command->info('👑 分配超級管理員角色...');
        
        // ✅ SRP 修復：使用穩定的 email 查找，替代脆弱的 User::find(1)
        $superAdminUser = \App\Models\User::where('email', 'admin@lomisx3.com')->first();
        
        if ($superAdminUser) {
            // 使用 syncRoles 確保角色唯一且正確
            $superAdminUser->syncRoles(['super_admin']);
            $this->command->line("  ✅ 使用者 '{$superAdminUser->name}' ({$superAdminUser->email}) 已設為超級管理員");
        } else {
            $this->command->warn("  ⚠️  找不到 admin@lomisx3.com 使用者，請確認 UserSeeder 已正確執行");
        }
        
        // 額外為其他用戶分配預設角色（基於 email 識別）
        $this->assignDefaultRoles();
    }

    /**
     * 為其他用戶分配預設角色
     * 
     * @return void
     */
    private function assignDefaultRoles(): void
    {
        $this->command->info('🔗 分配其他使用者角色...');
        
        $userRoleMap = [
            'north.manager@lomisx3.com' => 'store_admin',
            'taipei.manager@lomisx3.com' => 'manager',
            'taipei.staff1@lomisx3.com' => 'staff',
            'testuser@lomisx3.com' => 'guest',
        ];
        
        foreach ($userRoleMap as $email => $role) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles([$role]);
                $this->command->line("  ✅ {$user->name} ({$email}) → {$role}");
            }
        }
    }

    /**
     * 顯示建立摘要
     * 
     * @return void
     */
    private function printSummary(): void
    {
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        
        $this->command->info('');
        $this->command->info('📊 角色權限系統摘要:');
        $this->command->table(
            ['項目', '數量', '說明'],
            [
                ['權限總數', $totalPermissions, '涵蓋所有功能模組'],
                ['角色總數', $totalRoles, '企業級階層架構'],
                ['模組數量', count($this->permissions), '模組化權限管理'],
            ]
        );
        
        $this->command->info('');
        $this->command->info('🎯 角色階層架構:');
        foreach ($this->roles as $roleName => $roleData) {
            $this->command->line("  {$roleData['display_name']} ({$roleName}): {$roleData['description']}");
        }
        
        $this->command->info('');
        $this->command->warn('⚠️  預設密碼為 "password123"，請在生產環境中修改！');
    }
} 