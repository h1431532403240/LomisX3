/**
 * 型別安全的 API 客戶端 (支援 Laravel Sanctum SPA 認證)
 * 基於 OpenAPI 規範自動生成型別
 * 
 * @author LomisX3 開發團隊
 * @version 5.3.0 (改用 onRequest 中間件修復 CSRF Header)
 */
import createClient, { type Middleware } from 'openapi-fetch';
import type { paths } from '@/types/api';

/**
 * CSRF Cookie 獲取狀態管理
 */
let csrfInitialized = false;

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
 * 從瀏覽器 Cookie 中獲取指定名稱的 Cookie 值。
 * @param name - Cookie 的名稱。
 * @returns Cookie 的值，如果不存在則返回 null。
 */
function getCookie(name: string): string | null {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop()?.split(';').shift() || null;
  }
  return null;
}

/**
 * 獲取 CSRF Cookie (Sanctum SPA 認證必需)
 * 在執行任何需要認證的 API 請求前調用
 */
export async function initializeCsrfToken(): Promise<void> {
  if (csrfInitialized) return;

  try {
    console.log('🔐 正在初始化 CSRF Token...');
    
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'}/sanctum/csrf-cookie`, {
      method: 'GET',
      credentials: 'include',
      mode: 'cors',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (response.ok) {
      csrfInitialized = true;
      console.log('✅ CSRF Token 初始化成功');
    } else {
      console.error('❌ CSRF Token 初始化失敗:', response.status, response.statusText);
      throw new Error(`CSRF token initialization failed: ${response.status}`);
    }
  } catch (error) {
    console.error('❌ CSRF Token 初始化錯誤:', error);
    throw error;
  }
}

/**
 * 創建 OpenAPI 客戶端實例
 */
export const openapi = createClient<paths>({
  baseUrl: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000',
  // 為所有請求自動包含 credentials
  credentials: 'include', 
});

// -- 中間件註冊 (順序很重要) --

// 1. CSRF 標頭附加中間件
const csrfHeaderMiddleware: Middleware = {
  async onRequest({ request }) {
    const method = request.method.toUpperCase();
    if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
      const token = getCookie('XSRF-TOKEN');
      if (token) {
        request.headers.set('X-XSRF-TOKEN', decodeURIComponent(token));
        console.log(`[CSRF] ✅ 標頭 X-XSRF-TOKEN 已成功附加到 ${method} 請求。`);
      } else {
        console.warn(`[CSRF] ⚠️ 未能找到 XSRF-TOKEN cookie！請求很可能會失敗。`);
      }
    }
    return request;
  },
};

// 2. 錯誤回應中間件
const errorResponseMiddleware: Middleware = {
  onResponse({ response }) {
    if (response.status === 419) {
      console.log('🚨 CSRF Token 錯誤，重置狀態');
      csrfInitialized = false;
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

openapi.use(csrfHeaderMiddleware);
openapi.use(errorResponseMiddleware);

/**
 * 安全的 API 調用包裝器
 * 自動處理 CSRF token 初始化和錯誤重試
 * @version 5.4.0 (修復假 redirect 錯誤訊息)
 */
export async function safeApiCall<T>(
  apiCall: () => Promise<T>,
  maxRetries: number = 2
): Promise<T> {
  let lastError: Error | null = null;

  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      // 確保 CSRF token 已初始化
      if (attempt === 1 || !csrfInitialized) {
        await initializeCsrfToken();
      }

      console.log(`🔄 API 調用嘗試 ${attempt}/${maxRetries}`);
      const result = await apiCall();
      
      // 只在真正成功時記錄
      if (result && typeof result === 'object' && 'error' in result && result.error) {
        // 這是 openapi-fetch 的錯誤回應格式
        throw new Error((result.error as any).message || 'API 調用失敗');
      }
      
      console.log(`✅ API 調用成功 (嘗試 ${attempt})`);
      return result;

    } catch (error) {
      lastError = error instanceof Error ? error : new Error(String(error));
      
      // 避免記錄誤導性的 redirect 錯誤訊息
      const errorMessage = lastError.message.toLowerCase();
      const isNetworkError = errorMessage.includes('failed to fetch') || 
                            errorMessage.includes('network error') ||
                            errorMessage.includes('type error');
      
      // 簡化錯誤記錄，避免混淆
      if (!isNetworkError) {
        console.warn(`⚠️ API 調用失敗 (嘗試 ${attempt}/${maxRetries}): ${lastError.message}`);
      } else {
        console.warn(`⚠️ 網路錯誤 (嘗試 ${attempt}/${maxRetries})`);
      }

      // 檢查是否為 CSRF 相關錯誤
      if (errorMessage.includes('419') || 
          errorMessage.includes('csrf') || 
          errorMessage.includes('token mismatch')) {
        console.log('🔄 檢測到 CSRF 錯誤，重置狀態準備重試...');
        csrfInitialized = false;
        
        if (attempt < maxRetries) {
          console.log(`⏳ 等待 500ms 後重試...`);
          await new Promise(resolve => setTimeout(resolve, 500));
          continue;
        }
      }

      // 如果達到最大重試次數，退出循環
      if (attempt >= maxRetries) {
        break;
      }
    }
  }

  // 提供清晰的錯誤訊息，避免誤導性的 redirect 描述
  const finalError = lastError || new Error('API 調用失敗');
  console.error('❌ API 調用最終失敗，所有重試已耗盡');
  throw finalError;
} 