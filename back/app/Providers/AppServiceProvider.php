<?php

namespace App\Providers;

use App\Models\ProductCategory;
use App\Models\User;
use App\Observers\ProductCategoryObserver;
use App\Listeners\UserRoleEventListener;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Spatie\Permission\Events\{RoleAssigned, RoleRemoved, PermissionAssigned, PermissionRemoved};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 保持 register 方法簡潔，只處理服務註冊
        Log::info('📋 [AppServiceProvider::register] register() 方法開始執行');
        Log::info('📋 [AppServiceProvider::register] register() 方法執行完成');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅✅✅ V6.1 添加路由綁定驗證測試 ✅✅✅
        Log::info('🚀 [AppServiceProvider::boot] 服務提供者正在啟動');
        
        // 在 boot() 方法中註冊路由綁定（Laravel 11 標準做法）
        Route::bind('user', function ($value) {
            Log::info('🔥 [RouteBinding] user 參數綁定被觸發！', [
                'requested_user_id' => $value,
                'type_of_value' => gettype($value),
                'value_length' => is_string($value) ? strlen($value) : 'not_string'
            ]);
            
            $authUser = auth()->user();
            $hasPermission = $authUser ? $authUser->can('system.operate_across_stores') : false;
            
            Log::info('🔍 [RouteBinding] 開始解析 user 參數', [
                'requested_user_id' => $value,
                'auth_user_id' => $authUser ? $authUser->id : null,
                'has_cross_store_permission' => $hasPermission,
            ]);
            
            try {
                if ($hasPermission) {
                    Log::info('🚀 [RouteBinding] 使用跨門市權限查詢');
                    $user = User::withTrashed()->withoutGlobalScopes()->findOrFail($value);
                } else {
                    Log::info('🏪 [RouteBinding] 使用門市隔離查詢');
                    $user = User::withTrashed()->findOrFail($value);
                }
                
                Log::info('✅ [RouteBinding] 成功找到用戶', [
                    'found_user_id' => $user->id,
                    'found_username' => $user->username,
                    'found_store_id' => $user->store_id,
                    'is_deleted' => $user->deleted_at ? true : false
                ]);
                
                return $user;
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                Log::warning('❌ [RouteBinding] 找不到用戶', ['id' => $value]);
                abort(404, '使用者不存在或無法訪問。');
            }
        });
        
        Log::info('🎯 [AppServiceProvider::boot] 路由綁定註冊完成');
        
        // ✅✅✅ V6.1 立即驗證路由綁定是否生效 ✅✅✅
        try {
            $router = app('router');
            $hasBinding = false;
            
            // 檢查是否有 user 綁定
            $reflection = new \ReflectionClass($router);
            $bindersProperty = null;
            
            foreach ($reflection->getProperties() as $property) {
                if (strpos($property->getName(), 'bind') !== false || strpos($property->getName(), 'Bind') !== false) {
                    $property->setAccessible(true);
                    $value = $property->getValue($router);
                    if (is_array($value) && isset($value['user'])) {
                        $hasBinding = true;
                        break;
                    }
                }
            }
            
            Log::info('🧪 [AppServiceProvider::boot] 路由綁定驗證結果', [
                'has_user_binding' => $hasBinding,
                'router_class' => get_class($router)
            ]);
            
        } catch (\Exception $e) {
            Log::warning('⚠️ [AppServiceProvider::boot] 路由綁定驗證失敗', [
                'error' => $e->getMessage()
            ]);
        }
        
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
