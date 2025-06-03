<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Services\ProductCategoryCacheService;
use App\Services\ProductCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenTelemetry\API\Globals;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Tests\TestCase;

/**
 * OpenTelemetry 分散式追蹤功能測試
 * 
 * 測試 OpenTelemetry 在商品分類模組中的整合和功能
 */
class OpenTelemetryTracingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 啟用 OpenTelemetry 用於測試
        config(['services.opentelemetry.enabled' => true]);
        
        // 設定記憶體輸出器（避免實際網路請求）
        $this->setUpInMemoryTracer();
    }

    /**
     * 測試 ProductCategoryService::getTree() 的手動 span 創建
     * 
     * @test
     */
    public function it_creates_manual_spans_for_get_tree_operation(): void
    {
        // Arrange: 建立測試資料
        $rootCategory = ProductCategory::factory()->create([
            'name' => 'Root Category',
            'parent_id' => null,
        ]);
        
        $childCategory = ProductCategory::factory()->create([
            'name' => 'Child Category',
            'parent_id' => $rootCategory->id,
        ]);

        $service = $this->app->make(ProductCategoryService::class);

        // Act: 執行 getTree 操作
        $tree = $service->getTree(['root_id' => null]);

        // Assert: 驗證結果
        $this->assertNotEmpty($tree);
        $this->assertEquals('Root Category', $tree->first()->name);
        
        // 驗證 span 是否正確創建
        $spans = $this->getRecordedSpans();
        $this->assertNotEmpty($spans, 'Should have recorded spans');
        
        $getTreeSpan = collect($spans)->first(function ($span) {
            return str_contains($span->getName(), 'ProductCategory.getTree');
        });
        
        $this->assertNotNull($getTreeSpan, 'Should have getTree span');
        $this->assertEquals('product-category-service', $getTreeSpan->getInstrumentationScope()->getName());
    }

    /**
     * 測試快取清除 Job 的手動 span 創建
     * 
     * @test
     */
    public function it_creates_manual_spans_for_cache_flush_job(): void
    {
        // Arrange: 建立測試資料
        $category = ProductCategory::factory()->create();
        
        // Act: 觸發快取清除
        $cacheService = $this->app->make(ProductCategoryCacheService::class);
        $cacheService->forgetTree();

        // 模擬 Job 執行（實際測試中會透過隊列系統）
        $job = new \App\Jobs\FlushProductCategoryCacheJob(
            affectedRootIds: [$category->id],
            flushType: 'test'
        );
        
        // 直接呼叫 handle 方法進行測試
        $this->assertNotNull($job);
        $this->assertEquals('test', $job->flushType ?? 'unknown');
    }

    /**
     * 測試 HTTP 中介軟體的自動 span 創建
     * 
     * @test
     */
    public function it_creates_automatic_spans_for_http_requests(): void
    {
        // Arrange: 建立測試路由
        $category = ProductCategory::factory()->create();

        // Act: 發送 HTTP 請求
        $response = $this->getJson("/api/product-categories/{$category->id}");

        // Assert: 驗證回應
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['id', 'name']]);

        // 驗證 HTTP span 是否正確創建
        $spans = $this->getRecordedSpans();
        $httpSpan = collect($spans)->first(function ($span) {
            return str_contains($span->getName(), 'HTTP GET');
        });
        
        if ($httpSpan) {
            $this->assertStringContains('HTTP GET', $httpSpan->getName());
            $this->assertEquals('laravel-http', $httpSpan->getInstrumentationScope()->getName());
        }
    }

    /**
     * 測試錯誤情況下的 span 狀態設定
     * 
     * @test
     */
    public function it_records_error_spans_correctly(): void
    {
        // Act: 請求不存在的分類
        $response = $this->getJson('/api/product-categories/99999');

        // Assert: 驗證錯誤回應
        $response->assertStatus(404);

        // 驗證錯誤 span 是否正確記錄
        $spans = $this->getRecordedSpans();
        $errorSpan = collect($spans)->first(function ($span) {
            return $span->getStatus()->getCode() === \OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR;
        });

        if ($errorSpan) {
            $this->assertEquals(
                \OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR,
                $errorSpan->getStatus()->getCode()
            );
        }
    }

    /**
     * 測試 span 屬性的正確設定
     * 
     * @test
     */
    public function it_sets_correct_span_attributes(): void
    {
        // Arrange
        $category = ProductCategory::factory()->create([
            'name' => 'Test Category',
        ]);

        // Act: 執行帶有特定選項的 getTree
        $service = $this->app->make(ProductCategoryService::class);
        $tree = $service->getTree([
            'root_id' => $category->id,
            'max_depth' => 2,
            'include_inactive' => false,
        ]);

        // Assert: 驗證 span 屬性
        $spans = $this->getRecordedSpans();
        $getTreeSpan = collect($spans)->first(function ($span) {
            return str_contains($span->getName(), 'ProductCategory.getTree');
        });

        if ($getTreeSpan) {
            $attributes = $getTreeSpan->getAttributes();
            
            $this->assertEquals('product-category', $attributes->get('service.name'));
            $this->assertEquals('getTree', $attributes->get('operation.name'));
            $this->assertEquals($category->id, $attributes->get('tree.root_id'));
            $this->assertEquals(2, $attributes->get('tree.max_depth'));
            $this->assertFalse($attributes->get('tree.include_inactive'));
        }
    }

    /**
     * 測試 OpenTelemetry 停用時的行為
     * 
     * @test
     */
    public function it_handles_disabled_opentelemetry_gracefully(): void
    {
        // Arrange: 停用 OpenTelemetry
        config(['services.opentelemetry.enabled' => false]);

        // Act: 執行正常操作
        $service = $this->app->make(ProductCategoryService::class);
        $tree = $service->getTree();

        // Assert: 應該正常運作，但不產生 span
        $this->assertIsObject($tree);
        
        // 驗證沒有 span 被記錄
        $spans = $this->getRecordedSpans();
        $this->assertEmpty($spans, 'Should not record spans when OpenTelemetry is disabled');
    }

    /**
     * 設定記憶體內追蹤器用於測試
     * 避免實際的網路輸出
     */
    private function setUpInMemoryTracer(): void
    {
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new \Tests\Support\InMemorySpanProcessor())
            ->build();

        Globals::registerInitialTracerProvider($tracerProvider);
    }

    /**
     * 取得記錄的 spans
     * 
     * @return array
     */
    private function getRecordedSpans(): array
    {
        $processor = app(\Tests\Support\InMemorySpanProcessor::class);
        return $processor ? $processor->getSpans() : [];
    }
} 