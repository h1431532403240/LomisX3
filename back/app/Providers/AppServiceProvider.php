<?php

namespace App\Providers;

use App\Models\ProductCategory;
use App\Observers\ProductCategoryObserver;
use Illuminate\Support\ServiceProvider;

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
    }
}
