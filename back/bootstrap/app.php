<?php

use App\Providers\AuthServiceProvider;
use App\Providers\RepositoryServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        RepositoryServiceProvider::class,
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * 為 API 路由群組添加中間件
         * CORS 中間件必須在 Sanctum 中間件之前執行
         * 
         * @version 5.1.0 (修復 CORS 順序問題)
         */
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,  // CORS 處理 (必須最先)
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,  // Sanctum SPA 狀態
        ]);

        /**
         * 全域中間件，確保所有請求都經過 CORS 檢查
         */
        $middleware->append([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
