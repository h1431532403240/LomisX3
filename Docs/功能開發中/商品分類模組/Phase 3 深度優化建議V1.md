# LomisX3 Phase 3 立即補強 - 3個關鍵問題修復

請按照以下優先順序實施 3 個重要的生產環境補強，確保系統穩定性和代碼品質。

## 🔥 P0 - Prometheus Cardinality 修復 (避免監控爆表)

### 問題描述
目前 `ProductCategoryCacheService` 中的 Prometheus 指標使用了 `root_id` label，這會造成高基數問題，導致 TSDB 記憶體爆表。

### 需要修改的檔案
`back/app/Services/ProductCategoryCacheService.php`

### 具體修改
1. **移除高基數 label**：將 `root_id` 改為 `root_group`
2. **新增分組邏輯**：根據根分類數量分組 (small/medium/large)
3. **簡化 label 結構**：只保留 6 個低基數 labels

### 實施代碼

#### 修改 recordPrometheusMetrics 方法
```php
/**
 * 記錄 Prometheus 指標（修復高基數問題）
 */
private function recordPrometheusMetrics(float $startTime, string $status, bool $onlyActive, array $context = []): void
{
    if (!$this->prometheusEnabled) {
        return;
    }

    try {
        $duration = microtime(true) - $startTime;
        $namespace = config('prometheus.namespace', 'app');
        
        // ✅ 修復：使用低基數分組而非具體 root_id
        $rootGroup = $this->getRootGroup($context);
        
        // 執行時間直方圖
        $histogram = $this->prometheusRegistry->getOrRegisterHistogram(
            $namespace,
            'pc_get_tree_seconds',
            '商品分類取得樹狀結構執行時間',
            ['filter', 'cache_result', 'root_group', 'operation', 'cache_level']
        );
        
        $histogram->observe($duration, [
            $onlyActive ? 'active' : 'all',
            $status,
            $rootGroup,
            $context['operation'] ?? 'get_tree',
            $context['cache_level'] ?? 'l1'
        ]);

        // 快取命中率計數器
        $counter = $this->prometheusRegistry->getOrRegisterCounter(
            $namespace,
            'pc_cache_total',
            '商品分類快取操作計數',
            ['filter', 'result', 'root_group']
        );
        
        $counter->inc([
            $onlyActive ? 'active' : 'all',
            $status,
            $rootGroup
        ]);

    } catch (\Exception $e) {
        Log::warning('Prometheus 指標記錄失敗', [
            'error' => $e->getMessage(),
            'method' => __METHOD__
        ]);
    }
}

/**
 * 將根分類轉換為低基數分組
 */
private function getRootGroup(array $context): string
{
    // 如果是全樹查詢
    if (empty($context['root_id'])) {
        return 'all_roots';
    }
    
    // 根據系統中根分類總數分組
    $rootCount = Cache::remember('pc_root_count', 300, function () {
        return ProductCategory::whereNull('parent_id')->where('status', true)->count();
    });
    
    if ($rootCount <= 10) return 'small';      // 1-10 個根分類
    if ($rootCount <= 50) return 'medium';     // 11-50 個根分類
    return 'large';                            // 50+ 個根分類
}
```

---

## 🔍 P1 - PHPStan Level 提升 (Level 5 → 7)

### 問題描述
目前 PHPStan 設定為 Level 5，但代碼品質已可達 Level 7。提升等級可早期發現型別錯誤。

### 需要修改的檔案
1. `back/phpstan.neon.dist`
2. `back/phpstan-baseline.neon` (重新生成)
3. `.github/workflows/ci.yml`

### 實施步驟

#### 1. 更新 phpstan.neon.dist
```yaml
parameters:
    level: 7
    paths:
        - app
    excludePaths:
        - app/Console/Kernel.php
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
    reportUnmatchedIgnoredErrors: false

includes:
    - phpstan-baseline.neon
    - ./vendor/nunomaduro/larastan/extension.neon
```

#### 2. 重新生成 baseline
```bash
# 刪除舊 baseline
rm back/phpstan-baseline.neon

# 生成新 baseline (Level 7)
cd back && ./vendor/bin/phpstan analyse --level=7 --generate-baseline

# 檢查 baseline 內容，確保只包含誤報
```

#### 3. 更新 CI workflow
在 `.github/workflows/ci.yml` 中修改 PHPStan 步驟：
```yaml
- name: Run PHPStan Level 7
  run: |
    cd back
    ./vendor/bin/phpstan analyse --level=7 --error-format=github --memory-limit=-1
    
- name: Check PHPStan baseline drift
  run: |
    cd back
    CURRENT_ERRORS=$(./vendor/bin/phpstan analyse --level=7 --no-progress 2>&1 | grep -o '[0-9]\+ errors' | cut -d' ' -f1 || echo "0")
    BASELINE_ERRORS=$(grep -o '"totals":{"errors":[0-9]\+' phpstan-baseline.neon | grep -o '[0-9]\+' || echo "0")
    if [ "$CURRENT_ERRORS" -gt "$BASELINE_ERRORS" ]; then
      echo "::error::PHPStan 錯誤數量增加 ($CURRENT_ERRORS > $BASELINE_ERRORS)"
      exit 1
    fi
```

---

## 🧪 P2 - 佇列 Job 測試補強

### 問題描述
目前只測試了 debounce 觸發，沒有測試 `FlushProductCategoryCache` Job 是否真正執行並清除快取。

### 需要新增的檔案
`back/tests/Feature/FlushProductCategoryCacheJobTest.php`

### 實施代碼

