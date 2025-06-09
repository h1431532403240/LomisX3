# 🔐 LomisX3 CORS 與 Sanctum 配置檢查清單

> **目的**：解決前後端登入認證問題，確保 Laravel Sanctum SPA 認證正常運作
> 
> **版本**：v1.0.0  
> **更新日期**：2025-01-07  
> **適用版本**：Laravel 12.0 + React 19 + Vite 6.3+

## 📋 問題症狀檢查

### ❌ 常見問題症狀
- [ ] 登入頁面隨便輸入帳號密碼就能進入
- [ ] 前端 API 請求返回 CORS 錯誤
- [ ] 登入後刷新頁面會退出登入狀態
- [ ] Token 無法儲存或讀取
- [ ] API 請求返回 401 Unauthorized
- [ ] CSRF Token 錯誤

---

## 🔍 檢查步驟

### 1️⃣ **後端 Laravel Sanctum 配置檢查**

#### ✅ Sanctum 套件安裝
```bash
# 檢查 Sanctum 是否正確安裝
composer show | grep sanctum
# 應該顯示：laravel/sanctum
```

#### ✅ 檢查 `config/sanctum.php`
```php
// ✅ 必須包含前端域名
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost:5173,localhost:3000,127.0.0.1:5173,127.0.0.1:3000'
)),

// ✅ 使用 web guard
'guard' => ['web'],

// ✅ Token 過期時間 (null = 不過期)
'expiration' => null,
```

#### ✅ 檢查 `.env` 環境變數
```env
# ✅ 應用 URL
APP_URL=http://localhost:8000

# ✅ Sanctum 狀態化域名 (重要！)
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:3000,127.0.0.1:5173,127.0.0.1:3000

# ✅ Session 配置 (SPA 認證必需)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### 2️⃣ **CORS 配置檢查**

#### ✅ 檢查 `config/cors.php`
```php
// ✅ 允許的路徑
'paths' => [
    'api/*', 
    'sanctum/csrf-cookie',
    'broadcasting/auth',
],

// ✅ 允許的方法 (必須包含 OPTIONS)
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

// ✅ 允許的來源 (包含前端 URL)
'allowed_origins' => [
    'http://localhost:5173',    // Vite 開發服務器
    'http://localhost:3000',    // React 開發服務器
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
],

// ✅ 允許的標頭 (包含認證標頭)
'allowed_headers' => [
    'Accept',
    'Authorization',
    'Content-Type',
    'Origin',
    'X-Requested-With',
    'X-XSRF-TOKEN',
    'X-CSRF-TOKEN',
],

// ✅ 支援認證 Cookie (重要！)
'supports_credentials' => true,
```

### 3️⃣ **路由配置檢查**

#### ✅ 檢查 `routes/api.php`
```php
// ✅ 必須有 Sanctum 中介軟體保護的路由
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // 其他需要認證的 API 路由
});
```

#### ✅ 檢查 `routes/web.php`
```php
// ✅ 必須包含 CSRF Cookie 路由 (Sanctum SPA 認證必需)
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});
```

### 4️⃣ **中介軟體配置檢查**

#### ✅ 檢查 `app/Http/Kernel.php` 或 `bootstrap/app.php`
```php
// ✅ 確保 CORS 中介軟體已啟用
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### 5️⃣ **前端配置檢查**

#### ✅ 檢查 `.env.local`
```env
# ✅ 後端 API URL
VITE_API_BASE_URL=http://localhost:8000

# ✅ 應用名稱
VITE_APP_NAME="LomisX3 管理系統"
```

#### ✅ 檢查 API 客戶端配置
```typescript
// ✅ 必須設定 withCredentials 和 base URL
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  withCredentials: true, // 重要！允許 Cookie
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});
```

#### ✅ 檢查 Vite 代理配置 (可選)
```typescript
// vite.config.ts - 如果需要代理
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
```

---

## 🔧 常見問題修復

### ❌ 問題 1：CORS Preflight 失敗
**症狀**：瀏覽器控制台顯示 CORS 錯誤

**解決方案**：
```php
// config/cors.php
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'supports_credentials' => true,
```

