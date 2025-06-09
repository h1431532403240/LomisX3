<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UserService;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserCacheService;
use App\Models\{User, Store};
use App\Enums\{UserStatus, UserErrorCode};
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\{Hash, Cache, DB, Auth, Log};
use Illuminate\Database\Eloquent\Collection;
use Mockery;

/**
 * 使用者服務單元測試
 * 測試 UserService 的業務邏輯和錯誤處理
 * 
 * 測試範圍：
 * - 使用者建立和驗證
 * - 密碼強度檢查
 * - 門市隔離邏輯
 * - 批次操作
 * - 快取機制
 * - 錯誤處理
 * - 事務處理
 */
class UserServiceTest extends TestCase
{
    protected UserService $userService;
    protected UserRepositoryInterface $mockRepository;
    protected UserCacheService $mockCacheService;

    /**
     * 測試前設定
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 建立 Mock 物件
        $this->mockRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->mockCacheService = Mockery::mock(UserCacheService::class);
        
        // 注入 Mock 到服務容器
        $this->app->instance(UserRepositoryInterface::class, $this->mockRepository);
        $this->app->instance(UserCacheService::class, $this->mockCacheService);
        
        // 建立服務實例
        $this->userService = app(UserService::class);
    }

    /**
     * 建立認證使用者 Mock
     */
    private function mockAuthUser(int $storeId = 1, bool $canAccessAllStores = true, bool $canViewAcrossStores = false): void
    {
        $authUser = Mockery::mock(User::class);
        $authUser->shouldReceive('canAccessStore')->andReturn($canAccessAllStores);
        $authUser->shouldReceive('getAttribute')->with('store_id')->andReturn($storeId);
        $authUser->shouldReceive('hasRole')->with('admin')->andReturn(false); // 保持舊的檢查以防其他地方需要
        $authUser->shouldReceive('cannot')->with('viewAcrossStores', User::class)->andReturn(!$canViewAcrossStores);
        
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('id')->andReturn(999);
    }

    /**
     * 建立 Mock User 物件
     */
    private function mockUser(array $attributes = []): object
    {
        $user = Mockery::mock(User::class);
        
        foreach ($attributes as $key => $value) {
            $user->shouldReceive('getAttribute')->with($key)->andReturn($value);
        }
        
        $user->shouldReceive('fresh')->with(['roles', 'permissions'])->andReturn($user);
        
        return $user;
    }

    /**
     * 測試建立使用者 - 成功案例
     */
    public function test_create_user_successfully(): void
    {
        $userData = [
            'username' => 'new_user',
            'name' => '新使用者',
            'email' => 'new@example.com',
            'password' => 'Password@123',
            'store_id' => 1,
            'roles' => ['staff']
        ];

        $expectedUser = $this->mockUser(['id' => 1, 'username' => 'new_user']);

        // Mock 認證使用者
        $this->mockAuthUser(1, true, false); // 門市1, 可存取, 無跨店權限

        // Mock DB::transaction - 直接執行 callback
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // 設定 Mock 期望 - 根據實際 validateUserData 邏輯
        $this->mockRepository->shouldReceive('findByUsername')
                             ->with('new_user')
                             ->andReturn(null); // 使用者名稱不存在
                             
        $this->mockRepository->shouldReceive('findByEmail')
                             ->with('new@example.com')
                             ->andReturn(null); // Email 不存在
                             
        $this->mockRepository->shouldReceive('create')
                             ->with(Mockery::on(function ($data) {
                                 return $data['username'] === 'new_user' && 
                                        Hash::check('Password@123', $data['password']) &&
                                        isset($data['created_by']);
                             }))
                             ->andReturn($expectedUser);

        // Mock 角色同步 - syncUserRoles 方法
        $this->mockRepository->shouldReceive('findOrFail')
                             ->with(1)
                             ->andReturn($expectedUser);
        
        // Mock Spatie roles syncRoles 方法
        $expectedUser->shouldReceive('syncRoles')
                     ->with(['staff'])
                     ->andReturn(true);

        // Mock 快取清除（角色同步時會呼叫）
        Cache::shouldReceive('forget')->with('users:1');
        Cache::shouldReceive('forget')->with('user_accessible_stores_1');

        // Mock Log
        Log::shouldReceive('info');
        Log::shouldReceive('error')->never(); // 成功案例不應該有錯誤

        // 執行測試
        $result = $this->userService->create($userData);

        // 驗證結果
        $this->assertInstanceOf(get_class($expectedUser), $result);
    }

