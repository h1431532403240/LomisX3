# 🏗️ LomisX3 專案架構標準手冊

[![版本](https://img.shields.io/badge/版本-v3.0-blue.svg)](https://github.com/your-username/LomisX3)
[![狀態](https://img.shields.io/badge/狀態-生產就緒-success.svg)](https://github.com/your-username/LomisX3)
[![強制執行](https://img.shields.io/badge/強制執行-100%25-red.svg)](https://github.com/your-username/LomisX3)

> **⚠️ 重要提醒**: 本手冊為 LomisX3 專案的絕對標準，所有開發人員**必須**嚴格遵守。  
> 任何偏離此標準的開發行為都**禁止**進行，以避免架構分裂和重複開發。
> 
> **📅 更新日誌**: v3.0 (2025-01-08) - 新增使用者管理模組V6.2企業級參考、Spatie生態系統標準化、軟刪除唯一性約束創新解決方案

## 📋 完整目錄

### 🎯 基礎架構
- [專案概覽與核心原則](#-專案概覽與核心原則)
- [技術棧標準規範](#-技術棧標準規範)
- [項目結構與組織](#-項目結構與組織)

### 🏛️ 系統架構設計
- [整體架構設計模式](#-整體架構設計模式)
- [後端架構標準](#-後端架構標準)
- [前端架構標準](#-前端架構標準)
- [數據流向與通信規範](#-數據流向與通信規範)

### 🔐 權限與安全架構
- [多租戶架構設計](#-多租戶架構設計)
- [權限系統架構](#-權限系統架構)
- [安全防護標準](#-安全防護標準)

### 🗄️ 資料庫設計標準
- [資料庫架構設計](#-資料庫架構設計)
- [表格設計規範](#-表格設計規範)
- [索引與效能優化](#-索引與效能優化)

### 🌐 API 設計標準
- [RESTful API 設計](#-restful-api-設計)
- [API 回應格式](#-api-回應格式)
- [API 文檔與測試](#-api-文檔與測試)

### 🎨 前端開發標準
- [前端架構規範](#-前端架構規範)
- [組件開發標準](#-組件開發標準)
- [狀態管理規範](#-狀態管理規範)
- [型別安全標準](#-型別安全標準)

### 🧪 測試與品質保證
- [測試策略與覆蓋率](#-測試策略與覆蓋率)
- [後端測試標準](#-後端測試標準)
- [前端測試標準](#-前端測試標準)
- [E2E 測試標準](#-e2e-測試標準)

### 📚 已實現模組清單
- [完成模組列表](#-完成模組列表)
- [共用元件庫](#-共用元件庫)
- [開發工具與配置](#-開發工具與配置)

### 🚫 禁止行為與檢查清單
- [絕對禁止行為](#-絕對禁止行為)
- [開發檢查清單](#-開發檢查清單)
- [品質標準監控](#-品質標準監控)

---

## 🎯 專案概覽與核心原則

### 📊 LomisX3 專案概述

LomisX3 是一個企業級管理系統，採用現代化的前後端分離架構，支援多租戶（門市）的數據隔離。系統設計遵循微服務理念，具備高度的可擴展性和維護性。

### 🏗️ 核心架構原則

#### 1. **SOLID 設計原則 (不可妥協)**

- **單一職責原則 (SRP)**: 每個類別、方法、元件只負責一個明確功能
- **開放封閉原則 (OCP)**: 對擴展開放，對修改封閉
- **里氏替換原則 (LSP)**: 子類別必須能夠替換其父類別
- **介面隔離原則 (ISP)**: 不強迫依賴不使用的介面
- **依賴反轉原則 (DIP)**: 依賴抽象而非具體實現

#### 2. **架構層級設計**

```
┌─────────────────────────────────────────────────────────────┐
│                    🌐 Presentation Layer                     │
│          React 19 + TypeScript + shadcn/ui                 │
├─────────────────────────────────────────────────────────────┤
│                     🔌 API Gateway                          │
│                   Laravel Sanctum                          │
├─────────────────────────────────────────────────────────────┤
│                   ⚙️ Business Logic Layer                    │
│                     Service Classes                        │
├─────────────────────────────────────────────────────────────┤
│                   📊 Data Access Layer                      │
│                  Repository Pattern                        │
├─────────────────────────────────────────────────────────────┤
│                   🗄️ Persistence Layer                      │
│                 MySQL 8.0 + Redis 7.0                     │
└─────────────────────────────────────────────────────────────┘
```

#### 3. **嚴格的數據流向**

```
Request → Middleware → Controller → Service → Repository → Model → Database
                          ↓
Response ← Resource ← Service ← Repository ← Model ← Database
```

**絕對禁止的數據流**：
- ❌ Controller 直接操作 Model
- ❌ Component 直接調用 API
- ❌ Service 直接操作資料庫
- ❌ 跨層級的直接依賴

---

## 🛠️ 技術棧標準規範

### ⚙️ 後端技術棧 (不可變更)

| 技術類別 | 指定版本 | 用途說明 | 替代方案 |
|---------|---------|----------|----------|
| **PHP** | >= 8.2 | 主要程式語言 | ❌ 禁止 |
| **Laravel** | >= 12.0 | Web 框架 | ❌ 禁止 |
| **MySQL** | >= 8.0 | 主資料庫 | ✅ PostgreSQL (特殊情況) |
| **Redis** | >= 7.0 | 快取 & Session | ❌ 禁止 |
| **Laravel Sanctum** | >= 4.1 | API 認證 | ❌ 禁止 |
| **Spatie Permission** | >= 6.9 | 權限管理 | ❌ 禁止 |
| **Laravel Scribe** | >= 5.2 | API 文檔 | ❌ 禁止 |
| **Spatie Permission** | >= 6.9 | 權限管理 | ❌ 禁止 |
| **Spatie ActivityLog** | >= 4.8 | 活動記錄 | ❌ 禁止 |
| **Spatie MediaLibrary** | >= 11.7 | 媒體管理 | ❌ 禁止 |
| **Spatie Query Builder** | >= 6.0 | API 查詢建構器 | ❌ 禁止 |
| **PHPUnit/Pest** | >= 11.5 | 測試框架 | ❌ 禁止 |
| **Laravel Pint** | >= 1.13 | 程式碼格式化 | ❌ 禁止 |
| **PHPStan** | >= 1.12 | 靜態分析 | ❌ 禁止 |

### 🎨 前端技術棧 (不可變更)

| 技術類別 | 指定版本 | 用途說明 | 替代方案 |
|---------|---------|----------|----------|
| **React** | >= 19.1 | 前端框架 | ❌ 禁止 |
| **TypeScript** | >= 5.8 | 類型系統 | ❌ 禁止 |
| **Vite** | >= 6.3 | 建置工具 | ❌ 禁止 |
| **shadcn/ui** | Latest | UI 組件庫 (唯一指定) | ❌ 禁止 |
| **Lucide React** | Latest | 圖標庫 (shadcn/ui 配套) | ❌ 禁止 |
| **Tailwind CSS** | >= 3.4 | CSS 框架 | ❌ 禁止 |
| **TanStack Query** | >= 5.80 | 伺服器狀態管理 | ❌ 禁止 |
| **React Hook Form** | >= 7.57 | 表單處理 | ❌ 禁止 |
| **Zod** | >= 3.25 | Schema 驗證 | ❌ 禁止 |
| **React Router** | >= 7.6 | 路由管理 | ❌ 禁止 |
| **openapi-typescript** | >= 7.4 | 型別生成 | ❌ 禁止 |

### 🔧 開發工具標準

| 工具類別 | 指定工具 | 配置檔案 | 強制使用 |
|---------|---------|----------|----------|
| **IDE** | VS Code | `.vscode/settings.json` | ✅ 推薦 |
| **容器化** | Docker + Docker Compose | `docker-compose.yml` | ✅ 必須 |
| **版本控制** | Git | `.gitignore`, `.gitattributes` | ✅ 必須 |
| **CI/CD** | GitHub Actions | `.github/workflows/` | ✅ 必須 |
| **監控** | Prometheus + Grafana | `docker/prometheus/` | ✅ 必須 |
| **追蹤** | OpenTelemetry + Jaeger | `infrastructure/` | ✅ 必須 |

---

## 📁 項目結構與組織

### 🏗️ 完整項目結構

```
LomisX3/
├── back/                            # 後端應用
│   ├── app/
│   │   ├── Http/                    # HTTP 層
│   │   │   ├── Controllers/
│   │   │   │   └── Api/             # API 控制器
│   │   │   ├── Middleware/          # 中介軟體
│   │   │   ├── Requests/            # 請求驗證
│   │   │   └── Resources/           # 資源回應
│   │   ├── Models/                  # Eloquent 模型
│   │   ├── Repositories/            # 資料存取層
│   │   │   └── Contracts/           # Repository 介面
│   │   ├── Services/                # 業務邏輯層
│   │   │   └── Contracts/           # Service 介面
│   │   ├── Observers/               # 模型觀察者
│   │   ├── Policies/                # 權限策略
│   │   ├── Enums/                   # 枚舉定義
│   │   ├── Rules/                   # 自訂驗證規則
│   │   └── Exceptions/              # 自訂例外
│   ├── database/
│   │   ├── migrations/              # 資料庫遷移
│   │   ├── seeders/                 # 資料填充
│   │   └── factories/               # 模型工廠
│   ├── tests/
│   │   ├── Feature/                 # 功能測試
│   │   ├── Unit/                    # 單元測試
│   │   └── Support/                 # 測試輔助
│   ├── config/                      # 配置檔案
│   ├── routes/                      # 路由定義
│   └── storage/                     # 儲存目錄
├── front/                           # 前端應用
│   ├── src/
│   │   ├── components/              # 可重用組件
│   │   │   ├── ui/                  # shadcn/ui 基礎組件
│   │   │   ├── forms/               # 表單相關組件
│   │   │   ├── layout/              # 佈局組件
│   │   │   └── common/              # 通用組件
│   │   ├── pages/                   # 頁面組件
│   │   │   ├── auth/                # 認證頁面
│   │   │   ├── dashboard/           # 儀表板
│   │   │   └── {module-name}/       # 模組頁面
│   │   ├── hooks/                   # 自訂 Hooks
│   │   │   ├── api/                 # API Hooks
│   │   │   ├── auth/                # 認證 Hooks
│   │   │   ├── ui/                  # UI Hooks
│   │   │   └── common/              # 通用 Hooks
│   │   ├── lib/                     # 工具庫
│   │   │   ├── api-client.ts        # API 客戶端
│   │   │   ├── auth.ts              # 認證工具
│   │   │   ├── utils.ts             # 通用工具
│   │   │   └── constants.ts         # 常數定義
│   │   ├── store/                   # 狀態管理
│   │   │   ├── auth-store.ts        # 認證狀態
│   │   │   ├── ui-store.ts          # UI 狀態
│   │   │   └── {module}-store.ts    # 模組狀態
│   │   ├── types/                   # 型別定義
│   │   │   ├── api.ts               # API 型別 (自動生成)
│   │   │   ├── auth.ts              # 認證型別
│   │   │   ├── ui.ts                # UI 型別
│   │   │   └── {module}.ts          # 模組型別
│   │   ├── styles/                  # 樣式檔案
│   │   │   ├── globals.css          # 全域樣式
│   │   │   └── components.css       # 組件樣式
│   │   └── utils/                   # 工具函數
│   ├── public/                      # 靜態資源
│   ├── .env.example                 # 環境變數範例
│   ├── .eslintrc.json               # ESLint 配置
│   ├── .prettierrc                  # Prettier 配置
│   ├── tailwind.config.js           # Tailwind 配置
│   ├── tsconfig.json                # TypeScript 配置
│   ├── vite.config.ts               # Vite 配置
│   └── package.json                 # 依賴管理
├── docs/                            # 項目文檔
│   ├── api/                         # API 文檔
│   ├── deployment/                  # 部署文檔
│   └── development/                 # 開發文檔
├── infrastructure/                  # 基礎設施
│   ├── docker/                      # Docker 配置
│   ├── prometheus/                  # 監控配置
│   └── nginx/                       # 網頁伺服器配置
├── .github/                         # GitHub 配置
│   └── workflows/                   # CI/CD 流程
├── docker-compose.yml               # Docker 編排
├── README.md                        # 專案說明
└── ARCHITECTURE_STANDARDS.md       # 本架構手冊
```

### 📋 命名規範統一標準

#### 🗂️ 檔案與目錄命名

| 類型 | 規範 | 範例 | 說明 |
|------|------|------|------|
| **目錄** | kebab-case | `product-categories/` | 小寫字母 + 連字號 |
| **PHP 檔案** | PascalCase | `ProductCategoryController.php` | 大駝峰 |
| **TypeScript 檔案** | kebab-case | `product-category-list.tsx` | 小寫字母 + 連字號 |
| **組件檔案** | PascalCase | `ProductCategoryList.tsx` | 大駝峰 |
| **Hook 檔案** | kebab-case | `use-product-categories.ts` | use- 前綴 |
| **Type 檔案** | kebab-case | `product-category.types.ts` | .types 後綴 |
| **Test 檔案** | PascalCase | `ProductCategoryTest.php` | Test 後綴 |

#### 🏷️ 類別與介面命名

| 類型 | 命名規則 | 範例 |
|------|----------|------|
| **Controller** | `{ModuleName}Controller` | `ProductCategoryController` |
| **Model** | `{ModuleName}` | `ProductCategory` |
| **Repository Interface** | `{ModuleName}RepositoryInterface` | `ProductCategoryRepositoryInterface` |
| **Repository** | `{ModuleName}Repository` | `ProductCategoryRepository` |
| **Service Interface** | `{ModuleName}ServiceInterface` | `ProductCategoryServiceInterface` |
| **Service** | `{ModuleName}Service` | `ProductCategoryService` |
| **Policy** | `{ModuleName}Policy` | `ProductCategoryPolicy` |
| **Observer** | `{ModuleName}Observer` | `ProductCategoryObserver` |
| **Request** | `{Action}{ModuleName}Request` | `StoreProductCategoryRequest` |
| **Resource** | `{ModuleName}Resource` | `ProductCategoryResource` |
| **Migration** | `create_{table_name}_table` | `create_product_categories_table` |
| **Component** | `{ModuleName}{Action}` | `ProductCategoryList` |
| **Hook** | `use{ModuleName}` | `useProductCategories` |
| **Type Interface** | `{ModuleName}` | `ProductCategory` |

---

## 🏛️ 整體架構設計模式

### 📐 分層架構設計

LomisX3 採用嚴格的分層架構，每一層都有明確的職責和邊界：

#### 1. **表現層 (Presentation Layer)**
- **前端**: React 19 + TypeScript + shadcn/ui
- **職責**: 用戶介面、用戶交互、狀態管理
- **通信**: HTTP API 調用

#### 2. **應用層 (Application Layer)**
- **後端**: Laravel Controllers + Middleware
- **職責**: 路由處理、請求驗證、權限檢查
- **通信**: JSON API 回應

#### 3. **業務邏輯層 (Business Logic Layer)**
- **後端**: Service Classes
- **職責**: 業務規則、流程控制、事務管理
- **通信**: Repository 介面調用

#### 4. **資料存取層 (Data Access Layer)**
- **後端**: Repository Pattern
- **職責**: 資料查詢、數據轉換、快取管理
- **通信**: Eloquent ORM

#### 5. **持久化層 (Persistence Layer)**
- **資料庫**: MySQL 8.0 + Redis 7.0
- **職責**: 資料儲存、數據持久化
- **通信**: SQL 查詢

### 🔄 標準設計模式實現

#### 1. **Repository Pattern (強制實現)**

所有數據存取必須通過 Repository 模式：

```php
<?php

namespace App\Repositories\Contracts;

/**
 * Repository 基礎介面
 * 所有 Repository 必須實現此介面
 */
interface BaseRepositoryInterface
{
    public function find(int $id, array $relations = []): ?Model;
    public function findOrFail(int $id, array $relations = []): Model;
    public function all(array $relations = []): Collection;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function create(array $data): Model;
    public function update(int $id, array $data): Model;
    public function delete(int $id): bool;
    public function createMany(array $data): bool;
    public function updateMany(array $updates): bool;
    public function deleteMany(array $ids): bool;
}
```

#### 2. **Service Layer Pattern (強制實現)**

所有業務邏輯必須通過 Service 層：

```php
<?php

namespace App\Services\Contracts;

/**
 * Service 基礎介面
 * 所有 Service 必須實現此介面
 */
interface BaseServiceInterface
{
    public function getList(array $filters = [], int $perPage = 15);
    public function getDetail(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
}
```

#### 3. **Observer Pattern (強制實現)**

所有模型變更必須通過 Observer 處理：

```php
<?php

namespace App\Observers;

/**
 * Observer 基礎類別
 * 所有 Observer 必須繼承此類別
 */
abstract class BaseObserver
{
    protected function beforeCreate(BaseModel $model): void;
    protected function afterCreate(BaseModel $model): void;
    protected function beforeUpdate(BaseModel $model): void;
    protected function afterUpdate(BaseModel $model): void;
    protected function beforeDelete(BaseModel $model): void;
    protected function afterDelete(BaseModel $model): void;
    protected function clearRelatedCache(BaseModel $model): void;
}
```

### 🎯 依賴注入標準

#### ServiceProvider 註冊模式

每個模組必須建立專用的 ServiceProvider：

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 模組服務提供者
 * 負責註冊模組內的依賴注入綁定
 */
class {ModuleName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository 綁定
        $this->app->bind(
            {ModuleName}RepositoryInterface::class,
            {ModuleName}Repository::class
        );

        // Service 綁定
        $this->app->bind(
            {ModuleName}ServiceInterface::class,
            {ModuleName}Service::class
        );
    }

    public function boot(): void
    {
        // 註冊 Observer
        {ModuleName}::observe({ModuleName}Observer::class);
        
        // 註冊 Policy
        Gate::policy({ModuleName}::class, {ModuleName}Policy::class);
    }
}
```

---

## 🛠️ 後端架構標準

### 🏗️ Laravel 應用架構

#### 0. **核心擴展套件 (Spatie Ecosystem) - 官方標準**

為確保開發效率與業界標準一致性，本專案指定以下 Spatie 套件作為對應功能領域的**唯一官方解決方案**，嚴禁重複造輪子：

| 功能領域 | 指定套件 | 用途說明 | 整合狀態 |
|----------|----------|----------|----------|
| **權限管理** | `spatie/laravel-permission` | 角色與權限系統，已整合門市隔離機制 | ✅ V6.2 完成 |
| **活動日誌** | `spatie/laravel-activitylog` | 使用者操作記錄，已整合門市隔離與隱私保護 | ✅ V6.2 完成 |
| **媒體管理** | `spatie/laravel-medialibrary` | 檔案上傳、轉換、管理（頭像、附件） | ✅ V6.2 完成 |
| **API 查詢** | `spatie/laravel-query-builder` | 複雜的列表篩選、排序和關聯查詢 | ✅ 標準實現 |

**實施原則：**
- **強制使用**：所有涉及上述功能的開發，必須使用指定的 Spatie 套件
- **禁止替代**：不得使用其他同類型套件或自行實現
- **統一配置**：所有 Spatie 套件的配置必須遵循專案統一標準
- **整合擴展**：需要額外功能時，在現有套件基礎上擴展，不得更換

#### 1. **BaseModel 安全設計 (強制繼承)**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 安全模型基類
 * 所有業務模型必須繼承此類別
 */
abstract class BaseModel extends Model
{
    use SoftDeletes;

    /**
     * 不可批量賦值的欄位 (安全防護)
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'store_id',
    ];

    /**
     * 隱藏的欄位 (API 回應中不顯示)
     */
    protected $hidden = [
        'deleted_at',
        'created_by',
        'updated_by',
    ];

    /**
     * 型別轉換
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 全域查詢範圍 - 自動門市過濾
     */
    protected static function booted(): void
    {
        parent::booted();

        // 自動注入門市範圍
        static::addGlobalScope('store', function (Builder $builder) {
            if (auth()->check() && auth()->user()->store_id) {
                $builder->where('store_id', auth()->user()->store_id);
            }
        });
    }

    /**
     * 檢查是否屬於當前使用者的門市
     */
    public function belongsToCurrentStore(): bool
    {
        return auth()->check() && $this->store_id === auth()->user()->store_id;
    }
}
```

#### 2. **Controller 標準實現**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;

/**
 * API 控制器標準實現
 * 所有 API Controller 必須遵循此模式
 */
class {ModuleName}Controller extends BaseController
{
    protected {ModuleName}ServiceInterface $service;

    public function __construct({ModuleName}ServiceInterface $service)
    {
        $this->service = $service;
        
        // 權限中介軟體 (必須包含)
        $this->middleware('auth:sanctum');
        $this->middleware('permission:{module-name}.view')->only(['index', 'show']);
        $this->middleware('permission:{module-name}.create')->only(['store']);
        $this->middleware('permission:{module-name}.update')->only(['update']);
        $this->middleware('permission:{module-name}.delete')->only(['destroy']);
    }

    /**
     * 標準 CRUD 方法 (必須實現)
     */
    public function index(Index{ModuleName}Request $request): JsonResponse
    {
        $this->authorize('viewAny', {ModuleName}::class);
        
        $result = $this->service->getList($request->validated());
        
        return $this->success(
            new {ModuleName}Collection($result),
            '取得{ModuleName}列表成功'
        );
    }

    public function store(Store{ModuleName}Request $request): JsonResponse
    {
        $this->authorize('create', {ModuleName}::class);
        
        $model = $this->service->create($request->validated());
        
        return $this->success(
            new {ModuleName}Resource($model),
            '建立{ModuleName}成功',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $model = $this->service->getDetail($id);
        $this->authorize('view', $model);
        
        return $this->success(
            new {ModuleName}Resource($model),
            '取得{ModuleName}詳情成功'
        );
    }

    public function update(Update{ModuleName}Request $request, int $id): JsonResponse
    {
        $model = $this->service->getDetail($id);
        $this->authorize('update', $model);
        
        $updatedModel = $this->service->update($id, $request->validated());
        
        return $this->success(
            new {ModuleName}Resource($updatedModel),
            '更新{ModuleName}成功'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $model = $this->service->getDetail($id);
        $this->authorize('delete', $model);
        
        $this->service->delete($id);
        
        return $this->success(null, '刪除{ModuleName}成功');
    }
}
```

#### 3. **Service 層實現標準**

```php
<?php

namespace App\Services;

use App\Services\Contracts\BaseServiceInterface;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Service 基礎實現類別
 * 所有 Service 必須繼承此類別
 */
abstract class BaseService implements BaseServiceInterface
{
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 執行事務操作 (所有寫操作必須使用)
     */
    protected function executeInTransaction(callable $operation)
    {
        return DB::transaction(function () use ($operation) {
            try {
                return $operation();
            } catch (\Exception $e) {
                Log::error('Service 操作失敗', [
                    'service' => static::class,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * 業務規則驗證 (子類別可覆寫)
     */
    protected function validateBusinessRules(array $data, ?int $id = null): void
    {
        // 基礎驗證邏輯
    }

    /**
     * 標準建立流程
     */
    public function create(array $data)
    {
        return $this->executeInTransaction(function () use ($data) {
            $this->validateBusinessRules($data);
            $this->beforeCreate($data);
            
            $model = $this->repository->create($data);
            
            $this->afterCreate($model, $data);
            
            return $model;
        });
    }

    // 其他標準方法...
    protected function beforeCreate(array &$data): void {}
    protected function afterCreate($model, array $data): void {}
}
```

### 🛡️ 錯誤處理與例外管理

#### 統一錯誤處理機制

```php
<?php

namespace App\Exceptions;

use Exception;

/**
 * 業務邏輯例外
 * 所有業務錯誤必須使用此例外
 */
class BusinessException extends Exception
{
    protected $errorCode;
    protected $httpStatus;

    public function __construct(
        string $message,
        string $errorCode = 'BUSINESS_ERROR',
        int $httpStatus = 400,
        ?Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
```

```php
<?php

namespace App\Enums;

/**
 * 錯誤代碼枚舉
 * 統一管理所有錯誤代碼
 */
enum ErrorCode: string
{
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case DUPLICATE_ENTRY = 'DUPLICATE_ENTRY';
    case BUSINESS_RULE_VIOLATION = 'BUSINESS_RULE_VIOLATION';
    case EXTERNAL_SERVICE_ERROR = 'EXTERNAL_SERVICE_ERROR';
    
    public function message(): string
    {
        return match($this) {
            self::VALIDATION_FAILED => '資料驗證失敗',
            self::UNAUTHORIZED => '未授權的請求',
            self::FORBIDDEN => '權限不足',
            self::NOT_FOUND => '資源不存在',
            self::DUPLICATE_ENTRY => '資料重複',
            self::BUSINESS_RULE_VIOLATION => '違反業務規則',
            self::EXTERNAL_SERVICE_ERROR => '外部服務錯誤',
        };
    }
}
```

---

## 🔐 多租戶架構設計

### 🏢 門市隔離原則

LomisX3 採用基於門市（Store）的多租戶架構，確保資料完全隔離：

#### 核心隔離機制

1. **資料庫層級隔離**: 所有業務表格包含 `store_id` 欄位
2. **應用層級隔離**: Model 自動注入門市範圍過濾
3. **API 層級隔離**: Controller 自動檢查門市權限
4. **前端層級隔離**: 組件自動過濾門市資料

#### 門市範圍自動注入

```php
<?php

// 在 BaseModel 中自動注入
static::addGlobalScope('store', function (Builder $builder) {
    if (auth()->check() && auth()->user()->store_id) {
        $builder->where('store_id', auth()->user()->store_id);
    }
});
```

### 🔑 權限系統架構

#### 角色權限對應表

```php
<?php

namespace App\Enums;

/**
 * 系統角色枚舉
 * 定義所有可用的使用者角色
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';           // 超級管理員
    case STORE_ADMIN = 'store_admin';           // 門市管理員
    case PRODUCT_MANAGER = 'product_manager';   // 商品管理員
    case ORDER_MANAGER = 'order_manager';       // 訂單管理員
    case CUSTOMER_SERVICE = 'customer_service'; // 客服人員
    case EMPLOYEE = 'employee';                 // 一般員工

    /**
     * 取得角色權限
     */
    public function getPermissions(): array
    {
        return match($this) {
            self::SUPER_ADMIN => [
                'users.*',
                'product-categories.*',
                'products.*',
                'orders.*',
                'stores.*',
                'analytics.*',
            ],
            self::STORE_ADMIN => [
                'users.view', 'users.create', 'users.update',
                'product-categories.*',
                'products.*',
                'orders.*',
                'stores.view', 'stores.update',
                'analytics.*',
            ],
            self::PRODUCT_MANAGER => [
                'product-categories.*',
                'products.*',
                'orders.view',
                'analytics.products',
            ],
            // ... 其他角色權限
        };
    }
}
```

#### Policy 標準實現

```php
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * 權限策略基類
 * 所有模組的 Policy 必須繼承此類別
 */
abstract class BasePolicy
{
    /**
     * 通用的檢視權限檢查
     */
    protected function canView(User $user, string $permission, ?Model $model = null): bool
    {
        if (!$user->can($permission)) {
            return false;
        }

        if ($model && !$this->belongsToUserStore($user, $model)) {
            return false;
        }

        return true;
    }

    /**
     * 檢查資源是否屬於使用者門市
     */
    protected function belongsToUserStore(User $user, Model $model): bool
    {
        if (!$model->hasAttribute('store_id')) {
            return true;
        }

        return $user->store_id === $model->store_id;
    }

    /**
     * 檢查使用者是否為超級管理員
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
```

---

## 🗄️ 資料庫架構設計

### 📋 表格設計規範

#### 基礎欄位標準

所有業務表格必須包含的基礎欄位：

```sql
-- 標準業務表格結構
CREATE TABLE {table_name} (
    -- 主鍵 (統一使用 BIGINT UNSIGNED)
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- 門市隔離欄位 (必須)
    store_id BIGINT UNSIGNED NOT NULL,
    
    -- 業務欄位 (根據需求設計)
    name VARCHAR(255) NOT NULL COMMENT '名稱',
    slug VARCHAR(255) NOT NULL COMMENT 'URL 友善識別碼',
    description TEXT COMMENT '描述',
    status BOOLEAN DEFAULT TRUE COMMENT '狀態 (啟用/停用)',
    position INTEGER DEFAULT 0 COMMENT '排序位置',
    
    -- 審計欄位 (推薦包含)
    created_by BIGINT UNSIGNED COMMENT '建立者 ID',
    updated_by BIGINT UNSIGNED COMMENT '更新者 ID',
    
    -- 時間戳記 (必須)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    deleted_at TIMESTAMP NULL COMMENT '軟刪除時間',
    
    -- 索引設計
    INDEX idx_store_status (store_id, status),
    INDEX idx_store_position (store_id, position),
    INDEX idx_slug (slug),
    UNIQUE KEY uk_store_slug (store_id, slug),
    
    -- 外鍵約束
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='業務表格';
```

#### 階層式結構設計

```sql
-- 階層式資料結構 (如商品分類)
CREATE TABLE hierarchical_table (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    
    -- 階層欄位
    parent_id BIGINT UNSIGNED NULL COMMENT '父節點 ID',
    path VARCHAR(500) NOT NULL DEFAULT '/' COMMENT '節點路徑',
    depth TINYINT UNSIGNED DEFAULT 0 COMMENT '節點深度',
    position INTEGER DEFAULT 0 COMMENT '同層排序',
    
    -- 業務欄位
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    status BOOLEAN DEFAULT TRUE,
    
    -- 統計欄位 (可選)
    children_count INTEGER DEFAULT 0 COMMENT '直接子節點數量',
    descendants_count INTEGER DEFAULT 0 COMMENT '所有後代節點數量',
    
    -- 基礎時間戳記
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- 階層式索引設計
    INDEX idx_store_parent (store_id, parent_id),
    INDEX idx_store_depth (store_id, depth),
    INDEX idx_store_position (store_id, parent_id, position),
    INDEX idx_path (path),
    UNIQUE KEY uk_store_slug (store_id, slug),
    
    -- 外鍵約束
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES hierarchical_table(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 📊 索引設計標準

#### 索引設計原則

1. **主鍵索引**: 統一使用 `BIGINT UNSIGNED AUTO_INCREMENT`
2. **門市索引**: 所有查詢都會帶 `store_id`，必須建立相關複合索引
3. **狀態索引**: `(store_id, status)` 複合索引，支援狀態篩選
4. **排序索引**: `(store_id, position)` 或 `(store_id, created_at)` 支援排序
5. **搜尋索引**: 全文搜尋欄位使用 `FULLTEXT` 索引
6. **唯一索引**: 業務唯一欄位使用 `(store_id, slug)` 複合唯一索引

#### 索引命名規範

```sql
-- 索引命名規範
INDEX idx_{table}_{column}                    -- 單欄位索引
INDEX idx_{table}_{column1}_{column2}         -- 複合索引
UNIQUE KEY uk_{table}_{column}                -- 唯一索引
FOREIGN KEY fk_{table}_{ref_table}            -- 外鍵約束
FULLTEXT KEY ft_{table}_{column}              -- 全文索引
```

### 💡 軟刪除唯一性約束解決方案 (V6.2 創新模式)

**問題描述：**
傳統軟刪除實現中，當使用者被軟刪除後，其 `username` 或 `email` 等唯一欄位無法被新使用者重新註冊，這限制了資源的重複利用。

**V6.2 創新解決方案：MySQL 計算欄位 (Generated Column)**

此方案在資料庫層級完美解決軟刪除與唯一性約束的衝突，已成為專案標準模式：

```sql
-- V6.2 軟刪除唯一性約束標準實現
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id BIGINT UNSIGNED NOT NULL,
    
    -- 原始業務欄位
    username VARCHAR(255) NOT NULL COMMENT '使用者名稱',
    email VARCHAR(255) NOT NULL COMMENT '電子郵件',
    
    -- V6.2 創新：計算欄位實現軟刪除唯一性
    username_active VARCHAR(255) GENERATED ALWAYS AS (
        IF(deleted_at IS NULL, 
           username, 
           CONCAT(username, '_deleted_', UNIX_TIMESTAMP(deleted_at))
        )
    ) STORED COMMENT '軟刪除安全的使用者名稱',
    
    email_active VARCHAR(255) GENERATED ALWAYS AS (
        IF(deleted_at IS NULL, 
           email, 
           CONCAT(email, '_deleted_', UNIX_TIMESTAMP(deleted_at))
        )
    ) STORED COMMENT '軟刪除安全的電子郵件',
    
    -- 其他欄位...
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- V6.2 創新：對計算欄位建立唯一索引
    UNIQUE KEY uk_user_username_active (store_id, username_active),
    UNIQUE KEY uk_user_email_active (store_id, email_active),
    
    -- 傳統索引保留
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_store_status (store_id, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Migration 實現範例：**

```php
<?php
// V6.2 軟刪除唯一性約束 Migration 標準實現

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('主鍵 ID');
            $table->foreignId('store_id')->constrained()->comment('門市 ID');
            
            // 原始業務欄位
            $table->string('username')->comment('使用者名稱');
            $table->string('email')->comment('電子郵件');
            
            // 其他欄位...
            $table->timestamps();
            $table->softDeletes();
            
            // 傳統索引
            $table->index('username');
            $table->index('email');
        });
        
        // V6.2 創新：使用 raw SQL 添加計算欄位和唯一索引
        DB::statement("
            ALTER TABLE users 
            ADD COLUMN username_active VARCHAR(255) 
            GENERATED ALWAYS AS (
                IF(deleted_at IS NULL, 
                   username, 
                   CONCAT(username, '_deleted_', UNIX_TIMESTAMP(deleted_at))
                )
            ) STORED COMMENT '軟刪除安全的使用者名稱'
        ");
        
        DB::statement("
            ALTER TABLE users 
            ADD COLUMN email_active VARCHAR(255) 
            GENERATED ALWAYS AS (
                IF(deleted_at IS NULL, 
                   email, 
                   CONCAT(email, '_deleted_', UNIX_TIMESTAMP(deleted_at))
                )
            ) STORED COMMENT '軟刪除安全的電子郵件'
        ");
        
        // 對計算欄位建立唯一索引
        DB::statement("
            ALTER TABLE users 
            ADD UNIQUE INDEX uk_user_username_active (store_id, username_active)
        ");
        
        DB::statement("
            ALTER TABLE users 
            ADD UNIQUE INDEX uk_user_email_active (store_id, email_active)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

**技術優勢：**
- ✅ **100% 資料庫層面解決方案**：應用程式完全無感知，無需額外邏輯
- ✅ **高效能**：計算欄位儲存為 STORED，查詢速度與一般欄位相同
- ✅ **自動維護**：資料庫自動維護計算欄位值，無需手動更新
- ✅ **向後相容**：不影響現有業務邏輯和查詢
- ✅ **資源重用**：軟刪除後，相同用戶名/郵箱可立即重新註冊

**適用場景：**
此模式適用於所有需要「軟刪除 + 唯一約束」的欄位，如：
- 使用者管理：`username`, `email`, `phone`
- 商品管理：`sku`, `barcode`
- 門市管理：`code`, `tax_number`

**標準化要求：**
所有涉及軟刪除唯一性約束的新模組，**必須**採用此 V6.2 模式，禁止使用其他解決方案。

### 🔄 Migration 標準

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 標準實現
 * 所有 Migration 必須遵循此格式
 */
return new class extends Migration
{
    /**
     * 執行 Migration
     */
    public function up(): void
    {
        Schema::create('{table_name}', function (Blueprint $table) {
            // 主鍵
            $table->id()->comment('主鍵 ID');
            
            // 門市隔離 (必須)
            $table->foreignId('store_id')
                  ->constrained('stores')
                  ->onDelete('cascade')
                  ->comment('門市 ID');
            
            // 業務欄位
            $table->string('name')->comment('名稱');
            $table->string('slug')->comment('URL 識別碼');
            $table->text('description')->nullable()->comment('描述');
            $table->boolean('status')->default(true)->comment('狀態');
            $table->integer('position')->default(0)->comment('排序位置');
            
            // 審計欄位
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('建立者 ID');
                  
            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('更新者 ID');
            
            // 時間戳記
            $table->timestamps();
            $table->softDeletes();
            
            // 索引設計
            $table->index(['store_id', 'status'], 'idx_store_status');
            $table->index(['store_id', 'position'], 'idx_store_position');
            $table->index('slug', 'idx_slug');
            $table->unique(['store_id', 'slug'], 'uk_store_slug');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('{table_name}');
    }
};
```

---

## 🌐 RESTful API 設計

### 📋 API 設計原則

#### 資源命名規範

| HTTP Method | URI Pattern | 控制器方法 | 用途說明 |
|-------------|-------------|------------|----------|
| `GET` | `/api/{resources}` | `index()` | 取得資源列表 |
| `POST` | `/api/{resources}` | `store()` | 建立新資源 |
| `GET` | `/api/{resources}/{id}` | `show()` | 取得單一資源 |
| `PUT/PATCH` | `/api/{resources}/{id}` | `update()` | 更新資源 |
| `DELETE` | `/api/{resources}/{id}` | `destroy()` | 刪除資源 |

#### 自訂端點規範

```php
// 統計資訊
GET    /api/{resources}/statistics

// 樹狀結構  
GET    /api/{resources}/tree

// 批次操作
PATCH  /api/{resources}/batch-update
DELETE /api/{resources}/batch-delete

// 關聯資源
GET    /api/{resources}/{id}/children
GET    /api/{resources}/{id}/ancestors

// 特殊功能
POST   /api/{resources}/import
GET    /api/{resources}/export
POST   /api/{resources}/{id}/duplicate
```

### 📊 API 回應格式

#### 成功回應格式

```json
{
  "success": true,
  "message": "操作成功",
  "data": {
    // 實際資料內容
  },
  "meta": {
    // 元資料 (分頁、統計等)
  },
  "timestamp": "2025-01-07T10:00:00.000000Z"
}
```

#### 錯誤回應格式

```json
{
  "success": false,
  "message": "操作失敗的說明",
  "error_code": "BUSINESS_ERROR_CODE",
  "errors": {
    // 詳細錯誤資訊 (驗證錯誤等)
  },
  "timestamp": "2025-01-07T10:00:00.000000Z"
}
```

#### 分頁回應格式

```json
{
  "success": true,
  "message": "取得資料成功",
  "data": [
    // 資料陣列
  ],
  "links": {
    "first": "http://example.com/api/resources?page=1",
    "last": "http://example.com/api/resources?page=10",
    "prev": null,
    "next": "http://example.com/api/resources?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 20,
    "to": 20,
    "total": 200
  },
  "timestamp": "2025-01-07T10:00:00.000000Z"
}
```

### 📝 Request 驗證標準

```php
<?php

namespace App\Http\Requests\{ModuleName};

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request 驗證標準實現
 * 所有 Request 必須遵循此格式
 */
class Store{ModuleName}Request extends FormRequest
{
    /**
     * 授權檢查
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * 驗證規則
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string', 
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                'unique:table_name,slug,NULL,id,store_id,' . auth()->user()->store_id
            ],
            'description' => ['nullable', 'string', 'max:65535'],
            'status' => ['boolean'],
            'position' => ['integer', 'min:0'],
        ];
    }

    /**
     * 自訂錯誤訊息
     */
    public function messages(): array
    {
        return [
            'name.required' => '名稱為必填欄位',
            'name.max' => '名稱不能超過 255 個字元',
            'slug.regex' => 'URL 識別碼只能包含小寫字母、數字和連字號',
            'slug.unique' => '此 URL 識別碼已被使用',
        ];
    }

    /**
     * 資料預處理
     */
    protected function prepareForValidation(): void
    {
        // 自動生成 slug
        if (!$this->slug && $this->name) {
            $this->merge([
                'slug' => Str::slug($this->name)
            ]);
        }

        // 自動填充門市 ID
        $this->merge([
            'store_id' => auth()->user()->store_id
        ]);
    }
}
```

--- 

## 🎨 前端架構標準

### 🎨 UI 組件設計標準 (shadcn/ui 生態系統)

本專案**唯一指定**使用 `shadcn/ui` 作為 UI 組件庫，配合 `Tailwind CSS` 構建一致的使用者介面。任何偏離此標準的 UI 實現都將被視為架構違規。

#### **核心 UI 技術棧 (不可替代)**

| 組件類型 | 指定解決方案 | 說明 | 替代方案 |
|----------|-------------|------|----------|
| **基礎組件** | `shadcn/ui` | 唯一指定的 UI 組件庫 | ❌ 禁止 |
| **圖標系統** | `lucide-react` | shadcn/ui 官方配套圖標庫 | ❌ 禁止 |
| **樣式框架** | `Tailwind CSS` | 與 shadcn/ui 深度整合 | ❌ 禁止 |
| **通知系統** | `shadcn/ui useToast` | 內建的 Toast Hook | ❌ 禁止 react-hot-toast |
| **對話框** | `shadcn/ui Dialog` | 模態框和對話框解決方案 | ❌ 禁止 @headlessui/react |
| **表單組件** | `shadcn/ui Form + React Hook Form` | 表單處理標準組合 | ❌ 禁止其他表單庫 |

#### **具體實踐規範**

##### 1. **圖標使用標準**
```typescript
// ✅ 正確：統一使用 lucide-react
import { Search, Plus, Edit, Trash2, ChevronDown } from 'lucide-react';

// ❌ 禁止：使用其他圖標庫
import { SearchIcon } from '@heroicons/react/24/outline'; // 禁止
import { FaSearch } from 'react-icons/fa'; // 禁止
```

##### 2. **通知/Toast 使用標準**
```typescript
// ✅ 正確：使用 shadcn/ui 內建 useToast
import { useToast } from '@/components/ui/use-toast';

const Component = () => {
  const { toast } = useToast();
  
  const handleSuccess = () => {
    toast({
      title: "操作成功",
      description: "商品分類已成功建立",
      variant: "default",
    });
  };
  
  const handleError = () => {
    toast({
      title: "操作失敗",
      description: "請檢查輸入資料",
      variant: "destructive",
    });
  };
};

// ❌ 禁止：使用其他 toast 套件
import toast from 'react-hot-toast'; // 禁止
```

##### 3. **對話框/模態窗使用標準**
```typescript
// ✅ 正確：使用 shadcn/ui Dialog
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

const ConfirmDialog = () => (
  <Dialog>
    <DialogTrigger asChild>
      <Button variant="destructive">刪除</Button>
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle>確認刪除</DialogTitle>
        <DialogDescription>
          此操作無法復原，確定要刪除這個項目嗎？
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button variant="outline">取消</Button>
        <Button variant="destructive">確認刪除</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
);

// ❌ 禁止：使用其他對話框解決方案
import { Dialog } from '@headlessui/react'; // 禁止
```

##### 4. **表單組件使用標準**
```typescript
// ✅ 正確：shadcn/ui Form + React Hook Form + Zod
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';

const formSchema = z.object({
  name: z.string().min(2, '名稱至少需要 2 個字元'),
  email: z.string().email('請輸入有效的電子郵件地址'),
});

const UserForm = () => {
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
  });

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)}>
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>名稱</FormLabel>
              <FormControl>
                <Input placeholder="請輸入名稱" {...field} />
              </FormControl>
              <FormDescription>
                這是您的公開顯示名稱
              </FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button type="submit">提交</Button>
      </form>
    </Form>
  );
};
```

##### 5. **深色模式支援標準**
```typescript
// ✅ 正確：使用 Tailwind CSS 深色模式變數
const Card = () => (
  <div className="bg-background text-foreground border-border rounded-lg p-6">
    <h3 className="text-foreground font-semibold">標題</h3>
    <p className="text-muted-foreground">描述文字</p>
  </div>
);

// ❌ 禁止：硬編碼顏色值
const Card = () => (
  <div className="bg-white dark:bg-gray-900 text-black dark:text-white">
    // 禁止硬編碼
  </div>
);
```

#### **組件擴展規範**

當需要客製化 shadcn/ui 組件時，必須遵循以下擴展模式：

```typescript
// ✅ 正確：基於 shadcn/ui 組件擴展
import { Button, ButtonProps } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface LoadingButtonProps extends ButtonProps {
  loading?: boolean;
}

const LoadingButton = ({ loading, children, className, ...props }: LoadingButtonProps) => (
  <Button
    className={cn(className)}
    disabled={loading || props.disabled}
    {...props}
  >
    {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
    {children}
  </Button>
);

// ❌ 禁止：重新實現按鈕組件
const CustomButton = ({ children, onClick }) => (
  <button className="custom-btn" onClick={onClick}>
    {children}
  </button>
); // 禁止
```

### 🏗️ React 應用架構

#### 1. **組件層級架構**

```
┌─────────────────────────────────────────────────────────────┐
│                      📱 Pages Layer                         │
│                    (路由頁面組件)                             │
├─────────────────────────────────────────────────────────────┤
│                   🏗️ Layout Layer                           │
│                   (佈局容器組件)                              │
├─────────────────────────────────────────────────────────────┤
│                  🎨 Feature Layer                           │
│                (業務功能組件)                                 │
├─────────────────────────────────────────────────────────────┤
│                   🧱 UI Layer                               │
│                 (基礎 UI 組件)                               │
├─────────────────────────────────────────────────────────────┤
│                  🪝 Hooks Layer                             │
│               (邏輯處理 Hooks)                               │
├─────────────────────────────────────────────────────────────┤
│                  🔧 Utils Layer                             │
│                (工具函數)                                    │
└─────────────────────────────────────────────────────────────┘
```

#### 2. **組件設計標準**

```typescript
// components/ui/button.tsx - 基礎 UI 組件標準
import { forwardRef } from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

/**
 * Button 變體配置
 * 統一的按鈕樣式變體定義
 */
const buttonVariants = cva(
  'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
  {
    variants: {
      variant: {
        default: 'bg-primary text-primary-foreground hover:bg-primary/90',
        destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
        outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        ghost: 'hover:bg-accent hover:text-accent-foreground',
        link: 'text-primary underline-offset-4 hover:underline',
      },
      size: {
        default: 'h-10 px-4 py-2',
        sm: 'h-9 rounded-md px-3',
        lg: 'h-11 rounded-md px-8',
        icon: 'h-10 w-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
);

/**
 * Button 組件 Props 介面
 */
export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean;
}

/**
 * Button 基礎組件
 * 所有按鈕相關組件必須基於此組件建構
 */
const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';
    
    return (
      <Comp
        className={cn(buttonVariants({ variant, size, className }))}
        ref={ref}
        {...props}
      />
    );
  }
);

Button.displayName = 'Button';

export { Button, buttonVariants };
```

#### 3. **業務組件標準**

```typescript
// pages/product-categories/components/product-category-list.tsx
import { useState } from 'react';
import { useProductCategories } from '@/hooks/api/use-product-categories';
import { DataTable } from '@/components/common/data-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import type { ColumnDef } from '@tanstack/react-table';
import type { ProductCategory } from '@/types/api';

/**
 * 商品分類列表組件
 * 負責顯示商品分類的表格列表
 */
export function ProductCategoryList() {
  const [filters, setFilters] = useState({
    search: '',
    status: null as boolean | null,
    per_page: 20
  });

  const {
    data,
    isLoading,
    isError,
    refetch
  } = useProductCategories(filters);

  /**
   * 表格欄位定義
   */
  const columns: ColumnDef<ProductCategory>[] = [
    {
      accessorKey: 'name',
      header: '名稱',
      cell: ({ row }) => (
        <div className="font-medium">
          {row.original.name}
        </div>
      ),
    },
    {
      accessorKey: 'slug',
      header: 'URL 識別碼',
      cell: ({ row }) => (
        <code className="text-sm bg-muted px-2 py-1 rounded">
          {row.original.slug}
        </code>
      ),
    },
    {
      accessorKey: 'status',
      header: '狀態',
      cell: ({ row }) => (
        <Badge variant={row.original.status ? 'default' : 'secondary'}>
          {row.original.status ? '啟用' : '停用'}
        </Badge>
      ),
    },
    {
      accessorKey: 'position',
      header: '排序',
      cell: ({ row }) => row.original.position,
    },
    {
      id: 'actions',
      header: '操作',
      cell: ({ row }) => (
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => handleEdit(row.original.id)}
          >
            編輯
          </Button>
          <Button
            variant="destructive"
            size="sm"
            onClick={() => handleDelete(row.original.id)}
          >
            刪除
          </Button>
        </div>
      ),
    },
  ];

  /**
   * 編輯處理函數
   */
  const handleEdit = (id: number) => {
    // 導航到編輯頁面或開啟編輯模態框
  };

  /**
   * 刪除處理函數
   */
  const handleDelete = (id: number) => {
    // 顯示確認對話框並執行刪除
  };

  /**
   * 篩選器變更處理
   */
  const handleFilterChange = (key: string, value: any) => {
    setFilters(prev => ({
      ...prev,
      [key]: value
    }));
  };

  if (isError) {
    return (
      <div className="text-center py-8">
        <p className="text-destructive">載入資料時發生錯誤</p>
        <Button variant="outline" onClick={() => refetch()}>
          重試
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* 篩選器 */}
      <div className="flex gap-4">
        <Input
          placeholder="搜尋分類名稱..."
          value={filters.search}
          onChange={(e) => handleFilterChange('search', e.target.value)}
          className="max-w-sm"
        />
        
        <Button variant="outline" onClick={() => setFilters({ search: '', status: null, per_page: 20 })}>
          重置篩選
        </Button>
      </div>

      {/* 資料表格 */}
      <DataTable
        columns={columns}
        data={data?.data || []}
        loading={isLoading}
        pagination={data?.meta}
        onPaginationChange={(page, perPage) => {
          setFilters(prev => ({ ...prev, page, per_page: perPage }));
        }}
      />
    </div>
  );
}
```

### 🔒 TypeScript 型別安全

#### 自動 API 型別生成

```typescript
// scripts/generate-api-types.ts
import { generateApi } from 'swagger-typescript-api';
import fs from 'fs';
import path from 'path';

/**
 * 自動生成 API 型別
 * 從後端 OpenAPI 規格自動生成前端型別定義
 */
async function generateApiTypes() {
  try {
    const { files } = await generateApi({
      name: 'api.ts',
      url: 'http://localhost:8000/docs/openapi.json',
      httpClientType: 'fetch',
      generateClient: false,
      generateRouteTypes: true,
      extractRequestParams: true,
      extractRequestBody: true,
      extractResponseBody: true,
      modular: false,
    });

    const outputPath = path.join(process.cwd(), 'src/types');
    
    if (!fs.existsSync(outputPath)) {
      fs.mkdirSync(outputPath, { recursive: true });
    }

    files.forEach(({ content, name }) => {
      fs.writeFileSync(path.join(outputPath, name), content);
    });

    console.log('✅ API 型別生成完成');
  } catch (error) {
    console.error('❌ API 型別生成失敗:', error);
    process.exit(1);
  }
}

generateApiTypes();
```

#### API 客戶端型別安全

```typescript
// lib/api-client.ts
import createClient from 'openapi-fetch';
import type { paths } from '@/types/api';

/**
 * 型別安全的 API 客戶端
 * 100% 型別安全，自動同步後端 API 變更
 */
const apiClient = createClient<paths>({
  baseUrl: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

/**
 * 請求攔截器 - 自動添加認證 Token
 */
apiClient.use({
  onRequest({ request }) {
    const token = localStorage.getItem('auth_token');
    if (token) {
      request.headers.set('Authorization', `Bearer ${token}`);
    }
    return request;
  },
  onResponse({ response }) {
    // 處理 401 未授權錯誤
    if (response.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return response;
  },
});

export default apiClient;
```

### 🔄 狀態管理規範

#### Zustand Store 設計

```typescript
// store/auth-store.ts
import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import apiClient from '@/lib/api-client';
import type { User } from '@/types/api';

/**
 * 認證狀態介面
 */
interface AuthState {
  // 狀態
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;

  // Actions
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  refreshUser: () => Promise<void>;
  updateUser: (userData: Partial<User>) => void;
}

/**
 * 認證狀態管理 Store
 * 處理使用者登入、登出、資料更新等認證相關邏輯
 */
export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      // 初始狀態
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,

      // 登入函數
      login: async (email: string, password: string) => {
        set({ isLoading: true });
        
        try {
          const { data, error } = await apiClient.POST('/api/auth/login', {
            body: { email, password }
          });

          if (error) {
            throw new Error('登入失敗');
          }

          set({
            user: data.user,
            token: data.token,
            isAuthenticated: true,
            isLoading: false
          });

          localStorage.setItem('auth_token', data.token);
        } catch (error) {
          set({ isLoading: false });
          throw error;
        }
      },

      // 登出函數
      logout: () => {
        set({
          user: null,
          token: null,
          isAuthenticated: false,
          isLoading: false
        });
        
        localStorage.removeItem('auth_token');
      },

      // 重新整理使用者資訊
      refreshUser: async () => {
        const token = localStorage.getItem('auth_token');
        if (!token) return;

        try {
          const { data, error } = await apiClient.GET('/api/auth/me');

          if (error) {
            get().logout();
            return;
          }

          set({
            user: data,
            token,
            isAuthenticated: true
          });
        } catch (error) {
          get().logout();
        }
      },

      // 更新使用者資料
      updateUser: (userData) => {
        set(state => ({
          user: state.user ? { ...state.user, ...userData } : null
        }));
      }
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated
      })
    }
  )
);
```

#### TanStack Query 整合

```typescript
// hooks/api/use-product-categories.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useAuthStore } from '@/store/auth-store';
import apiClient from '@/lib/api-client';
import type { ProductCategory } from '@/types/api';

/**
 * 商品分類 API Hooks
 * 提供完整的 CRUD 操作和快取管理
 */

/**
 * 查詢商品分類列表
 */
export function useProductCategories(params?: {
  search?: string;
  status?: boolean;
  per_page?: number;
  page?: number;
}) {
  return useQuery({
    queryKey: ['product-categories', params],
    queryFn: async () => {
      const { data, error } = await apiClient.GET('/api/product-categories', {
        params: { query: params }
      });
      
      if (error) {
        throw new Error('取得商品分類列表失敗');
      }
      
      return data;
    },
    staleTime: 5 * 60 * 1000, // 5 分鐘快取
  });
}

/**
 * 查詢單一商品分類
 */
export function useProductCategory(id: number) {
  return useQuery({
    queryKey: ['product-categories', id],
    queryFn: async () => {
      const { data, error } = await apiClient.GET('/api/product-categories/{id}', {
        params: { path: { id } }
      });
      
      if (error) {
        throw new Error('取得商品分類失敗');
      }
      
      return data;
    },
    enabled: !!id,
  });
}

/**
 * 建立商品分類
 */
export function useCreateProductCategory() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Omit<ProductCategory, 'id' | 'created_at' | 'updated_at'>) => {
      const { data: result, error } = await apiClient.POST('/api/product-categories', {
        body: data
      });
      
      if (error) {
        throw new Error('建立商品分類失敗');
      }
      
      return result;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['product-categories'] });
    },
  });
}

/**
 * 更新商品分類
 */
export function useUpdateProductCategory() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, data }: { id: number; data: Partial<ProductCategory> }) => {
      const { data: result, error } = await apiClient.PUT('/api/product-categories/{id}', {
        params: { path: { id } },
        body: data
      });
      
      if (error) {
        throw new Error('更新商品分類失敗');
      }
      
      return result;
    },
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['product-categories'] });
      queryClient.invalidateQueries({ queryKey: ['product-categories', id] });
    },
  });
}

/**
 * 刪除商品分類
 */
export function useDeleteProductCategory() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      const { error } = await apiClient.DELETE('/api/product-categories/{id}', {
        params: { path: { id } }
      });
      
      if (error) {
        throw new Error('刪除商品分類失敗');
      }
      
      return true;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['product-categories'] });
    },
  });
}
```

---

## 🧪 測試標準

### 📊 測試金字塔策略

```
     5% E2E 測試
    ┌─────────────┐
   │  E2E Tests   │  ← Playwright (關鍵使用者流程)
  ┌─────────────────┐
 │ Integration Test │ ← React Testing Library + API 整合
┌─────────────────────┐
│    Unit Tests      │ ← Jest + Vitest (業務邏輯)
└─────────────────────┘
        70%
```

### 🔧 後端測試標準

#### Pest 測試框架

```php
<?php

// tests/Feature/ProductCategoryTest.php

use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Store;

/**
 * 商品分類功能測試
 * 測試完整的 CRUD 操作和業務邏輯
 */
describe('ProductCategory API', function () {
    beforeEach(function () {
        $this->store = Store::factory()->create();
        $this->user = User::factory()->create(['store_id' => $this->store->id]);
        $this->actingAs($this->user, 'sanctum');
    });

    describe('GET /api/product-categories', function () {
        it('應該返回分頁的商品分類列表', function () {
            // 準備測試資料
            ProductCategory::factory(5)->create(['store_id' => $this->store->id]);
            ProductCategory::factory(3)->create(); // 其他門市的資料
            
            // 執行 API 請求
            $response = $this->getJson('/api/product-categories');
            
            // 驗證回應
            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'description',
                            'status',
                            'position',
                            'store_id',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ]
                ]);
            
            // 驗證只返回當前門市的資料
            expect($response->json('data'))->toHaveCount(5);
            expect($response->json('meta.total'))->toBe(5);
        });

        it('應該支援搜尋篩選', function () {
            ProductCategory::factory()->create([
                'name' => '電子產品',
                'store_id' => $this->store->id
            ]);
            ProductCategory::factory()->create([
                'name' => '家具用品',
                'store_id' => $this->store->id
            ]);
            
            $response = $this->getJson('/api/product-categories?search=電子');
            
            $response->assertOk();
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.name'))->toBe('電子產品');
        });
    });

    describe('POST /api/product-categories', function () {
        it('應該能建立新的商品分類', function () {
            $data = [
                'name' => '新分類',
                'description' => '測試分類描述',
                'status' => true,
                'position' => 10
            ];
            
            $response = $this->postJson('/api/product-categories', $data);
            
            $response->assertCreated()
                ->assertJsonFragment([
                    'name' => '新分類',
                    'slug' => 'new-category',
                    'store_id' => $this->store->id
                ]);
            
            $this->assertDatabaseHas('product_categories', [
                'name' => '新分類',
                'store_id' => $this->store->id
            ]);
        });

        it('應該驗證必填欄位', function () {
            $response = $this->postJson('/api/product-categories', []);
            
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        });

        it('應該確保 slug 在門市內唯一', function () {
            ProductCategory::factory()->create([
                'slug' => 'existing-category',
                'store_id' => $this->store->id
            ]);
            
            $response = $this->postJson('/api/product-categories', [
                'name' => '現有分類',
                'slug' => 'existing-category'
            ]);
            
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['slug']);
        });
    });
});
```

#### Repository 單元測試

```php
<?php

// tests/Unit/Repositories/ProductCategoryRepositoryTest.php

use App\Models\ProductCategory;
use App\Models\Store;
use App\Repositories\ProductCategoryRepository;

/**
 * ProductCategoryRepository 單元測試
 */
describe('ProductCategoryRepository', function () {
    beforeEach(function () {
        $this->repository = new ProductCategoryRepository();
        $this->store = Store::factory()->create();
    });

    describe('findBySlug', function () {
        it('應該根據 slug 找到商品分類', function () {
            $category = ProductCategory::factory()->create([
                'slug' => 'test-category',
                'store_id' => $this->store->id
            ]);
            
            $result = $this->repository->findBySlug('test-category', $this->store->id);
            
            expect($result)->not->toBeNull();
            expect($result->id)->toBe($category->id);
        });

        it('應該在找不到時返回 null', function () {
            $result = $this->repository->findBySlug('non-existent', $this->store->id);
            
            expect($result)->toBeNull();
        });
    });

    describe('getTree', function () {
        it('應該返回階層式樹狀結構', function () {
            $parent = ProductCategory::factory()->create([
                'name' => '父分類',
                'store_id' => $this->store->id
            ]);
            
            $child1 = ProductCategory::factory()->create([
                'name' => '子分類1',
                'parent_id' => $parent->id,
                'store_id' => $this->store->id
            ]);
            
            $child2 = ProductCategory::factory()->create([
                'name' => '子分類2',
                'parent_id' => $parent->id,
                'store_id' => $this->store->id
            ]);
            
            $tree = $this->repository->getTree($this->store->id);
            
            expect($tree)->toHaveCount(1);
            expect($tree[0]->children)->toHaveCount(2);
            expect($tree[0]->children[0]->name)->toBe('子分類1');
        });
    });
});
```

### 🎭 前端測試標準

#### React Testing Library 組件測試

```typescript
// __tests__/components/product-category-list.test.tsx
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ProductCategoryList } from '@/pages/product-categories/components/product-category-list';
import { useProductCategories } from '@/hooks/api/use-product-categories';

// Mock API Hook
jest.mock('@/hooks/api/use-product-categories');
const mockUseProductCategories = useProductCategories as jest.MockedFunction<typeof useProductCategories>;

/**
 * 商品分類列表組件測試
 */
describe('ProductCategoryList', function () {
  let queryClient: QueryClient;

  beforeEach(() => {
    queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false },
        mutations: { retry: false },
      },
    });
  });

  const renderWithProviders = (component: React.ReactElement) => {
    return render(
      <QueryClientProvider client={queryClient}>
        {component}
      </QueryClientProvider>
    );
  };

  it('應該顯示載入狀態', () => {
    mockUseProductCategories.mockReturnValue({
      data: undefined,
      isLoading: true,
      isError: false,
      refetch: jest.fn(),
    } as any);

    renderWithProviders(<ProductCategoryList />);

    expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();
  });

  it('應該顯示商品分類列表', () => {
    const mockData = {
      data: [
        {
          id: 1,
          name: '電子產品',
          slug: 'electronics',
          status: true,
          position: 1,
          created_at: '2023-01-01T00:00:00Z',
          updated_at: '2023-01-01T00:00:00Z',
        },
        {
          id: 2,
          name: '家具用品',
          slug: 'furniture',
          status: false,
          position: 2,
          created_at: '2023-01-01T00:00:00Z',
          updated_at: '2023-01-01T00:00:00Z',
        },
      ],
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 2,
      }
    };

    mockUseProductCategories.mockReturnValue({
      data: mockData,
      isLoading: false,
      isError: false,
      refetch: jest.fn(),
    } as any);

    renderWithProviders(<ProductCategoryList />);

    expect(screen.getByText('電子產品')).toBeInTheDocument();
    expect(screen.getByText('家具用品')).toBeInTheDocument();
    expect(screen.getByText('electronics')).toBeInTheDocument();
    expect(screen.getByText('啟用')).toBeInTheDocument();
    expect(screen.getByText('停用')).toBeInTheDocument();
  });

  it('應該支援搜尋功能', async () => {
    const mockRefetch = jest.fn();
    mockUseProductCategories.mockReturnValue({
      data: { data: [], meta: {} },
      isLoading: false,
      isError: false,
      refetch: mockRefetch,
    } as any);

    renderWithProviders(<ProductCategoryList />);

    const searchInput = screen.getByPlaceholderText('搜尋分類名稱...');
    fireEvent.change(searchInput, { target: { value: '電子' } });

    await waitFor(() => {
      expect(mockUseProductCategories).toHaveBeenCalledWith(
        expect.objectContaining({
          search: '電子'
        })
      );
    });
  });

  it('應該處理錯誤狀態', () => {
    const mockRefetch = jest.fn();
    mockUseProductCategories.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      refetch: mockRefetch,
    } as any);

    renderWithProviders(<ProductCategoryList />);

    expect(screen.getByText('載入資料時發生錯誤')).toBeInTheDocument();
    
    const retryButton = screen.getByText('重試');
    fireEvent.click(retryButton);
    
    expect(mockRefetch).toHaveBeenCalled();
  });
});
```

#### Hook 測試

```typescript
// __tests__/hooks/use-product-categories.test.ts
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useProductCategories } from '@/hooks/api/use-product-categories';
import apiClient from '@/lib/api-client';

// Mock API Client
jest.mock('@/lib/api-client');
const mockApiClient = apiClient as jest.Mocked<typeof apiClient>;

/**
 * useProductCategories Hook 測試
 */
describe('useProductCategories', () => {
  let queryClient: QueryClient;

  beforeEach(() => {
    queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false },
        mutations: { retry: false },
      },
    });
  });

  const wrapper = ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>
      {children}
    </QueryClientProvider>
  );

  it('應該成功獲取商品分類資料', async () => {
    const mockData = {
      data: [{ id: 1, name: '測試分類' }],
      meta: { total: 1 }
    };

    mockApiClient.GET.mockResolvedValueOnce({
      data: mockData,
      error: undefined
    } as any);

    const { result } = renderHook(
      () => useProductCategories({ search: '測試' }),
      { wrapper }
    );

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true);
    });

    expect(result.current.data).toEqual(mockData);
    expect(mockApiClient.GET).toHaveBeenCalledWith('/api/product-categories', {
      params: { query: { search: '測試' } }
    });
  });

  it('應該處理 API 錯誤', async () => {
    mockApiClient.GET.mockResolvedValueOnce({
      data: undefined,
      error: { message: 'API 錯誤' }
    } as any);

    const { result } = renderHook(
      () => useProductCategories(),
      { wrapper }
    );

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });

    expect(result.current.error).toBeInstanceOf(Error);
  });
});
```

### 🎯 E2E 測試標準

#### Playwright E2E 測試

```typescript
// e2e/product-categories.spec.ts
import { test, expect } from '@playwright/test';

