/**
 * 使用者登入 Hook
 * 
 * V5.1.0 版本：完整權限系統整合
 * 提供型別安全的登入功能，與後端 API 完美整合
 * 
 * ⚡ 特色功能：
 * - 🔒 完整錯誤處理和重試機制
 * - 📊 詳細的登入流程日誌
 * - 🔄 自動 CSRF 處理
 * - 🚀 型別安全的 API 呼叫
 * - 👤 完整的使用者權限同步
 * 
 * @author LomisX3 開發團隊
 * @version 5.1.0
 */

import { useMutation } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import type { User, Store } from '@/types/user';

/**
 * 登入 API 請求介面
 * 定義登入所需的參數
 */
interface LoginCredentials {
  /** 登入帳號（使用者名稱或電子郵件） */
  email: string;
  /** 密碼 */
  password: string;
  /** 記住我（延長 Token 有效期） */
  remember?: boolean;
}

/**
 * 後端登入請求介面 (與實際後端實現一致)
 */
interface BackendLoginRequest {
  /** 登入帳號（後端使用 login 欄位接收 email 或 username） */
  login: string;
  /** 密碼 */
  password: string;
  /** 記住我 */
  remember?: boolean;
}

/**
 * 角色資訊介面
 */
interface UserRoleInfo {
  id: number;
  name: string;
  display_name: string;
  permissions: string[];
}

/**
 * API 回應的 Store 介面（簡化版）
 */
interface ApiStore {
  id: number;
  name: string;
  code: string;
}

/**
 * 簡化的 User 介面（來自後端 API）
 */
interface ApiUser {
  id: number;
  username: string;
  email: string;
  first_name: string | null;
  last_name: string | null;
  full_name: string;
  display_name: string;
  role: string;
  status: {
    value: string;
    label: string;
  };
  store_id: number;
  permissions: string[];
  roles: UserRoleInfo[];
  store: ApiStore;
  profile: {
    avatar_url?: string;
    phone?: string;
    last_login_at?: string;
    last_login_ip?: string;
  };
  security: {
    has_2fa: boolean;
    requires_2fa: boolean;
    last_password_change?: string;
  };
  // 添加其他必需的 User 欄位
  phone: string | null;
  avatar_url: string | null;
  timezone: string | null;
  locale: string;
  email_verified_at: string | null;
  two_factor_enabled: boolean;
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
  created_by: number | null;
  updated_by: number | null;
}

/**
 * 登入回應介面
 * 定義後端返回的完整使用者資訊結構
 */
interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    /** 認證 Token */
    token: string;
    /** 使用者完整資訊（包含角色權限） */
    user: ApiUser;
  };
}

/**
 * 轉換 API User 為前端 User 型別
 */
function convertApiUserToUser(apiUser: ApiUser): User {
  // 轉換 API Store 為前端 Store 型別
  const store: Store = {
    id: apiUser.store.id,
    name: apiUser.store.name,
    code: apiUser.store.code,
    status: true, // 預設為 active
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  };

  return {
    id: apiUser.id,
    email: apiUser.email,
    username: apiUser.username,
    first_name: apiUser.first_name,
    last_name: apiUser.last_name,
    full_name: apiUser.full_name,
    display_name: apiUser.display_name,
    role: apiUser.role as any, // 簡化型別轉換
    permissions: apiUser.permissions,
    status: apiUser.status.value as any, // 簡化型別轉換
    email_verified_at: apiUser.email_verified_at,
    two_factor_enabled: apiUser.two_factor_enabled,
    last_login_at: apiUser.last_login_at,
    store_id: apiUser.store_id,
    store: store,
    phone: apiUser.phone,
    avatar_url: apiUser.avatar_url,
    timezone: apiUser.timezone,
    locale: apiUser.locale,
    created_at: apiUser.created_at,
    updated_at: apiUser.updated_at,
    created_by: apiUser.created_by,
    updated_by: apiUser.updated_by
  };
}

/**
 * API 呼叫（使用 openapi 客戶端和 CSRF 保護）
 * 自動處理 CSRF token 和錯誤重試
 */
async function loginApiCall(request: BackendLoginRequest): Promise<LoginResponse> {
  console.log('🔐 [loginApiCall] 使用 openapi 客戶端進行登入...');
  
  return await safeApiCall(async () => {
    const response = await openapi.POST('/api/auth/login', {
      body: request as any  // 暫時使用 any 解決型別不匹配問題
    });

    if (response.error) {
      console.error('❌ [loginApiCall] API 錯誤:', response.error);
      throw new Error(response.error.message || '登入失敗');
    }

    if (!response.data) {
      throw new Error('API 回應缺少資料');
    }

    console.log('✅ [loginApiCall] API 成功');
    return response.data as LoginResponse;
  });
}

/**
 * 登入 Hook
 * 
 * 提供使用者登入功能，包括：
 * - API 呼叫和錯誤處理
 * - AuthStore 狀態更新
 * - 權限資訊同步
 * - 自動頁面導航
 * 
 * @returns 包含 mutate（登入函數）、isLoading、error 等狀態的物件
 * 
 * @example
 * ```typescript
 * const LoginPage = () => {
 *   const { mutate: login, isLoading, error } = useLogin();
 *   
 *   const handleSubmit = (data: LoginCredentials) => {
 *     login(data);
 *   };
 *   
 *   return (
 *     <form onSubmit={handleSubmit}>
 *       // 表單內容
 *     </form>
 *   );
 * };
 * ```
 */