    /**
     * 測試建立使用者 - 使用者名稱重複
     */
    public function test_create_user_with_duplicate_username(): void
    {
        $userData = [
            'username' => 'existing_user',
            'name' => '新使用者',
            'email' => 'new@example.com',
            'password' => 'Password@123',
            'store_id' => 1
        ];

        $existingUser = $this->mockUser(['id' => 2, 'username' => 'existing_user']);

        // Mock 認證使用者
        $this->mockAuthUser(1, true, false); // 門市1, 可存取, 無跨店權限

        // Mock DB::transaction
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // 設定 Mock 期望 - 使用者名稱已存在
        $this->mockRepository->shouldReceive('findByUsername')
                             ->with('existing_user')
                             ->andReturn($existingUser); // 回傳存在的使用者

        // 預期拋出例外 - 根據 UserErrorCode::USERNAME_EXISTS
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('使用者名稱已存在');

        $this->userService->create($userData);
    }

    /**
     * 測試建立使用者 - Email 重複
     */
    public function test_create_user_with_duplicate_email(): void
    {
        $userData = [
            'username' => 'new_user',
            'name' => '新使用者',
            'email' => 'existing@example.com',
            'password' => 'Password@123',
            'store_id' => 1
        ];

        $existingUser = $this->mockUser(['id' => 3, 'email' => 'existing@example.com']);

        // Mock 認證使用者
        $this->mockAuthUser(1, true, false); // 門市1, 可存取, 無跨店權限

        // Mock DB::transaction
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // 設定 Mock 期望
        $this->mockRepository->shouldReceive('findByUsername')
                             ->with('new_user')
                             ->andReturn(null); // 使用者名稱不存在
                             
        $this->mockRepository->shouldReceive('findByEmail')
                             ->with('existing@example.com')
                             ->andReturn($existingUser); // Email 已存在

        // 預期拋出例外 - 根據 UserErrorCode::EMAIL_EXISTS
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('電子郵件已被使用');

        $this->userService->create($userData);
    }

    /**
     * 測試建立使用者 - 密碼強度不足
     */
    public function test_create_user_with_weak_password(): void
    {
        $userData = [
            'username' => 'new_user',
            'name' => '新使用者',
            'email' => 'new@example.com',
            'password' => 'weak', // 弱密碼
            'store_id' => 1
        ];

        // Mock 認證使用者
        $this->mockAuthUser(1, true, false); // 門市1, 可存取, 無跨店權限

        // Mock DB::transaction
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // 設定 Mock 期望
        $this->mockRepository->shouldReceive('findByUsername')
                             ->with('new_user')
                             ->andReturn(null);
                             
        $this->mockRepository->shouldReceive('findByEmail')
                             ->with('new@example.com')
                             ->andReturn(null);

        // 預期拋出例外 - 根據 UserErrorCode::WEAK_PASSWORD
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('密碼強度不足，需包含大小寫字母、數字及特殊字元');

        $this->userService->create($userData);
    }

    /**
     * 測試密碼強度驗證
     */
    public function test_password_strength_validation(): void
    {
        $userData = [
            'username' => 'new_user',
            'name' => '新使用者',
            'email' => 'new@example.com',
            'password' => 'ValidPassword@123',
            'store_id' => 1
        ];

        $expectedUser = $this->mockUser(['id' => 1, 'username' => 'new_user']);

        // Mock 認證使用者
        $this->mockAuthUser(1, true, false); // 門市1, 可存取, 無跨店權限

        // Mock DB::transaction
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // 設定 Mock 期望
        $this->mockRepository->shouldReceive('findByUsername')->andReturn(null);
        $this->mockRepository->shouldReceive('findByEmail')->andReturn(null);
        $this->mockRepository->shouldReceive('create')->andReturn($expectedUser);

        // Mock Log
        Log::shouldReceive('info');
        Log::shouldReceive('error')->never(); // 成功案例不應該有錯誤

        // 執行測試 - 強密碼應該成功
        $result = $this->userService->create($userData);
        
        $this->assertInstanceOf(get_class($expectedUser), $result);
    }

