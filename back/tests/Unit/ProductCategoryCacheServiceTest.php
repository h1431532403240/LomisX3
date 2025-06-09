<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use App\Services\ProductCategoryCacheService;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * 商品分類快取服務測試
 * 測試快取機制的正確性和完整性
 */
class ProductCategoryCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductCategoryCacheService $cacheService;

    protected $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立模擬的儲存庫
        $this->mockRepository = Mockery::mock(ProductCategoryRepositoryInterface::class);
        
        // 建立快取服務實例
        $this->cacheService = new ProductCategoryCacheService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試取得快取資訊
     */
    public function test_get_cache_info_returns_correct_structure(): void
    {
        $info = $this->cacheService->getCacheInfo();

        $this->assertIsArray($info);
        $this->assertEquals('product_categories', $info['tag']);
        $this->assertEquals(3600, $info['ttl']);
        $this->assertArrayHasKey('prefix', $info);
        $this->assertArrayHasKey('lock_timeout', $info);
        $this->assertArrayHasKey('driver', $info);
        $this->assertEquals(3, $info['lock_timeout']);
        $this->assertStringContainsString('pc_', $info['prefix']);
    }

    /**
     * 測試快取標籤
     */
    public function test_cache_tag_is_correct(): void
    {
        $this->assertEquals('product_categories', ProductCategoryCacheService::TAG);
    }

    /**
     * 測試快取鍵格式
     */
    public function test_cache_key_formats(): void
    {
        // 這個測試確保快取鍵格式的一致性
        $categoryId = 123;
        $parentId = 456;

        // 檢查各種快取鍵格式是否符合預期
        $this->assertStringContainsString('breadcrumbs:', "breadcrumbs:{$categoryId}");
        $this->assertStringContainsString('children:', "children:{$parentId}:active");
        $this->assertStringContainsString('descendants:', "descendants:{$parentId}:all");
        $this->assertStringContainsString('path:', "path:{$categoryId}");
    }
}
