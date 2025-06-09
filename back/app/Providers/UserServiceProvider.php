<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Services\Contracts\UserServiceInterface;
use App\Services\UserService;
use App\Services\Contracts\UserCacheServiceInterface;
use App\Services\UserCacheService;
use App\Models\User;
use App\Observers\UserObserver;

/**
 * 使用者管理模組服務提供者
 * 負責註冊所有相關的服務綁定和觀察者
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
class UserServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務綁定
     *
     * @return void
     */
    public function register(): void
    {
        // Repository 服務綁定
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Service 服務綁定
        $this->app->bind(UserServiceInterface::class, UserService::class);

        // Cache Service 服務綁定
        $this->app->bind(UserCacheServiceInterface::class, UserCacheService::class);

        // 單例模式的服務
        $this->app->singleton(UserCacheService::class, function ($app) {
            return new UserCacheService(
                $app->make(UserRepositoryInterface::class)
            );
        });
    }

    /**
     * 啟動服務
     *
     * @return void
     */
    public function boot(): void
    {
        // 註冊模型觀察者
        User::observe(UserObserver::class);

        // 註冊自訂命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\UserCacheWarmupCommand::class,
                \App\Console\Commands\TokenCleanupCommand::class,
                \App\Console\Commands\ActivityPruneCommand::class,
            ]);
        }
    }

    /**
     * 取得提供的服務
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            UserRepositoryInterface::class,
            UserServiceInterface::class,
            UserCacheServiceInterface::class,
        ];
    }
} 