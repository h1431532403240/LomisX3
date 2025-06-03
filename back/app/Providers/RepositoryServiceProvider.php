<?php

namespace App\Providers;

use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use App\Repositories\ProductCategoryRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 * 註冊所有 Repository 介面與實作的綁定
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * 所有的容器綁定
     */
    public array $bindings = [
        ProductCategoryRepositoryInterface::class => ProductCategoryRepository::class,
    ];

    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 綁定商品分類 Repository
        $this->app->bind(
            ProductCategoryRepositoryInterface::class,
            ProductCategoryRepository::class
        );
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 在這裡可以添加其他啟動邏輯
    }
}
