<?php

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\RepositoryServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',

    )
    ->withProviders([
        AppServiceProvider::class,
        RepositoryServiceProvider::class,
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * 全域中間件配置 - Bearer Token 認證模式
         * CORS 處理必須在所有其他中間件之前執行
         * 已移除 Sanctum SPA 中間件，使用純 Bearer Token 認證
         * 
         * @version 6.0.0 (Bearer Token 認證模式 - 移除 CSRF 依賴)
         */
        $middleware->group('web', [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        $middleware->group('api', [
            \Illuminate\Http\Middleware\HandleCors::class,  // API 專用 CORS 處理
            // 已移除 EnsureFrontendRequestsAreStateful，使用純 Bearer Token 認證
        ]);
        
        /**
         * 全域中間件，確保所有請求都有 CORS 支援
         */
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
