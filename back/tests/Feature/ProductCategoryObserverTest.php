<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ProductCategory;
use App\Services\ProductCategoryCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Bus;
use Mockery;

/**
 * ProductCategory Observer 功能測試
 * 
 * 測試 Observer 在模型事件中的行為，包括：
 * - Slug 生成與唯一性處理
 * - 父子關係變更時的深度批量更新
 * - 快取清除的觸發機制
 */
class ProductCategoryObserverTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategoryCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 模擬快取服務
        $this->cacheService = Mockery::mock(ProductCategoryCacheService::class);
        $this->app->instance(ProductCategoryCacheService::class, $this->cacheService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試建立時自動生成 slug
     */
    public function test_creating_generates_slug_automatically(): void
    {
        // 準備測試資料
        $categoryData = [
            'name' => '電子產品',
            'description' => '各種電子產品分類',
            'is_active' => true,
        ];

        // 預期快取服務會被呼叫
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->once()
                          ->andReturn();

        // 執行測試
        $category = ProductCategory::create($categoryData);

        // 驗證結果
        $this->assertNotNull($category->slug);
        $this->assertEquals('電子產品', $category->slug);
        $this->assertDatabaseHas('product_categories', [
            'name' => '電子產品',
            'slug' => '電子產品',
        ]);
    }

    /**
     * 測試 slug 衝突時的重試機制（最多3次）
     */
    public function test_slug_generation_with_conflict_retry(): void
    {
        // 先建立一個分類佔用slug
        ProductCategory::create([
            'name' => '手機',
            'slug' => '手機',
            'is_active' => true,
        ]);

        // 模擬快取服務呼叫
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->twice()
                          ->andReturn();

        // 建立同名分類，應該自動生成不重複的slug
        $category = ProductCategory::create([
            'name' => '手機',
            'is_active' => true,
        ]);

        // 驗證新分類有不同的slug
        $this->assertNotEquals('手機', $category->slug);
        $this->assertStringContains('手機', $category->slug);
        
        // 驗證資料庫中有兩筆不同的記錄
        $this->assertDatabaseCount('product_categories', 2);
    }

    /**
     * 測試父分類變更時的深度批量更新
     */
    public function test_parent_change_triggers_depth_update(): void
    {
        // 建立多層級結構
        $root = ProductCategory::create(['name' => '根分類']);
        $parent1 = ProductCategory::create(['name' => '父分類1', 'parent_id' => $root->id]);
        $parent2 = ProductCategory::create(['name' => '父分類2', 'parent_id' => $root->id]);
        $child = ProductCategory::create(['name' => '子分類', 'parent_id' => $parent1->id]);
        $grandchild = ProductCategory::create(['name' => '孫分類', 'parent_id' => $child->id]);

        // 模擬快取服務呼叫（建立階段）
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->times(5) // 建立5個分類
                          ->andReturn();

        // 模擬變更時的快取清除
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->once()
                          ->with(Mockery::type(ProductCategory::class), Mockery::any())
                          ->andReturn();

        // 重新整理模型以確保正確的深度
        $root->refresh();
        $parent1->refresh();
        $parent2->refresh();
        $child->refresh();
        $grandchild->refresh();

        // 驗證初始深度
        $this->assertEquals(0, $root->depth);
        $this->assertEquals(1, $parent1->depth);
        $this->assertEquals(1, $parent2->depth);
        $this->assertEquals(2, $child->depth);
        $this->assertEquals(3, $grandchild->depth);

        // 將child移動到parent2下
        $child->update(['parent_id' => $parent2->id]);

        // 重新整理模型
        $child->refresh();
        $grandchild->refresh();

        // 驗證深度已正確更新
        $this->assertEquals(2, $child->depth);
        $this->assertEquals(3, $grandchild->depth);
    }

    /**
     * 測試刪除分類時觸發快取清除
     */
    public function test_deleting_triggers_cache_flush(): void
    {
        // 建立測試分類
        $category = ProductCategory::create([
            'name' => '測試分類',
            'is_active' => true,
        ]);

        // 模擬建立時的快取清除
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->once()
                          ->andReturn();

        // 模擬刪除時的快取清除  
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->once()
                          ->with(Mockery::type(ProductCategory::class), Mockery::any())
                          ->andReturn();

        // 執行刪除
        $category->delete();

        // 驗證已從資料庫中刪除
        $this->assertDatabaseMissing('product_categories', [
            'id' => $category->id,
        ]);
    }

    /**
     * 測試更新分類時觸發快取清除
     */
    public function test_updating_triggers_cache_flush(): void
    {
        // 建立測試分類
        $category = ProductCategory::create([
            'name' => '原始名稱',
            'is_active' => true,
        ]);

        // 模擬建立時的快取清除
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->once()
                          ->andReturn();

        // 模擬更新時的快取清除
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->once()
                          ->with(Mockery::type(ProductCategory::class), Mockery::any())
                          ->andReturn();

        // 執行更新
        $category->update(['name' => '更新後名稱']);

        // 驗證更新成功
        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => '更新後名稱',
        ]);
    }

    /**
     * 測試批量操作不會造成過多的快取清除呼叫
     */
    public function test_batch_operations_efficient_cache_flush(): void
    {
        // 建立多個分類
        $categories = [];
        for ($i = 1; $i <= 5; $i++) {
            $categories[] = [
                'name' => "分類 {$i}",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 使用批量插入避免觸發Observer
        ProductCategory::insert($categories);

        // 取得插入的分類
        $insertedCategories = ProductCategory::latest()->take(5)->get();

        // 模擬批量更新的快取清除（每個更新都會觸發一次）
        $this->cacheService->shouldReceive('forgetAffectedTreeParts')
                          ->times(5)
                          ->andReturn();

        // 批量更新
        foreach ($insertedCategories as $category) {
            $category->update(['description' => "描述 {$category->id}"]);
        }

        // 驗證所有分類都已更新
        foreach ($insertedCategories as $category) {
            $this->assertDatabaseHas('product_categories', [
                'id' => $category->id,
                'description' => "描述 {$category->id}",
            ]);
        }
    }
} 