export const useLogin = () => {
  const navigate = useNavigate();
  const { setUser, setToken, setPermissions, setRoles, logout } = useAuthStore();

  return useMutation({
    /**
     * 執行登入 API 呼叫
     * 
     * @param credentials 登入憑證
     * @returns 登入回應
     */
    mutationFn: async (credentials: LoginCredentials): Promise<LoginResponse> => {
      try {
        console.log('🔐 開始登入流程', { 
          email: credentials.email,
          remember: credentials.remember || false
        });

        // 將前端格式轉換為後端期望的格式 (實際的後端格式)
        const backendRequest: BackendLoginRequest = {
          login: credentials.email,     // 後端使用 login 欄位接收 email
          password: credentials.password,
          remember: credentials.remember || false
        };

        console.log('🔄 轉換後的登入資料', { login: backendRequest.login });

        // 呼叫後端登入 API
        const result = await loginApiCall(backendRequest);

        console.log('✅ 登入 API 成功', {
          user_id: result.data?.user?.id,
          username: result.data?.user?.username,
          roles_count: result.data?.user?.roles?.length || 0,
          permissions_count: result.data?.user?.permissions?.length || 0,
          store_id: result.data?.user?.store_id
        });

        return result;
      } catch (error) {
        console.error('❌ 登入過程發生錯誤', error);
        
        // 處理不同類型的錯誤
        if (error instanceof Error) {
          throw error;
        }
        
        throw new Error('登入過程發生未知錯誤，請稍後再試');
      }
    },

    /**
     * 登入成功後的處理
     * 
     * @param response 登入成功的回應資料
     * @param variables 原始登入參數
     */
    onSuccess: (response: LoginResponse, variables: LoginCredentials) => {
      try {
        console.log('🎉 登入成功，開始更新 AuthStore');

        const { token, user: apiUser } = response.data;

        // 轉換 API User 為前端 User 型別
        const user = convertApiUserToUser(apiUser);

        // 儲存 Token 到 localStorage（用於 API 認證）
        localStorage.setItem('auth_token', token);
        console.log('💾 Token 已儲存到 localStorage');

        // 更新 AuthStore 狀態
        setToken(token);
        setUser(user);
        
        // 設置角色資訊（轉換為字串陣列）
        if (apiUser.roles && Array.isArray(apiUser.roles)) {
          const roleNames = apiUser.roles.map(role => role.name);
          setRoles(roleNames);
        }

        // 設置權限資訊（合併所有角色的權限）
        const allPermissions = Array.from(new Set([
          ...(apiUser.permissions || []),
          ...(apiUser.roles?.flatMap(role => role.permissions || []) || [])
        ]));
        setPermissions(allPermissions);

        console.log('✅ AuthStore 更新完成', {
          user_id: user.id,
          username: user.username,
          roles: apiUser.roles?.map(r => r.name) || [],
          permissions_count: allPermissions.length,
          store_id: user.store_id,
          store_name: user.store?.name || 'Unknown'
        });

        // 成功提示
        console.log(`🎊 歡迎回來，${user.display_name}！`);

        // 自動導航到儀表板
        const redirectTo = new URLSearchParams(window.location.search).get('redirect') || '/dashboard';
        console.log('🧭 準備導航到', redirectTo);
        
        navigate(redirectTo, { replace: true });

      } catch (error) {
        console.error('❌ 登入成功後處理失敗', error);
        
        // 清理可能的錯誤狀態
        logout();
        localStorage.removeItem('auth_token');
        
        throw new Error('登入成功但狀態更新失敗，請重新登入');
      }
    },

    /**
     * 登入失敗後的處理
     * 
     * @param error 錯誤物件
     * @param variables 原始登入參數
     */
    onError: (error: Error, variables: LoginCredentials) => {
      console.error('❌ 登入失敗', {
        error: error.message,
        email: variables.email,
        timestamp: new Date().toISOString()
      });

      // 清理認證狀態
      logout();
      localStorage.removeItem('auth_token');

      // 根據錯誤類型顯示不同的使用者友好訊息
      let userMessage = '登入失敗，請檢查您的帳號和密碼';
      
      if (error.message.includes('401') || error.message.includes('Unauthorized')) {
        userMessage = '帳號或密碼錯誤，請檢查您的登入資訊';
      } else if (error.message.includes('429')) {
        userMessage = '登入嘗試次數過多，請稍後再試';
      } else if (error.message.includes('423')) {
        userMessage = '帳號已被鎖定，請聯繫管理員';
      } else if (error.message.includes('network') || error.message.includes('fetch')) {
        userMessage = '網路連線異常，請檢查您的網路設定';
      }

      console.log('🚨 顯示錯誤訊息給使用者', userMessage);
      
      // 這裡整合 Toast 通知系統
      // toast.error(userMessage);
    },

    /**
     * 請求完成後的清理（無論成功或失敗）
     */
    onSettled: () => {
      console.log('🏁 登入請求完成');
    }
  });
}; 