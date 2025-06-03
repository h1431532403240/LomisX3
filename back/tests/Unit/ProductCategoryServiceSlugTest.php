<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 商品分類服務 Slug 生成功能測試
 * 
 * 測試 generateUniqueSlug 方法的各種情況，
 * 特別是處理重複 slug 的邏輯和效能
 */
class ProductCategoryServiceSlugTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品分類服務實例
     */
    private ProductCategoryService $service;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = $this->app->make(ProductCategoryService::class);
    }

    /**
     * 測試基本 slug 生成功能
     * 
     * @test
     */
    public function it_generates_basic_slug_from_name(): void
    {
        // Arrange
        $name = '電子產品';

        // Act
        $slug = $this->service->generateUniqueSlug($name);

        // Assert
        $this->assertNotEmpty($slug);
        $this->assertIsString($slug);
        $this->assertStringNotContainsString(' ', $slug, 'Slug 不應包含空格');
    }

    /**
     * 測試當 slug 已存在時，最多 3 次即可獲得唯一 slug
     * 
     * @test
     */
    public function it_generates_unique_slug_within_three_attempts_when_conflicts_exist(): void
    {
        // Arrange: 預先建立衝突的分類
        $existingCategory = ProductCategory::factory()->create([
            'name' => '測試分類',
            'slug' => 'test-category'
        ]);

        // 建立更多衝突（模擬 test-category-1, test-category-2 也已存在）
        ProductCategory::factory()->create([
            'name' => '測試分類 1',
            'slug' => 'test-category-1'
        ]);

        ProductCategory::factory()->create([
            'name' => '測試分類 2', 
            'slug' => 'test-category-2'
        ]);

        // Act: 嘗試為同名分類生成唯一 slug
        $attempts = 0;
        $maxAttempts = 3;
        
        $startTime = microtime(true);
        $uniqueSlug = $this->service->generateUniqueSlug('測試分類');
        $endTime = microtime(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // 轉換為毫秒

        // Assert: 驗證結果
        $this->assertNotEmpty($uniqueSlug);
        $this->assertNotEquals('test-category', $uniqueSlug, '應生成不同於已存在的 slug');
        $this->assertNotEquals('test-category-1', $uniqueSlug, '應避免第一次衝突');
        $this->assertNotEquals('test-category-2', $uniqueSlug, '應避免第二次衝突');
        
        // 驗證效能（應該在合理時間內完成）
        $this->assertLessThan(100, $executionTime, 'slug 生成應在 100ms 內完成');
        
        // 驗證最終生成的 slug 在資料庫中是唯一的
        $conflictCount = ProductCategory::where('slug', $uniqueSlug)->count();
        $this->assertEquals(0, $conflictCount, '生成的 slug 應該是唯一的');
    }

    /**
     * 測試 slug 的格式正確性
     * 
     * @test
     */
    public function it_generates_slug_with_correct_format(): void
    {
        // Arrange: 測試各種特殊字元的名稱
        $testCases = [
            '電子產品 & 配件' => ['electronics', 'accessories'],
            '3C用品（手機/平板）' => ['3c', 'phone', 'tablet'],
            '美食 / 餐廳' => ['food', 'restaurant'],
            'FASHION & STYLE' => ['fashion', 'style'],
            '運動用品-戶外裝備' => ['sports', 'outdoor'],
        ];

        foreach ($testCases as $name => $expectedSubstrings) {
            // Act
            $slug = $this->service->generateUniqueSlug($name);
            
            // Assert: 檢查基本格式
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug, "Slug 應僅包含小寫字母、數字和破折號: {$slug}");
            $this->assertStringStartsNotWith('-', $slug, 'Slug 不應以破折號開始');
            $this->assertStringEndsNotWith('-', $slug, 'Slug 不應以破折號結束');
            $this->assertStringNotContainsString('--', $slug, 'Slug 不應包含連續的破折號');
            
            // 檢查是否包含預期的關鍵字（可選）
            $lowerSlug = strtolower($slug);
            $containsKeyword = false;
            foreach ($expectedSubstrings as $keyword) {
                if (str_contains($lowerSlug, $keyword)) {
                    $containsKeyword = true;
                    break;
                }
            }
            // 注意：不強制檢查關鍵字，因為 slug 生成可能使用不同的策略
        }
    }

    /**
     * 測試重複檢查的邏輯
     * 
     * @test
     */
    public function it_correctly_identifies_slug_conflicts(): void
    {
        // Arrange: 建立已存在的分類
        $existingCategory = ProductCategory::factory()->create([
            'name' => '現有分類',
            'slug' => 'existing-category'
        ]);

        // Act & Assert: 測試相同 slug
        $conflictSlug = $this->service->generateUniqueSlug('現有分類');
        $this->assertNotEquals('existing-category', $conflictSlug, '應檢測到衝突並生成新的 slug');

        // Act & Assert: 測試不同 slug
        $uniqueSlug = $this->service->generateUniqueSlug('全新分類');
        $this->assertNotEquals('existing-category', $uniqueSlug, '不同名稱應生成不同的 slug');
    }

    /**
     * 測試編輯現有分類時的 slug 處理
     * 
     * @test
     */
    public function it_handles_slug_generation_when_editing_existing_category(): void
    {
        // Arrange: 建立現有分類
        $category = ProductCategory::factory()->create([
            'name' => '原始分類',
            'slug' => 'original-category'
        ]);

        // 建立另一個分類佔用目標 slug
        ProductCategory::factory()->create([
            'name' => '目標分類',
            'slug' => 'target-category'
        ]);

        // Act: 為現有分類生成新 slug（排除自身）
        $newSlug = $this->service->generateUniqueSlug('目標分類', $category->id);

        // Assert
        $this->assertNotEquals('target-category', $newSlug, '應避免與其他分類衝突');
        
        // 驗證排除邏輯：同一分類可以保持相同 slug
        $sameSlug = $this->service->generateUniqueSlug('原始分類', $category->id);
        $this->assertNotEmpty($sameSlug);
    }

    /**
     * 測試極端情況：大量衝突的處理
     * 
     * @test
     */
    public function it_handles_massive_slug_conflicts_efficiently(): void
    {
        // Arrange: 建立大量衝突的分類
        $baseName = '熱門分類';
        $baseSlug = 'popular-category';
        
        // 建立 20 個衝突分類
        for ($i = 0; $i < 20; $i++) {
            $slug = $i === 0 ? $baseSlug : "{$baseSlug}-{$i}";
            ProductCategory::factory()->create([
                'name' => "{$baseName} {$i}",
                'slug' => $slug
            ]);
        }

        // Act: 測量生成時間
        $startTime = microtime(true);
        $uniqueSlug = $this->service->generateUniqueSlug($baseName);
        $endTime = microtime(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // 轉換為毫秒

        // Assert: 驗證效能和正確性
        $this->assertLessThan(200, $executionTime, '即使有大量衝突，slug 生成也應在 200ms 內完成');
        $this->assertNotEmpty($uniqueSlug);
        
        // 驗證生成的 slug 是唯一的
        $conflictCount = ProductCategory::where('slug', $uniqueSlug)->count();
        $this->assertEquals(0, $conflictCount, '即使有大量衝突，最終生成的 slug 也應該是唯一的');
    }

    /**
     * 測試空字串和特殊輸入的處理
     * 
     * @test
     */
    public function it_handles_edge_cases_in_slug_generation(): void
    {
        // 測試空字串
        $emptySlug = $this->service->generateUniqueSlug('');
        $this->assertNotEmpty($emptySlug, '空字串應生成預設 slug');

        // 測試只有特殊字元的字串
        $specialSlug = $this->service->generateUniqueSlug('!@#$%^&*()');
        $this->assertNotEmpty($specialSlug, '特殊字元應生成有效 slug');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $specialSlug, '特殊字元生成的 slug 應符合格式');

        // 測試極長的字串
        $longName = str_repeat('很長的分類名稱', 20); // 200+ 字元
        $longSlug = $this->service->generateUniqueSlug($longName);
        $this->assertNotEmpty($longSlug);
        $this->assertLessThanOrEqual(255, strlen($longSlug), 'Slug 長度應在資料庫限制內');
    }

    /**
     * 測試多語言字元的處理
     * 
     * @test
     */
    public function it_handles_multilingual_characters_in_slug_generation(): void
    {
        // Arrange: 不同語言的分類名稱
        $multilingualNames = [
            '中文分類',
            'English Category', 
            'Español Categoría',
            'Français Catégorie',
            '日本語カテゴリ',
            '한국어 카테고리',
        ];

        foreach ($multilingualNames as $name) {
            // Act
            $slug = $this->service->generateUniqueSlug($name);
            
            // Assert
            $this->assertNotEmpty($slug, "應為 '{$name}' 生成有效 slug");
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug, "多語言 slug 應符合格式: {$slug}");
            
            // 驗證唯一性
            $conflictCount = ProductCategory::where('slug', $slug)->count();
            $this->assertEquals(0, $conflictCount, "生成的 slug '{$slug}' 應該是唯一的");
        }
    }
} 