/**
 * 商品分類 E2E 測試
 * 測試完整的使用者流程
 */
test.describe('商品分類管理', () => {
  test.beforeEach(async ({ page }) => {
    // 登入系統
    await page.goto('/login');
    await page.fill('[data-testid=email-input]', 'admin@example.com');
    await page.fill('[data-testid=password-input]', 'password123');
    await page.click('[data-testid=login-button]');
    
    // 等待登入完成
    await expect(page).toHaveURL('/dashboard');
    
    // 導航到商品分類頁面
    await page.click('[data-testid=nav-product-categories]');
    await expect(page).toHaveURL('/product-categories');
  });

  test('應該能查看商品分類列表', async ({ page }) => {
    // 驗證頁面標題
    await expect(page.locator('h1')).toContainText('商品分類管理');
    
    // 驗證表格存在
    await expect(page.locator('[data-testid=categories-table]')).toBeVisible();
    
    // 驗證至少有一筆資料
    await expect(page.locator('tbody tr')).toHaveCountGreaterThan(0);
  });

  test('應該能建立新的商品分類', async ({ page }) => {
    // 點擊新增按鈕
    await page.click('[data-testid=add-category-button]');
    
    // 填寫表單
    await page.fill('[data-testid=name-input]', '測試分類');
    await page.fill('[data-testid=description-input]', '這是一個測試分類');
    
    // 提交表單
    await page.click('[data-testid=save-button]');
    
    // 驗證成功訊息
    await expect(page.locator('[data-testid=success-message]')).toContainText('建立成功');
    
    // 驗證資料出現在列表中
    await expect(page.locator('tbody')).toContainText('測試分類');
  });

  test('應該能編輯商品分類', async ({ page }) => {
    // 點擊第一筆資料的編輯按鈕
    await page.click('tbody tr:first-child [data-testid=edit-button]');
    
    // 修改名稱
    await page.fill('[data-testid=name-input]', '修改後的分類');
    
    // 提交表單
    await page.click('[data-testid=save-button]');
    
    // 驗證成功訊息
    await expect(page.locator('[data-testid=success-message]')).toContainText('更新成功');
    
    // 驗證資料已更新
    await expect(page.locator('tbody')).toContainText('修改後的分類');
  });

  test('應該能刪除商品分類', async ({ page }) => {
    // 記錄刪除前的資料數量
    const initialCount = await page.locator('tbody tr').count();
    
    // 點擊第一筆資料的刪除按鈕
    await page.click('tbody tr:first-child [data-testid=delete-button]');
    
    // 確認刪除
    await page.click('[data-testid=confirm-delete-button]');
    
    // 驗證成功訊息
    await expect(page.locator('[data-testid=success-message]')).toContainText('刪除成功');
    
    // 驗證資料數量減少
    await expect(page.locator('tbody tr')).toHaveCount(initialCount - 1);
  });

  test('應該支援搜尋篩選', async ({ page }) => {
    // 在搜尋框輸入關鍵字
    await page.fill('[data-testid=search-input]', '電子');
    
    // 等待搜尋結果
    await page.waitForTimeout(500);
    
    // 驗證搜尋結果只包含相關資料
    const rows = page.locator('tbody tr');
    const count = await rows.count();
    
    for (let i = 0; i < count; i++) {
      await expect(rows.nth(i)).toContainText('電子', { ignoreCase: true });
    }
  });
});
```

--- 

## 📚 已實現模組清單

### ✅ 完成模組列表

| 模組名稱 | 版本 | 開發狀態 | 測試覆蓋率 | 開發者 | 完成日期 |
|---------|------|----------|-----------|--------|----------|
| **商品分類模組** | v2.3 | ✅ 企業級完成 | 95%+ | 團隊 | 2025-01 |
| **使用者管理模組** | v6.2 | ✅ 企業級完成 | 100% | 團隊 | 2025-01 |
| 使用者認證模組 | v1.0 | 🟡 基礎完成 | 85%+ | 待定 | 計劃中 |
| 權限管理模組 | - | 📝 規劃中 | - | 待分配 | 待定 |
| 商品管理模組 | - | 📝 規劃中 | - | 待分配 | 待定 |
| 訂單管理模組 | - | 📝 規劃中 | - | 待分配 | 待定 |

### 🎯 商品分類模組 v2.3 詳細規格

#### 後端實現狀態
- ✅ **Model**: `ProductCategory` - 完整的門市隔離、軟刪除、階層結構
- ✅ **Repository**: `ProductCategoryRepository` - 完整的 CRUD、樹狀查詢、快取
- ✅ **Service**: `ProductCategoryService` - 業務邏輯、事務處理、驗證
- ✅ **Controller**: `ProductCategoryController` - RESTful API、權限檢查
- ✅ **Policy**: `ProductCategoryPolicy` - 細粒度權限控制
- ✅ **Observer**: `ProductCategoryObserver` - 自動化處理、快取清理
- ✅ **Request**: 完整的驗證規則、錯誤訊息
- ✅ **Resource**: 標準化的 API 回應格式
- ✅ **Migration**: 標準表格結構、索引優化
- ✅ **Factory**: 測試資料生成
- ✅ **Seeder**: 初始資料填充

#### 前端實現狀態
- ✅ **Pages**: 列表、建立、編輯、詳情頁面
- ✅ **Components**: 表格、表單、篩選器、樹狀結構
- ✅ **Hooks**: 完整的 CRUD Hooks、快取管理
- ✅ **Types**: 100% 型別安全
- ✅ **Store**: Zustand 狀態管理
- ✅ **Routes**: 完整路由配置

#### 測試實現狀態
- ✅ **Unit Tests**: Repository、Service、Hook 測試
- ✅ **Feature Tests**: API 端點測試
- ✅ **Component Tests**: React 組件測試
- ✅ **E2E Tests**: 完整使用者流程測試

### 🚀 使用者管理模組 v6.2 詳細規格 (進階參考)

使用者管理模組 V6.2 代表了本專案**最新的、最全面的企業級實踐**，是所有新模組開發的**優先參考標準**。它成功整合了 Spatie 生態系統，並引入了創新的資料庫設計模式。

#### **核心技術創新 (V6.2 獨有)**

##### 1. **Spatie 生態系統深度整合**
- ✅ **權限管理**: `spatie/laravel-permission` + 門市隔離擴展
- ✅ **活動記錄**: `spatie/laravel-activitylog` + 隱私保護機制
- ✅ **媒體管理**: `spatie/laravel-medialibrary` + 頭像上傳轉換
- ✅ **API 查詢**: `spatie/laravel-query-builder` + 智能篩選

##### 2. **V6.2 創新資料庫模式**
- ✅ **軟刪除唯一性約束**: MySQL 計算欄位 `username_active`, `email_active`
- ✅ **零應用程式感知**: 100% 資料庫層面解決方案
- ✅ **資源重用**: 軟刪除後可立即重新註冊相同用戶名/郵箱

##### 3. **完整門市隔離架構**
- ✅ **智能權限**: 管理員可跨門市，一般使用者限制門市
- ✅ **Spatie 整合**: 門市隔離與 Permission 系統完美結合
- ✅ **活動追蹤**: 跨門市操作的完整審計軌跡

#### **後端實現狀態 (100% 完成)**
- ✅ **Traits**: `HasStoreIsolation`, `HasAuditFields` - 企業級共用邏輯
- ✅ **Model**: `User` - 整合所有 Spatie Traits (HasRoles + LogsActivity + InteractsWithMedia)
- ✅ **Repository**: `UserRepository` - 34 個完整方法，包含安全查詢、批次操作、統計分析
- ✅ **Service**: `UserService` - 企業級業務邏輯，2FA 支援，媒體管理
- ✅ **Cache**: `UserCacheService` - Spatie 快取深度整合，智能失效策略
- ✅ **Controller**: `UserController`, `AuthController` - 完整 RESTful + 自訂端點
- ✅ **Policy**: `UserPolicy` - 基於 Spatie Permission 的細粒度權限
- ✅ **Observer**: `UserObserver` - 快取清除 + Spatie 套件快取同步
- ✅ **Request**: 5 個驗證類別，包含 V6.2 軟刪除唯一性驗證
- ✅ **Resource**: `UserResource`, `UserCollection` - 完整分頁結構
- ✅ **Migration**: V6.2 軟刪除創新模式 + 計算欄位實現
- ✅ **Factory**: 完整測試資料生成，支援角色權限
- ✅ **Seeder**: 管理員帳號 + 權限矩陣初始化

#### **前端實現狀態 (100% 完成)**
- ✅ **Pages**: 使用者列表、建立、編輯、個人資料、2FA 設定
- ✅ **Components**: 高度複用的表格、表單、對話框組件
- ✅ **Hooks**: 完整的 CRUD + 角色權限 + 媒體上傳 Hooks
- ✅ **Types**: 100% 型別安全，與後端 API 自動同步
- ✅ **Store**: Zustand 使用者狀態 + 權限狀態管理
- ✅ **Routes**: 權限守衛 + 巢狀路由配置

#### **測試實現狀態 (100% 覆蓋)**
- ✅ **Unit Tests**: 15 個測試，130 個斷言，Service + Repository 完整覆蓋
- ✅ **Feature Tests**: API 端點 + Spatie 整合測試
- ✅ **Component Tests**: React 組件 + Hook 測試
- ✅ **Integration Tests**: Spatie 套件整合驗證
- ✅ **E2E Tests**: 2FA 流程 + 媒體上傳完整測試

#### **V6.2 開發價值 (技術債務償還)**
- 🔥 **減少 60%+ 開發時間**: 透過 Spatie 生態系統標準化
- 🛡️ **提升安全等級**: 企業級權限 + 活動追蹤 + 媒體安全
- 📈 **長期維護成本降低**: 跟隨 Laravel 生態系統演進
- 🧪 **100% 測試覆蓋**: 保證生產環境穩定性
- 📚 **完整文檔**: 社群支援 + 企業級註解

#### **新模組開發指導原則**
1. **優先參考 V6.2 模式**: 所有新模組應以使用者管理模組 V6.2 為設計藍圖
2. **強制使用 Spatie 生態**: 權限、日誌、媒體功能必須使用對應的 Spatie 套件
3. **採用 V6.2 資料庫模式**: 涉及軟刪除唯一性約束時，必須使用計算欄位解決方案
4. **複用 V6.2 共用元件**: Traits、Services、組件等可直接複用或擴展

---

## ♻️ 共用元件庫

### 🔧 後端共用元件

#### 基礎類別
```php
// 所有模組必須使用的基礎類別
app/Models/BaseModel.php              ✅ 已實現 - 門市隔離、軟刪除、審計
app/Http/Controllers/BaseController.php  ✅ 已實現 - API 回應格式、錯誤處理
app/Services/BaseService.php         ✅ 已實現 - 事務處理、業務邏輯基礎
app/Repositories/BaseRepository.php  ✅ 已實現 - 標準 CRUD 操作
app/Policies/BasePolicy.php          ✅ 已實現 - 權限檢查基礎邏輯
```

#### 例外處理
```php
app/Exceptions/BusinessException.php  ✅ 已實現 - 業務邏輯例外
app/Enums/ErrorCode.php              ✅ 已實現 - 統一錯誤代碼
```

#### 快取服務
```php
app/Services/BaseCacheService.php    ✅ 已實現 - Redis 快取管理
```

#### API 回應處理
```php
app/Traits/ApiResponseTrait.php      ✅ 已實現 - 統一 API 回應格式
```

### 🎨 前端共用元件

#### shadcn/ui 基礎組件
```typescript
components/ui/button.tsx              ✅ 已實現 - 按鈕組件
components/ui/input.tsx               ✅ 已實現 - 輸入框組件
components/ui/dialog.tsx              ✅ 已實現 - 對話框組件
components/ui/select.tsx              ✅ 已實現 - 選擇器組件
components/ui/badge.tsx               ✅ 已實現 - 徽章組件
components/ui/table.tsx               ✅ 已實現 - 表格組件
```

#### 複合功能組件
```typescript
components/common/confirm-dialog.tsx  ✅ 已實現 - 確認對話框
components/common/data-table.tsx      ✅ 已實現 - 數據表格
components/common/loading-spinner.tsx ✅ 已實現 - 載入指示器
components/common/error-boundary.tsx  ✅ 已實現 - 錯誤邊界
components/common/permission-guard.tsx ✅ 已實現 - 權限守衛
components/common/pagination.tsx      ✅ 已實現 - 分頁組件
```

#### API 客戶端
```typescript
lib/api-client.ts                     ✅ 已實現 - 型別安全的 API 客戶端
```

#### 統一 API Hooks
```typescript
hooks/api/use-list.ts                 ✅ 已實現 - 列表查詢 Hook
hooks/api/use-create.ts               ✅ 已實現 - 建立資源 Hook
hooks/api/use-update.ts               ✅ 已實現 - 更新資源 Hook
hooks/api/use-delete.ts               ✅ 已實現 - 刪除資源 Hook
```

#### 表單處理
```typescript
hooks/forms/use-form-validation.ts    ✅ 已實現 - React Hook Form + Zod 整合
components/forms/form-field.tsx       ✅ 已實現 - 統一表單欄位
components/forms/form-wrapper.tsx     ✅ 已實現 - 表單容器
```

### 📋 開發工作流程標準

#### 新模組開發流程 (強制遵循)
1. **需求分析** → 確認模組功能範圍和依賴關係
2. **架構設計** → 遵循本手冊的設計模式
3. **資料庫設計** → 使用標準表格結構和索引
4. **後端開發** → Repository → Service → Controller → Tests
5. **前端開發** → Hooks → Components → Pages → Tests
6. **整合測試** → API 測試 → E2E 測試
7. **程式碼審核** → 遵循檢查清單
8. **部署上線** → CI/CD 流程

#### 共用元件使用規則 (不可違背)
1. **強制使用**: 所有模組必須使用已實現的共用元件
2. **禁止重複**: 不得重新實現已存在的功能
3. **擴展規範**: 需要新功能時，先擴展共用元件再使用
4. **版本管理**: 共用元件變更需要版本號管理

---

## 🚫 絕對禁止行為

### ❌ 架構違規行為 (零容忍)

#### 後端禁止行為
1. **❌ 跳過 Repository 層直接在 Controller 中操作 Model**
   ```php
   // 絕對禁止
   public function index() {
       return ProductCategory::where('store_id', auth()->user()->store_id)->get();
   }
   
   // 必須使用
   public function index() {
       return $this->service->getList();
   }
   ```

2. **❌ 在 Repository 中處理業務邏輯**
   ```php
   // 絕對禁止
   public function create($data) {
       if ($data['parent_id']) {
           // 業務邏輯不應在 Repository 中
           $parent = $this->find($data['parent_id']);
           $data['depth'] = $parent->depth + 1;
       }
       return $this->model->create($data);
   }
   ```

3. **❌ 不使用事務處理寫操作**
   ```php
   // 絕對禁止
   public function create($data) {
       $category = $this->repository->create($data);
       $this->updateParentCount($category->parent_id);
       return $category;
   }
   
   // 必須使用事務
   public function create($data) {
       return DB::transaction(function () use ($data) {
           $category = $this->repository->create($data);
           $this->updateParentCount($category->parent_id);
           return $category;
       });
   }
   ```

4. **❌ 忽略門市隔離機制**
   ```php
   // 絕對禁止
   ProductCategory::withoutGlobalScope('store')->get();
   ```

5. **❌ 硬編碼魔術數字和字串**
   ```php
   // 絕對禁止
   if ($user->role === 'admin') { ... }
   
   // 必須使用枚舉
   if ($user->role === UserRole::ADMIN) { ... }
   ```

#### 前端禁止行為
1. **❌ 組件直接調用 API**
   ```typescript
   // 絕對禁止
   const Component = () => {
     useEffect(() => {
       fetch('/api/categories').then(...);
     }, []);
   };
   
   // 必須使用 Hooks
   const Component = () => {
     const { data } = useProductCategories();
   };
   ```

2. **❌ 不使用 TypeScript 或忽略型別檢查**
   ```typescript
   // 絕對禁止
   const data: any = response.data;
   
   // 必須使用正確型別
   const data: ProductCategory[] = response.data;
   ```

3. **❌ 跳過錯誤處理**
   ```typescript
   // 絕對禁止
   const handleSubmit = async (data) => {
     await createCategory(data);
     navigate('/categories');
   };
   
   // 必須處理錯誤
   const handleSubmit = async (data) => {
     try {
       await createCategory(data);
       navigate('/categories');
     } catch (error) {
       showError(error.message);
     }
   };
   ```

4. **❌ 重複實現已存在的組件**
   ```typescript
   // 絕對禁止：重新實現按鈕
   const CustomButton = ({ children, onClick }) => (
     <button className="custom-btn" onClick={onClick}>
       {children}
     </button>
   );
   
   // 必須使用共用組件
   import { Button } from '@/components/ui/button';
   ```

#### 通用禁止行為
1. **❌ 不遵循命名規範**
2. **❌ 不寫註釋或文檔**
3. **❌ 不進行測試**
4. **❌ 跳過 Code Review**
5. **❌ 不使用 Git 分支策略**
6. **❌ 硬編碼敏感資訊**
7. **❌ 不處理例外情況**
8. **❌ 使用已棄用的技術**

### ⚠️ 違規後果
- **第一次**: 警告 + 強制重構
- **第二次**: 程式碼權限暫停 + 培訓
- **第三次**: 專案移除

---

## ✅ 開發檢查清單

### 📋 新功能開發檢查

#### 🎯 開發前檢查
- [ ] **需求確認**: 功能需求已明確定義
- [ ] **技術選型**: 確認使用指定的技術棧，無替代方案
- [ ] **依賴分析**: 確認對現有模組的依賴關係
- [ ] **資料庫設計**: 遵循標準表格設計規範
- [ ] **API 設計**: 遵循 RESTful 設計原則
- [ ] **權限設計**: 確認權限檢查機制

#### ⚙️ 後端開發檢查
- [ ] **Model 繼承**: 繼承 `BaseModel` 並實現門市隔離
- [ ] **Repository 介面**: 實現 `RepositoryInterface` 並注入 DI 容器
- [ ] **Service 層**: 實現 `ServiceInterface` 並處理業務邏輯
- [ ] **Controller**: 繼承 `BaseController` 並實現標準 CRUD
- [ ] **Policy**: 實現權限檢查邏輯
- [ ] **Observer**: 實現模型事件處理
- [ ] **Request 驗證**: 實現完整的輸入驗證
- [ ] **Resource**: 實現標準化的 API 回應
- [ ] **Migration**: 遵循資料庫設計標準
- [ ] **異常處理**: 使用 `BusinessException` 處理業務錯誤

#### 🎨 前端開發檢查
- [ ] **型別定義**: 100% TypeScript 覆蓋，無 `any` 型別
- [ ] **API 整合**: 使用型別安全的 API 客戶端
- [ ] **Hooks 實現**: 實現完整的 CRUD Hooks
- [ ] **組件設計**: 遵循組件層級架構
- [ ] **狀態管理**: 正確使用 Zustand + TanStack Query
- [ ] **錯誤處理**: 實現完整的錯誤處理機制
- [ ] **載入狀態**: 處理所有的載入和錯誤狀態
- [ ] **權限控制**: 使用 `PermissionGuard` 組件
- [ ] **響應式設計**: 支援所有螢幕尺寸
- [ ] **無障礙性**: 遵循 WCAG 2.1 標準

#### 🧪 測試檢查
- [ ] **單元測試**: 覆蓋率 ≥ 85% (後端) / ≥ 80% (前端)
- [ ] **整合測試**: API 端點完整測試
- [ ] **組件測試**: React 組件功能測試
- [ ] **E2E 測試**: 關鍵使用者流程測試
- [ ] **效能測試**: 載入時間 < 3 秒
- [ ] **安全測試**: SQL 注入、XSS 防護測試

### 🔍 程式碼品質檢查

#### 📝 程式碼審核清單
- [ ] **命名規範**: 遵循統一命名標準
- [ ] **程式碼註釋**: 每個函數都有完整註釋
- [ ] **錯誤處理**: 所有可能的錯誤都有處理
- [ ] **效能優化**: 無明顯效能瓶頸
- [ ] **安全檢查**: 無安全漏洞
- [ ] **重複程式碼**: 無重複實現的功能
- [ ] **依賴注入**: 正確使用 DI 模式
- [ ] **設計模式**: 遵循 SOLID 原則

#### 🚨 自動化檢查
- [ ] **ESLint**: 無 Lint 錯誤或警告
- [ ] **Prettier**: 程式碼格式化一致
- [ ] **PHPStan**: Level 8 靜態分析通過
- [ ] **TypeScript**: 嚴格模式檢查通過
- [ ] **測試覆蓋率**: 達到最低要求標準
- [ ] **建置檢查**: CI/CD 流程全部通過

### 📦 部署前檢查

#### 🔧 環境配置
- [ ] **環境變數**: 正確配置所有必要的環境變數
- [ ] **資料庫遷移**: Migration 已執行且可回滾
- [ ] **種子資料**: 基礎資料已正確填充
- [ ] **快取配置**: Redis 快取正常運作
- [ ] **檔案權限**: 儲存目錄權限正確
- [ ] **SSL 憑證**: HTTPS 配置正確

#### 📊 效能檢查
- [ ] **資料庫查詢**: 無 N+1 查詢問題
- [ ] **索引優化**: 關鍵查詢都有適當索引
- [ ] **API 回應時間**: < 500ms
- [ ] **前端載入時間**: < 3 秒
- [ ] **記憶體使用**: 無記憶體洩漏
- [ ] **磁碟空間**: 充足的儲存空間

#### 🔐 安全檢查
- [ ] **身份驗證**: 所有端點都有適當的認證
- [ ] **權限控制**: 細粒度權限檢查
- [ ] **資料驗證**: 輸入資料完整驗證
- [ ] **SQL 注入**: 防護機制有效
- [ ] **XSS 攻擊**: 防護機制有效
- [ ] **CSRF 保護**: Token 驗證正常

### 📋 發布檢查清單

#### ✅ 最終確認
- [ ] **功能測試**: 所有功能正常運作
- [ ] **瀏覽器相容**: 支援主流瀏覽器
- [ ] **行動裝置**: 行動端體驗良好
- [ ] **文檔更新**: 相關文檔已更新
- [ ] **版本標記**: Git 標籤已建立
- [ ] **團隊通知**: 相關人員已通知
- [ ] **監控設定**: 生產環境監控已設定
- [ ] **回滾計劃**: 緊急回滾方案已準備

---

## 📊 品質標準監控

### 🎯 程式碼品質指標

| 指標 | 最低標準 | 目標標準 | 當前狀態 |
|------|----------|----------|----------|
| **後端測試覆蓋率** | 85% | 95% | 🟢 95%+ |
| **前端測試覆蓋率** | 80% | 90% | 🟢 85%+ |
| **TypeScript 嚴格度** | 100% | 100% | 🟢 100% |
| **ESLint 零警告** | 必須 | 必須 | 🟢 通過 |
| **PHPStan Level** | 8 | 8 | 🟢 Level 8 |
| **API 回應時間** | < 500ms | < 200ms | 🟢 < 300ms |
| **前端載入時間** | < 3s | < 1.5s | 🟢 < 2s |

### 📈 持續改進計劃

#### 每週檢查
- 程式碼覆蓋率報告
- 效能監控報告
- 安全掃描報告

#### 每月檢查
- 架構標準遵循度審核
- 技術債務評估
- 團隊培訓需求分析

#### 季度檢查
- 架構標準更新
- 工具鏈升級計劃
- 最佳實踐分享

---

## 📞 支援與回饋

### 🆘 技術支援
- **架構問題**: 聯繫架構負責人
- **開發疑問**: 查閱本手冊或詢問團隊
- **工具問題**: 參考工具官方文檔

### 📝 文檔回饋
如發現本手冊有任何問題或改進建議：
1. 建立 GitHub Issue
2. 提交 Pull Request
3. 聯繫專案維護者

### 🔄 版本歷史
- **v2.4** (2025-01-07): 初始完整版本
- 後續版本將根據專案需求持續更新

---

**📌 重要提醒**: 本手冊為 LomisX3 專案的核心技術規範，所有開發人員必須嚴格遵守。任何偏離本標準的行為都可能導致架構不一致和技術債務累積。

**🎯 目標**: 通過統一的架構標準，確保專案的可維護性、可擴展性和程式碼品質，為團隊提供清晰的開發指導方針。

---