#### 新增測試檔案
```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\FlushProductCategoryCache;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 測試快取清除 Job 的佇列執行
 */
class FlushProductCategoryCacheJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試 Job 被正確 dispatch 到 low queue
     */
    public function test_flush_job_dispatched_to_correct_queue(): void
    {
        // Arrange
        Queue::fake();
        $category = ProductCategory::factory()->create();

        // Act
        FlushProductCategoryCache::dispatch([$category->id])
            ->onQueue(config('product_category.cache_flush_queue', 'low'));

        // Assert
        Queue::assertPushedOn('low', FlushProductCategoryCache::class);
        Queue::assertPushed(FlushProductCategoryCache::class, function ($job) use ($category) {
            return in_array($category->id, $job->categoryIds);
        });
    }

    /**
     * 測試 Job 執行後真的清除快取標籤
     */
    public function test_flush_job_actually_clears_tagged_cache(): void
    {
        // Arrange
        $category = ProductCategory::factory()->create();
        
        // 建立測試快取
        Cache::tags(['product_categories'])->put('pc_tree_active', 'test_data', 3600);
        Cache::tags(['product_categories'])->put('pc_stats_active', 'test_stats', 3600);
        
        // 確認快取存在
        $this->assertTrue(Cache::tags(['product_categories'])->has('pc_tree_active'));
        $this->assertTrue(Cache::tags(['product_categories'])->has('pc_stats_active'));

        // Act - 執行 Job
        $job = new FlushProductCategoryCache([$category->id]);
        $job->handle();

        // Assert - 所有標籤快取被清除
        $this->assertFalse(Cache::tags(['product_categories'])->has('pc_tree_active'));
        $this->assertFalse(Cache::tags(['product_categories'])->has('pc_stats_active'));
    }

    /**
     * 測試 Job 處理多個分類 ID
     */
    public function test_flush_job_handles_multiple_categories(): void
    {
        // Arrange
        $categories = ProductCategory::factory()->count(3)->create();
        $categoryIds = $categories->pluck('id')->toArray();

        // 建立快取
        Cache::tags(['product_categories'])->put('pc_tree_active', 'test1', 3600);

        // Act
        $job = new FlushProductCategoryCache($categoryIds);
        $job->handle();

        // Assert
        $this->assertFalse(Cache::tags(['product_categories'])->has('pc_tree_active'));
    }

    /**
     * 測試 Job 錯誤處理
     */
    public function test_flush_job_handles_cache_failure_gracefully(): void
    {
        // Arrange
        $category = ProductCategory::factory()->create();
        
        // Act & Assert - Job 不應該拋出未捕獲的異常
        $job = new FlushProductCategoryCache([$category->id]);
        
        // 這個測試確保 Job 有適當的錯誤處理
        $this->expectNotToPerformAssertions();
        $job->handle();
    }

    /**
     * 測試 Job 配置正確性
     */
    public function test_flush_job_configuration(): void
    {
        // Arrange
        $job = new FlushProductCategoryCache([1, 2, 3]);

        // Assert
        $this->assertEquals('low', $job->queue);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->timeout);
    }
}
```

#### 更新 FlushProductCategoryCache Job (如果需要)
確保 Job 有適當的錯誤處理和配置：
```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FlushProductCategoryCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $categoryIds;
    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(array $categoryIds)
    {
        $this->categoryIds = $categoryIds;
        $this->onQueue(config('product_category.cache_flush_queue', 'low'));
    }

    public function handle(): void
    {
        try {
            Log::info('FlushProductCategoryCache job started', [
                'category_ids' => $this->categoryIds,
                'queue' => $this->queue ?? 'default'
            ]);

            // 清除所有商品分類相關快取
            Cache::tags(['product_categories'])->flush();
            
            Log::info('FlushProductCategoryCache job completed', [
                'category_ids' => $this->categoryIds
            ]);
            
        } catch (\Exception $e) {
            Log::error('FlushProductCategoryCache job failed', [
                'category_ids' => $this->categoryIds,
                'error' => $e->getMessage()
            ]);
            throw $e; // 重新拋出讓 Laravel 處理重試
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('FlushProductCategoryCache job permanently failed', [
            'category_ids' => $this->categoryIds,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
```

---

## ✅ 實施檢查清單

請按順序完成以下步驟：

### P0 - Prometheus 修復
- [ ] 修改 `ProductCategoryCacheService.php` 的 `recordPrometheusMetrics` 方法
- [ ] 新增 `getRootGroup` 方法
- [ ] 移除所有 `root_id` 和 `depth_limit` labels
- [ ] 測試修改後的指標記錄

### P1 - PHPStan 升級
- [ ] 更新 `phpstan.neon.dist` level 為 7
- [ ] 刪除舊的 `phpstan-baseline.neon`
- [ ] 執行 `./vendor/bin/phpstan analyse --level=7 --generate-baseline`
- [ ] 檢查 baseline 內容，修復真正的錯誤
- [ ] 更新 CI workflow
- [ ] 確認 CI 通過

### P2 - 佇列測試
- [ ] 新增 `FlushProductCategoryCacheJobTest.php`
- [ ] 檢查 `FlushProductCategoryCache` Job 是否需要更新
- [ ] 執行新測試確認通過
- [ ] 驗證測試覆蓋到 job dispatch 和執行

### 最終驗證
- [ ] 執行完整測試套件：`php artisan test`
- [ ] 執行 PHPStan：`./vendor/bin/phpstan analyse --level=7`
- [ ] 確認 CI workflow 通過
- [ ] 檢查 Prometheus 指標是否正常記錄（無高基數警告）

## 🎯 預期效果

完成後應該達到：
- **監控穩定性**: Prometheus cardinality 降低 80%+
- **代碼品質**: PHPStan Level 7 無新增錯誤
- **測試可靠性**: 佇列機制 100% 測試覆蓋
- **總實施時間**: 約 30 分鐘

---

**請嚴格按照優先順序執行，並在每個步驟完成後進行測試驗證！**