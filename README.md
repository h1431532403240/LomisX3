# 🚀 LomisX3 管理系統

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19.x-61DAFB.svg)](https://reactjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.8.x-3178C6.svg)](https://www.typescriptlang.org)

現代化的電商管理系統，採用前後端分離架構，提供完整的商品、訂單、使用者管理功能。

## 📋 目錄

- [技術棧](#-技術棧)
- [項目結構](#-項目結構)
- [環境要求](#-環境要求)
- [安裝指南](#-安裝指南)
- [開發指南](#-開發指南)
- [API 文檔](#-api-文檔)
- [部署指南](#-部署指南)
- [貢獻指南](#-貢獻指南)

## 🛠 技術棧

### 🎨 前端技術

| 技術 | 版本 | 說明 |
|------|------|------|
| **框架** | | |
| React | ^19.1.0 | 前端框架 |
| TypeScript | ~5.8.3 | 類型安全 |
| Vite | ^6.3.5 | 建置工具 |
| **UI 框架** | | |
| shadcn/ui | Latest | 組件庫 |
| Tailwind CSS | ^3.4.17 | CSS 框架 |
| Radix UI | Latest | 無頭組件 |
| Lucide React | ^0.511.0 | 圖標庫 |
| **狀態管理** | | |
| TanStack Query | ^5.80.2 | 服務端狀態管理 |
| **表單處理** | | |
| React Hook Form | ^7.57.0 | 表單處理 |
| Zod | ^3.25.49 | Schema 驗證 |
| **路由** | | |
| React Router | ^7.6.1 | 前端路由 |
| **主題** | | |
| next-themes | ^0.4.6 | 主題切換 |
| **圖表** | | |
| Recharts | ^2.15.3 | 數據視覺化 |
| **拖拽** | | |
| DND Kit | ^6.3.1 | 拖拽功能 |
| **工具** | | |
| class-variance-authority | ^0.7.1 | 條件樣式 |
| clsx | ^2.1.1 | 樣式合併 |
| cmdk | ^1.1.1 | 命令選單 |

### ⚙️ 後端技術

| 技術 | 版本 | 說明 |
|------|------|------|
| **框架** | | |
| Laravel | ^12.0 | PHP 框架 |
| PHP | ^8.2 | 程式語言 |
| **API** | | |
| Laravel Sanctum | ^4.1 | API 認證 |
| RESTful API | - | API 設計規範 |
| **開發工具** | | |
| Laravel Tinker | ^2.10.1 | REPL 工具 |
| Laravel Pail | ^1.2.2 | 日誌工具 |
| Laravel Pint | ^1.13 | 代碼格式化 |
| Laravel Sail | ^1.41 | Docker 開發環境 |
| **測試** | | |
| PHPUnit | ^11.5.3 | 單元測試 |
| Faker | ^1.23 | 假資料生成 |
| Mockery | ^1.6 | 模擬物件 |

### 🗄️ 資料庫 & 部署

| 技術 | 說明 |
|------|------|
| **資料庫** | |
| MySQL / PostgreSQL | 主要資料庫 |
| SQLite | 開發環境 |
| **快取** | |
| Redis | 快取系統 |
| **佇列** | |
| Laravel Queue | 背景任務處理 |
| **容器化** | |
| Docker | 容器化部署 |
| Docker Compose | 多容器編排 |

## 📁 項目結構

```
LomisX3/
├── 📁 front/                 # 前端應用
│   ├── 📁 src/
│   │   ├── 📁 components/     # React 組件
│   │   │   ├── 📁 ui/         # shadcn/ui 組件
│   │   │   ├── 📁 theme/      # 主題相關
│   │   │   └── 📁 layout/     # 佈局組件
│   │   ├── 📁 pages/          # 頁面組件
│   │   ├── 📁 hooks/          # 自定義 Hooks
│   │   ├── 📁 lib/            # 工具函數
│   │   └── 📁 types/          # TypeScript 類型
│   ├── 📄 package.json
│   ├── 📄 vite.config.ts
│   ├── 📄 tailwind.config.js
│   └── 📄 tsconfig.json
├── 📁 back/                   # 後端應用
│   ├── 📁 app/
│   │   ├── 📁 Http/
│   │   │   ├── 📁 Controllers/Api/  # API 控制器
│   │   │   ├── 📁 Resources/        # API 資源
│   │   │   └── 📁 Requests/         # 表單驗證
│   │   ├── 📁 Models/         # Eloquent 模型
│   │   └── 📁 Services/       # 業務邏輯層
│   ├── 📁 database/
│   │   ├── 📁 migrations/     # 資料庫遷移
│   │   ├── 📁 seeders/        # 種子資料
│   │   └── 📁 factories/      # 模型工廠
│   ├── 📁 routes/
│   │   └── 📄 api.php         # API 路由
│   ├── 📄 composer.json
│   └── 📄 .env.example
└── 📄 README.md
```

## 💻 環境要求

### 前端環境
- Node.js >= 18.0.0
- npm >= 9.0.0 或 yarn >= 1.22.0

### 後端環境
- PHP >= 8.2
- Composer >= 2.0
- MySQL >= 8.0 或 PostgreSQL >= 13
- Redis >= 6.0 (可選)

## 🚀 安裝指南

### 1. 克隆項目

```bash
git clone https://github.com/your-username/LomisX3.git
cd LomisX3
```

### 2. 後端安裝

```bash
cd back

# 安裝依賴
composer install

# 環境配置
cp .env.example .env
php artisan key:generate

# 資料庫設定
php artisan migrate
php artisan db:seed

# 啟動服務
php artisan serve
```

### 3. 前端安裝

```bash
cd front

# 安裝依賴
npm install

# 啟動開發服務器
npm run dev
```

## 🔧 開發指南

### 前端開發規範

- **語言**: 使用 TypeScript，禁止使用 `any`
- **組件**: 僅使用 Function Component
- **UI**: 統一使用 shadcn/ui，禁用其他 UI 庫
- **狀態管理**: 使用 TanStack Query 處理 API
- **表單**: 使用 React Hook Form + Zod 驗證
- **路由**: 使用 React Router v6
- **樣式**: 使用 Tailwind CSS 變數，支援主題切換

### 後端開發規範

- **架構**: 使用 Repository Pattern + Observer Pattern
- **API**: 採用 RESTful API 設計
- **驗證**: 使用 FormRequest 進行請求驗證
- **資源**: 使用 Laravel Resource 包裝回應
- **路由**: 使用 `Route::apiResource` 聲明
- **資料庫**: 避免直接使用 raw query，使用 Eloquent ORM
- **依賴注入**: 使用介面進行依賴注入，提升可測試性
- **事件處理**: 使用 Observer 處理 Model 生命週期事件

#### Repository Pattern 使用
```php
// Controller 中注入 Repository 介面
public function __construct(
    protected ProductCategoryRepositoryInterface $categoryRepository
) {}

// 使用 Repository 方法
$tree = $this->categoryRepository->getTree();
$categories = $this->categoryRepository->paginate(20, ['status' => true]);
```

#### Observer Pattern 使用
- Model Events 統一在 Observer 中處理
- 自動處理 slug 生成、depth 計算、position 設定
- 支援完整的 Model 生命週期管理

### 程式碼風格

```bash
# 前端 Lint
cd front
npm run lint

# 後端格式化
cd back
./vendor/bin/pint
```

## 📚 API 文檔

### 基本資訊
- **Base URL**: `http://localhost:8000/api`
- **認證方式**: Laravel Sanctum
- **回應格式**: JSON

### 主要端點

#### 商品分類管理 ✅ Phase 2 完成
```http
# 基礎 CRUD
GET    /api/product-categories              # 獲取分類列表（支援篩選、分頁）
POST   /api/product-categories              # 創建分類
GET    /api/product-categories/{id}         # 獲取單一分類
PUT    /api/product-categories/{id}         # 更新分類
DELETE /api/product-categories/{id}         # 刪除分類

# 樹狀結構功能
GET    /api/product-categories/tree         # 取得完整樹狀結構
GET    /api/product-categories/{id}/breadcrumbs    # 取得麵包屑路徑
GET    /api/product-categories/{id}/descendants   # 取得子孫分類

# 進階操作
PATCH  /api/product-categories/sort         # 拖曳排序
PATCH  /api/product-categories/batch-status # 批次狀態更新
DELETE /api/product-categories/batch-delete # 批次刪除
GET    /api/product-categories/statistics   # 取得統計資訊
```

**查詢參數支援**：
- `search` - 關鍵字搜尋
- `status` - 狀態篩選 (boolean)
- `parent_id` - 父分類篩選
- `depth` - 深度篩選
- `with_children` - 包含子分類
- `per_page` - 分頁筆數

#### 商品管理 🚧 開發中
```http
GET    /api/products                    # 獲取商品列表
POST   /api/products                    # 創建商品
GET    /api/products/{id}               # 獲取單一商品
PUT    /api/products/{id}               # 更新商品
DELETE /api/products/{id}               # 刪除商品
```

### 認證
```http
POST   /api/auth/login                  # 使用者登入
POST   /api/auth/logout                 # 使用者登出
POST   /api/auth/register               # 使用者註冊
GET    /api/auth/me                     # 獲取當前使用者
```

## 🚀 部署指南

### Docker 部署

```bash
# 啟動所有服務
docker-compose up -d

# 查看服務狀態
docker-compose ps

# 停止服務
docker-compose down
```

### 生產環境

```bash
# 前端建置
cd front
npm run build

# 後端優化
cd back
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🏗️ 系統特色

### ✨ 前端特色
- 🌙 **深色/淺色主題** - 無縫切換，支援系統偏好
- 📱 **響應式設計** - 完美支援桌面和移動端
- 🎨 **現代化 UI** - 基於 shadcn/ui 的精美界面
- ⚡ **效能優化** - Vite 建置，React 19 支援
- 🔍 **智能搜尋** - 全局搜尋與過濾功能
- 🖱️ **拖拽排序** - 直觀的拖拽操作體驗

### ⚙️ 後端特色
- 🔐 **安全認證** - Laravel Sanctum API 認證
- 📊 **RESTful API** - 標準化 API 設計
- 🏗️ **模組化架構** - 清晰的代碼組織結構
- 🔄 **資料驗證** - 完整的請求驗證機制
- 📈 **可擴展性** - 易於擴展的業務邏輯層
- 🧪 **測試覆蓋** - PHPUnit 單元測試支援

## 🤝 貢獻指南

1. Fork 這個項目
2. 創建你的特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交你的修改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 開啟一個 Pull Request

## 📄 授權條款

本項目基於 [MIT License](LICENSE) 開源授權。

## 👥 開發團隊

- **項目維護者**: [Your Name](https://github.com/your-username)
- **前端開發**: React + TypeScript + shadcn/ui
- **後端開發**: Laravel + PHP + RESTful API

## 📧 聯絡方式

- **項目問題**: [GitHub Issues](https://github.com/your-username/LomisX3/issues)
- **功能建議**: [GitHub Discussions](https://github.com/your-username/LomisX3/discussions)
- **郵件聯絡**: your-email@example.com

---

**⭐ 如果這個項目對你有幫助，請給我們一個 Star！** 