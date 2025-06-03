# Laravel 商品分類模組

## 📋 專案概覽

**版本**：v2.0.0 (Phase 2 深度優化完成)  
**狀態**：✅ 企業級生產就緒  
**架構等級**：⭐⭐⭐⭐⭐  

Laravel 商品分類模組提供完整的階層式商品分類系統，支援巢狀結構、快取優化、權限控制等企業級功能。

## 🚀 開發階段

### ✅ Phase 1 - 基礎架構建立 (已完成)
- CRUD API 端點
- 基礎 Model、Repository、Service 架構
- 基本權限控制和快取機制

### ✅ Phase 2 - 深度架構優化 (已完成)
- 企業級快取架構（標籤式、分層快取）
- 完整權限控制系統（Role 枚舉 + Policy）
- 壓力測試工具和 CI/CD 流程
- 靜態分析和代碼品質保證

### 🎯 Phase 3 - 功能擴展 (規劃中)
- API 文檔生成 (Swagger/OpenAPI)
- 前端 TypeScript 型別定義
- 效能監控儀表板
- 搜尋引擎整合

## 📊 技術指標

| 項目 | 數值 |
|------|------|
| 程式碼行數 | 1,500+ 行 |
| API 端點 | 12 個 |
| 測試覆蓋率 | 目標 80%+ |
| 靜態分析等級 | PHPStan Level 5 |
| 快取策略 | 分層標籤式 |
| 權限操作 | 15 種 |

## 🎯 API 端點

### 基礎 CRUD
- `GET /api/product-categories` - 取得分類清單
- `POST /api/product-categories` - 建立新分類
- `GET /api/product-categories/{id}` - 取得指定分類
- `PUT /api/product-categories/{id}` - 更新分類
- `DELETE /api/product-categories/{id}` - 刪除分類

### 進階功能
- `GET /api/product-categories/tree` - 取得樹狀結構
- `GET /api/product-categories/statistics` - 取得統計資訊
- `GET /api/product-categories/{id}/breadcrumbs` - 取得麵包屑
- `GET /api/product-categories/{id}/descendants` - 取得子孫分類

### 批次操作
- `PATCH /api/product-categories/sort` - 拖曳排序
- `PATCH /api/product-categories/batch-status` - 批次更新狀態
- `DELETE /api/product-categories/batch-delete` - 批次刪除

## 🛠️ 快速開始

### 環境需求
- PHP 8.2+
- Laravel 12.0+
- MySQL 8.0+ / PostgreSQL 13+
- Redis (推薦，用於快取)

### 安裝步驟

1. **複製環境變數**
```bash
cp .env.example .env
```

2. **安裝依賴**
```bash
composer install
```

3. **執行遷移**
```bash
php artisan migrate
```

4. **生成測試資料** (可選)
```bash
# 生成 100 筆測試分類，最大深度 3 層
php artisan category:seed:stress --count=100 --depth=3

# 乾跑模式查看將生成的資料結構
php artisan category:seed:stress --count=50 --depth=2 --dry-run
```

5. **啟動開發伺服器**
```bash
php artisan serve
```

## 🧪 開發工具

### 代碼品質檢查
```bash
# 格式化檢查和自動修正
./vendor/bin/pint

# 靜態分析
./vendor/bin/phpstan analyse --memory-limit=-1 --level=5

# 單元測試
./vendor/bin/pest
```

### 壓力測試
```bash
# 生成大量測試資料
php artisan category:seed:stress --count=1000 --depth=4 --chunk=200

# 清空並重新生成
php artisan category:seed:stress --count=500 --depth=3 --clean
```

## 📚 詳細文檔

### 開發文檔
- [Phase 2 深度優化完成報告](./PRODUCT_CATEGORY_PHASE2_DEEP_OPTIMIZATION_REPORT.md) 📊 **最新**
- [Phase 2 架構重構文檔](./Docs/功能開發中/PRODUCT_CATEGORY_PHASE2_ARCHITECTURE_REFACTORING.md)
- [Phase 2 優化總結](./Docs/功能開發中/PRODUCT_CATEGORY_PHASE2_SUMMARY.md)

### API 使用範例

#### 取得樹狀結構
```bash
curl -X GET "http://localhost:8000/api/product-categories/tree?only_active=true" \
  -H "Accept: application/json"
```

#### 建立新分類
```bash
curl -X POST "http://localhost:8000/api/product-categories" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "電子產品",
    "slug": "electronics",
    "parent_id": null,
    "description": "各類電子產品分類"
  }'
```

## 🏗️ 架構特色

### 1. **企業級快取架構**
- 標籤式快取管理 (`product_categories`)
- 分層快取策略（樹狀、麵包屑、統計）
- Redis 鎖防重機制
- 防抖動佇列快取清除

### 2. **完整權限控制**
- Role 枚舉 (ADMIN, MANAGER, STAFF, GUEST)
- Policy 策略模式，15 種權限操作
- 自動資源授權機制
- Sanctum token 權限整合

### 3. **SOLID 原則實施**
- 單一職責原則：Model、Service、Repository 分離
- 開放封閉原則：介面導向設計
- 依賴注入：Service Provider 管理
- 介面隔離：Repository 介面設計

### 4. **高效能設計**
- Cursor Pagination 支援
- 批次操作優化
- 索引策略設計
- 查詢效能優化

## 🤝 貢獻指南

### 開發規範
1. 遵循 PSR-12 編碼標準
2. 所有程式碼必須包含繁體中文註釋
3. 新功能需要對應的單元測試
4. 提交前執行 `./vendor/bin/pint` 和 `./vendor/bin/phpstan`

### 分支策略
- `main` - 生產環境穩定版
- `develop` - 開發環境整合分支
- `feature/*` - 功能開發分支
- `hotfix/*` - 緊急修復分支

## 📞 技術支援

如有問題或建議，歡迎透過以下方式聯繫：

- 📧 技術問題：請建立 Issue
- 📋 功能需求：請提交 Feature Request
- 🔧 Bug 回報：請提供詳細重現步驟

---

**最後更新**：2025年1月21日  
**開發團隊**：Laravel 商品分類模組開發組  
**授權方式**：MIT License