    /**
     * 測試更新使用者
     */
    public function test_update_user_successfully(): void
    {
        $userId = 1;
        $updateData = ['name' => '更新後名稱'];

        $existingUser = $this->mockUser(['id' => $userId, 'store_id' => 1]);
        $updatedUser = $this->mockUser(['id' => $userId, 'name' => '更新後名稱']);

        // Mock 認證使用者
        $authUser = Mockery::mock(User::class);
        $authUser->shouldReceive('hasRole')->with('admin')->andReturn(false);
        $authUser->shouldReceive('getAttribute')->with('store_id')->andReturn(1);
        $authUser->shouldReceive('cannot')->with('viewAcrossStores', User::class)->andReturn(true);
        
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('id')->andReturn(999);

        // Mock DB::transaction
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // Mock Cache::remember for getDetail method
        Cache::shouldReceive('remember')
             ->with("users:{$userId}", 3600, Mockery::type('Closure'))
             ->andReturnUsing(function ($key, $ttl, $callback) use ($existingUser) {
                 return $callback();
             });

        // 設定 Mock 期望 - getDetail 方法使用 find
        $this->mockRepository->shouldReceive('find')
                             ->with($userId)
                             ->andReturn($existingUser);

        // Mock load 方法
        $existingUser->shouldReceive('load')
                     ->with(['roles', 'permissions', 'store'])
                     ->andReturn($existingUser);

        // validateUserData 不會檢查 name 欄位，所以不需要 findByUsername/findByEmail

        $this->mockRepository->shouldReceive('update')
                             ->with($userId, Mockery::on(function ($data) {
                                 return isset($data['name']) && 
                                        isset($data['updated_by']);
                             }))
                             ->andReturn($updatedUser);

        // Mock Cache 和 Log
        Cache::shouldReceive('forget');
        Log::shouldReceive('info');
        Log::shouldReceive('error')->never(); // 成功案例不應該有錯誤

        // 執行測試
        $result = $this->userService->update($userId, $updateData);

        // 驗證結果
        $this->assertInstanceOf(get_class($updatedUser), $result);
    }

    /**
     * 測試批次狀態更新 - 成功案例
     */
    public function test_batch_status_update_successfully(): void
    {
        $userIds = [1, 2, 3];
        $status = UserStatus::INACTIVE->value;

        // 建立非管理員使用者集合 - 使用 Eloquent Collection
        $users = new Collection([
            Mockery::mock(User::class)->shouldReceive('hasRole')->with('admin')->andReturn(false)->getMock(),
            Mockery::mock(User::class)->shouldReceive('hasRole')->with('admin')->andReturn(false)->getMock(),
            Mockery::mock(User::class)->shouldReceive('hasRole')->with('admin')->andReturn(false)->getMock()
        ]);

        // Mock 認證使用者
        $this->mockAuthUser(1, true, false); // 門市1, 可存取, 無跨店權限

        // Mock DB::transaction
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // 設定 Mock 期望
        $this->mockRepository->shouldReceive('findWhereIn')
                             ->with('id', $userIds)
                             ->andReturn($users);

        $this->mockRepository->shouldReceive('batchUpdateStatus')
                             ->with($userIds, $status)
                             ->andReturn(3);

        // Mock 快取清除、認證和記錄
        Cache::shouldReceive('forget');
        Log::shouldReceive('info');
        Log::shouldReceive('error')->never(); // 成功案例不應該有錯誤

        // 執行測試
        $result = $this->userService->batchUpdateStatus($userIds, $status);

        // 驗證結果
        $this->assertEquals(3, $result);
    }

    /**
     * 測試批次狀態更新 - 包含管理員帳號
     */
    public function test_batch_status_update_with_admin_account(): void
    {
        $userIds = [1, 2, 3];
        $status = UserStatus::INACTIVE->value;

        // 建立包含管理員的使用者集合
        $adminUser = Mockery::mock(User::class);
        $adminUser->shouldReceive('hasRole')
                  ->with('admin')
                  ->andReturn(true);

        $users = new Collection([$adminUser]); // 使用 Eloquent Collection

        // 設定 Mock 期望
        $this->mockRepository->shouldReceive('findWhereIn')
                             ->with('id', $userIds)
                             ->andReturn($users);

        // 預期拋出例外
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('無法刪除管理員帳號');

        $this->userService->batchUpdateStatus($userIds, $status);
    }

