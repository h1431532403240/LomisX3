<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ProductCategory;
use App\Http\Resources\ProductCategoryCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\CursorPaginator;

/**
 * Cursor Pagination 功能測試
 * 
 * 測試游標分頁的完整功能，包括：
 * - next_cursor 和 prev_cursor 的 base64 編碼
 * - 分頁元數據的正確性
 * - API 回應格式驗證
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
     * 測試 Cursor Pagination 的基本功能
     */
    public function test_cursor_pagination_basic_functionality(): void
    {
        // 取得第一頁
        $response = $this->getJson('/api/product-categories?per_page=5&cursor=true');

        $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'status',
                            'position',
                        ]
                    ],
                    'pagination' => [
                        'type',
                        'per_page',
                        'has_more_pages',
                        'has_previous_pages',
                        'next_cursor',
                        'prev_cursor',
                        'path',
                        'next_page_url',
                        'prev_page_url',
                    ],
                ]);

        $data = $response->json();

        // 驗證分頁類型
        $this->assertEquals('cursor', $data['pagination']['type']);
        
        // 驗證每頁筆數
        $this->assertEquals(5, $data['pagination']['per_page']);
        
        // 第一頁應該有更多頁面但沒有前一頁
        $this->assertTrue($data['pagination']['has_more_pages']);
        $this->assertFalse($data['pagination']['has_previous_pages']);
        
        // 第一頁不應該有 prev_cursor
        $this->assertNull($data['pagination']['prev_cursor']);
        
        // 應該有 next_cursor
        $this->assertNotNull($data['pagination']['next_cursor']);
        
        // 驗證資料筆數
        $this->assertCount(5, $data['data']);
    }

    /**
     * 測試 next_cursor 和 prev_cursor 的 base64 編碼
     */
    public function test_cursor_base64_encoding(): void
    {
        // 取得第一頁
        $response = $this->getJson('/api/product-categories?per_page=3&cursor=true');
        $firstPageData = $response->json();
        
        $nextCursor = $firstPageData['pagination']['next_cursor'];
        $this->assertNotNull($nextCursor);
        
        // 驗證 cursor 是有效的 base64 編碼
        $decoded = base64_decode($nextCursor, true);
        $this->assertNotFalse($decoded, 'next_cursor 應該是有效的 base64 編碼');
        
        // 使用 next_cursor 取得下一頁
        $response = $this->getJson("/api/product-categories?per_page=3&cursor={$nextCursor}");
        $secondPageData = $response->json();
        
        $response->assertOk();
        
        // 第二頁應該有 prev_cursor
        $this->assertNotNull($secondPageData['pagination']['prev_cursor']);
        $this->assertTrue($secondPageData['pagination']['has_previous_pages']);
        
        // 驗證 prev_cursor 也是有效的 base64 編碼
        $prevCursor = $secondPageData['pagination']['prev_cursor'];
        $decoded = base64_decode($prevCursor, true);
        $this->assertNotFalse($decoded, 'prev_cursor 應該是有效的 base64 編碼');
    }

    /**
     * 測試分頁導航的完整流程
     */
    public function test_cursor_pagination_navigation_flow(): void
    {
        // 第一頁
        $response = $this->getJson('/api/product-categories?per_page=5&cursor=true');
        $page1 = $response->json();
        
        $this->assertCount(5, $page1['data']);
        $this->assertFalse($page1['pagination']['has_previous_pages']);
        $this->assertTrue($page1['pagination']['has_more_pages']);
        
        // 使用 next_cursor 取得第二頁
        $nextCursor = $page1['pagination']['next_cursor'];
        $response = $this->getJson("/api/product-categories?per_page=5&cursor={$nextCursor}");
        $page2 = $response->json();
        
        $this->assertCount(5, $page2['data']);
        $this->assertTrue($page2['pagination']['has_previous_pages']);
        $this->assertTrue($page2['pagination']['has_more_pages']);
        
        // 使用 prev_cursor 回到第一頁
        $prevCursor = $page2['pagination']['prev_cursor'];
        $response = $this->getJson("/api/product-categories?per_page=5&cursor={$prevCursor}");
        $backToPage1 = $response->json();
        
        // 驗證回到第一頁的資料與原始第一頁相同
        $this->assertEquals($page1['data'], $backToPage1['data']);
    }

    /**
     * 測試最後一頁的行為
     */
    public function test_cursor_pagination_last_page(): void
    {
        // 使用大的 per_page 來接近最後一頁
        $response = $this->getJson('/api/product-categories?per_page=18&cursor=true');
        $firstPage = $response->json();
        
        // 取得下一頁（應該是最後一頁）
        $nextCursor = $firstPage['pagination']['next_cursor'];
        $response = $this->getJson("/api/product-categories?per_page=18&cursor={$nextCursor}");
        $lastPage = $response->json();
        
        // 最後一頁的特徵
        $this->assertFalse($lastPage['pagination']['has_more_pages']);
        $this->assertTrue($lastPage['pagination']['has_previous_pages']);
        $this->assertNull($lastPage['pagination']['next_cursor']);
        $this->assertNotNull($lastPage['pagination']['prev_cursor']);
        
        // 驗證剩餘資料數量
        $this->assertLessThanOrEqual(18, count($lastPage['data']));
    }

    /**
     * 測試 ProductCategoryCollection 的 Meta 資料
     */
    public function test_product_category_collection_meta(): void
    {
        // 建立 Cursor Paginator 來測試 Collection
        $categories = ProductCategory::orderBy('id')->limit(5)->get();
        $paginator = new CursorPaginator(
            $categories,
            5,
            null,
            [
                'path' => '/api/product-categories',
            ]
        );
        
        // 建立 Collection Resource
        $collection = new ProductCategoryCollection($paginator);
        $request = request();
        
        // 轉換為陣列
        $array = $collection->toArray($request);
        $with = $collection->with($request);
        
        // 驗證基本結構
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('pagination', $with);
        
        // 驗證分頁 Meta
        $pagination = $with['pagination'];
        $this->assertEquals('cursor', $pagination['type']);
        $this->assertEquals(5, $pagination['per_page']);
        $this->assertIsBool($pagination['has_more_pages']);
        $this->assertIsBool($pagination['has_previous_pages']);
    }

    /**
     * 測試 Collection 的靜態方法
     */
    public function test_collection_static_methods(): void
    {
        // 測試成功回應
        $successResponse = ProductCategoryCollection::success(['test' => 'data'], '測試成功');
        
        $this->assertTrue($successResponse['success']);
        $this->assertEquals('測試成功', $successResponse['message']);
        $this->assertArrayHasKey('data', $successResponse);
        
        // 測試錯誤回應
        $errorResponse = ProductCategoryCollection::error(
            '測試錯誤',
            ['field' => 'error detail'],
            'TEST_ERROR'
        );
        
        $this->assertFalse($errorResponse['success']);
        $this->assertEquals('測試錯誤', $errorResponse['message']);
        $this->assertEquals('TEST_ERROR', $errorResponse['code']);
        $this->assertArrayHasKey('errors', $errorResponse);
    }

    /**
     * 測試無效 Cursor 的處理
     */
    public function test_invalid_cursor_handling(): void
    {
        // 使用無效的 cursor
        $response = $this->getJson('/api/product-categories?per_page=5&cursor=invalid_cursor');
        
        // 應該返回錯誤或回到第一頁
        // 具體行為取決於控制器的實作
        $this->assertTrue(
            $response->status() === 400 || 
            $response->status() === 200
        );
    }

    /**
     * 測試空結果的 Cursor Pagination
     */
    public function test_cursor_pagination_empty_results(): void
    {
        // 清空所有分類
        ProductCategory::query()->delete();
        
        $response = $this->getJson('/api/product-categories?per_page=5&cursor=true');
        $data = $response->json();
        
        $response->assertOk();
        $this->assertEmpty($data['data']);
        $this->assertFalse($data['pagination']['has_more_pages']);
        $this->assertFalse($data['pagination']['has_previous_pages']);
        $this->assertNull($data['pagination']['next_cursor']);
        $this->assertNull($data['pagination']['prev_cursor']);
    }

    /**
     * 測試 Cursor Pagination 與篩選條件結合
     */
    public function test_cursor_pagination_with_filters(): void
    {
        // 將部分分類設為非啟用
        ProductCategory::where('id', '>', 10)->update(['status' => false]);
        
        // 使用狀態篩選
        $response = $this->getJson('/api/product-categories?per_page=5&cursor=true&status=1');
        $data = $response->json();
        
        $response->assertOk();
        
        // 驗證所有回傳的分類都是啟用狀態
        foreach ($data['data'] as $category) {
            $this->assertTrue($category['status']);
        }
        
        // 驗證分頁資訊
        $this->assertEquals('cursor', $data['pagination']['type']);
        $this->assertLessThanOrEqual(5, count($data['data']));
    }
} 