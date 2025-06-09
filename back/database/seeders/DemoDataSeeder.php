<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{Store, ProductCategory};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 示範資料種子檔案
 * 
 * 建立 LomisX3 系統的示範資料
 * 包含商品分類等業務資料，方便開發和展示
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */
class DemoDataSeeder extends Seeder
{
    /**
     * 商品分類示範資料
     * 支援樹狀結構和多層級分類
     * 
     * @var array<string, array<string, mixed>>
     */
    private array $categoryData = [
        // 電子產品
        'electronics' => [
            'name' => '電子產品',
            'slug' => 'electronics',
            'description' => '各種電子產品和數位設備',
            'status' => 'active',
            'position' => 1,
            'image' => 'categories/electronics.jpg',
            'children' => [
                'smartphones' => [
                    'name' => '智慧型手機',
                    'slug' => 'smartphones',
                    'description' => '各品牌智慧型手機',
                    'status' => 'active',
                    'position' => 1,
                    'children' => [
                        'iphone' => [
                            'name' => 'iPhone',
                            'slug' => 'iphone',
                            'description' => 'Apple iPhone 系列',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'samsung' => [
                            'name' => 'Samsung',
                            'slug' => 'samsung',
                            'description' => 'Samsung Galaxy 系列',
                            'status' => 'active',
                            'position' => 2,
                        ],
                        'xiaomi' => [
                            'name' => '小米',
                            'slug' => 'xiaomi',
                            'description' => '小米手機系列',
                            'status' => 'active',
                            'position' => 3,
                        ],
                    ],
                ],
                'laptops' => [
                    'name' => '筆記型電腦',
                    'slug' => 'laptops',
                    'description' => '各品牌筆記型電腦',
                    'status' => 'active',
                    'position' => 2,
                    'children' => [
                        'macbook' => [
                            'name' => 'MacBook',
                            'slug' => 'macbook',
                            'description' => 'Apple MacBook 系列',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'windows_laptops' => [
                            'name' => 'Windows 筆電',
                            'slug' => 'windows-laptops',
                            'description' => 'Windows 作業系統筆電',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
                'accessories' => [
                    'name' => '電子配件',
                    'slug' => 'accessories',
                    'description' => '各種電子產品配件',
                    'status' => 'active',
                    'position' => 3,
                    'children' => [
                        'chargers' => [
                            'name' => '充電器',
                            'slug' => 'chargers',
                            'description' => '各種充電器和電源配件',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'cases' => [
                            'name' => '保護套',
                            'slug' => 'cases',
                            'description' => '手機和平板保護套',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
            ],
        ],
        
        // 服飾配件
        'fashion' => [
            'name' => '服飾配件',
            'slug' => 'fashion',
            'description' => '時尚服飾和配件用品',
            'status' => 'active',
            'position' => 2,
            'image' => 'categories/fashion.jpg',
            'children' => [
                'mens_clothing' => [
                    'name' => '男性服飾',
                    'slug' => 'mens-clothing',
                    'description' => '男性服裝和配件',
                    'status' => 'active',
                    'position' => 1,
                    'children' => [
                        'shirts' => [
                            'name' => '襯衫',
                            'slug' => 'shirts',
                            'description' => '正式和休閒襯衫',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'pants' => [
                            'name' => '褲裝',
                            'slug' => 'pants',
                            'description' => '各式男性褲裝',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
                'womens_clothing' => [
                    'name' => '女性服飾',
                    'slug' => 'womens-clothing',
                    'description' => '女性服裝和配件',
                    'status' => 'active',
                    'position' => 2,
                    'children' => [
                        'dresses' => [
                            'name' => '洋裝',
                            'slug' => 'dresses',
                            'description' => '各式女性洋裝',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'tops' => [
                            'name' => '上衣',
                            'slug' => 'tops',
                            'description' => '女性上衣和T恤',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
                'bags' => [
                    'name' => '包包',
                    'slug' => 'bags',
                    'description' => '各種包包和手提袋',
                    'status' => 'active',
                    'position' => 3,
                    'children' => [
                        'handbags' => [
                            'name' => '手提包',
                            'slug' => 'handbags',
                            'description' => '時尚手提包',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'backpacks' => [
                            'name' => '後背包',
                            'slug' => 'backpacks',
                            'description' => '休閒和商務後背包',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
            ],
        ],
        
        // 居家生活
        'home_living' => [
            'name' => '居家生活',
            'slug' => 'home-living',
            'description' => '居家用品和生活用具',
            'status' => 'active',
            'position' => 3,
            'image' => 'categories/home.jpg',
            'children' => [
                'furniture' => [
                    'name' => '傢俱',
                    'slug' => 'furniture',
                    'description' => '各種居家傢俱',
                    'status' => 'active',
                    'position' => 1,
                    'children' => [
                        'chairs' => [
                            'name' => '椅子',
                            'slug' => 'chairs',
                            'description' => '辦公椅和休閒椅',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'tables' => [
                            'name' => '桌子',
                            'slug' => 'tables',
                            'description' => '書桌和餐桌',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
                'kitchenware' => [
                    'name' => '廚房用品',
                    'slug' => 'kitchenware',
                    'description' => '廚房烹飪和用餐用具',
                    'status' => 'active',
                    'position' => 2,
                    'children' => [
                        'cookware' => [
                            'name' => '烹飪用具',
                            'slug' => 'cookware',
                            'description' => '鍋具和烹飪工具',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'tableware' => [
                            'name' => '餐具',
                            'slug' => 'tableware',
                            'description' => '碗盤和餐具組合',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
            ],
        ],
        
        // 運動休閒
        'sports' => [
            'name' => '運動休閒',
            'slug' => 'sports',
            'description' => '運動用品和休閒設備',
            'status' => 'active',
            'position' => 4,
            'image' => 'categories/sports.jpg',
            'children' => [
                'fitness' => [
                    'name' => '健身用品',
                    'slug' => 'fitness',
                    'description' => '健身器材和用品',
                    'status' => 'active',
                    'position' => 1,
                    'children' => [
                        'weights' => [
                            'name' => '重量訓練',
                            'slug' => 'weights',
                            'description' => '啞鈴和重訓器材',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'cardio' => [
                            'name' => '有氧器材',
                            'slug' => 'cardio',
                            'description' => '跑步機和有氧設備',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
                'outdoor' => [
                    'name' => '戶外用品',
                    'slug' => 'outdoor',
                    'description' => '戶外運動和露營用品',
                    'status' => 'active',
                    'position' => 2,
                    'children' => [
                        'camping' => [
                            'name' => '露營用品',
                            'slug' => 'camping',
                            'description' => '帳篷和露營設備',
                            'status' => 'active',
                            'position' => 1,
                        ],
                        'hiking' => [
                            'name' => '登山用品',
                            'slug' => 'hiking',
                            'description' => '登山裝備和用品',
                            'status' => 'active',
                            'position' => 2,
                        ],
                    ],
                ],
            ],
        ],
        
        // 測試分類（暫停狀態）
        'test_category' => [
            'name' => '測試分類',
            'slug' => 'test-category',
            'description' => '系統測試用分類（已暫停）',
            'status' => 'inactive',
            'position' => 999,
        ],
    ];

    /**
     * 執行示範資料建立
     * 
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🎯 開始建立示範資料...');
        
        // 檢查門市是否存在
        $this->validateStoresExist();
        
        DB::transaction(function () {
            $this->createCategories();
        });
        
        $this->command->info('✅ 示範資料建立完成！');
        $this->printSummary();
    }

    /**
     * 驗證門市是否存在
     * 
     * @return void
     */
    private function validateStoresExist(): void
    {
        $storeCount = Store::count();
        
        if ($storeCount === 0) {
            $this->command->error('❌ 找不到門市資料，請先執行 StoreSeeder');
            throw new \Exception('請先執行 php artisan db:seed --class=StoreSeeder');
        }
        
        $this->command->info("🔍 已找到 {$storeCount} 個門市，將建立示範資料");
    }

    /**
     * 建立商品分類示範資料
     * 
     * @return void
     */
    private function createCategories(): void
    {
        $this->command->info('📂 建立商品分類示範資料...');
        
        // 為每個門市建立分類資料
        $stores = Store::where('status', 'active')->get();
        
        foreach ($stores as $store) {
            $this->command->line("  為門市 [{$store->code}] {$store->name} 建立分類...");
            $this->createCategoriesForStore($store, $this->categoryData, null, 0);
        }
    }

    /**
     * 為特定門市遞迴建立分類資料
     * 
     * @param Store $store 門市模型
     * @param array $categories 分類資料陣列
     * @param int|null $parentId 父分類ID
     * @param int $depth 層級深度
     * @return void
     */
    private function createCategoriesForStore(Store $store, array $categories, ?int $parentId = null, int $depth = 0): void
    {
        foreach ($categories as $categoryKey => $categoryData) {
            $indent = str_repeat('    ', $depth + 1);
            $this->command->line("{$indent}建立分類: {$categoryData['name']}");
            
            // 建立分類
            $category = ProductCategory::firstOrCreate(
                [
                    'slug' => $categoryData['slug'],
                    'store_id' => $store->id,
                ],
                [
                    'name' => $categoryData['name'],
                    'parent_id' => $parentId,
                    'description' => $categoryData['description'] ?? null,
                    'status' => ($categoryData['status'] ?? 'active') === 'active' ? true : false,
                    'position' => $categoryData['position'] ?? 0,
                    'depth' => $depth,
                    'image' => $categoryData['image'] ?? null,
                    'meta_title' => $categoryData['name'],
                    'meta_description' => $categoryData['description'] ?? null,
                    'meta_keywords' => $this->generateKeywords($categoryData['name']),
                    'created_by' => 1, // 假設系統管理員ID為1
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            // 遞迴建立子分類
            if (isset($categoryData['children'])) {
                $this->createCategoriesForStore($store, $categoryData['children'], $category->id, $depth + 1);
            }
        }
    }

    /**
     * 產生SEO關鍵字
     * 
     * @param string $name 分類名稱
     * @return string
     */
    private function generateKeywords(string $name): string
    {
        $keywords = [$name];
        
        // 根據名稱添加相關關鍵字
        $keywordMap = [
            '電子產品' => ['3C', '數位', '科技', '電子'],
            '智慧型手機' => ['手機', '行動電話', 'smartphone', '智能手機'],
            '筆記型電腦' => ['筆電', 'laptop', '電腦', '筆記本'],
            '服飾配件' => ['服裝', '時尚', '穿搭', '配件'],
            '居家生活' => ['家居', '生活用品', '居家用品', '家用'],
            '運動休閒' => ['運動', '健身', '休閒', '戶外'],
        ];
        
        foreach ($keywordMap as $key => $values) {
            if (str_contains($name, $key)) {
                $keywords = array_merge($keywords, $values);
                break;
            }
        }
        
        return implode(', ', array_unique($keywords));
    }

    /**
     * 顯示建立摘要
     * 
     * @return void
     */
    private function printSummary(): void
    {
        $totalCategories = ProductCategory::count();
        $categoriesByStore = ProductCategory::join('stores', 'product_categories.store_id', '=', 'stores.id')
            ->selectRaw('stores.name, COUNT(*) as count')
            ->groupBy('stores.name')
            ->pluck('count', 'name')
            ->toArray();
        
        $activeCount = ProductCategory::where('status', true)->count();
        $inactiveCount = ProductCategory::where('status', false)->count();
        
        $maxDepth = ProductCategory::max('depth');
        
        $this->command->info('');
        $this->command->info('📊 示範資料摘要:');
        $this->command->table(
            ['項目', '數量', '說明'],
            [
                ['分類總數', $totalCategories, '完整分類結構'],
                ['最大層級', $maxDepth + 1, '支援多層分類'],
                ['啟用分類', $activeCount, '營運中分類'],
                ['停用分類', $inactiveCount, '測試分類'],
                ['門市數量', count($categoriesByStore), '涵蓋門市數'],
            ]
        );
        
        $this->command->info('');
        $this->command->info('🏢 各門市分類數量:');
        foreach ($categoriesByStore as $storeName => $count) {
            $this->command->line("  {$storeName}: {$count} 個分類");
        }
        
        $this->command->info('');
        $this->command->info('🌳 分類結構範例 (第一個門市):');
        $firstStore = Store::first();
        if ($firstStore) {
            $rootCategories = ProductCategory::where('store_id', $firstStore->id)
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('position')
                ->get();
            
            foreach ($rootCategories as $category) {
                $this->printCategoryTree($category, 0);
            }
        }
        
        $this->command->info('');
        $this->command->info('💡 提示:');
        $this->command->line('  - 每個門市都有完整的分類結構');
        $this->command->line('  - 使用 ProductCategory::tree() 取得樹狀結構');
        $this->command->line('  - 支援SEO優化（meta標籤、關鍵字）');
        $this->command->line('  - 包含測試用的停用分類');
    }

    /**
     * 遞迴顯示分類樹狀結構
     * 
     * @param ProductCategory $category 分類模型
     * @param int $depth 層級深度
     * @return void
     */
    private function printCategoryTree(ProductCategory $category, int $depth): void
    {
        $indent = str_repeat('  ', $depth);
        $statusIcon = $category->status ? '✅' : '❌';
        
        $this->command->line("  {$indent}📂 {$category->name} ({$category->slug}) {$statusIcon}");
        
        foreach ($category->children as $child) {
            $this->printCategoryTree($child, $depth + 1);
        }
    }
} 