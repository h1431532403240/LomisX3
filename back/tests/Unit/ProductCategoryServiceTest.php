<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ProductCategoryErrorCode;
use App\Exceptions\BusinessException;
use App\Models\ProductCategory;
use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use App\Services\ProductCategoryCacheService;
use App\Services\ProductCategoryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

/**
 * ProductCategoryService 單元測試
 * 
 * 測試商品分類服務層的所有業務邏輯
 */
class ProductCategoryServiceTest extends TestCase
{
    /**
     * 商品分類儲存庫模擬物件
     */
    private $repository;

    /**
     * 快取服務模擬物件
     */
    private $cacheService;

    /**
     * 待測試的服務實例
     */
    private ProductCategoryService $service;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立模擬物件
        $this->repository = Mockery::mock(ProductCategoryRepositoryInterface::class);
        $this->cacheService = Mockery::mock(ProductCategoryCacheService::class);
        
        // 建立服務實例
        $this->service = new ProductCategoryService(
            $this->repository,
            $this->cacheService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試建立分類
     */
    public function test_create_category(): void
    {
        // 準備測試資料
        $data = [
            'name' => '新分類',
            'description' => '分類描述',
            'parent_id' => null,
            'status' => true,
        ];

        $expectedCategory = new ProductCategory(['id' => 1] + $data);

        // 設定期望 - generateUniqueSlug 內的 slugExists 檢查
        // 中文名稱會轉換為預設的 'category' slug
        $this->repository->shouldReceive('slugExists')
                        ->once()
                        ->with('category', null)
                        ->andReturn(false);

        // 設定期望 - createCategory 內的 slugExists 檢查（只有一個參數）
        $this->repository->shouldReceive('slugExists')
                        ->once()
                        ->with('category')
                        ->andReturn(false);

        // 設定期望 - 建立分類
        $this->repository->shouldReceive('create')
                        ->once()
                        ->with(Mockery::type('array'))
                        ->andReturn($expectedCategory);

        // 設定期望 - 清除快取
        $this->cacheService->shouldReceive('forgetTree')
                          ->once()
                          ->andReturnNull();

        // 執行測試
        $result = $this->service->createCategory($data);

        // 驗證結果
        $this->assertInstanceOf(ProductCategory::class, $result);
        $this->assertEquals('新分類', $result->name);
    }

    /**
     * 測試建立分類時的父分類驗證
     */
    public function test_create_category_with_invalid_parent(): void
    {
        // 準備測試資料
        $data = [
            'name' => '新分類',
            'parent_id' => 999, // 不存在的父分類
            'status' => true,
        ];

        // 設定期望 - 父分類不存在
        $this->repository->shouldReceive('findById')
                        ->once()
                        ->with(999)
                        ->andReturn(null);

        // 設定期望 - 不會建立分類
        $this->repository->shouldNotReceive('create');

        // 執行測試並預期例外
        $this->expectException(BusinessException::class);

        $this->service->createCategory($data);
    }

    /**
     * 測試更新分類
     */
    public function test_update_category(): void
    {
        // 準備測試資料
        $categoryId = 1;
        $updateData = [
            'name' => '更新後名稱',
            'description' => '更新後描述',
        ];

        $existingCategory = new ProductCategory([
            'id' => $categoryId,
            'name' => '原始名稱',
            'description' => '原始描述',
        ]);

        $updatedCategory = new ProductCategory([
            'id' => $categoryId,
            'name' => '更新後名稱',
            'description' => '更新後描述',
        ]);

        // 設定期望 - 查找分類
        $this->repository->shouldReceive('findById')
                        ->once()
                        ->with($categoryId)
                        ->andReturn($existingCategory);

        // 設定期望 - generateUniqueSlug 內的 slugExists 檢查
        // 中文名稱會轉換為預設的 'category' slug
        $this->repository->shouldReceive('slugExists')
                        ->once()
                        ->with('category', $categoryId)
                        ->andReturn(false);

        // 設定期望 - updateCategory 內的 slugExists 檢查（帶兩個參數）
        $this->repository->shouldReceive('slugExists')
                        ->once()
                        ->with('category', $categoryId)
                        ->andReturn(false);

        // 設定期望 - 更新分類
        $this->repository->shouldReceive('update')
                        ->once()
                        ->with($categoryId, Mockery::type('array'))
                        ->andReturn($updatedCategory);

        // 設定期望 - 清除快取
        $this->cacheService->shouldReceive('forgetTree')
                          ->once()
                          ->andReturnNull();

        // 執行測試
        $result = $this->service->updateCategory($categoryId, $updateData);

        // 驗證結果
        $this->assertInstanceOf(ProductCategory::class, $result);
        $this->assertEquals('更新後名稱', $result->name);
    }

    /**
     * 測試更新不存在的分類
     */
    public function test_update_nonexistent_category(): void
    {
        // 準備測試資料
        $categoryId = 999;
        $updateData = ['name' => '更新名稱'];

        // 設定期望 - 分類不存在
        $this->repository->shouldReceive('findById')
                        ->once()
                        ->with($categoryId)
                        ->andReturn(null);

        // 設定期望 - 不會執行更新
        $this->repository->shouldNotReceive('update');

        // 執行測試並預期例外
        $this->expectException(BusinessException::class);

        $this->service->updateCategory($categoryId, $updateData);
    }

    /**
     * 測試刪除分類
     */
    public function test_delete_category(): void
    {
        // 準備測試資料
        $categoryId = 1;
        $category = new ProductCategory([
            'id' => $categoryId,
            'name' => '要刪除的分類',
        ]);

        // 設定期望 - 查找分類
        $this->repository->shouldReceive('findById')
                        ->once()
                        ->with($categoryId)
                        ->andReturn($category);

        // 設定期望 - 沒有子分類
        $this->repository->shouldReceive('hasChildren')
                        ->once()
                        ->with($categoryId)
                        ->andReturn(false);

        // 設定期望 - 刪除分類
        $this->repository->shouldReceive('delete')
                        ->once()
                        ->with($categoryId)
                        ->andReturn(true);

        // 設定期望 - 清除快取
        $this->cacheService->shouldReceive('forgetTree')
                          ->once()
                          ->andReturnNull();

        // 執行測試
        $result = $this->service->deleteCategory($categoryId);

        // 驗證結果
        $this->assertTrue($result);
    }

    /**
     * 測試刪除有子分類的分類
     */
    public function test_delete_category_with_children(): void
    {
        // 準備測試資料
        $categoryId = 1;
        $category = new ProductCategory([
            'id' => $categoryId,
            'name' => '有子分類的分類',
        ]);

        // 設定期望 - 查找分類
        $this->repository->shouldReceive('findById')
                        ->once()
                        ->with($categoryId)
                        ->andReturn($category);

        // 設定期望 - 有子分類
        $this->repository->shouldReceive('hasChildren')
                        ->once()
                        ->with($categoryId)
                        ->andReturn(true);

        // 設定期望 - 不會執行刪除
        $this->repository->shouldNotReceive('delete');

        // 執行測試並預期例外
        $this->expectException(BusinessException::class);

        $this->service->deleteCategory($categoryId);
    }

    /**
     * 測試從快取取得樹狀結構
     */
    public function test_get_tree_from_cache(): void
    {
        // 準備測試資料
        $options = ['root_id' => null];
        $treeData = new Collection([
            new ProductCategory(['id' => 1, 'name' => '分類1']),
            new ProductCategory(['id' => 2, 'name' => '分類2']),
        ]);

        // 設定期望 - 從快取取得樹狀結構
        $this->cacheService->shouldReceive('getTree')
                          ->once()
                          ->with($options)
                          ->andReturn($treeData);

        // 執行測試
        $result = $this->service->getTree($options);

        // 驗證結果
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(2, $result->count());
    }

    /**
     * 測試批次更新狀態
     */
    public function test_batch_update_status(): void
    {
        // 準備測試資料
        $ids = [1, 2, 3];
        $status = true;
        
        // 建立測試分類集合
        $categories = new Collection([
            new ProductCategory(['id' => 1, 'name' => '分類1', 'status' => false]),
            new ProductCategory(['id' => 2, 'name' => '分類2', 'status' => false]),
            new ProductCategory(['id' => 3, 'name' => '分類3', 'status' => false]),
        ]);

        // 設定期望 - 驗證分類是否存在
        $this->repository->shouldReceive('findByIds')
                        ->once()
                        ->with($ids)
                        ->andReturn($categories);

        // 設定期望 - 批次更新狀態
        $this->repository->shouldReceive('batchUpdateStatus')
                        ->once()
                        ->with($ids, $status)
                        ->andReturn(3);

        // 設定期望 - 清除快取
        $this->cacheService->shouldReceive('forgetTree')
                          ->once()
                          ->andReturnNull();

        // 執行測試
        $result = $this->service->batchUpdateStatus($ids, $status);

        // 驗證結果
        $this->assertEquals(3, $result);
    }

    /**
     * 測試更新排序
     */
    public function test_update_sort_order(): void
    {
        // 準備測試資料
        $positions = [
            ['id' => 1, 'position' => 1],
            ['id' => 2, 'position' => 2],
        ];

        // 設定期望 - 更新位置
        $this->repository->shouldReceive('updatePositions')
                        ->once()
                        ->with($positions)
                        ->andReturn(true);

        // 設定期望 - 清除快取
        $this->cacheService->shouldReceive('forgetTree')
                          ->once()
                          ->andReturnNull();

        // 執行測試
        $result = $this->service->updatePositions($positions);

        // 驗證結果
        $this->assertTrue($result);
    }

    /**
     * 測試產生唯一 slug
     */
    public function test_generate_unique_slug(): void
    {
        // 準備測試資料
        $name = '測試分類';
        
        // 設定期望 - generateUniqueSlug 內的 slugExists 檢查
        // 中文名稱會轉換為預設的 'category' slug
        $this->repository->shouldReceive('slugExists')
                        ->once()
                        ->with('category', null)
                        ->andReturn(false);

        // 執行測試
        $result = $this->service->generateUniqueSlug($name);

        // 驗證結果
        $this->assertEquals('category', $result);
    }

    /**
     * 測試取得麵包屑
     */
    public function test_get_breadcrumbs(): void
    {
        // 準備測試資料
        $categoryId = 3;
        $breadcrumbs = new Collection([
            new ProductCategory(['id' => 1, 'name' => '根分類']),
            new ProductCategory(['id' => 2, 'name' => '子分類']),
            new ProductCategory(['id' => 3, 'name' => '目標分類']),
        ]);

        // 設定期望 - 取得麵包屑（返回 Collection）
        $this->cacheService->shouldReceive('getBreadcrumbs')
                          ->once()
                          ->with($categoryId)
                          ->andReturn($breadcrumbs);

        // 執行測試
        $result = $this->service->getCachedBreadcrumbs($categoryId);

        // 驗證結果
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(3, $result->count());
    }
} 