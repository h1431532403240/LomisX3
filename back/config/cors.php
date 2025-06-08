<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration - V6.0 權威版本
    |--------------------------------------------------------------------------
    |
    | 最嚴格、最明確的 CORS 配置，消除所有歧義
    | 不再使用萬用字元，明確列出所有需要的方法和標頭
    | 
    | 特別針對 LomisX3 Pure Bearer Token 認證模式優化
    |
    */

    /**
     * 指定哪些路徑允許跨域請求
     * api/* - 所有 API 端點
     * sanctum/csrf-cookie - CSRF token 端點（向後兼容）
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /**
     * 明確允許的 HTTP 方法
     * 不使用萬用字元，列出所有需要的方法
     */
    'allowed_methods' => ['POST', 'GET', 'OPTIONS', 'PUT', 'PATCH', 'DELETE'],

    /**
     * 明確允許的來源域名
     * 不使用萬用字元，明確列出前端域名
     */
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        // 未來可以添加生產環境的前端 URL
    ],

    'allowed_origins_patterns' => [],

    /**
     * 明確允許的請求標頭
     * 不使用萬用字元，列出所有需要的標頭
     * Authorization - Bearer Token 認證
     * Content-Type - JSON 請求內容類型
     * X-Requested-With - AJAX 請求標識
     * X-XSRF-TOKEN - CSRF 保護（向後兼容）
     * Accept - 回應內容類型協商
     */
    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'X-XSRF-TOKEN',
        'Accept',
    ],

    /**
     * 暴露給前端的回應標頭
     * 空陣列表示不暴露額外標頭，提高安全性
     */
    'exposed_headers' => [],

    /**
     * Preflight 請求的最大快取時間 (秒)
     * 0 表示不快取，每次都重新驗證，確保即時性
     */
    'max_age' => 0,

    /**
     * 支援認證 Credentials
     * true 以支援 Authorization 標頭和其他認證機制
     */
    'supports_credentials' => true,

];
