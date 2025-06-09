<?php

namespace App\Console\Commands;

use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use App\Services\ProductCategoryCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 商品分類壓力測試命令
 * 
 * 執行各種壓力測試場景來驗證系統在高負載下的表現
 * 包含併發操作、大量數據查詢、快取效能等測試
 */
class ProductCategoryStressTest extends Command
{
    /**
     * 命令名稱和描述
     */
    protected $signature = 'stress:product-category
                           {--scenario=all : 測試場景 (all|cache|query|crud|concurrent)}
                           {--users=100 : 模擬用戶數量}
                           {--requests=1000 : 總請求數量}
                           {--duration=60 : 測試持續時間(秒)}';

    protected $description = '執行商品分類模組的壓力測試';

    /**
     * 服務注入
     */
    public function __construct(
        private ProductCategoryService $categoryService,
        private ProductCategoryCacheService $cacheService
    ) {
        parent::__construct();
    }

    /**
     * 執行壓力測試
     */
    public function handle(): int
    {
        $scenario = $this->option('scenario');
        $users = (int) $this->option('users');
        $requests = (int) $this->option('requests');
        $duration = (int) $this->option('duration');

        $this->info("🔥 開始商品分類壓力測試");
        $this->info("測試場景: {$scenario}");
        $this->info("模擬用戶: {$users}");
        $this->info("總請求數: {$requests}");
        $this->info("測試時長: {$duration}秒");

        $startTime = microtime(true);
        $results = [];

        try {
            switch ($scenario) {
                case 'cache':
                    $results = $this->testCachePerformance($users, $requests);
                    break;
                case 'query':
                    $results = $this->testQueryPerformance($users, $requests);
                    break;
                case 'crud':
                    $results = $this->testCrudOperations($users, $requests);
                    break;
                case 'concurrent':
                    $results = $this->testConcurrentOperations($users, $duration);
                    break;
                case 'all':
                default:
                    $results = $this->runAllScenarios($users, $requests, $duration);
                    break;
            }

            $totalTime = microtime(true) - $startTime;
            $this->displayResults($results, $totalTime);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("壓力測試執行失敗: " . $e->getMessage());
            Log::error('ProductCategory stress test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }

    /**
     * 測試快取效能
     * 
     * @param int $users 用戶數量
     * @param int $requests 請求數量
     * @return array 測試結果
     */
    private function testCachePerformance(int $users, int $requests): array
    {
        $this->info("📊 測試快取效能...");

        $results = [
            'scenario' => 'cache_performance',
            'total_requests' => $requests,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'avg_response_time' => 0,
            'min_response_time' => PHP_FLOAT_MAX,
            'max_response_time' => 0,
            'errors' => 0,
        ];

        $totalTime = 0;
        $progressBar = $this->output->createProgressBar($requests);

        for ($i = 0; $i < $requests; $i++) {
            $startTime = microtime(true);

            try {
                // 測試不同的快取場景
                $scenario = $i % 4;
                
                switch ($scenario) {
                    case 0:
                        // 測試樹狀結構快取
                        $tree = $this->cacheService->getTree(true);
                        break;
                    case 1:
                        // 測試麵包屑快取
                        $categoryId = rand(1, 100);
                        $breadcrumbs = $this->cacheService->getBreadcrumbs($categoryId);
                        break;
                    case 2:
                        // 測試子分類快取
                        $parentId = rand(1, 50);
                        $children = $this->cacheService->getChildren($parentId);
                        break;
                    case 3:
                        // 測試根祖先快取
                        $categoryId = rand(1, 100);
                        $category = ProductCategory::find($categoryId);
                        if ($category) {
                            $rootId = $category->getRootAncestorId();
                        }
                        break;
                }

                $endTime = microtime(true);
                $responseTime = ($endTime - $startTime) * 1000; // 轉換為毫秒

                $totalTime += $responseTime;
                $results['min_response_time'] = min($results['min_response_time'], $responseTime);
                $results['max_response_time'] = max($results['max_response_time'], $responseTime);

                // 檢查是否為快取命中（簡化判斷）
                if ($responseTime < 10) { // 10ms 以下視為快取命中
                    $results['cache_hits']++;
                } else {
                    $results['cache_misses']++;
                }

            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Cache stress test error', ['error' => $e->getMessage()]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $results['avg_response_time'] = $totalTime / $requests;
        $results['cache_hit_rate'] = ($results['cache_hits'] / $requests) * 100;

        return $results;
    }

    /**
     * 測試查詢效能
     * 
     * @param int $users 用戶數量
     * @param int $requests 請求數量
     * @return array 測試結果
     */
    private function testQueryPerformance(int $users, int $requests): array
    {
        $this->info("🔍 測試查詢效能...");

        $results = [
            'scenario' => 'query_performance',
            'total_requests' => $requests,
            'successful_queries' => 0,
            'failed_queries' => 0,
            'avg_response_time' => 0,
            'min_response_time' => PHP_FLOAT_MAX,
            'max_response_time' => 0,
            'avg_db_queries' => 0,
            'errors' => 0,
        ];

        $totalTime = 0;
        $totalDbQueries = 0;
        $progressBar = $this->output->createProgressBar($requests);

        for ($i = 0; $i < $requests; $i++) {
            $startTime = microtime(true);
            $queryCount = 0;

            // 監聽資料庫查詢
            DB::listen(function($query) use (&$queryCount) {
                $queryCount++;
            });

            try {
                // 測試不同的查詢場景
                $scenario = $i % 5;
                
                switch ($scenario) {
                    case 0:
                        // 基本查詢
                        ProductCategory::active()->take(20)->get();
                        break;
                    case 1:
                        // 階層查詢
                        $category = ProductCategory::with('children')->first();
                        if ($category) {
                            $descendants = $category->descendants();
                        }
                        break;
                    case 2:
                        // 搜尋查詢
                        ProductCategory::search('分類')->take(10)->get();
                        break;
                    case 3:
                        // 深度查詢
                        ProductCategory::withDepth(3)->ordered()->get();
                        break;
                    case 4:
                        // 複雜查詢
                        ProductCategory::root()
                            ->with(['children.children'])
                            ->active()
                            ->ordered()
                            ->get();
                        break;
                }

                $endTime = microtime(true);
                $responseTime = ($endTime - $startTime) * 1000;

                $totalTime += $responseTime;
                $totalDbQueries += $queryCount;
                $results['min_response_time'] = min($results['min_response_time'], $responseTime);
                $results['max_response_time'] = max($results['max_response_time'], $responseTime);
                $results['successful_queries']++;

            } catch (\Exception $e) {
                $results['failed_queries']++;
                $results['errors']++;
                Log::error('Query stress test error', ['error' => $e->getMessage()]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $results['avg_response_time'] = $totalTime / $requests;
        $results['avg_db_queries'] = $totalDbQueries / $requests;

        return $results;
    }

    /**
     * 測試 CRUD 操作效能
     * 
     * @param int $users 用戶數量
     * @param int $requests 請求數量
     * @return array 測試結果
     */
    private function testCrudOperations(int $users, int $requests): array
    {
        $this->info("📝 測試 CRUD 操作效能...");

        $results = [
            'scenario' => 'crud_operations',
            'total_operations' => $requests,
            'create_operations' => 0,
            'read_operations' => 0,
            'update_operations' => 0,
            'delete_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'avg_response_time' => 0,
            'errors' => 0,
        ];

        $totalTime = 0;
        $progressBar = $this->output->createProgressBar($requests);
        $testCategoryIds = [];

        for ($i = 0; $i < $requests; $i++) {
            $startTime = microtime(true);

            try {
                $operation = $i % 4; // 平均分配 CRUD 操作

                switch ($operation) {
                    case 0: // Create
                        $category = $this->categoryService->createCategory([
                            'name' => "測試分類-{$i}",
                            'status' => true,
                            'position' => $i + 1,
                        ]);
                        $testCategoryIds[] = $category->id;
                        $results['create_operations']++;
                        break;

                    case 1: // Read
                        if (!empty($testCategoryIds)) {
                            $categoryId = $testCategoryIds[array_rand($testCategoryIds)];
                            ProductCategory::with(['parent', 'children'])->find($categoryId);
                        } else {
                            ProductCategory::first();
                        }
                        $results['read_operations']++;
                        break;

                    case 2: // Update
                        if (!empty($testCategoryIds)) {
                            $categoryId = $testCategoryIds[array_rand($testCategoryIds)];
                            $this->categoryService->updateCategory($categoryId, [
                                'name' => "更新測試分類-{$i}",
                                'description' => "壓力測試更新 - " . now()->toDateTimeString(),
                            ]);
                        }
                        $results['update_operations']++;
                        break;

                    case 3: // Delete
                        if (count($testCategoryIds) > 10) { // 保留一些測試數據
                            $categoryId = array_shift($testCategoryIds);
                            $this->categoryService->deleteCategory($categoryId);
                        }
                        $results['delete_operations']++;
                        break;
                }

                $endTime = microtime(true);
                $responseTime = ($endTime - $startTime) * 1000;
                $totalTime += $responseTime;
                $results['successful_operations']++;

            } catch (\Exception $e) {
                $results['failed_operations']++;
                $results['errors']++;
                Log::error('CRUD stress test error', ['error' => $e->getMessage()]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // 清理測試數據
        if (!empty($testCategoryIds)) {
            ProductCategory::whereIn('id', $testCategoryIds)->forceDelete();
        }

        $results['avg_response_time'] = $totalTime / $requests;

        return $results;
    }

    /**
     * 測試併發操作
     * 
     * @param int $users 用戶數量
     * @param int $duration 測試持續時間
     * @return array 測試結果
     */
    private function testConcurrentOperations(int $users, int $duration): array
    {
        $this->info("⚡ 測試併發操作...");

        $results = [
            'scenario' => 'concurrent_operations',
            'duration' => $duration,
            'simulated_users' => $users,
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'operations_per_second' => 0,
            'errors' => 0,
        ];

        $endTime = time() + $duration;
        $operationCount = 0;

        $this->info("模擬 {$users} 個併發用戶，持續 {$duration} 秒...");

        while (time() < $endTime) {
            $userOperations = [];

            // 模擬多個用戶同時執行操作
            for ($user = 0; $user < $users; $user++) {
                $userOperations[] = $this->simulateUserOperation($user);
                $operationCount++;
            }

            // 等待一小段時間模擬真實用戶行為
            usleep(rand(10000, 50000)); // 10-50ms
        }

        $results['total_operations'] = $operationCount;
        $results['operations_per_second'] = $operationCount / $duration;

        return $results;
    }

    /**
     * 模擬單個用戶操作
     * 
     * @param int $userId 用戶ID
     * @return bool 操作是否成功
     */
    private function simulateUserOperation(int $userId): bool
    {
        try {
            $operations = ['tree', 'search', 'breadcrumbs', 'children'];
            $operation = $operations[array_rand($operations)];

            switch ($operation) {
                case 'tree':
                    $this->categoryService->getTree(['include_inactive' => false]);
                    break;
                case 'search':
                    ProductCategory::search('分類')->take(5)->get();
                    break;
                case 'breadcrumbs':
                    $categoryId = rand(1, 100);
                    $this->categoryService->getCachedBreadcrumbs($categoryId);
                    break;
                case 'children':
                    $parentId = rand(1, 50);
                    $this->categoryService->getCachedChildren($parentId);
                    break;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Concurrent operation error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 執行所有測試場景
     * 
     * @param int $users 用戶數量
     * @param int $requests 請求數量
     * @param int $duration 持續時間
     * @return array 測試結果
     */
    private function runAllScenarios(int $users, int $requests, int $duration): array
    {
        $allResults = [
            'overall' => [
                'total_scenarios' => 4,
                'completed_scenarios' => 0,
                'start_time' => now()->toDateTimeString(),
            ]
        ];

        // 執行所有測試場景
        $scenarios = [
            ['cache', $users, $requests],
            ['query', $users, $requests],
            ['crud', $users, min($requests, 100)], // CRUD 操作較重，減少請求數
            ['concurrent', $users, min($duration, 30)], // 併發測試時間較短
        ];

        foreach ($scenarios as [$scenario, $scenarioUsers, $scenarioRequests]) {
            $this->info("\n--- 執行 {$scenario} 測試場景 ---");
            
            switch ($scenario) {
                case 'cache':
                    $result = $this->testCachePerformance($scenarioUsers, $scenarioRequests);
                    break;
                case 'query':
                    $result = $this->testQueryPerformance($scenarioUsers, $scenarioRequests);
                    break;
                case 'crud':
                    $result = $this->testCrudOperations($scenarioUsers, $scenarioRequests);
                    break;
                case 'concurrent':
                    $result = $this->testConcurrentOperations($scenarioUsers, $scenarioRequests);
                    break;
            }

            $allResults[$scenario] = $result;
            $allResults['overall']['completed_scenarios']++;
        }

        $allResults['overall']['end_time'] = now()->toDateTimeString();

        return $allResults;
    }

    /**
     * 顯示測試結果
     * 
     * @param array $results 測試結果
     * @param float $totalTime 總執行時間
     */
    private function displayResults(array $results, float $totalTime): void
    {
        $this->newLine(2);
        $this->info("📈 壓力測試結果報告");
        $this->info("================");

        if (isset($results['overall'])) {
            // 顯示整體結果
            $this->displayOverallResults($results, $totalTime);
        } else {
            // 顯示單一場景結果
            $this->displayScenarioResults($results, $totalTime);
        }

        $this->newLine();
        $this->info("測試完成！");
    }

    /**
     * 顯示整體測試結果
     * 
     * @param array $results 測試結果
     * @param float $totalTime 總執行時間
     */
    private function displayOverallResults(array $results, float $totalTime): void
    {
        $overall = $results['overall'];
        
        $this->table(
            ['項目', '數值'],
            [
                ['總測試場景', $overall['total_scenarios']],
                ['完成場景', $overall['completed_scenarios']],
                ['總執行時間', number_format($totalTime, 2) . '秒'],
                ['開始時間', $overall['start_time']],
                ['結束時間', $overall['end_time']],
            ]
        );

        // 顯示各場景摘要
        foreach (['cache', 'query', 'crud', 'concurrent'] as $scenario) {
            if (isset($results[$scenario])) {
                $this->newLine();
                $this->info("--- {$scenario} 場景結果 ---");
                $this->displayScenarioResults($results[$scenario]);
            }
        }
    }

    /**
     * 顯示單一場景測試結果
     * 
     * @param array $results 測試結果
     * @param float|null $totalTime 總執行時間
     */
    private function displayScenarioResults(array $results, ?float $totalTime = null): void
    {
        $scenario = $results['scenario'] ?? 'unknown';

        switch ($scenario) {
            case 'cache_performance':
                $this->table(
                    ['指標', '數值'],
                    [
                        ['總請求數', $results['total_requests']],
                        ['快取命中', $results['cache_hits']],
                        ['快取未命中', $results['cache_misses']],
                        ['快取命中率', number_format($results['cache_hit_rate'], 2) . '%'],
                        ['平均回應時間', number_format($results['avg_response_time'], 2) . 'ms'],
                        ['最短回應時間', number_format($results['min_response_time'], 2) . 'ms'],
                        ['最長回應時間', number_format($results['max_response_time'], 2) . 'ms'],
                        ['錯誤數', $results['errors']],
                    ]
                );
                break;

            case 'query_performance':
                $this->table(
                    ['指標', '數值'],
                    [
                        ['總查詢數', $results['total_requests']],
                        ['成功查詢', $results['successful_queries']],
                        ['失敗查詢', $results['failed_queries']],
                        ['平均回應時間', number_format($results['avg_response_time'], 2) . 'ms'],
                        ['平均DB查詢數', number_format($results['avg_db_queries'], 2)],
                        ['最短回應時間', number_format($results['min_response_time'], 2) . 'ms'],
                        ['最長回應時間', number_format($results['max_response_time'], 2) . 'ms'],
                        ['錯誤數', $results['errors']],
                    ]
                );
                break;

            case 'crud_operations':
                $this->table(
                    ['指標', '數值'],
                    [
                        ['總操作數', $results['total_operations']],
                        ['建立操作', $results['create_operations']],
                        ['讀取操作', $results['read_operations']],
                        ['更新操作', $results['update_operations']],
                        ['刪除操作', $results['delete_operations']],
                        ['成功操作', $results['successful_operations']],
                        ['失敗操作', $results['failed_operations']],
                        ['平均回應時間', number_format($results['avg_response_time'], 2) . 'ms'],
                        ['錯誤數', $results['errors']],
                    ]
                );
                break;

            case 'concurrent_operations':
                $this->table(
                    ['指標', '數值'],
                    [
                        ['測試持續時間', $results['duration'] . '秒'],
                        ['模擬用戶數', $results['simulated_users']],
                        ['總操作數', $results['total_operations']],
                        ['成功操作', $results['successful_operations']],
                        ['失敗操作', $results['failed_operations']],
                        ['每秒操作數', number_format($results['operations_per_second'], 2)],
                        ['錯誤數', $results['errors']],
                    ]
                );
                break;
        }

        if ($totalTime !== null) {
            $this->info("總執行時間: " . number_format($totalTime, 2) . "秒");
        }
    }
} 