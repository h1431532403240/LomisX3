# 商品分類模組 Phase 1 重構文檔

## 📖 重構總覽

### 重構目標
將現有的商品分類模組調整為更符合 SOLID 原則的企業級架構，提升可測試性、可維護性，為 Phase 2 的開發打下更好的基礎。

### 重構原則
1. **單一職責**：Model 只負責資料和領域邏輯
2. **依賴注入**：使用介面而非具體實作
3. **可測試性**：Repository 和 Observer 都易於測試
4. **關注分離**：查詢邏輯在 Repository，事件處理在 Observer

## 🏗️ 架構變更

### 原架構問題
- Model 承擔過多職責（資料存取、業務邏輯、事件處理）
- 靜態方法不利於測試
- 直接在 Model 中處理複雜的查詢邏輯
- 驗證規則與 Model 緊耦合

### 新架構優勢
- 清晰的職責分離
- 高可測試性
- 遵循依賴注入原則
- 易於擴展和維護

## 📂 新增檔案

### 1. Observer（觀察者模式）
**檔案**：`app/Observers/ProductCategoryObserver.php`

**職責**：
- 處理 Model 生命週期事件
- 自動生成 slug
- 自動計算 depth
- 自動設定 position
- 處理子分類的級聯操作

**主要方法**：
- `creating()` - 建立前處理
- `updating()` - 更新前處理
- `updated()` - 更新後處理（更新子分類深度）
- `deleting()` - 刪除前處理（軟刪除子分類）
- `updateDescendantsDepth()` - 更新所有子孫分類深度

### 2. Repository Interface（資料存取層介面）
**檔案**：`app/Repositories/Contracts/ProductCategoryRepositoryInterface.php`

**定義的契約**：
- 基本 CRUD 操作
- 樹狀結構查詢
- 祖先/子孫查詢
- 搜尋和篩選
- 批次操作
- 統計功能

**主要方法**：
```php
getTree(bool $onlyActive = true): Collection
paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
getAncestors(int $categoryId): Collection
getDescendants(int $categoryId): Collection
checkCircularReference(int $categoryId, ?int $parentId): bool
updatePositions(array $positions): bool
batchUpdateStatus(array $ids, bool $status): int
getStatistics(): array
```

### 3. Repository Implementation（資料存取層實作）
**檔案**：`app/Repositories/ProductCategoryRepository.php`

**功能特色**：
- 實作所有介面定義的方法
- 包含完整的錯誤處理
- 支援複雜的篩選條件
- 提供詳細的統計資訊
- 使用 Model 的 Scopes 進行查詢

### 4. Repository Service Provider
**檔案**：`app/Providers/RepositoryServiceProvider.php`

**功能**：
- 註冊 Repository 介面與實作的綁定
- 支援依賴注入
- 便於未來添加其他 Repository

## 🔄 修改檔案

### 1. ProductCategory Model
**變更**：
- 移除所有靜態方法（`getTree()`, `updatePositions()`, `checkCircularReference()`）
- 移除 Model Events（`booted()` 方法）
- 保留實例方法和領域邏輯
- 簡化職責，專注於資料模型和關聯

**保留的方法**：
- `generateUniqueSlug()` - 生成唯一 slug
- `calculateDepth()` - 計算深度
- `getNextPosition()` - 取得下一個位置
- `ancestors()` - 祖先分類
- `descendants()` - 子孫分類
- `isDescendantOf()` - 判斷是否為子孫
- `isAncestorOf()` - 判斷是否為祖先

### 2. 驗證規則重構
**MaxDepthRule.php**：
- 使用 Repository 而非直接使用 Model
- 透過依賴注入取得 Repository 實例

**NotSelfOrDescendant.php**：
- 使用 Repository 的 `checkCircularReference()` 方法
- 更好的錯誤處理
- 支援新增時的跳過檢查

### 3. Service Provider 設定
**AppServiceProvider.php**：
- 註冊 ProductCategoryObserver

**bootstrap/app.php**：
- 註冊 RepositoryServiceProvider

## ⚡ 功能驗證

### 測試要點
1. **自動處理功能**：
   - ✅ slug 自動生成正常
   - ✅ depth 自動計算正常
   - ✅ position 自動設定正常

2. **驗證規則**：
   - ✅ 循環引用防護正常
   - ✅ 最大深度限制正常

3. **Repository 功能**：
   - ✅ 樹狀結構查詢
   - ✅ 分頁查詢
   - ✅ 搜尋功能
   - ✅ 批次操作

4. **Observer 功能**：
   - ✅ 建立時自動處理
   - ✅ 更新時重新計算
   - ✅ 刪除時級聯處理

## 💡 開發指南

### 使用 Repository
```php
// 在 Controller 中注入
public function __construct(
    protected ProductCategoryRepositoryInterface $categoryRepository
) {}

// 使用方法
$tree = $this->categoryRepository->getTree();
$categories = $this->categoryRepository->paginate(20, ['status' => true]);
$statistics = $this->categoryRepository->getStatistics();
```

### 測試建議
```php
// 測試時可以 Mock Repository
$mockRepository = Mockery::mock(ProductCategoryRepositoryInterface::class);
$this->app->instance(ProductCategoryRepositoryInterface::class, $mockRepository);
```

## 🎯 Phase 2 準備

重構完成後，架構已準備好進行 Phase 2 開發：

### Controller 開發
- 可直接注入 Repository 介面
- 清晰的職責分離
- 易於測試

### Service Layer 開發
- 可在 Repository 之上建立 Service 層
- 處理複雜的業務邏輯
- 整合多個 Repository

### API 端點開發
- 標準化的資料存取方式
- 一致的錯誤處理
- 完整的驗證機制

## 📊 架構比較

| 項目 | 重構前 | 重構後 |
|------|--------|--------|
| Model 職責 | 資料、查詢、事件、驗證 | 僅資料和領域邏輯 |
| 可測試性 | 低（靜態方法） | 高（依賴注入） |
| 耦合度 | 高 | 低 |
| 擴展性 | 困難 | 容易 |
| 維護性 | 困難 | 良好 |

## ✅ 總結

Phase 1 重構成功實現：
- 🏗️ **架構優化**：清晰的職責分離
- 🧪 **可測試性**：支援依賴注入和 Mock
- 🔧 **可維護性**：模組化設計
- 📈 **可擴展性**：遵循 SOLID 原則
- 🛡️ **穩定性**：完整的錯誤處理

為 Phase 2 的 Controller、Service Layer 和 API 開發奠定了堅實的基礎。 