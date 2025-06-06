/**
 * 型別安全的 API 客戶端
 * 基於 OpenAPI 規範自動生成型別
 */
import createClient, { type Middleware } from 'openapi-fetch';
import type { paths } from '@/types/api';

// 🆕 建立型別安全的 API 客戶端
export const openapi = createClient<paths>({
  baseUrl: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// 🆕 認證中間件
const authMiddleware: Middleware = {
  async onRequest({ request }) {
    const token = localStorage.getItem('auth_token');
    if (token) {
      request.headers.set('Authorization', `Bearer ${token}`);
    }
    return request;
  },
};

// 🆕 錯誤處理中間件
const errorMiddleware: Middleware = {
  async onResponse({ response }) {
    if (!response.ok) {
      console.error('API Error:', {
        status: response.status,
        statusText: response.statusText,
        url: response.url,
      });
    }
    return response;
  },
};

// 🆕 註冊中間件
openapi.use(authMiddleware);
openapi.use(errorMiddleware);

// 🆕 API 錯誤類型
export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
  code?: string;
}

// 🆕 API 響應包裝器，提供統一的錯誤處理
export async function safeApiCall<T>(
  apiCall: () => Promise<{ data?: T; error?: unknown; response: Response }>
): Promise<{ data?: T; error?: ApiError }> {
  try {
    const result = await apiCall();
    
    if (result.error) {
      return {
        error: {
          success: false,
          message: '請求失敗',
          status: result.response.status,
          ...(result.error as object),
        } as ApiError,
      };
    }
    
    return { data: result.data };
  } catch (error) {
    console.error('API 呼叫異常:', error);
    return {
      error: {
        success: false,
        message: '網路錯誤或伺服器異常',
        status: 500,
      },
    };
  }
}

// 🆕 導出默認客戶端
export default openapi; 