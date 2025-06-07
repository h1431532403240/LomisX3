/**
 * 使用者登入 Hook
 * 
 * V5.1.0 版本：完整權限系統整合
 * 提供型別安全的登入功能，與後端 API 完美整合
 * 
 * ⚡ 特色功能：
 * - 🔒 完整錯誤處理和重試機制
 * - 📊 詳細的登入流程日誌
 * - 🔑 自動 Bearer Token 處理
 * - 🚀 型別安全的 API 呼叫
 * - 👤 完整的使用者權限同步
 * 
 * @author LomisX3 開發團隊
 * @version 5.1.0
 */

import { useMutation } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
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
  name: string;  // API 實際回應中的顯示名稱欄位
  display_name: string;
  role: string;
  status: {
    value: string;
    label: string;
  };
  store_id: number;
  permissions: string[];
  roles: UserRoleInfo[] | string[]; // 支援兩種格式：物件陣列或字串陣列
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
  // 檢查必要欄位
  if (!apiUser) {
    throw new Error('無法轉換空的用戶資料');
  }

  if (!apiUser.id) {
    throw new Error('用戶資料缺少 ID 欄位');
  }

  if (!apiUser.store) {
    throw new Error('用戶資料缺少門市資訊');
  }

  // 轉換 API Store 為前端 Store 型別
  const store: Store = {
    id: apiUser.store.id,
    name: apiUser.store.name,
    code: apiUser.store.code,
    status: true, // 預設為 active
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  };

  // 📝 角色資料處理 - 支援兩種格式：字串陣列或物件陣列
  let extractedRole: string | undefined;
  let extractedRoles: string[] = [];
  let rolePermissions: string[] = [];

  if (Array.isArray(apiUser.roles)) {
    // 檢查是字串陣列還是物件陣列
    if (apiUser.roles.length > 0) {
      if (typeof apiUser.roles[0] === 'string') {
        // 字串陣列格式：['admin', 'manager']
        extractedRoles = apiUser.roles as string[];
        extractedRole = extractedRoles[0]; // 使用第一個角色作為主要角色
      } else if (typeof apiUser.roles[0] === 'object') {
        // 物件陣列格式：[{name: 'admin', permissions: [...]}]
        const objectRoles = apiUser.roles as UserRoleInfo[];
        extractedRoles = objectRoles.map(role => role.name);
        extractedRole = extractedRoles[0];
        // 合併所有角色的權限
        rolePermissions = objectRoles.flatMap(role => role.permissions || []);
      }
    }
  }

  // 如果還是沒有角色，嘗試從 apiUser.role 取得
  if (!extractedRole && apiUser.role) {
    extractedRole = apiUser.role;
    extractedRoles = [apiUser.role];
  }



  // 構建最終的 User 物件
  const result: User = {
    id: apiUser.id,
    email: apiUser.email,
    username: apiUser.username,
    first_name: apiUser.first_name,
    last_name: apiUser.last_name,
    full_name: apiUser.full_name,
    display_name: apiUser.name || apiUser.display_name || apiUser.full_name || apiUser.username,
    role: extractedRole as any, // 使用提取的主要角色
    roles: extractedRoles, // 使用提取的角色陣列
    permissions: Array.from(new Set([
      ...(apiUser.permissions || []),
      ...rolePermissions // 使用預處理的角色權限
    ])), // 智能合併權限：來自使用者直接權限和角色權限
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

  return result;
}

/**
         * API 呼叫（使用 openapi 客戶端和 Bearer Token 認證）
        * 自動處理 Bearer Token 和錯誤重試
 */
async function loginApiCall(request: BackendLoginRequest): Promise<LoginResponse> {
  return await safeApiCall(async () => {
    const response = await openapi.POST('/api/auth/login', {
      body: request as any  // 暫時使用 any 解決型別不匹配問題
    });

    if (response.error) {
      throw new Error(response.error.message || '登入失敗');
    }

    if (!response.data) {
      throw new Error('API 回應缺少資料');
    }

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
  const { logout } = useAuthStore();

  return useMutation({
    /**
     * 執行登入 API 呼叫
     * 
     * @param credentials 登入憑證
     * @returns 登入回應
     */
    mutationFn: async (credentials: LoginCredentials): Promise<LoginResponse> => {
      try {
        // 將前端格式轉換為後端期望的格式
        const backendRequest: BackendLoginRequest = {
          login: credentials.email,     // 後端使用 login 欄位接收 email
          password: credentials.password,
          remember: credentials.remember || false
        };

        // 呼叫後端登入 API
        const result = await loginApiCall(backendRequest);

        return result;
      } catch (error) {
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
      if (response && response.data) {
        const { user: apiUser, token } = response.data;

        if (apiUser && token) {
          // 轉換 API User 為前端 User 格式
          const convertedUser = convertApiUserToUser(apiUser);

          // 檢查轉換後的 user 是否有效
          if (convertedUser) {
            // 更新認證狀態
            useAuthStore.getState().login(convertedUser, token);

            // 顯示歡迎訊息
            toast.success(`歡迎回來，${convertedUser.display_name || convertedUser.username}！`);

            // 自動導航到儀表板
            const redirectTo = new URLSearchParams(window.location.search).get('redirect') || '/dashboard';
            navigate(redirectTo, { replace: true });

          } else {
            logout();
            localStorage.removeItem('auth_token');
            throw new Error('用戶資料轉換失敗，請重新登入');
          }
        } else {
          logout();
          localStorage.removeItem('auth_token');
          throw new Error('登入成功但回應資料不完整，請重新登入');
        }
      } else {
        logout();
        localStorage.removeItem('auth_token');
        throw new Error('登入成功但 API 回應格式不正確，請重新登入');
      }
    },

    /**
     * 登入失敗後的處理
     * 
     * @param error 錯誤物件
     * @param variables 原始登入參數
     */
    onError: (error: Error, variables: LoginCredentials) => {
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
      
      // 顯示錯誤訊息
      toast.error(userMessage);
    }
  });
}; 