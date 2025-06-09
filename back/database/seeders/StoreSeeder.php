<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 門市種子檔案
 * 
 * 建立 LomisX3 系統所需的門市資料
 * 支援樹狀結構、多租戶隔離和企業級門市管理
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */
class StoreSeeder extends Seeder
{
    /**
     * 門市資料定義
     * 支援樹狀結構和多層級組織架構
     * 
     * @var array<string, array<string, mixed>>
     */
    private array $storeData = [
        // 總公司
        'headquarters' => [
            'name' => 'LomisX3 總公司',
            'code' => 'HQ001',
            'status' => 'active',
            'sort_order' => 1,
            'address' => '台北市信義區信義路五段7號',
            'phone' => '+886-2-2345-6789',
            'email' => 'contact@lomisx3.com',
            'description' => 'LomisX3 企業管理系統總公司，負責整體營運管理',
            'settings' => [
                'timezone' => 'Asia/Taipei',
                'currency' => 'TWD',
                'language' => 'zh-TW',
                'features' => ['all'],
                'manager_name' => '執行長',
                'type' => 'headquarters',
            ],
            'children' => [
                // 北區門市群
                'north_region' => [
                    'name' => '北區營運中心',
                    'code' => 'NOR001',
                    'status' => 'active',
                    'sort_order' => 1,
                    'address' => '台北市中山區南京東路二段132號',
                    'phone' => '+886-2-2567-8901',
                    'email' => 'north@lomisx3.com',
                    'description' => '負責台北、新北、桃園、新竹地區營運',
                    'settings' => [
                        'manager_name' => '北區經理',
                        'type' => 'region',
                        'coverage' => ['台北', '新北', '桃園', '新竹'],
                    ],
                    'children' => [
                        'taipei_store' => [
                            'name' => '台北旗艦店',
                            'code' => 'TP001',
                            'status' => 'active',
                            'sort_order' => 1,
                            'address' => '台北市信義區忠孝東路四段555號',
                            'phone' => '+886-2-2345-1234',
                            'email' => 'taipei@lomisx3.com',
                            'description' => '台北市信義區旗艦門市',
                            'settings' => [
                                'manager_name' => '台北店長',
                                'type' => 'flagship',
                                'operating_hours' => '09:00-22:00',
                            ],
                        ],
                        'xinyi_store' => [
                            'name' => '信義分店',
                            'code' => 'TP002',
                            'status' => 'active',
                            'sort_order' => 2,
                            'address' => '台北市信義區市府路45號',
                            'phone' => '+886-2-2723-4567',
                            'email' => 'xinyi@lomisx3.com',
                            'description' => '信義商圈分店',
                            'settings' => [
                                'manager_name' => '信義店長',
                                'type' => 'branch',
                                'operating_hours' => '10:00-21:00',
                            ],
                        ],
                        'taoyuan_store' => [
                            'name' => '桃園中正店',
                            'code' => 'TY001',
                            'status' => 'active',
                            'sort_order' => 3,
                            'address' => '桃園市桃園區中正路1234號',
                            'phone' => '+886-3-333-5678',
                            'email' => 'taoyuan@lomisx3.com',
                            'description' => '桃園市中正路門市',
                            'settings' => [
                                'manager_name' => '桃園店長',
                                'type' => 'standard',
                                'operating_hours' => '09:30-21:30',
                            ],
                        ],
                    ],
                ],
                
                // 中區門市群
                'central_region' => [
                    'name' => '中區營運中心',
                    'code' => 'CEN001',
                    'status' => 'active',
                    'sort_order' => 2,
                    'address' => '台中市西屯區台灣大道三段160號',
                    'phone' => '+886-4-2314-5678',
                    'email' => 'central@lomisx3.com',
                    'description' => '負責台中、彰化、南投、雲林地區營運',
                    'settings' => [
                        'manager_name' => '中區經理',
                        'type' => 'region',
                        'coverage' => ['台中', '彰化', '南投', '雲林'],
                    ],
                    'children' => [
                        'taichung_store' => [
                            'name' => '台中逢甲店',
                            'code' => 'TC001',
                            'status' => 'active',
                            'sort_order' => 1,
                            'address' => '台中市西屯區文華路100號',
                            'phone' => '+886-4-2451-2345',
                            'email' => 'taichung@lomisx3.com',
                            'description' => '逢甲商圈旗艦門市',
                            'settings' => [
                                'manager_name' => '台中店長',
                                'type' => 'flagship',
                                'operating_hours' => '10:00-23:00',
                            ],
                        ],
                        'changhua_store' => [
                            'name' => '彰化員林店',
                            'code' => 'CH001',
                            'status' => 'active',
                            'sort_order' => 2,
                            'address' => '彰化縣員林市中山路二段123號',
                            'phone' => '+886-4-8356-7890',
                            'email' => 'changhua@lomisx3.com',
                            'description' => '彰化員林門市',
                            'settings' => [
                                'manager_name' => '彰化店長',
                                'type' => 'standard',
                                'operating_hours' => '09:00-21:00',
                            ],
                        ],
                    ],
                ],
                
                // 南區門市群
                'south_region' => [
                    'name' => '南區營運中心',
                    'code' => 'SOU001',
                    'status' => 'active',
                    'sort_order' => 3,
                    'address' => '高雄市前金區中正四路211號',
                    'phone' => '+886-7-2516-7890',
                    'email' => 'south@lomisx3.com',
                    'description' => '負責嘉義、台南、高雄、屏東地區營運',
                    'settings' => [
                        'manager_name' => '南區經理',
                        'type' => 'region',
                        'coverage' => ['嘉義', '台南', '高雄', '屏東'],
                    ],
                    'children' => [
                        'kaohsiung_store' => [
                            'name' => '高雄夢時代店',
                            'code' => 'KH001',
                            'status' => 'active',
                            'sort_order' => 1,
                            'address' => '高雄市前鎮區中華五路789號',
                            'phone' => '+886-7-9706-1234',
                            'email' => 'kaohsiung@lomisx3.com',
                            'description' => '夢時代購物中心旗艦門市',
                            'settings' => [
                                'manager_name' => '高雄店長',
                                'type' => 'flagship',
                                'operating_hours' => '11:00-22:00',
                            ],
                        ],
                        'tainan_store' => [
                            'name' => '台南安平店',
                            'code' => 'TN001',
                            'status' => 'active',
                            'sort_order' => 2,
                            'address' => '台南市安平區安平路456號',
                            'phone' => '+886-6-2995-6789',
                            'email' => 'tainan@lomisx3.com',
                            'description' => '台南安平觀光區門市',
                            'settings' => [
                                'manager_name' => '台南店長',
                                'type' => 'standard',
                                'operating_hours' => '09:00-21:00',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * 執行門市種子資料建立
     * 
     * @return void
     */
    public function run(): void
    {
        $this->command->info('🏢 開始建立門市種子資料...');
        
        DB::transaction(function () {
            $this->createStores($this->storeData, null, 0);
        });
        
        $this->command->info('✅ 門市種子資料建立完成！');
        $this->printSummary();
    }

    /**
     * 遞迴建立門市資料
     * 
     * @param array $stores 門市資料陣列
     * @param int|null $parentId 父門市ID
     * @param int $depth 層級深度
     * @return void
     */
    private function createStores(array $stores, ?int $parentId = null, int $depth = 0): void
    {
        foreach ($stores as $storeKey => $storeData) {
            $indent = str_repeat('  ', $depth + 1);
            $this->command->line("{$indent}建立門市: {$storeData['name']} ({$storeData['code']})");
            
            // 建立門市
            $store = Store::firstOrCreate(
                ['code' => $storeData['code']],
                [
                    'name' => $storeData['name'],
                    'parent_id' => $parentId,
                    'status' => $storeData['status'],
                    'sort_order' => $storeData['sort_order'] ?? 0,
                    'address' => $storeData['address'] ?? null,
                    'phone' => $storeData['phone'] ?? null,
                    'email' => $storeData['email'] ?? null,
                    'description' => $storeData['description'] ?? null,
                    'settings' => isset($storeData['settings']) ? json_encode($storeData['settings']) : null,
                    'created_by' => 1, // 假設系統管理員ID為1
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            // 遞迴建立子門市
            if (isset($storeData['children'])) {
                $this->createStores($storeData['children'], $store->id, $depth + 1);
            }
        }
    }

    /**
     * 顯示建立摘要
     * 
     * @return void
     */
    private function printSummary(): void
    {
        $totalStores = Store::count();
        
        // 根據名稱來分類門市類型統計
        $storesByTypeTemp = Store::all()->groupBy(function($store) {
            if (str_contains($store->name, '總公司')) return 'headquarters';
            if (str_contains($store->name, '營運中心')) return 'region';
            if (str_contains($store->name, '旗艦')) return 'flagship';
            if (str_contains($store->name, '分店')) return 'branch';
            return 'standard';
        });
        
        $storesByType = $storesByTypeTemp->map(function($stores) {
            return $stores->count();
        })->toArray();
        
        $storesByStatus = Store::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // 計算最大層級（透過查詢父子關係）
        $maxDepth = 0;
        $stores = Store::with('parent')->get();
        foreach ($stores as $store) {
            $depth = $this->calculateDepth($store);
            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }
        }
        
        $this->command->info('');
        $this->command->info('📊 門市系統摘要:');
        $this->command->table(
            ['項目', '數量', '說明'],
            [
                ['門市總數', $totalStores, '完整組織架構'],
                ['最大層級', $maxDepth + 1, '支援樹狀結構'],
                ['營運中心', $storesByType['region'] ?? 0, '區域管理單位'],
                ['旗艦門市', $storesByType['flagship'] ?? 0, '主要營業據點'],
                ['標準門市', $storesByType['standard'] ?? 0, '一般營業據點'],
                ['分店', $storesByType['branch'] ?? 0, '分店據點'],
            ]
        );
        
        $this->command->info('');
        $this->command->info('🎯 門市狀態分布:');
        foreach ($storesByStatus as $status => $count) {
            $statusName = match($status) {
                'active' => '營運中',
                'inactive' => '已關閉',
                default => $status,
            };
            $this->command->line("  {$statusName}: {$count} 家門市");
        }
        
        $this->command->info('');
        $this->command->info('🌳 門市樹狀結構:');
        $this->printStoreTree();
        
        $this->command->info('');
        $this->command->info('💡 提示: 使用 Store 模型的關聯方法可以取得完整樹狀結構');
    }

    /**
     * 顯示門市樹狀結構
     * 
     * @return void
     */
    private function printStoreTree(): void
    {
        $rootStores = Store::whereNull('parent_id')->with('children')->get();
        
        foreach ($rootStores as $store) {
            $this->printStoreNode($store, 0);
        }
    }

    /**
     * 遞迴顯示門市節點
     * 
     * @param Store $store 門市模型
     * @param int $depth 層級深度
     * @return void
     */
    private function printStoreNode(Store $store, int $depth): void
    {
        $indent = str_repeat('  ', $depth);
        
        // 根據名稱判斷類型
        $icon = '📍';
        if (str_contains($store->name, '總公司')) $icon = '🏢';
        elseif (str_contains($store->name, '營運中心')) $icon = '🏛️';
        elseif (str_contains($store->name, '旗艦')) $icon = '🏪';
        elseif (str_contains($store->name, '分店')) $icon = '🏬';
        
        $statusIcon = match($store->status) {
            'active' => '✅',
            'inactive' => '❌',
            default => '❓',
        };
        
        $this->command->line("  {$indent}{$icon} {$store->name} ({$store->code}) {$statusIcon}");
        
        foreach ($store->children as $child) {
            $this->printStoreNode($child, $depth + 1);
        }
    }

    /**
     * 計算門市層級深度
     * 
     * @param Store $store 門市模型
     * @return int
     */
    private function calculateDepth(Store $store): int
    {
        $depth = 0;
        $current = $store;
        
        while ($current->parent_id !== null) {
            $depth++;
            $current = $current->parent;
            
            // 防止無限迴圈
            if ($depth > 10) break;
        }
        
        return $depth;
    }
} 