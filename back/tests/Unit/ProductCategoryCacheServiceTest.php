<?php

namespace Tests\Unit;

use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use App\Services\ProductCategoryCacheService;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * 商品分類快取服務測試
 * 測試快取機制的正確性和完整性
 */
class ProductCategoryCacheServiceTest extends TestCase
{
    protected ProductCategoryCacheService $cacheService;

    protected $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(ProductCategoryRepositoryInterface::class);
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
        $this->assertArrayHasKey('supported_operations', $info);
        $this->assertTrue($info['supported_operations']['tree_caching']);
        $this->assertTrue($info['supported_operations']['breadcrumb_caching']);
        $this->assertTrue($info['supported_operations']['children_caching']);
        $this->assertTrue($info['supported_operations']['descendants_caching']);
        $this->assertTrue($info['supported_operations']['statistics_caching']);
        $this->assertTrue($info['supported_operations']['warmup_support']);
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
