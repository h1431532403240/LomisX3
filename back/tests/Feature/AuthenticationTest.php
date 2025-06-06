<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Store};
use App\Enums\{UserStatus, UserErrorCode};
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Illuminate\Support\Facades\{Hash, RateLimiter};
use Laravel\Sanctum\Sanctum;

/**
 * 認證功能測試
 * 測試登入、2FA、Token 管理等認證相關功能
 * 
 * 測試範圍：
 * - 基礎登入流程
 * - 2FA 雙因子驗證
 * - Token 管理
 * - 登入限流保護
 * - 帳號鎖定機制
 * - 錯誤處理
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Store $store;
    protected User $activeUser;
    protected User $inactiveUser;
    protected User $lockedUser;
    protected User $pendingUser;

    /**
     * 測試前設定
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試門市
        $this->store = Store::create([
            'name' => '測試門市',
            'code' => 'TEST001',
            'address' => '台北市',
            'status' => 'active'
        ]);

        // 建立不同狀態的測試使用者
        $this->createTestUsers();
    }

    /**
     * 建立測試使用者
     */
    private function createTestUsers(): void
    {
        // 正常使用者
        $this->activeUser = User::create([
            'username' => 'active_user',
            'name' => '正常使用者',
            'email' => 'active@example.com',
            'password' => Hash::make('password123'),
            'store_id' => $this->store->id,
            'status' => UserStatus::ACTIVE->value,
            'email_verified_at' => now()
        ]);

        // 停用使用者
        $this->inactiveUser = User::create([
            'username' => 'inactive_user',
            'name' => '停用使用者',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'store_id' => $this->store->id,
            'status' => UserStatus::INACTIVE->value,
            'email_verified_at' => now()
        ]);

        // 鎖定使用者
        $this->lockedUser = User::create([
            'username' => 'locked_user',
            'name' => '鎖定使用者',
            'email' => 'locked@example.com',
            'password' => Hash::make('password123'),
            'store_id' => $this->store->id,
            'status' => UserStatus::LOCKED->value,
            'email_verified_at' => now(),
            'locked_until' => now()->addHours(1)
        ]);

        // 待審核使用者
        $this->pendingUser = User::create([
            'username' => 'pending_user',
            'name' => '待審核使用者',
            'email' => 'pending@example.com',
            'password' => Hash::make('password123'),
            'store_id' => $this->store->id,
            'status' => UserStatus::PENDING->value,
            'email_verified_at' => now()
        ]);
    }

    /**
     * 測試成功登入
     */
    public function test_successful_login(): void
    {
        $loginData = [
            'login' => $this->activeUser->username,
            'password' => 'password123',
            'device_name' => '測試裝置'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => [
                             'id', 'username', 'name', 'email',
                             'store_id', 'status'
                         ],
                         'token',
                         'expires_at',
                         'permissions',
                         'store'
                     ]
                 ])
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.user.username', $this->activeUser->username);

        // 檢查 Token 是否有效
        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // 檢查登入資訊更新
        $this->activeUser->refresh();
        $this->assertNotNull($this->activeUser->last_login_at);
        $this->assertEquals(request()->ip(), $this->activeUser->last_login_ip);
    }

    /**
     * 測試使用 Email 登入
     */
    public function test_login_with_email(): void
    {
        $loginData = [
            'login' => $this->activeUser->email,
            'password' => 'password123',
            'device_name' => '測試裝置'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertOk()
                 ->assertJsonPath('data.user.email', $this->activeUser->email);
    }

    /**
     * 測試登入失敗 - 錯誤密碼
     */
    public function test_login_with_wrong_password(): void
    {
        $loginData = [
            'login' => $this->activeUser->username,
            'password' => 'wrong_password',
            'device_name' => '測試裝置'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(400)
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('error_code', UserErrorCode::INVALID_CREDENTIALS->value);
    }

    /**
     * 測試登入失敗 - 使用者不存在
     */
    public function test_login_with_nonexistent_user(): void
    {
        $loginData = [
            'login' => 'nonexistent_user',
            'password' => 'password123',
            'device_name' => '測試裝置'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(400)
                 ->assertJsonPath('error_code', UserErrorCode::INVALID_CREDENTIALS->value);
    }

    /**
     * 測試停用帳號登入
     */
    public function test_login_with_inactive_account(): void
    {
        $loginData = [
            'login' => $this->inactiveUser->username,
            'password' => 'password123',
            'device_name' => '測試裝置'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(403)
                 ->assertJsonPath('error_code', UserErrorCode::ACCOUNT_INACTIVE->value);
    }

    /**
     * 測試鎖定帳號登入
     */
    public function test_login_with_locked_account(): void
    {
        $loginData = [
            'login' => $this->lockedUser->username,
            'password' => 'password123',
            'device_name' => '測試裝置'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(423)
                 ->assertJsonPath('error_code', UserErrorCode::ACCOUNT_LOCKED->value);
    }

    /**
     * 測試待審核帳號登入
     */
    public function test_login_with_pending_account(): void
    {
        $loginData = [
            'login' => $this->pendingUser->username,
            'password' => 'password123',
            'device_name' => '測試裝置'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(202)
                 ->assertJsonPath('error_code', UserErrorCode::ACCOUNT_PENDING->value);
    }

    /**
     * 測試登入限流保護
     */
    public function test_login_rate_limiting(): void
    {
        $loginData = [
            'login' => $this->activeUser->username,
            'password' => 'wrong_password',
            'device_name' => '測試裝置'
        ];

        // 進行多次錯誤登入嘗試
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', $loginData);
            
            if ($i < 5) {
                $response->assertStatus(400); // 前 5 次應該是密碼錯誤
            } else {
                $response->assertStatus(429); // 第 6 次應該觸發限流
            }
        }
    }

    /**
     * 測試成功登出
     */
    public function test_successful_logout(): void
    {
        Sanctum::actingAs($this->activeUser);

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('message', '登出成功');
    }

    /**
     * 測試取得當前使用者資訊
     */
    public function test_get_current_user_info(): void
    {
        Sanctum::actingAs($this->activeUser);

        $response = $this->getJson('/api/auth/me');

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user' => [
                             'id', 'username', 'name', 'email',
                             'store_id', 'status'
                         ],
                         'permissions',
                         'roles'
                     ]
                 ])
                 ->assertJsonPath('data.user.id', $this->activeUser->id);
    }

    /**
     * 測試 Token 刷新
     */
    public function test_token_refresh(): void
    {
        $token = $this->activeUser->createToken('測試Token');
        
        Sanctum::actingAs($this->activeUser, ['*'], $token->accessToken);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'token',
                         'expires_at'
                     ]
                 ]);

        // 檢查舊 Token 是否被刪除
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id
        ]);

        // 檢查新 Token 是否可用
        $newToken = $response->json('data.token');
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($token->plainTextToken, $newToken);
    }

    /**
     * 測試 2FA 啟用流程
     */
    public function test_enable_2fa(): void
    {
        Sanctum::actingAs($this->activeUser);

        $enableData = [
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/2fa/enable', $enableData);

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'secret_key',
                         'qr_code_url',
                         'recovery_codes'
                     ]
                 ]);

        // 檢查 2FA 秘鑰是否已儲存
        $this->activeUser->refresh();
        $this->assertNotNull($this->activeUser->two_factor_secret);
    }

    /**
     * 測試 2FA 啟用 - 錯誤密碼
     */
    public function test_enable_2fa_with_wrong_password(): void
    {
        Sanctum::actingAs($this->activeUser);

        $enableData = [
            'password' => 'wrong_password'
        ];

        $response = $this->postJson('/api/auth/2fa/enable', $enableData);

        $response->assertStatus(400)
                 ->assertJsonPath('error_code', UserErrorCode::INVALID_CREDENTIALS->value);
    }

    /**
     * 測試 2FA 確認設定
     */
    public function test_confirm_2fa(): void
    {
        Sanctum::actingAs($this->activeUser);

        // 先啟用 2FA
        $this->activeUser->update([
            'two_factor_secret' => encrypt('TESTSECRET123456')
        ]);

        // 模擬正確的 TOTP 代碼 (實際測試中可能需要使用 mock)
        $confirmData = [
            'code' => '123456' // 在實際測試中需要生成真實的 TOTP 代碼
        ];

        // 這裡我們 mock TwoFactorService 的 confirm 方法
        $this->mock(\App\Services\TwoFactorService::class, function ($mock) {
            $mock->shouldReceive('confirm')
                 ->once()
                 ->andReturn(true);
        });

        $response = $this->postJson('/api/auth/2fa/confirm', $confirmData);

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    /**
     * 測試 2FA 停用
     */
    public function test_disable_2fa(): void
    {
        Sanctum::actingAs($this->activeUser);

        // 設定使用者已啟用 2FA
        $this->activeUser->update([
            'two_factor_secret' => encrypt('TESTSECRET123456'),
            'two_factor_confirmed_at' => now()
        ]);

        $disableData = [
            'password' => 'password123',
            'code' => '123456'
        ];

        // Mock 相關服務
        $this->mock(\App\Services\AuthService::class, function ($mock) {
            $mock->shouldReceive('verifyPassword')
                 ->once()
                 ->andReturn(true);
        });

        $this->mock(\App\Services\TwoFactorService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')
                 ->once()
                 ->andReturn(true);
            $mock->shouldReceive('disable')
                 ->once();
        });

        $response = $this->postJson('/api/auth/2fa/disable', $disableData);

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    /**
     * 測試 2FA 挑戰驗證
     */
    public function test_2fa_challenge(): void
    {
        // 設定使用者啟用 2FA
        $this->activeUser->update([
            'two_factor_secret' => encrypt('TESTSECRET123456'),
            'two_factor_confirmed_at' => now()
        ]);

        $challengeData = [
            'user_id' => $this->activeUser->id,
            'code' => '123456',
            'device_name' => '測試裝置'
        ];

        // Mock TwoFactorService
        $this->mock(\App\Services\TwoFactorService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')
                 ->once()
                 ->andReturn(true);
        });

        // Mock UserService
        $this->mock(\App\Services\UserService::class, function ($mock) {
            $mock->shouldReceive('updateLoginInfo')
                 ->once();
        });

        $response = $this->postJson('/api/auth/2fa/challenge', $challengeData);

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user',
                         'token',
                         'expires_at',
                         'permissions'
                     ]
                 ]);
    }

    /**
     * 測試 2FA 挑戰 - 錯誤代碼
     */
    public function test_2fa_challenge_with_wrong_code(): void
    {
        // 設定使用者啟用 2FA
        $this->activeUser->update([
            'two_factor_secret' => encrypt('TESTSECRET123456'),
            'two_factor_confirmed_at' => now()
        ]);

        $challengeData = [
            'user_id' => $this->activeUser->id,
            'code' => '000000',
            'device_name' => '測試裝置'
        ];

        // Mock TwoFactorService 返回驗證失敗
        $this->mock(\App\Services\TwoFactorService::class, function ($mock) {
            $mock->shouldReceive('verifyCode')
                 ->once()
                 ->andReturn(false);
        });

        $response = $this->postJson('/api/auth/2fa/challenge', $challengeData);

        $response->assertStatus(400)
                 ->assertJsonPath('error_code', UserErrorCode::INVALID_2FA_CODE->value);
    }

    /**
     * 測試使用恢復代碼進行 2FA 挑戰
     */
    public function test_2fa_challenge_with_recovery_code(): void
    {
        // 設定使用者啟用 2FA 和恢復代碼
        $this->activeUser->update([
            'two_factor_secret' => encrypt('TESTSECRET123456'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['RECOVERY123', 'RECOVERY456']))
        ]);

        $challengeData = [
            'user_id' => $this->activeUser->id,
            'recovery_code' => 'RECOVERY123',
            'device_name' => '測試裝置'
        ];

        // Mock TwoFactorService
        $this->mock(\App\Services\TwoFactorService::class, function ($mock) {
            $mock->shouldReceive('verifyRecoveryCode')
                 ->once()
                 ->andReturn(true);
        });

        // Mock UserService
        $this->mock(\App\Services\UserService::class, function ($mock) {
            $mock->shouldReceive('updateLoginInfo')
                 ->once();
        });

        $response = $this->postJson('/api/auth/2fa/challenge', $challengeData);

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user',
                         'token',
                         'expires_at',
                         'permissions'
                     ]
                 ]);
    }

    /**
     * 測試未認證存取受保護的路由
     */
    public function test_unauthenticated_access_to_protected_route(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    /**
     * 測試無效 Token 存取
     */
    public function test_invalid_token_access(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
                         ->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    /**
     * 測試 Token 過期
     */
    public function test_expired_token_access(): void
    {
        // 建立已過期的 Token
        $token = $this->activeUser->createToken('過期Token', ['*'], now()->subHour());
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
                         ->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    /**
     * 測試登入驗證規則
     */
    public function test_login_validation_rules(): void
    {
        // 缺少必要欄位
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['login', 'password']);

        // 密碼長度不足
        $response = $this->postJson('/api/auth/login', [
            'login' => 'test_user',
            'password' => '123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * 測試 2FA 挑戰驗證規則
     */
    public function test_2fa_challenge_validation_rules(): void
    {
        // 缺少使用者 ID
        $response = $this->postJson('/api/auth/2fa/challenge', [
            'code' => '123456'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['user_id']);

        // 同時提供代碼和恢復代碼
        $response = $this->postJson('/api/auth/2fa/challenge', [
            'user_id' => $this->activeUser->id,
            'code' => '123456',
            'recovery_code' => 'RECOVERY123'
        ]);

        $response->assertOk(); // 應該允許，但會優先使用 code

        // 都沒有提供
        $response = $this->postJson('/api/auth/2fa/challenge', [
            'user_id' => $this->activeUser->id
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['code']);
    }

    /**
     * 清理測試後的限流記錄
     */
    protected function tearDown(): void
    {
        // 清理 RateLimiter
        RateLimiter::clear('login_attempts:' . request()->ip());
        
        parent::tearDown();
    }
} 