<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ProductCategory;
use App\Http\Resources\ProductCategoryCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Cursor;
use Illuminate\Http\Request;

/**
 * Cursor Pagination 功能測試
 * 
 * 測試 ProductCategoryCollection 的 Cursor Pagination 支援，包括：
 * - Cursor meta 資訊格式
 * - next_cursor 和 prev_cursor 編碼
 * - 分頁導航資訊
 */
class PaginationCursorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試資料
        $this->createTestCategories();
    }

    /**
     * 建立測試分類資料
     */
    private function createTestCategories(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            ProductCategory::create([
                'name' => "測試分類 {$i}",
                'slug' => "test-category-{$i}",
                'status' => true,
                'position' => $i,
                'description' => "這是第 {$i} 個測試分類",
            ]);
        }
    }

    /**
     * 測試 Cursor Pagination Meta 資訊格式
     */
    public function test_cursor_pagination_meta_format(): void
    {
        // 建立測試資料
        $categories = ProductCategory::factory()->count(10)->create([
            'status' => true,
            'parent_id' => null,
        ]);

        // 建立 Cursor Paginator
        $perPage = 5;
        $cursor = null; // 第一頁
        
        $paginator = new CursorPaginator(
            $categories->take($perPage),
            $perPage,
            $cursor,
            [
                'path' => '/api/product-categories',
                'pageName' => 'cursor',
            ]
        );

        // 建立 Resource Collection
        $collection = new ProductCategoryCollection($paginator);
        $request = Request::create('/api/product-categories');
        
        // 取得 meta 資訊
        $response = $collection->toResponse($request);
        $responseData = $response->getData(true);

        // 驗證基本結構
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);

        // 驗證 pagination meta 格式
        $pagination = $responseData['pagination'];
        $this->assertEquals('cursor', $pagination['type']);
        $this->assertEquals('/api/product-categories', $pagination['path']);
        $this->assertEquals($perPage, $pagination['per_page']);
        
        // 驗證 cursor 相關欄位存在
        $this->assertArrayHasKey('next_cursor', $pagination);
        $this->assertArrayHasKey('prev_cursor', $pagination);
        $this->assertArrayHasKey('has_more_pages', $pagination);
        $this->assertArrayHasKey('has_previous_pages', $pagination);
        $this->assertArrayHasKey('next_page_url', $pagination);
        $this->assertArrayHasKey('prev_page_url', $pagination);
    }

    /**
     * 測試 next_cursor 編碼
     */
    public function test_next_cursor_encoding(): void
    {
        // 建立足夠的測試資料以產生下一頁
        $categories = ProductCategory::factory()->count(10)->create([
            'status' => true,
            'parent_id' => null,
        ]);

        $perPage = 3;
        // 取得前 4 個項目（比 perPage 多 1 個，這樣會有下一頁）
        $items = $categories->take($perPage + 1);
        
        $paginator = new CursorPaginator(
            $items,
            $perPage,
            null,
            [
                'path' => '/api/product-categories',
                'pageName' => 'cursor',
            ]
        );

        $collection = new ProductCategoryCollection($paginator);
        $request = Request::create('/api/product-categories');
        
        $response = $collection->toResponse($request);
        $responseData = $response->getData(true);
        
        $pagination = $responseData['pagination'];

        // 驗證 next_cursor 存在且為字串（base64 編碼）
        if ($pagination['has_more_pages']) {
            $this->assertIsString($pagination['next_cursor']);
            $this->assertNotEmpty($pagination['next_cursor']);
            
            // 驗證是有效的 base64 編碼
            $decoded = base64_decode($pagination['next_cursor'], true);
            $this->assertNotFalse($decoded, 'next_cursor 應該是有效的 base64 編碼');
        }

        // 第一頁的 prev_cursor 應該為 null
        $this->assertNull($pagination['prev_cursor']);
        $this->assertFalse($pagination['has_previous_pages']);
    }

    /**
     * 測試 prev_cursor 編碼
     */
    public function test_prev_cursor_encoding(): void
    {
        $categories = ProductCategory::factory()->count(10)->create([
            'status' => true,
            'parent_id' => null,
        ]);

        $perPage = 3;
        
        // 模擬第二頁的情況（有 previous cursor）
        // 使用 Cursor 物件而不是字串
        $mockCursor = new Cursor(['id' => $categories->first()->id], true);
        
        $paginator = new CursorPaginator(
            $categories->skip($perPage)->take($perPage),
            $perPage,
            $mockCursor,
            [
                'path' => '/api/product-categories',
                'pageName' => 'cursor',
            ]
        );

        $collection = new ProductCategoryCollection($paginator);
        $request = Request::create('/api/product-categories?cursor=' . $mockCursor->encode());
        
        $response = $collection->toResponse($request);
        $responseData = $response->getData(true);
        
        $pagination = $responseData['pagination'];

        // 驗證 prev_cursor 存在且為字串
        if ($pagination['has_previous_pages']) {
            $this->assertIsString($pagination['prev_cursor']);
            $this->assertNotEmpty($pagination['prev_cursor']);
            
            // 驗證是有效的 base64 編碼
            $decoded = base64_decode($pagination['prev_cursor'], true);
            $this->assertNotFalse($decoded, 'prev_cursor 應該是有效的 base64 編碼');
        }
    }

    /**
     * 測試分頁導航 URL
     */
    public function test_pagination_navigation_urls(): void
    {
        $categories = ProductCategory::factory()->count(10)->create([
            'status' => true,
            'parent_id' => null,
        ]);

        $perPage = 4;
        $paginator = new CursorPaginator(
            $categories->take($perPage),
            $perPage,
            null,
            [
                'path' => '/api/product-categories',
                'pageName' => 'cursor',
            ]
        );

        $collection = new ProductCategoryCollection($paginator);
        $request = Request::create('/api/product-categories');
        
        $response = $collection->toResponse($request);
        $responseData = $response->getData(true);
        
        $pagination = $responseData['pagination'];

        // 驗證 URL 格式
        $this->assertStringContainsString('/api/product-categories', $pagination['path']);
        
        // 如果有下一頁，next_page_url 應該包含 cursor 參數
        if ($pagination['has_more_pages'] && $pagination['next_cursor']) {
            $this->assertIsString($pagination['next_page_url']);
            $this->assertStringContainsString('cursor=', $pagination['next_page_url']);
        }

        // 第一頁的 prev_page_url 應該為 null
        $this->assertNull($pagination['prev_page_url']);
    }

    /**
     * 測試空結果的 Cursor Pagination
     */
    public function test_empty_cursor_pagination(): void
    {
        // 不建立任何資料，測試空結果
        $paginator = new CursorPaginator(
            collect([]),
            10,
            null,
            [
                'path' => '/api/product-categories',
                'pageName' => 'cursor',
            ]
        );

        $collection = new ProductCategoryCollection($paginator);
        $request = Request::create('/api/product-categories');
        
        $response = $collection->toResponse($request);
        $responseData = $response->getData(true);

        // 驗證基本結構仍然存在
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);
        $this->assertEmpty($responseData['data']);

        $pagination = $responseData['pagination'];
        $this->assertEquals('cursor', $pagination['type']);
        $this->assertFalse($pagination['has_more_pages']);
        $this->assertFalse($pagination['has_previous_pages']);
        $this->assertNull($pagination['next_cursor']);
        $this->assertNull($pagination['prev_cursor']);
    }

    /**
     * 測試與標準分頁的區別
     */
    public function test_cursor_vs_standard_pagination_meta(): void
    {
        $categories = ProductCategory::factory()->count(5)->create([
            'status' => true,
            'parent_id' => null,
        ]);

        // Cursor Pagination
        $cursorPaginator = new CursorPaginator(
            $categories,
            5,
            null,
            ['path' => '/api/product-categories']
        );

        $cursorCollection = new ProductCategoryCollection($cursorPaginator);
        $request = Request::create('/api/product-categories');
        
        $cursorResponse = $cursorCollection->toResponse($request);
        $cursorData = $cursorResponse->getData(true);

        // 驗證 Cursor Pagination 特有的欄位
        $this->assertEquals('cursor', $cursorData['pagination']['type']);
        $this->assertArrayHasKey('next_cursor', $cursorData['pagination']);
        $this->assertArrayHasKey('prev_cursor', $cursorData['pagination']);
        
        // 驗證不包含標準分頁的欄位
        $this->assertArrayNotHasKey('current_page', $cursorData['pagination']);
        $this->assertArrayNotHasKey('total', $cursorData['pagination']);
        $this->assertArrayNotHasKey('last_page', $cursorData['pagination']);
    }

    /**
     * 測試 per_page 參數正確性
     */
    public function test_per_page_parameter(): void
    {
        $categories = ProductCategory::factory()->count(20)->create([
            'status' => true,
            'parent_id' => null,
        ]);

        $testPerPage = 7;
        $paginator = new CursorPaginator(
            $categories->take($testPerPage),
            $testPerPage,
            null,
            ['path' => '/api/product-categories']
        );

        $collection = new ProductCategoryCollection($paginator);
        $request = Request::create('/api/product-categories');
        
        $response = $collection->toResponse($request);
        $responseData = $response->getData(true);

        // 驗證 per_page 值正確
        $this->assertEquals($testPerPage, $responseData['pagination']['per_page']);
        
        // 驗證實際返回的資料數量
        $this->assertCount($testPerPage, $responseData['data']);
    }
} 