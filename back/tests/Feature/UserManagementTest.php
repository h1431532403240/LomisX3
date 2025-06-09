<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Store};
use App\Enums\{UserStatus, UserRole, UserErrorCode};
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\{Role, Permission};
use Laravel\Sanctum\Sanctum;

/**
 * 使用者管理功能測試
 * 測試使用者 CRUD、權限控制、門市隔離等核心功能
 * 
 * 測試範圍：
 * - 使用者基礎 CRUD 操作
 * - 門市隔離驗證
 * - 權限檢查
 * - 批次操作
 * - 統計功能
 * - 錯誤處理
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Store $store1;
    protected Store $store2;
    protected User $adminUser;
    protected User $storeAdminUser;
    protected User $staffUser;

    /**
     * 測試前設定
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試門市
        $this->store1 = Store::create([
            'name' => '門市一',
            'code' => 'STORE001',
            'address' => '台北市信義區',
            'status' => 'active'
        ]);
        
        $this->store2 = Store::create([
            'name' => '門市二', 
            'code' => 'STORE002',
            'address' => '台中市西屯區',
            'status' => 'active'
        ]);

        // 建立角色和權限
        $this->createRolesAndPermissions();
        
        // 建立測試使用者
        $this->createTestUsers();
    }

    /**
     * 建立角色和權限
     */
    private function createRolesAndPermissions(): void
    {
        // 建立權限
        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'users.batch-status', 'users.reset-password', 'users.view_statistics',
            'users.view_security_stats'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 建立角色
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);

        $storeAdminRole = Role::create(['name' => 'store_admin']);
        $storeAdminRole->givePermissionTo(['users.view', 'users.create', 'users.update']);

        $staffRole = Role::create(['name' => 'staff']);
        $staffRole->givePermissionTo(['users.view']);
    }

    /**
     * 建立測試使用者
     */
    private function createTestUsers(): void
    {
        // 系統管理員 (可跨門市)
        $this->adminUser = User::create([
            'username' => 'admin001',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'store_id' => $this->store1->id,
            'status' => UserStatus::ACTIVE->value,
            'created_by' => 1
        ]);
        $this->adminUser->assignRole('admin');

        // 門市管理員 (限制門市)
        $this->storeAdminUser = User::create([
            'username' => 'store_admin001',
            'name' => '門市管理員',
            'email' => 'store_admin@example.com',
            'password' => bcrypt('password123'),
            'store_id' => $this->store1->id,
            'status' => UserStatus::ACTIVE->value,
            'created_by' => $this->adminUser->id
        ]);
        $this->storeAdminUser->assignRole('store_admin');

        // 一般員工
        $this->staffUser = User::create([
            'username' => 'staff001',
            'name' => '一般員工',
            'email' => 'staff@example.com', 
            'password' => bcrypt('password123'),
            'store_id' => $this->store1->id,
            'status' => UserStatus::ACTIVE->value,
            'created_by' => $this->storeAdminUser->id
        ]);
        $this->staffUser->assignRole('staff');
    }

    /**
     * 測試管理員可以取得所有門市的使用者列表
     */
    public function test_admin_can_view_all_stores_users(): void
    {
        // 在門市二建立使用者
        $store2User = User::create([
            'username' => 'store2_user',
            'name' => '門市二使用者',
            'email' => 'store2@example.com',
            'password' => bcrypt('password123'),
            'store_id' => $this->store2->id,
            'status' => UserStatus::ACTIVE->value
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/users');

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'data' => [
                             '*' => [
                                 'id', 'username', 'name', 'email',
                                 'store_id', 'status', 'roles'
                             ]
                         ],
                         'meta' => [
                             'statistics',
                             'status_distribution',
                             'role_distribution'
                         ]
                     ]
                 ]);

        // 管理員應能看到所有門市的使用者
        $users = $response->json('data.data');
        $this->assertGreaterThanOrEqual(4, count($users)); // 至少包含 4 個使用者
        
        // 檢查是否包含不同門市的使用者
        $storeIds = collect($users)->pluck('store_id')->unique();
        $this->assertContains($this->store1->id, $storeIds);
        $this->assertContains($this->store2->id, $storeIds);
    }

    /**
     * 測試門市管理員只能看到自己門市的使用者
     */
    public function test_store_admin_can_only_view_own_store_users(): void
    {
        // 在門市二建立使用者
        $store2User = User::create([
            'username' => 'store2_user',
            'name' => '門市二使用者',
            'email' => 'store2@example.com',
            'password' => bcrypt('password123'),
            'store_id' => $this->store2->id,
            'status' => UserStatus::ACTIVE->value
        ]);

        Sanctum::actingAs($this->storeAdminUser);

        $response = $this->getJson('/api/users');

        $response->assertOk();

        $users = $response->json('data.data');
        
        // 門市管理員應只能看到自己門市的使用者
        foreach ($users as $user) {
            $this->assertEquals($this->store1->id, $user['store_id']);
        }
        
        // 不應該看到門市二的使用者
        $userIds = collect($users)->pluck('id');
        $this->assertNotContains($store2User->id, $userIds);
    }

    /**
     * 測試建立使用者
     */
    public function test_create_user_with_valid_data(): void
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'username' => 'new_user001',
            'name' => '新使用者',
            'email' => 'new_user@example.com',
            'password' => 'ComplexP@ssw0rd!2024',
            'password_confirmation' => 'ComplexP@ssw0rd!2024',
            'store_id' => $this->store1->id,
            'phone' => '0912345678',
            'status' => UserStatus::ACTIVE->value,
            'roles' => ['staff']
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertCreated()
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id', 'username', 'name', 'email',
                         'store_id', 'status', 'roles'
                     ]
                 ]);

        // 檢查資料庫
        $this->assertDatabaseHas('users', [
            'username' => 'new_user001',
            'email' => 'new_user@example.com',
            'store_id' => $this->store1->id,
            'status' => UserStatus::ACTIVE->value
        ]);

        // 檢查角色分配
        $user = User::where('username', 'new_user001')->first();
        $this->assertTrue($user->hasRole('staff'));
    }

    /**
     * 測試使用者名稱重複驗證
     */
    public function test_create_user_with_duplicate_username(): void
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'username' => $this->staffUser->username, // 重複的使用者名稱
            'name' => '新使用者',
            'email' => 'unique@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'store_id' => $this->store1->id,
            'roles' => ['staff']
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['username']);
    }

    /**
     * 測試軟刪除使用者後可重複使用名稱
     */
    public function test_can_reuse_username_after_soft_delete(): void
    {
        Sanctum::actingAs($this->adminUser);

        $originalUsername = $this->staffUser->username;
        $originalEmail = $this->staffUser->email;

        // 軟刪除使用者
        $deleteResponse = $this->deleteJson("/api/users/{$this->staffUser->id}");
        $deleteResponse->assertOk();

        // 確認軟刪除
        $this->assertSoftDeleted('users', ['id' => $this->staffUser->id]);

        // 建立新使用者使用相同名稱
        $userData = [
            'username' => $originalUsername,
            'name' => '新使用者',
            'email' => 'new_unique@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'store_id' => $this->store1->id,
            'roles' => ['staff']
        ];

        $response = $this->postJson('/api/users', $userData);
        $response->assertCreated();

        // 檢查新使用者建立成功
        $this->assertDatabaseHas('users', [
            'username' => $originalUsername,
            'email' => 'new_unique@example.com',
            'deleted_at' => null
        ]);
    }

    /**
     * 測試更新使用者
     */
    public function test_update_user(): void
    {
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'name' => '更新後的名稱',
            'phone' => '0987654321',
            'status' => UserStatus::INACTIVE->value
        ];

        $response = $this->putJson("/api/users/{$this->staffUser->id}", $updateData);

        $response->assertOk()
                 ->assertJsonPath('data.name', '更新後的名稱')
                 ->assertJsonPath('data.phone', '0987654321')
                 ->assertJsonPath('data.status.value', UserStatus::INACTIVE->value);

        // 檢查資料庫更新
        $this->assertDatabaseHas('users', [
            'id' => $this->staffUser->id,
            'name' => '更新後的名稱',
            'phone' => '0987654321',
            'status' => UserStatus::INACTIVE->value
        ]);
    }

    /**
     * 測試門市管理員無法更新其他門市的使用者
     */
    public function test_store_admin_cannot_update_other_store_user(): void
    {
        // 建立門市二的使用者
        $store2User = User::create([
            'username' => 'store2_user',
            'name' => '門市二使用者',
            'email' => 'store2@example.com',
            'password' => bcrypt('password123'),
            'store_id' => $this->store2->id,
            'status' => UserStatus::ACTIVE->value
        ]);

        Sanctum::actingAs($this->storeAdminUser);

        $updateData = [
            'name' => '嘗試更新'
        ];

        $response = $this->putJson("/api/users/{$store2User->id}", $updateData);

        $response->assertForbidden();
    }

    /**
     * 測試批次狀態更新
     */
    public function test_batch_status_update(): void
    {
        Sanctum::actingAs($this->adminUser);

        // 建立額外的測試使用者
        $user1 = User::create([
            'username' => 'batch_user_1',
            'name' => '批次使用者 1',
            'email' => 'batch1@example.com',
            'password' => bcrypt('password123'),
            'store_id' => $this->store1->id,
            'status' => UserStatus::ACTIVE->value
        ]);

        $user2 = User::create([
            'username' => 'batch_user_2',
            'name' => '批次使用者 2',
            'email' => 'batch2@example.com',
            'password' => bcrypt('password123'),
            'store_id' => $this->store1->id,
            'status' => UserStatus::ACTIVE->value
        ]);

        $batchData = [
            'user_ids' => [$user1->id, $user2->id],
            'status' => UserStatus::INACTIVE->value,
            'reason' => '批次停用測試'
        ];

        $response = $this->patchJson('/api/users/batch-status', $batchData);

        $response->assertOk()
                 ->assertJsonPath('data.affected_count', 2);

        // 檢查狀態更新
        $this->assertDatabaseHas('users', [
            'id' => $user1->id,
            'status' => UserStatus::INACTIVE->value
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
            'status' => UserStatus::INACTIVE->value
        ]);
    }

    /**
     * 測試統計功能
     */
    public function test_user_statistics(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/users/statistics');

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'total_users',
                         'active_users',
                         'inactive_users',
                         'status_distribution' => [
                             '*' => [
                                 'status',
                                 'count',
                                 'percentage',
                                 'color'
                             ]
                         ],
                         'role_distribution',
                         'store_distribution'
                     ]
                 ]);

        $statistics = $response->json('data');
        
        // 檢查統計數據合理性
        $this->assertGreaterThan(0, $statistics['total_users']);
        $this->assertArrayHasKey('status_distribution', $statistics);
        $this->assertArrayHasKey('role_distribution', $statistics);
    }

    /**
     * 測試密碼重設
     */
    public function test_reset_password(): void
    {
        Sanctum::actingAs($this->adminUser);

        $resetData = [
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123'
        ];

        $response = $this->patchJson("/api/users/{$this->staffUser->id}/reset-password", $resetData);

        $response->assertOk();

        // 檢查新密碼有效
        $this->staffUser->refresh();
        $this->assertTrue(Hash::check('NewPassword@123', $this->staffUser->password));
    }

    /**
     * 測試權限不足的錯誤處理
     */
    public function test_insufficient_permission_error(): void
    {
        // 使用只有查看權限的員工
        Sanctum::actingAs($this->staffUser);

        $userData = [
            'username' => 'unauthorized_user',
            'name' => '未授權使用者',
            'email' => 'unauthorized@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'store_id' => $this->store1->id
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertForbidden();
    }

    /**
     * 測試未認證的錯誤處理
     */
    public function test_unauthenticated_access(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertUnauthorized();
    }

    /**
     * 測試分頁功能
     */
    public function test_pagination(): void
    {
        Sanctum::actingAs($this->adminUser);

        // 建立更多測試資料
        for ($i = 1; $i <= 15; $i++) {
            User::create([
                'username' => "test_user_{$i}",
                'name' => "測試使用者 {$i}",
                'email' => "test{$i}@example.com",
                'password' => bcrypt('password123'),
                'store_id' => $this->store1->id,
                'status' => UserStatus::ACTIVE->value
            ]);
        }

        $response = $this->getJson('/api/users?per_page=10&page=1');

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'data',
                         'links',
                         'meta' => [
                             'current_page',
                             'per_page',
                             'total',
                             'last_page'
                         ]
                     ]
                 ]);

        $meta = $response->json('data.meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(1, $meta['current_page']);
        $this->assertGreaterThan(15, $meta['total']); // 包含原有使用者
    }

    /**
     * 測試搜尋功能
     */
    public function test_search_users(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/users?keyword=staff');

        $response->assertOk();

        $users = $response->json('data.data');
        
        // 檢查搜尋結果包含相關使用者
        $found = false;
        foreach ($users as $user) {
            if (str_contains(strtolower($user['username']), 'staff') || 
                str_contains(strtolower($user['name']), 'staff')) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found, '搜尋結果應包含關鍵字相關的使用者');
    }

    /**
     * 測試狀態篩選
     */
    public function test_filter_by_status(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/users?status=active');

        $response->assertOk();

        $users = $response->json('data.data');
        
        // 所有使用者都應該是 active 狀態
        foreach ($users as $user) {
            $this->assertEquals(UserStatus::ACTIVE->value, $user['status']['value']);
        }
    }
} 