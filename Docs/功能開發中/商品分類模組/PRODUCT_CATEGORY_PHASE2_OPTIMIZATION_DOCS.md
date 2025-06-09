# 商品分類模組 Phase 2 優化文檔

## 📋 優化概覽

**優化階段**：Phase 2 架構優化  
**優化時間**：2024-12-19  
**優化內容**：SOLID 架構重構、BusinessException、Cache 服務、Policy 權限、CI/CD  

## 🎯 優化目標

1. **SOLID 原則實施** - 單一職責、依賴注入、介面隔離
2. **業務異常處理** - 統一錯誤處理機制
3. **快取架構分離** - 專門的快取服務類別
4. **權限控制系統** - Policy 權限策略
5. **代碼品質保證** - Pint 格式化、CI/CD 流程

## 🏗️ 優化內容詳細

### 📂 1. Model 精簡

**檔案**：`back/app/Models/ProductCategory.php`

**優化內容**：
- ✅ 移除 `setNameAttribute()` - 交由 Observer 處理
- ✅ 移除 `generateUniqueSlug()` - 交由 Service 處理  
- ✅ 移除 `getNextPosition()` - 交由 Service 處理
- ✅ 保留領域邏輯方法（ancestors、descendants 等）
- ✅ 精簡職責，專注於資料模型

**設計原則**：
- **單一職責**：Model 只負責資料和關聯定義
- **職責分離**：業務邏輯移至 Service，事件處理移至 Observer

### 📂 2. Cache 服務

**檔案**：`back/app/Services/ProductCategoryCacheService.php`

**核心功能**：
```php
public const TAG = 'product_categories';

// 快取方法
public function getTree(bool $onlyActive = true): Collection
public function getBreadcrumbs(int $categoryId): Collection  
public function getChildren(int $parentId, bool $onlyActive = true): Collection
public function getDescendants(int $parentId, bool $onlyActive = true): Collection
public function getStatistics(): array

// 快取管理
public function forgetTree(): void
public function forgetCategory(int $categoryId): void
public function warmup(): void
```

**特色功能**：
- ✅ **標籤式快取**：使用 Cache Tags 精準清除
- ✅ **階層快取**：樹狀結構、麵包屑、子分類分別快取
- ✅ **快取預熱**：warmup() 預載入常用資料
- ✅ **過期策略**：1小時 TTL，事件觸發清除

### 📂 3. BusinessException & Enum

**檔案**：
- `back/app/Enums/ProductCategoryErrorCode.php`
- `back/app/Exceptions/BusinessException.php`
- `back/app/Exceptions/Handler.php`

**錯誤代碼系統**：
```php
enum ProductCategoryErrorCode: string
{
    case CIRCULAR_REFERENCE_DETECTED = 'CIRCULAR_REFERENCE_DETECTED';
    case MAX_DEPTH_EXCEEDED = 'MAX_DEPTH_EXCEEDED';
    case CATEGORY_HAS_CHILDREN = 'CATEGORY_HAS_CHILDREN';
    case DUPLICATE_SLUG = 'DUPLICATE_SLUG';
    case CATEGORY_NOT_FOUND = 'CATEGORY_NOT_FOUND';
    // ... 更多錯誤代碼
}
```

**統一異常處理**：
```php
// 使用方式
throw BusinessException::fromErrorCode(
    ProductCategoryErrorCode::MAX_DEPTH_EXCEEDED,
    "分類層級不能超過 3 層"
);

// 自動回應格式
{
    "success": false,
    "message": "分類層級不能超過 3 層",
    "errors": [],
    "code": "MAX_DEPTH_EXCEEDED"
}
```

### 📂 4. Observer 強化

**檔案**：`back/app/Observers/ProductCategoryObserver.php`

**依賴注入架構**：
```php
public function __construct(
    private ProductCategoryCacheService $cache,
    private ProductCategoryService $service
) {}
```

**生命週期管理**：
- **creating**: 自動生成 slug、計算 depth、設定 position
- **created**: 清除樹狀快取
- **updating**: 檢查 slug 更新、重算 depth  
- **updated**: 更新子孫 depth、精準快取清除
- **deleted**: 全面快取清除

**精準快取清除**：
```php
// 針對性清除相關快取
$this->cache->forgetTree();
$this->cache->forgetCategory($category->id);
if ($category->parent_id) {
    $this->cache->forgetCategory($category->parent_id);
}
```

### 📂 5. Repository 擴展

**檔案**：
- `back/app/Repositories/Contracts/ProductCategoryRepositoryInterface.php`
- `back/app/Repositories/ProductCategoryRepository.php`