    /**
     * 測試重設密碼
     */
    public function test_reset_password_successfully(): void
    {
        $userId = 1;
        $newPassword = 'NewPassword@123';

        $existingUser = $this->mockUser(['id' => $userId, 'store_id' => 1]);
        
        // Mock tokens 關聯
        $tokensRelation = Mockery::mock();
        $tokensRelation->shouldReceive('delete')->andReturn(true);
        $existingUser->shouldReceive('tokens')->andReturn($tokensRelation);

        $updatedUser = $this->mockUser(['id' => $userId, 'username' => 'test_user']);

        // Mock 認證使用者
        $authUser = Mockery::mock(User::class);
        $authUser->shouldReceive('hasRole')->with('admin')->andReturn(false);
        $authUser->shouldReceive('getAttribute')->with('store_id')->andReturn(1);
        $authUser->shouldReceive('cannot')->with('viewAcrossStores', User::class)->andReturn(true);
        
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('id')->andReturn(999);

        // Mock DB::transaction
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // Mock Cache::remember for getDetail method
        Cache::shouldReceive('remember')
             ->with("users:{$userId}", 3600, Mockery::type('Closure'))
             ->andReturnUsing(function ($key, $ttl, $callback) use ($existingUser) {
                 return $callback();
             });

        // 設定 Mock 期望 - getDetail 方法使用 find
        $this->mockRepository->shouldReceive('find')
                             ->with($userId)
                             ->andReturn($existingUser);

        // Mock load 方法
        $existingUser->shouldReceive('load')
                     ->with(['roles', 'permissions', 'store'])
                     ->andReturn($existingUser);

        $this->mockRepository->shouldReceive('update')
                             ->with($userId, Mockery::on(function ($data) use ($newPassword) {
                                 return isset($data['password']) && 
                                        Hash::check($newPassword, $data['password']) &&
                                        isset($data['updated_by']);
                             }))
                             ->andReturn($updatedUser);

        // Mock Log
        Log::shouldReceive('info');
        Log::shouldReceive('error')->never(); // 成功案例不應該有錯誤

        // 執行測試
        $result = $this->userService->resetPassword($userId, $newPassword);

        // 驗證結果
        $this->assertInstanceOf(get_class($updatedUser), $result);
    }

    /**
     * 測試門市隔離檢查
     */
    public function test_store_isolation_check(): void
    {
        $userId = 1;

        // 建立不同門市的使用者
        $otherStoreUser = $this->mockUser(['id' => $userId, 'store_id' => 999]);

        // Mock 認證使用者 (門市1, 無跨店查看權限)
        $authUser = Mockery::mock(User::class);
        $authUser->shouldReceive('hasRole')->with('admin')->andReturn(false);
        $authUser->shouldReceive('getAttribute')->with('store_id')->andReturn(1);
        $authUser->shouldReceive('cannot')->with('viewAcrossStores', User::class)->andReturn(true); // 無跨店權限
        
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('id')->andReturn(888);

        // Mock Cache::remember for getDetail method
        Cache::shouldReceive('remember')
             ->with("users:{$userId}", 3600, Mockery::type('Closure'))
             ->andReturnUsing(function ($key, $ttl, $callback) use ($otherStoreUser) {
                 return $callback();
             });

        // 設定 Mock 期望 - getDetail 方法使用 find
        $this->mockRepository->shouldReceive('find')
                             ->with($userId)
                             ->andReturn($otherStoreUser);

        // 預期拋出門市存取拒絕例外
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('無權存取此門市資料');

        $this->userService->getDetail($userId);
    }

    /**
     * 測試更新使用者 - getDetail 失敗
     */
    public function test_update_user_get_detail_failure(): void
    {
        $userId = 999; // 不存在的使用者ID
        $updateData = ['name' => '測試更新'];

        // Mock 認證使用者
        $authUser = Mockery::mock(User::class);
        $authUser->shouldReceive('hasRole')->with('admin')->andReturn(false);
        $authUser->shouldReceive('getAttribute')->with('store_id')->andReturn(1);
        $authUser->shouldReceive('cannot')->with('viewAcrossStores', User::class)->andReturn(true);
        
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('id')->andReturn(999);

        // Mock DB::transaction - 因為 getDetail 在事務內被呼叫
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // Mock Cache::remember for getDetail method - 返回 null 表示找不到使用者
        Cache::shouldReceive('remember')
             ->with("users:{$userId}", 3600, Mockery::type('Closure'))
             ->andReturnUsing(function ($key, $ttl, $callback) {
                 return $callback();
             });

        // Mock Repository 找不到使用者
        $this->mockRepository->shouldReceive('find')
                             ->with($userId)
                             ->andReturn(null); // 使用者不存在

        // Mock 錯誤記錄 - 不需要，因為 getDetail 會拋出 BusinessException，不會被 catch
        // Log::shouldReceive('error');

        // 預期拋出例外 - getDetail 找不到使用者會拋出 USER_NOT_FOUND BusinessException，不會被 executeInTransaction 重新包裝
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('使用者不存在');

        $this->userService->update($userId, $updateData);
    }