### ❌ 問題 2：CSRF Token 不匹配
**症狀**：API 請求返回 419 CSRF Token Mismatch

**解決方案**：
```typescript
// 登入前先取得 CSRF Cookie
await axios.get('/sanctum/csrf-cookie');
// 然後再執行登入
await axios.post('/api/auth/login', credentials);
```

### ❌ 問題 3：Session 無法持久化
**症狀**：登入後刷新頁面就退出

**解決方案**：
```env
# .env
SESSION_DRIVER=database
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false  # 開發環境
```

### ❌ 問題 4：Token 無法傳送
**症狀**：API 請求沒有帶 Authorization Header

**解決方案**：
```typescript
// 設定 Axios 攔截器
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

---

## 🧪 測試檢查清單

### ✅ 手動測試步驟

1. **CSRF Cookie 測試**
```bash
curl -X GET http://localhost:8000/sanctum/csrf-cookie \
  -H "Origin: http://localhost:5173" \
  -v
```

2. **登入 API 測試**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{"email":"admin@test.com","password":"password"}' \
  -v
```

3. **受保護路由測試**
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Origin: http://localhost:5173" \
  -v
```

### ✅ 瀏覽器開發者工具檢查

1. **Network 標籤**：
   - [ ] OPTIONS 請求成功 (200)
   - [ ] POST 登入請求成功 (200)
   - [ ] Cookie 正確設定

2. **Application 標籤**：
   - [ ] Session Cookie 存在
   - [ ] LocalStorage Token 存在 (如使用)

3. **Console 標籤**：
   - [ ] 無 CORS 錯誤
   - [ ] 無 401/419 錯誤

---

## 🚀 自動化檢查腳本

### 後端檢查腳本
```bash
#!/bin/bash
# 檢查 Laravel Sanctum 配置

echo "=== 檢查 Sanctum 配置 ==="

# 檢查套件
composer show | grep sanctum

# 檢查路由
php artisan route:list | grep sanctum

# 檢查環境變數
echo "SANCTUM_STATEFUL_DOMAINS: $SANCTUM_STATEFUL_DOMAINS"
echo "SESSION_DRIVER: $SESSION_DRIVER"
```

### 前端檢查腳本
```typescript
// 前端配置檢查
const checkConfig = () => {
  console.log('API Base URL:', import.meta.env.VITE_API_BASE_URL);
  console.log('With Credentials:', apiClient.defaults.withCredentials);
  
  // 測試 CSRF Cookie
  axios.get('/sanctum/csrf-cookie')
    .then(() => console.log('✅ CSRF Cookie 正常'))
    .catch(() => console.log('❌ CSRF Cookie 失敗'));
};
```

---

## ✅ 最終檢查清單

完成所有配置後，請確認：

- [ ] 後端 Sanctum 狀態化域名包含前端 URL
- [ ] CORS 允許前端域名和必要標頭
- [ ] Session 配置正確（特別是 domain 和 secure）
- [ ] 前端 API 客戶端設定 withCredentials
- [ ] CSRF Cookie 端點可正常存取
- [ ] 登入 API 端點回傳正確 Token
- [ ] 受保護路由需要認證才能存取
- [ ] Cookie 和 Token 正確儲存
- [ ] 頁面刷新後認證狀態保持

---

## 📞 支援與除錯

如果問題持續存在，請檢查：

1. **瀏覽器網路請求**：查看詳細的錯誤訊息
2. **Laravel 日誌**：`storage/logs/laravel.log`
3. **Web 伺服器日誌**：Apache/Nginx 錯誤日誌
4. **PHP 錯誤日誌**：檢查 PHP 配置和錯誤

**常用除錯指令**：
```bash
# 清除快取
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 重新產生 APP_KEY
php artisan key:generate

# 檢查路由
php artisan route:list
```

---

**🎯 成功標準**：完成此檢查清單後，應該能夠：
- 正常登入並取得認證狀態
- API 請求正確帶入認證資訊
- 頁面刷新後保持登入狀態
- 無 CORS 或認證相關錯誤 