<?php

namespace App\Providers;

use App\Models\ProductCategory;
use App\Observers\ProductCategoryObserver;
use App\Listeners\UserRoleEventListener;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Prometheus\CollectorRegistry;
use Spatie\Permission\Events\{RoleAssigned, RoleRemoved, PermissionAssigned, PermissionRemoved};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 註冊商品分類觀察者
        ProductCategory::observe(ProductCategoryObserver::class);
        
        // 註冊使用者角色權限事件監聽器
        Event::listen(RoleAssigned::class, [UserRoleEventListener::class, 'handleRoleAssigned']);
        Event::listen(RoleRemoved::class, [UserRoleEventListener::class, 'handleRoleRemoved']);
        Event::listen(PermissionAssigned::class, [UserRoleEventListener::class, 'handlePermissionAssigned']);
        Event::listen(PermissionRemoved::class, [UserRoleEventListener::class, 'handlePermissionRemoved']);
        
        // 在測試環境中禁用 Prometheus
        if (app()->runningUnitTests()) {
            // 禁用 Prometheus 收集器
            if (class_exists(CollectorRegistry::class)) {
                // 這裡可以設置測試環境的 Prometheus 配置
                // 實際的禁用邏輯會在具體使用時處理
            }
        }
    }
}
