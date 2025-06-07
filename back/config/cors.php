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
     * 純 Bearer Token 模式只需要 API 路徑
     */
    'paths' => ['api/*'],

    /**
     * 允許的 HTTP 方法
     * 使用萬用字元以支援所有方法和 preflight 請求
     */
    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    /**
     * 允許的請求標頭
     * 使用萬用字元以支援所有標頭
     */
    'allowed_headers' => ['*'],

    /**
     * 暴露給前端的回應標頭
     * 空陣列表示不暴露額外標頭
     */
    'exposed_headers' => [],

    /**
     * Preflight 請求的最大快取時間 (秒)
     * 0 表示不快取，每次都重新驗證
     */
    'max_age' => 0,

    /**
     * 支援認證 Credentials
     * 純 Bearer Token 模式不依賴 Cookie，但保持 true 以支援其他認證標頭
     */
    'supports_credentials' => true,

];