    /**
     * 測試快取清除機制
     */
    public function test_cache_clearing_mechanism(): void
    {
        $userId = 1;
        $updateData = ['status' => UserStatus::INACTIVE->value];

        $existingUser = $this->mockUser(['id' => $userId, 'store_id' => 1]);
        $updatedUser = $this->mockUser(['id' => $userId, 'status' => UserStatus::INACTIVE->value]);

        // Mock 認證使用者
        $authUser = Mockery::mock(User::class);
        $authUser->shouldReceive('hasRole')->with('admin')->andReturn(false);
        $authUser->shouldReceive('getAttribute')->with('store_id')->andReturn(1);
        $authUser->shouldReceive('cannot')->with('viewAcrossStores', User::class)->andReturn(true);
        
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('id')->andReturn(999);

        // Mock DB::transaction
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // Mock Cache::remember for getDetail method
        Cache::shouldReceive('remember')
             ->with("users:{$userId}", 3600, Mockery::type('Closure'))
             ->andReturnUsing(function ($key, $ttl, $callback) use ($existingUser) {
                 return $callback();
             });

        // 設定 Mock 期望
        $this->mockRepository->shouldReceive('find')
                             ->with($userId)
                             ->andReturn($existingUser);

        // Mock load 方法
        $existingUser->shouldReceive('load')
                     ->with(['roles', 'permissions', 'store'])
                     ->andReturn($existingUser);

        // validateUserData 不會檢查 status 欄位

        $this->mockRepository->shouldReceive('update')
                             ->with($userId, Mockery::on(function ($data) {
                                 return isset($data['status']) && 
                                        isset($data['updated_by']);
                             }))
                             ->andReturn($updatedUser);

        // 驗證快取清除被正確呼叫
        Cache::shouldReceive('forget')->with("users:{$userId}");
        Cache::shouldReceive('forget')->with("user_accessible_stores_{$userId}");

        // Mock 記錄
        Log::shouldReceive('info');
        Log::shouldReceive('error')->never(); // 成功案例不應該有錯誤

        // 執行測試
        $result = $this->userService->update($userId, $updateData);

        // 驗證結果 - 確保有斷言
        $this->assertInstanceOf(get_class($updatedUser), $result);
        $this->assertEquals(UserStatus::INACTIVE->value, $result->getAttribute('status'));
    }

    /**
     * 測試事務處理
     */
    public function test_transaction_handling(): void
    {
        $userData = [
            'username' => 'transaction_user',
            'name' => '事務測試使用者',
            'email' => 'transaction@example.com',
            'password' => 'Password@123',
            'store_id' => 1
        ];

        // Mock 認證使用者
        $this->mockAuthUser(1, true, false); // 門市1, 可存取, 無跨店權限

        // Mock DB::transaction - 拋出例外模擬事務失敗
        DB::shouldReceive('transaction')
          ->once()
          ->andThrow(new \Exception('Database error'));

        // Mock 錯誤記錄
        Log::shouldReceive('error');

        // 預期拋出例外 - executeInTransaction 會包裝為通用錯誤
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('操作失敗，請稍後重試');

        // 執行測試 - 應該因為事務回滾而失敗
        $this->userService->create($userData);
    }

    /**
     * 測試無效狀態處理
     */
    public function test_invalid_status_handling(): void
    {
        $userIds = [1, 2];
        $invalidStatus = 'invalid_status';

        // 預期拋出例外
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('無效的使用者狀態');

        $this->userService->batchUpdateStatus($userIds, $invalidStatus);
    }

    /**
     * 測試建立使用者 - Repository 錯誤處理
     */
    public function test_create_user_repository_error(): void
    {
        $userData = [
            'username' => 'error_user',
            'name' => '錯誤測試使用者',
            'email' => 'error@example.com',
            'password' => 'Password@123',
            'store_id' => 1
        ];

        // Mock 認證使用者
        $this->mockAuthUser(1, true, false); // 門市1, 可存取, 無跨店權限

        // Mock DB::transaction - Repository 拋出例外
        DB::shouldReceive('transaction')
          ->once()
          ->andReturnUsing(function ($callback) {
              return $callback();
          });

        // 設定 Mock 期望 - Repository 拋出例外
        $this->mockRepository->shouldReceive('findByUsername')->andReturn(null);
        $this->mockRepository->shouldReceive('findByEmail')->andReturn(null);
        $this->mockRepository->shouldReceive('create')
                             ->andThrow(new \Exception('Repository error'));

        // Mock 錯誤記錄
        Log::shouldReceive('error');

        // 預期拋出包裝後的例外 - executeInTransaction 會重新包裝
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('操作失敗，請稍後重試');

        $this->userService->create($userData);
    }

    /**
     * 測試後清理
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 