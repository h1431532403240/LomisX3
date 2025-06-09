<?php

use Knuckles\Scribe\Extracting\Strategies;
use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Config\AuthIn;
use function Knuckles\Scribe\Config\{removeStrategies, configureStrategy};

// Only the most common configs are shown. See the https://scribe.knuckles.wtf/laravel/reference/config for all.

return [
    /*
     * 文檔標題和描述
     */
    'title' => 'LomisX3 商品分類 API 文檔',
    'description' => '商品分類模組的完整 RESTful API 文檔，包含分頁、篩選、樹狀結構等功能',
    'base_url' => env('APP_URL', 'http://localhost'),
    'version' => '2.0.0',

    /*
     * 路由設定
     */
    'routes' => [
        [
            'match' => [
                'domains' => ['*'],
                'prefixes' => ['api/*'],
                'versions' => ['v1'],
            ],
            'include' => [
                'api/product-categories*',
                'api/categories*',
            ],
            'exclude' => [
                'api/admin/*',
                'api/internal/*',
            ],
        ],
    ],

    /*
     * 認證設定
     */
    'auth' => [
        'enabled' => true,
        'default' => false,
        'in' => 'bearer',
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => 'YOUR_AUTH_TOKEN_HERE',
        'extra_info' => '使用 Laravel Sanctum Bearer Token 進行認證。請在請求頭中包含：Authorization: Bearer {token}',
    ],

    /*
     * 輸出設定
     */
    'type' => 'static',
    'static' => [
        'output_path' => 'public/docs',
    ],

    /*
     * 樣式設定
     */
    'theme' => 'default',
    'logo' => '',
    'favicon' => '',

    /*
     * 範例設定
     */
    'examples' => [
        'faker_seed' => 1234,
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],

    /*
     * 群組設定
     */
    'groups' => [
        'file' => 'custom.md',
        'order' => [
            '商品分類管理',
            '統計與監控',
            '系統管理',
        ],
    ],

    /*
     * 語言設定
     */
    'locale' => 'zh_TW',

    /*
     * 解析設定
     */
    'extract' => [
        'strategies' => [
            'metadata' => [
                \Knuckles\Scribe\Extracting\Strategies\Metadata\GetFromDocBlocks::class,
            ],
            'urlParameters' => [
                \Knuckles\Scribe\Extracting\Strategies\UrlParameters\GetFromLaravelAPI::class,
                \Knuckles\Scribe\Extracting\Strategies\UrlParameters\GetFromUrlParamTag::class,
            ],
            'queryParameters' => [
                \Knuckles\Scribe\Extracting\Strategies\QueryParameters\GetFromFormRequest::class,
                \Knuckles\Scribe\Extracting\Strategies\QueryParameters\GetFromInlineValidator::class,
                \Knuckles\Scribe\Extracting\Strategies\QueryParameters\GetFromQueryParamTag::class,
            ],
            'headers' => [
                \Knuckles\Scribe\Extracting\Strategies\Headers\GetFromRouteRules::class,
                \Knuckles\Scribe\Extracting\Strategies\Headers\GetFromHeaderTag::class,
            ],
            'bodyParameters' => [
                \Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromFormRequest::class,
                \Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromInlineValidator::class,
                \Knuckles\Scribe\Extracting\Strategies\BodyParameters\GetFromBodyParamTag::class,
            ],
            'responses' => [
                \Knuckles\Scribe\Extracting\Strategies\Responses\UseTransformerTags::class,
                \Knuckles\Scribe\Extracting\Strategies\Responses\UseApiResourceTags::class,
                \Knuckles\Scribe\Extracting\Strategies\Responses\UseResponseTag::class,
                \Knuckles\Scribe\Extracting\Strategies\Responses\UseResponseFileTag::class,
                \Knuckles\Scribe\Extracting\Strategies\Responses\ResponseCalls::class,
            ],
            'responseFields' => [
                \Knuckles\Scribe\Extracting\Strategies\ResponseFields\GetFromResponseFieldTag::class,
            ],
        ],
    ],

    /*
     * 資料庫設定
     */
    'database_connections_to_transact' => [config('database.default')],

    /*
     * Laravel 特定設定
     */
    'laravel' => [
        'add_routes' => true,
        'docs_url' => '/docs',
        'assets_directory' => null,
        'middleware' => [],
    ],

    /*
     * Try It Out 設定
     */
    'try_it_out' => [
        'enabled' => true,
        'base_url' => null,
        'use_csrf' => false,
    ],

    'fractal' => [
        'serializer' => null,
    ],

    'routeMatcher' => \Knuckles\Scribe\Matching\RouteMatcher::class,

    'openapi' => [
        'enabled' => true,
        'overrides' => [],
    ],

    'postman' => [
        'enabled' => true,
        'overrides' => [],
    ],
];
