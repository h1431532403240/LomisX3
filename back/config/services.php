<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry 分散式追蹤配置
    |--------------------------------------------------------------------------
    |
    | 配置 OpenTelemetry 分散式追蹤系統
    | 支援 Jaeger、Zipkin、OTLP 等多種後端
    |
    */

    'opentelemetry' => [
        'enabled' => env('OTEL_ENABLED', false),
        
        // 批次處理配置
        'batch_size' => env('OTEL_BATCH_SIZE', 512),
        'timeout_ms' => env('OTEL_TIMEOUT_MS', 30000),
        'max_queue_size' => env('OTEL_MAX_QUEUE_SIZE', 2048),
        'max_export_batch_size' => env('OTEL_MAX_EXPORT_BATCH_SIZE', 512),
        
        // 取樣配置
        'sampling_ratio' => env('OTEL_SAMPLING_RATIO', 1.0),
        
        // 輸出器配置
        'exporters' => [
            [
                'type' => env('OTEL_EXPORTER_TYPE', 'otlp'),
                'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318/v1/traces'),
                'headers' => [
                    'Authorization' => env('OTEL_EXPORTER_OTLP_HEADERS_AUTHORIZATION', ''),
                ],
            ],
            
            // 開發環境可啟用控制台輸出
            [
                'type' => 'console',
                'enabled' => env('APP_ENV') === 'local',
            ],
        ],
        
        // 資源屬性
        'resource' => [
            'service.name' => env('OTEL_SERVICE_NAME', config('app.name')),
            'service.version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
            'deployment.environment' => env('OTEL_DEPLOYMENT_ENVIRONMENT', config('app.env')),
        ],
    ],

];