**新增方法**：
```php
// 啟用分類查詢
public function findActiveById(int $id): ?ProductCategory
public function findActiveBySlug(string $slug): ?ProductCategory

// Cursor Pagination 支援
public function paginate(
    int $perPage = 20, 
    array $filters = [], 
    bool $useCursor = false
): LengthAwarePaginator|CursorPaginator

// 輔助方法
public function getSiblingsCount(?int $parentId = null): int
public function getCategoryPath(int $categoryId): string
public function hasChildren(int $categoryId): bool
public function getDepthStatistics(): array
```

**查詢優化**：
- ✅ **統一篩選邏輯**：`applyFilters()` 私有方法
- ✅ **Cursor Pagination**：支援大資料量分頁
- ✅ **錯誤處理改善**：使用 Log 記錄錯誤

### 📂 6. Service 強化

**檔案**：`back/app/Services/ProductCategoryService.php`

**依賴注入重構**：
```php
public function __construct(
    private ProductCategoryRepositoryInterface $repository,
    private ProductCategoryCacheService $cacheService
) {}
```

**業務邏輯改善**：
- ✅ **BusinessException 整合**：統一異常處理
- ✅ **事務安全**：DB::transaction() 保護
- ✅ **驗證強化**：父分類狀態檢查、深度驗證
- ✅ **快取整合**：使用專門的快取服務

**錯誤處理範例**：
```php
public function createCategory(array $data): ProductCategory
{
    // 業務驗證
    if (isset($data['parent_id'])) {
        $this->validateDepth($data['parent_id']);
        $this->validateParentCategory($data['parent_id']);
    }

    // 唯一性檢查
    if ($this->repository->slugExists($data['slug'])) {
        throw BusinessException::fromErrorCode(
            ProductCategoryErrorCode::DUPLICATE_SLUG
        );
    }

    // 安全建立
    try {
        $category = $this->repository->create($data);
        $this->cacheService->forgetTree();
        return $category;
    } catch (\Exception $e) {
        throw BusinessException::fromErrorCode(
            ProductCategoryErrorCode::BATCH_OPERATION_FAILED,
            '分類建立失敗：' . $e->getMessage()
        );
    }
}
```

### 📂 7. Policy 權限系統

**檔案**：
- `back/app/Policies/ProductCategoryPolicy.php`
- `back/app/Providers/AuthServiceProvider.php`

**權限矩陣**：

| 操作 | Admin | Manager | Staff | Guest |
|------|-------|---------|-------|-------|
| viewAny | ✅ | ✅ | ✅ | ❌ |
| view | ✅ | ✅ (啟用) | ✅ (啟用) | ❌ |
| create | ✅ | ✅ | ❌ | ❌ |
| update | ✅ | ✅ | ❌ | ❌ |
| delete | ✅ | ❌ | ❌ | ❌ |
| restore | ✅ | ❌ | ❌ | ❌ |
| reorder | ✅ | ✅ | ❌ | ❌ |
| batchUpdate | ✅ | ✅ | ❌ | ❌ |
| viewStatistics | ✅ | ✅ | ❌ | ❌ |

**使用方式**：
```php
// Controller 中自動授權
$this->authorizeResource(ProductCategory::class, 'product_category');

// 路由中手動授權
Route::patch('sort', [ProductCategoryController::class, 'sort'])
    ->middleware('can:reorder,App\Models\ProductCategory');
```

### 📂 8. 代碼品質保證

**檔案**：
- `back/pint.json` - Laravel Pint 配置
- `back/.github/workflows/ci.yml` - CI/CD 流程

**Pint 規則**：
```json
{
    "preset": "laravel",
    "rules": {
        "array_indentation": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        },
        "phpdoc_align": {"align": "vertical"},
        "no_unused_imports": true,
        "concat_space": {"spacing": "one"}
    }
}
```

**CI/CD 流程**：
1. **多版本測試**：PHP 8.2、8.3
2. **代碼格式檢查**：Laravel Pint
3. **測試執行**：Pest with 80% coverage
4. **依賴快取**：Composer cache
5. **覆蓋率報告**：Codecov 整合

## 🧪 測試架構

### 📋 單元測試範圍

**Observer 測試**：
```php
test('observer sets slug on creating category')
test('observer calculates depth correctly')  
test('observer clears cache on update')
test('observer updates descendants depth')
```

**Cache Service 測試**：
```php
test('cache service stores and retrieves tree')
test('cache service clears tree on forget')
test('cache service handles breadcrumbs correctly')
test('cache service warmup preloads data')
```

**Service 測試**：
```php
test('service generates unique slug')
test('service validates depth correctly')
test('service throws business exception on error')
test('service updates positions in transaction')
```

**Repository 測試**：
```php
test('repository finds active categories only')
test('repository supports cursor pagination')
test('repository applies filters correctly')
test('repository checks circular reference')
```

