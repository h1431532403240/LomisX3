<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /**
     * 指定哪些路徑允許跨域請求
     * 包含 API 路由和 Sanctum CSRF cookie 端點
     * 
     * @version 5.1.0 (修復 CORS Preflight 支援)
     */
    'paths' => [
        'api/*', 
        'sanctum/csrf-cookie',
        'broadcasting/auth', // 廣播認證支援 (如需要)
    ],

    /**
     * 允許的 HTTP 方法
     * 包含 OPTIONS 以支援 preflight 請求
     */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /**
     * 允許的來源 (支援 Laravel Sanctum SPA 認證)
     * 包含 Vite 開發服務器和生產環境域名
     */
    'allowed_origins' => [
        'http://localhost:5173',    // Vite 開發服務器
        'http://localhost:3000',    // React 開發服務器 (備用)
        'http://127.0.0.1:5173',    // IPv4 本地回環
        'http://127.0.0.1:3000',    // IPv4 本地回環 (備用)
        // 生產環境時需要添加實際域名
    ],

    'allowed_origins_patterns' => [],

    /**
     * 允許的請求標頭
     * 包含 Sanctum 和 API 必需的標頭
     */
    'allowed_headers' => [
        'Accept',
        'Accept-Language',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-XSRF-TOKEN',
        'X-CSRF-TOKEN',
        'DNT',
        'User-Agent',
        'If-Modified-Since',
        'Cache-Control',
    ],

    /**
     * 暴露給前端的回應標頭
     * 用於前端獲取特定的回應資訊
     */
    'exposed_headers' => [
        'Content-Length',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    /**
     * Preflight 請求的最大快取時間 (秒)
     * 0 表示不快取，每次都重新驗證
     */
    'max_age' => 86400, // 24 小時

    /**
     * 支援認證 Cookie (Laravel Sanctum SPA 認證必需)
     * 這允許前端傳送和接收認證相關的 Cookie
     */
    'supports_credentials' => true,

];
