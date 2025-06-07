/**
 * 型別安全的 API 客戶端 (支援 Laravel Sanctum Bearer Token 認證)
 * 基於 OpenAPI 規範自動生成型別
 * 
 * @author LomisX3 開發團隊
 * @version 6.0.0 (完全採用 Bearer Token 認證模式)
 */
import createClient, { type Middleware } from 'openapi-fetch';
import type { paths } from '@/types/api';

/**
 * API 錯誤類型定義
 */
export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
  code?: string;
}

/**
 * 創建 OpenAPI 客戶端實例 (純 Bearer Token 模式)
 */
export const openapi = createClient<paths>({
  baseUrl: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000',
  // 移除 credentials: 'include' - Bearer Token 不需要 cookies
});

// -- 中間件註冊 --

// 1. Bearer Token 認證中間件
const authMiddleware: Middleware = {
  async onRequest({ request }) {
    const token = localStorage.getItem('auth_token');
    if (token) {
      request.headers.set('Authorization', `Bearer ${token}`);
      console.log(`🔐 [Auth] Bearer Token 已附加到 ${request.method} 請求`);
    }
    return request;
  },
};

// 2. 錯誤回應中間件
const errorResponseMiddleware: Middleware = {
  onResponse({ response }) {
    console.log(`📥 API 響應 [${response.status}]:`, response.url)
    
    // 處理 401 未授權錯誤
    if (response.status === 401) {
      console.warn('⚠️ 收到 401 未授權響應')
      
      // 只有在非登入請求時才清除認證狀態
      if (!response.url.includes('/api/auth/login')) {
        // 動態導入避免循環依賴
        import('@/stores/authStore').then(({ useAuthStore }) => {
          const authStore = useAuthStore.getState()
          if (authStore.isAuthenticated) {
            console.log('🔄 清除過期的認證狀態')
            authStore.logout()
            // 不要在這裡重定向，讓 ProtectedRoute 處理
          }
        })
      }
    }
    
    if (!response.ok) {
      console.warn('⚠️ API 回應錯誤:', {
        status: response.status,
        statusText: response.statusText,
        url: response.url,
      });
    }
    return response;
  },
};

openapi.use(authMiddleware);
openapi.use(errorResponseMiddleware);

/**
 * 安全的 API 調用包裝器
 * 適用於 Bearer Token 認證模式，簡化錯誤處理
 * @version 6.0.0 (移除 CSRF 相關邏輯，純 Bearer Token 模式)
 */
export async function safeApiCall<T>(
  apiCall: () => Promise<T>,
  maxRetries: number = 2
): Promise<T> {
  let lastError: Error | null = null;

  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      console.log(`🔄 API 調用嘗試 ${attempt}/${maxRetries}`);
      const result = await apiCall();
      
      // 檢查是否為 openapi-fetch 錯誤回應格式
      if (result && typeof result === 'object' && 'error' in result && result.error) {
        throw new Error((result.error as any).message || 'API 調用失敗');
      }
      
      console.log(`✅ API 調用成功 (嘗試 ${attempt})`);
      return result;

    } catch (error) {
      lastError = error instanceof Error ? error : new Error(String(error));
      
      // 簡化錯誤記錄
      const errorMessage = lastError.message.toLowerCase();
      const isNetworkError = errorMessage.includes('failed to fetch') || 
                            errorMessage.includes('network error') ||
                            errorMessage.includes('type error');
      
      if (!isNetworkError) {
        console.warn(`⚠️ API 調用失敗 (嘗試 ${attempt}/${maxRetries}): ${lastError.message}`);
      } else {
        console.warn(`⚠️ 網路錯誤 (嘗試 ${attempt}/${maxRetries})`);
      }

      // 對於 401 錯誤，不進行重試（Token 無效）
      if (errorMessage.includes('401') || errorMessage.includes('unauthorized')) {
        console.log('🚫 檢測到 401 錯誤，停止重試（Token 可能無效）');
        break;
      }

      // 如果達到最大重試次數，退出循環
      if (attempt >= maxRetries) {
        break;
      }

      // 等待後重試
      console.log(`⏳ 等待 500ms 後重試...`);
      await new Promise(resolve => setTimeout(resolve, 500));
    }
  }

  // 提供清晰的錯誤訊息
  const finalError = lastError || new Error('API 調用失敗');
  console.error('❌ API 調用最終失敗，所有重試已耗盡');
  throw finalError;
} 