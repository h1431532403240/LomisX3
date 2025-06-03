# Laravel Sanctum 配置文檔

## 概述

本文檔記錄了 Laravel Sanctum 和 CORS 的配置設定，用於支援前端 SPA 應用程式的認證功能。

## 已完成的配置

### 1. Middleware 配置 (`bootstrap/app.php`)

```php
->withMiddleware(function (Middleware $middleware) {
    /**
     * 為 API 路由群組添加 Sanctum middleware
     * 確保前端請求能正確處理狀態維護
     */
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
})
```

**功能說明：**
- `EnsureFrontendRequestsAreStateful` middleware 確保來自前端 SPA 的請求能夠維持狀態
- 這個 middleware 會自動套用到所有 `api/*` 路由

### 2. CORS 配置 (`config/cors.php`)

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['http://localhost:5173'],
'supports_credentials' => true,
```

**功能說明：**
- 允許 `api/*` 和 `sanctum/csrf-cookie` 路徑的跨域請求
- 允許來自 Vite 開發服務器 (`localhost:5173`) 的請求
- 啟用認證 Cookie 支援，這對 Sanctum 認證是必需的

### 3. Sanctum 配置 (`config/sanctum.php`)

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,localhost:5173,127.0.0.1,127.0.0.1:8000,::1',
    Sanctum::currentApplicationUrlWithPort(),
))),
```

**功能說明：**
- 添加了 `localhost:5173` 到狀態化域名列表
- 來自這些域名的請求會使用基於 Cookie 的認證而非 Token

### 4. API 路由 (`routes/api.php`)

已創建基本的 API 路由文件，包含：
- `/api/user` - 取得認證用戶資訊 (需要認證)
- `/api/test` - 測試 API 連接

## 可用的路由

```
GET|HEAD  api/test                - 測試路由
GET|HEAD  api/user                - 取得認證用戶 (需要認證)
GET|HEAD  sanctum/csrf-cookie     - 取得 CSRF Cookie
```

## 前端整合指南

### 1. 設定 Axios 基本配置

```javascript
import axios from 'axios';

// 設定基本 URL 和認證
axios.defaults.baseURL = 'http://localhost:8000';
axios.defaults.withCredentials = true;

// 取得 CSRF Cookie (第一次請求前)
await axios.get('/sanctum/csrf-cookie');
```

### 2. 登入流程示例

```javascript
// 1. 取得 CSRF Cookie
await axios.get('/sanctum/csrf-cookie');

// 2. 執行登入
const response = await axios.post('/login', {
    email: 'user@example.com',
    password: 'password'
});

// 3. 之後的 API 請求會自動包含認證 Cookie
const user = await axios.get('/api/user');
```

## 環境變數配置

可以在 `.env` 文件中設定：

```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:3000
```

## 安全注意事項

1. **生產環境設定：**
   - 更新 `allowed_origins` 為實際的生產域名
   - 確保 `SANCTUM_STATEFUL_DOMAINS` 包含正確的生產域名

2. **HTTPS 要求：**
   - 生產環境必須使用 HTTPS
   - Cookie 設定應包含 `Secure` 和 `SameSite` 屬性

3. **CSRF 保護：**
   - 所有狀態改變的請求都需要有效的 CSRF Token
   - 前端必須先取得 CSRF Cookie

## 疑難排解

### 常見問題

1. **CORS 錯誤：**
   - 檢查 `config/cors.php` 中的 `allowed_origins`
   - 確認前端 URL 正確包含在允許列表中

2. **認證失敗：**
   - 確認已取得 CSRF Cookie
   - 檢查 `withCredentials: true` 設定
   - 驗證 `SANCTUM_STATEFUL_DOMAINS` 配置

3. **路由不存在：**
   - 執行 `php artisan route:clear`
   - 檢查 `bootstrap/app.php` 中的路由配置

### 除錯命令

```bash
# 清除所有快取
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 檢查路由
php artisan route:list

# 檢查配置
php artisan config:show cors
php artisan config:show sanctum
```

## 更新日誌

- **2025-01-03**: 初始 Sanctum 配置完成
  - 添加 EnsureFrontendRequestsAreStateful middleware
  - 配置 CORS 支援 localhost:5173
  - 更新 Sanctum stateful domains
  - 創建基本 API 路由 