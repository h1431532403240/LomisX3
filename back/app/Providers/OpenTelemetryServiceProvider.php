<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

/**
 * OpenTelemetry 服務提供者
 * 
 * 負責初始化和配置 OpenTelemetry 追蹤系統
 * 支援 Jaeger、OTLP 等多種輸出器
 */
class OpenTelemetryServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     * 配置 OpenTelemetry 追蹤器和資源資訊
     */
    public function register(): void
    {
        // 檢查是否啟用 OpenTelemetry
        if (!config('services.opentelemetry.enabled', false)) {
            return;
        }

        $this->app->singleton(TracerProviderInterface::class, function () {
            return $this->createTracerProvider();
        });

        // 設定全域追蹤器提供者
        $this->app->resolving(TracerProviderInterface::class, function (TracerProviderInterface $tracerProvider) {
            Globals::registerInitialTracerProvider($tracerProvider);
        });
    }

    /**
     * 啟動服務
     * 初始化追蹤器
     */
    public function boot(): void
    {
        if (config('services.opentelemetry.enabled', false)) {
            $this->app->make(TracerProviderInterface::class);
        }
    }

    /**
     * 建立追蹤器提供者
     * 配置資源資訊、span 處理器和輸出器
     * 
     * @return TracerProviderInterface
     */
    private function createTracerProvider(): TracerProviderInterface
    {
        // 建立資源資訊
        $resource = $this->createResourceInfo();
        
        // 建立追蹤器提供者
        $tracerProvider = TracerProvider::builder()
            ->setResource($resource);

        // 添加 span 處理器
        $spanProcessors = $this->createSpanProcessors();
        foreach ($spanProcessors as $processor) {
            $tracerProvider->addSpanProcessor($processor);
        }

        return $tracerProvider->build();
    }

    /**
     * 建立資源資訊
     * 包含服務名稱、版本、環境等基本資訊
     * 
     * @return ResourceInfo
     */
    private function createResourceInfo(): ResourceInfo
    {
        $attributes = Attributes::create([
            'service.name' => config('app.name', 'laravel-app'),
            'service.namespace' => 'product-category',
            'service.version' => config('app.version', '1.0.0'),
            'deployment.environment' => config('app.env', 'production'),
            'host.name' => gethostname() ?: 'unknown',
            'telemetry.sdk.name' => 'opentelemetry',
            'telemetry.sdk.language' => 'php',
            'telemetry.sdk.version' => '1.0.0',
        ]);

        return ResourceInfoFactory::create($attributes);
    }

    /**
     * 建立 span 處理器陣列
     * 根據配置創建不同的輸出器
     * 
     * @return array<BatchSpanProcessor>
     */
    private function createSpanProcessors(): array
    {
        $processors = [];
        $exporters = config('services.opentelemetry.exporters', []);

        foreach ($exporters as $exporterConfig) {
            $exporter = $this->createExporter($exporterConfig);
            if ($exporter) {
                $processors[] = new BatchSpanProcessor(
                    $exporter,
                    config('services.opentelemetry.batch_size', 512),
                    config('services.opentelemetry.timeout_ms', 30000),
                    config('services.opentelemetry.max_queue_size', 2048),
                    config('services.opentelemetry.max_export_batch_size', 512)
                );
            }
        }

        return $processors;
    }

    /**
     * 建立輸出器
     * 支援 OTLP、Jaeger 等多種輸出格式
     * 
     * @param array $config 輸出器配置
     * @return mixed
     */
    private function createExporter(array $config)
    {
        $type = $config['type'] ?? 'otlp';

        switch ($type) {
            case 'otlp':
                return $this->createOtlpExporter($config);
            
            case 'jaeger':
                return $this->createJaegerExporter($config);
            
            case 'console':
                return $this->createConsoleExporter();
            
            default:
                \Log::warning("Unknown OpenTelemetry exporter type: {$type}");
                return null;
        }
    }

    /**
     * 建立 OTLP 輸出器
     * 用於輸出到 OTLP 相容的接收器 (如 Jaeger、Zipkin)
     * 
     * @param array $config
     * @return SpanExporter
     */
    private function createOtlpExporter(array $config): SpanExporter
    {
        $transport = (new OtlpHttpTransportFactory())->create(
            $config['endpoint'] ?? 'http://localhost:4318/v1/traces',
            'application/x-protobuf',
            $config['headers'] ?? []
        );

        return new SpanExporter($transport);
    }

    /**
     * 建立 Jaeger 輸出器
     * 直接輸出到 Jaeger Agent
     * 
     * @param array $config
     * @return \OpenTelemetry\Contrib\Jaeger\SpanExporter
     */
    private function createJaegerExporter(array $config)
    {
        if (!class_exists('\OpenTelemetry\Contrib\Jaeger\SpanExporter')) {
            \Log::warning('Jaeger exporter not available. Install open-telemetry/exporter-jaeger');
            return null;
        }

        return new \OpenTelemetry\Contrib\Jaeger\SpanExporter(
            $config['endpoint'] ?? 'http://localhost:14268/api/traces',
            $config['agent_host'] ?? 'localhost',
            $config['agent_port'] ?? 6832
        );
    }

    /**
     * 建立控制台輸出器（開發用）
     * 
     * @return \OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporter
     */
    private function createConsoleExporter()
    {
        return new \OpenTelemetry\SDK\Trace\SpanExporter\ConsoleSpanExporter();
    }
} 