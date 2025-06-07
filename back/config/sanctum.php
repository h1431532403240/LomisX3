<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | 🚫 純 Bearer Token 模式 - 已禁用狀態化域名
    | 
    | 在純 Bearer Token API 模式下，我們不使用基於 Cookie 的認證
    | 所有請求都使用 Authorization: Bearer {token} 標頭進行認證
    | 因此不需要配置任何狀態化域名
    |
    */

    /**
     * 狀態化域名配置 - 純 Bearer Token 模式已禁用
     * 
     * 注意：在純 Bearer Token 模式下，此陣列應保持為空
     * 所有認證都通過 Authorization 標頭處理，無需 Cookie
     */
    'stateful' => [
        // 🚫 純 Bearer Token 模式：不需要任何狀態化域名
        // 所有 API 請求都使用 Authorization: Bearer {token} 標頭
    ],

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | 純 Bearer Token 模式下，以下中間件設定僅作為向後相容保留
    | 實際上不會被使用，因為所有認證都透過 Bearer Token 處理
    | 無需 Session、Cookie 或 CSRF 處理
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
