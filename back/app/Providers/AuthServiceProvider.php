<?php

namespace App\Providers;

use App\Models\ProductCategory;
use App\Models\User;
use App\Policies\ProductCategoryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * 認證服務提供者
 * 註冊應用程式的權限策略
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * 應用程式的權限策略對應
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ProductCategory::class => ProductCategoryPolicy::class,
    ];

    /**
     * 註冊任何認證/授權服務
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // ✅ V4.0 SUPER ADMIN 模式: 註冊一個全域權限檢查，繞過所有權限驗證
        // 這個回呼會在所有其他授權檢查之前執行
        Gate::before(function (User $user, string $ability) {
            // 如果使用者擁有 'super_admin' 角色，則自動授予所有權限
            // 注意: 'super_admin' 角色應極其謹慎地分配
            if ($user->hasRole('super_admin')) {
                return true;
            }

            // 如果沒有 'super_admin' 角色，則返回 null，讓權限檢查繼續執行後續的 Policy 或 Permission 驗證
            return null;
        });

        // 這裡可以定義額外的 Gate 規則
        // Gate::define('admin-only', function (User $user) {
        //     return $user->role === 'admin';
        // });
    }
}
