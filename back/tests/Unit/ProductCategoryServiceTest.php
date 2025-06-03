<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use App\Repositories\ProductCategoryRepositoryInterface;
use App\Services\ProductCategoryCacheService;
use App\Exceptions\BusinessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

/**
 * ProductCategory Service 單元測試
 * 
 * 測試服務層的業務邏輯，包括：
 * - CRUD 操作的業務規則
 * - 快取整合機制
 * - 錯誤處理和驗證
 * - 複雜業務邏輯的正確性
 */
class ProductCategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategoryService $service;
    private ProductCategoryRepositoryInterface $repository;
    private ProductCategoryCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 模擬依賴
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
     * 測試取得分頁列表
     */
    public function test_get_paginated_list(): void
    {
        // 準備測試資料
        $categories = collect([
            new ProductCategory(['id' => 1, 'name' => '分類1']),
            new ProductCategory(['id' => 2, 'name' => '分類2']),
        ]);
        
        $paginator = new LengthAwarePaginator(
            $categories,
            2,
            10,
            1
        );

        // 設定期望
        $this->repository->shouldReceive('getPaginated')
                        ->once()
                        ->with(10, ['name', 'parent_id'])
                        ->andReturn($paginator);

        // 執行測試
        $result = $this->service->getPaginatedList(10, ['name', 'parent_id']);

        // 驗證結果
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());
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
            'is_active' => true,
        ];

        $expectedCategory = new ProductCategory(['id' => 1] + $data);

        // 設定期望
        $this->repository->shouldReceive('create')
                        ->once()
                        ->with($data)
                        ->andReturn($expectedCategory);

        // 執行測試
        $result = $this->service->create($data);

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
            'is_active' => true,
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
        $this->expectExceptionMessage('父分類不存在');

        $this->service->create($data);
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

        // 設定期望
        $this->repository->shouldReceive('findById')
                        ->once()
                        ->with($categoryId)
                        ->andReturn($existingCategory);

        $this->repository->shouldReceive('update')
                        ->once()
                        ->with($existingCategory, $updateData)
                        ->andReturn($updatedCategory);

        // 執行測試
        $result = $this->service->update($categoryId, $updateData);

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
        $this->expectExceptionMessage('分類不存在');

        $this->service->update($categoryId, $updateData);
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

        // 設定期望
        $this->repository->shouldReceive('findById')
                        ->once()
                        ->with($categoryId)
                        ->andReturn($category);

        $this->repository->shouldReceive('hasChildren')
                        ->once()
                        ->with($category)
                        ->andReturn(false);

        $this->repository->shouldReceive('delete')
                        ->once()
                        ->with($category)
                        ->andReturn(true);

        // 執行測試
        $result = $this->service->delete($categoryId);

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

        // 設定期望
        $this->repository->shouldReceive('findById')
                        ->once()
                        ->with($categoryId)
                        ->andReturn($category);

        $this->repository->shouldReceive('hasChildren')
                        ->once()
                        ->with($category)
                        ->andReturn(true);

        // 設定期望 - 不會執行刪除
        $this->repository->shouldNotReceive('delete');

        // 執行測試並預期例外
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('無法刪除擁有子分類的分類');

        $this->service->delete($categoryId);
    }

    /**
     * 測試取得樹狀結構（使用快取）
     */
    public function test_get_tree_from_cache(): void
    {
        // 準備測試資料
        $treeData = [
            [
                'id' => 1,
                'name' => '根分類',
                'children' => [
                    ['id' => 2, 'name' => '子分類1'],
                    ['id' => 3, 'name' => '子分類2'],
                ]
            ]
        ];

        // 設定期望 - 從快取取得
        $this->cacheService->shouldReceive('getTree')
                          ->once()
                          ->with(true)
                          ->andReturn($treeData);

        // 執行測試
        $result = $this->service->getTree(true);

        // 驗證結果
        $this->assertEquals($treeData, $result);
    }

    /**
     * 測試取得統計資訊
     */
    public function test_get_statistics(): void
    {
        // 準備測試資料
        $statsData = [
            'total' => 10,
            'active' => 8,
            'inactive' => 2,
            'max_depth' => 3,
        ];

        // 設定期望
        $this->cacheService->shouldReceive('getStatistics')
                          ->once()
                          ->andReturn($statsData);

        // 執行測試
        $result = $this->service->getStatistics();

        // 驗證結果
        $this->assertEquals($statsData, $result);
    }

    /**
     * 測試取得麵包屑路徑
     */
    public function test_get_breadcrumbs(): void
    {
        // 準備測試資料
        $categoryId = 3;
        $breadcrumbs = [
            ['id' => 1, 'name' => '根分類'],
            ['id' => 2, 'name' => '父分類'],
            ['id' => 3, 'name' => '當前分類'],
        ];

        // 設定期望
        $this->cacheService->shouldReceive('getBreadcrumbs')
                          ->once()
                          ->with($categoryId)
                          ->andReturn($breadcrumbs);

        // 執行測試
        $result = $this->service->getBreadcrumbs($categoryId);

        // 驗證結果
        $this->assertEquals($breadcrumbs, $result);
    }

    /**
     * 測試批次更新狀態
     */
    public function test_batch_update_status(): void
    {
        // 準備測試資料
        $categoryIds = [1, 2, 3];
        $isActive = false;

        // 設定期望
        $this->repository->shouldReceive('batchUpdateStatus')
                        ->once()
                        ->with($categoryIds, $isActive)
                        ->andReturn(3);

        // 執行測試
        $result = $this->service->batchUpdateStatus($categoryIds, $isActive);

        // 驗證結果
        $this->assertEquals(3, $result);
    }

    /**
     * 測試排序更新
     */
    public function test_update_sort_order(): void
    {
        // 準備測試資料
        $sortData = [
            ['id' => 1, 'sort_order' => 1],
            ['id' => 2, 'sort_order' => 2],
            ['id' => 3, 'sort_order' => 3],
        ];

        // 設定期望
        $this->repository->shouldReceive('updateSortOrder')
                        ->once()
                        ->with($sortData)
                        ->andReturn(true);

        // 執行測試
        $result = $this->service->updateSortOrder($sortData);

        // 驗證結果
        $this->assertTrue($result);
    }

    /**
     * 測試搜尋功能
     */
    public function test_search_categories(): void
    {
        // 準備測試資料
        $keyword = '電子';
        $categories = collect([
            new ProductCategory(['id' => 1, 'name' => '電子產品']),
            new ProductCategory(['id' => 2, 'name' => '電子配件']),
        ]);

        $paginator = new LengthAwarePaginator(
            $categories,
            2,
            10,
            1
        );

        // 設定期望
        $this->repository->shouldReceive('search')
                        ->once()
                        ->with($keyword, 10)
                        ->andReturn($paginator);

        // 執行測試
        $result = $this->service->search($keyword, 10);

        // 驗證結果
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());
    }
} 