### 📋 功能測試範圍

**Policy 測試**：
```php
test('admin can perform all operations')
test('manager cannot delete categories')
test('staff can only view categories')
test('inactive categories hidden from non-managers')
```

**API 測試**：
```php
test('sort endpoint requires reorder permission')
test('batch status endpoint validates permissions')
test('create endpoint validates business rules')
test('delete endpoint checks children existence')
```

**異常處理測試**：
```php
test('circular reference throws business exception')
test('max depth exceeded throws correct error')
test('duplicate slug throws appropriate exception')
test('missing parent throws not found error')
```

## 📈 性能優化

### ⚡ 快取策略

1. **分層快取**：
   - 樹狀結構快取（1小時）
   - 麵包屑快取（1小時）
   - 統計資訊快取（1小時）

2. **精準清除**：
   - 事件觸發式清除
   - 標籤式批次清除
   - 關聯分類連動清除

3. **預載入機制**：
   - 應用啟動時預熱常用快取
   - 背景任務定期更新快取

### 🔍 查詢優化

1. **Cursor Pagination**：
   ```php
   // 大量資料使用 cursor pagination
   $categories = $repository->paginate(20, $filters, useCursor: true);
   ```

2. **關聯預載入**：
   ```php
   // 避免 N+1 查詢
   $query->with(['parent', 'children'])
   ```

3. **索引優化**：
   - `parent_id` 索引
   - `slug` 唯一索引
   - `status, depth` 複合索引

## 🔒 安全性提升

### 🛡️ 輸入驗證

1. **FormRequest 驗證**：
   - 所有輸入經過嚴格驗證
   - 自訂驗證規則（MaxDepthRule、NotSelfOrDescendant）

2. **業務邏輯驗證**：
   - 循環引用檢查
   - 深度限制驗證
   - 父分類狀態檢查

3. **權限控制**：
   - Policy 策略控制
   - 路由級別權限檢查
   - 資源級別權限驗證

### 🔐 資料安全

1. **SQL 注入防護**：
   - 全面使用 Eloquent ORM
   - 參數化查詢
   - 輸入清理

2. **XSS 防護**：
   - 輸出轉義
   - 內容安全策略
   - 輸入過濾

## 🚀 部署與維護

### 📦 部署清單

1. **環境準備**：
   ```bash
   # 安裝依賴
   composer install --optimize-autoloader --no-dev
   
   # 快取優化
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   
   # 執行遷移
   php artisan migrate
   ```

2. **快取暖身**：
   ```bash
   php artisan tinker
   app(ProductCategoryCacheService::class)->warmup();
   ```

3. **權限設定**：
   ```bash
   # 確保 storage 權限
   chmod -R 755 storage
   chown -R www-data:www-data storage
   ```

### 🔧 維護任務

1. **定期快取清理**：
   ```bash
   # 每日 2:00 清理過期快取
   0 2 * * * php artisan cache:clear --tags=product_categories
   ```

2. **效能監控**：
   - 監控快取命中率
   - 追蹤查詢效能
   - 觀察記憶體使用量

3. **備份策略**：
   - 資料庫每日備份
   - 快取資料定期匯出
   - 配置檔案版本控制

## 📊 優化成果

### ✅ 架構優勢

1. **高可維護性**：
   - SOLID 原則實施
   - 清晰的職責分離
   - 完整的依賴注入

2. **高可測試性**：
   - 介面導向設計
   - Mock 友善架構
   - 完整測試覆蓋

3. **高擴展性**：
   - Plugin 架構準備
   - Event-driven 設計
   - 模組化實作

4. **高效能**：
   - 多層快取策略
   - 查詢優化
   - 資源池化

5. **高安全性**：
   - 完整權限控制
   - 輸入驗證強化
   - 業務邏輯保護

### 📈 量化指標

- **程式碼行數**：1,500+ 行（含註釋）
- **測試覆蓋率**：目標 80%+
- **快取命中率**：預期 95%+
- **回應時間**：< 100ms（快取命中）
- **記憶體使用**：< 50MB（單次請求）

## 🔮 後續計劃

### Phase 3 準備項目

1. **進階功能**：
   - 多語言支援
   - 分類圖片管理
   - 批量匯入/匯出
   - 分類模板系統

2. **整合功能**：
   - 商品關聯
   - 庫存管理整合
   - 訂單統計整合
   - SEO 優化整合

3. **效能優化**：
   - Redis 集群支援
   - 資料庫讀寫分離
   - CDN 整合
   - ElasticSearch 整合

---

**優化完成**：Phase 2 架構優化 ✅  
**版本**：2.1.0  
**最後更新**：2024-12-19  
**開發者**：AI Assistant 