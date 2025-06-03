<?php

namespace App\Providers;

use App\Models\ProductCategory;
use App\Policies\ProductCategoryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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

        // 這裡可以定義額外的 Gate 規則
        // Gate::define('admin-only', function (User $user) {
        //     return $user->role === 'admin';
        // });
    }
}
