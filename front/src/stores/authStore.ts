/**
 * 認證狀態管理 Store
 * 使用 Zustand 實現輕量級狀態管理
 * 
 * V6.8 版本更新：
 * - 🚀 簡化 roles 和 permissions 資料來源處理
 * - 🔒 直接從 user 物件中獲取 roles 字串陣列
 * - ⚡ 移除陳舊的角色轉換邏輯
 * - 🛡️ 統一權限和角色的資料處理
 */
import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { User, UserRole, UserStatus } from '@/types/user';
import { openapi, safeApiCall } from '@/lib/openapi-client';

/**
 * 認證狀態介面
 */
interface AuthState {
  // 基礎狀態
  user: User | null;
  token: string | null;
  permissions: string[];
  roles: string[];
  isAuthenticated: boolean;
  isLoading: boolean;

  // Actions
  setUser: (user: User) => void;
  setToken: (token: string) => void;
  setPermissions: (permissions: string[]) => void;
  setRoles: (roles: string[]) => void;
  setLoading: (loading: boolean) => void;
  logout: () => void;
  initialize: () => Promise<void>;
  login: (user: User, token: string) => void;
  fetchUserInfo: () => Promise<void>;
  
  // 權限檢查方法
  hasPermission: (permission: string) => boolean;
  hasAnyPermission: (permissions: string[]) => boolean;
  hasAllPermissions: (permissions: string[]) => boolean;
  hasRole: (role: UserRole) => boolean;
  hasAnyRole: (roles: UserRole[]) => boolean;
  canAccessStore: (storeId: number) => boolean;
  
  // 特殊權限檢查
  isAdmin: () => boolean;
  isStoreAdmin: () => boolean;
  canManageUsers: () => boolean;
  canViewUsers: () => boolean;
  canCreateUsers: () => boolean;
  canUpdateUsers: () => boolean;
  canDeleteUsers: () => boolean;
  
  // 輔助方法
  requires2FA: () => boolean;
  isFullyAuthenticated: () => boolean;
}

/**
 * 權限層級定義
 */
const ROLE_HIERARCHY: Record<UserRole, number> = {
  admin: 100,
  store_admin: 80,
  manager: 60,
  staff: 40,
  guest: 20,
};

/**
 * 建立認證 Store
 */
export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      // 初始狀態 - V6.7 優化：嚴格的初始狀態
      user: null,
      token: null,
      permissions: [],
      roles: [],
      isAuthenticated: false,
      isLoading: true,

      // Actions
      setUser: (user: User) => {
        const token = get().token;
        set({ 
          user, 
          isAuthenticated: !!(token && user),
          isLoading: false,
        });
      },

      setToken: (token: string) => {
        const user = get().user;
        set({ 
          token,
          isAuthenticated: !!(token && user),
        });
        // 同步到 localStorage 供 API 中間件使用
        try {
          localStorage.setItem('auth_token', token);
        } catch (error) {
          // 靜默處理 localStorage 錯誤
        }
      },

      setPermissions: (permissions: string[]) => 
        set({ permissions }),

      setRoles: (roles: string[]) => 
        set({ roles }),

      setLoading: (isLoading: boolean) => {
        set({ isLoading });
      },

      /**
       * 登出方法
       * V6.7 確保完整清理：包含權限清空
       */
      logout: () => {
        try {
          set({
            user: null,
            token: null,
            permissions: [], // ✅ 確保權限也被清空
            roles: [],
            isAuthenticated: false,
            isLoading: false,
          });
          localStorage.removeItem('auth_token');
        } catch (error) {
          // 靜默處理登出錯誤
          // 即使發生錯誤也要清除狀態
          set({
            user: null,
            token: null,
            permissions: [], // ✅ 確保權限也被清空
            roles: [],
            isAuthenticated: false,
            isLoading: false,
          });
        }
      },

      /**
       * 獲取用戶資訊
       */
      fetchUserInfo: async () => {
        try {
          const result = await safeApiCall(() => openapi.GET('/api/auth/me' as any, {}));

          if (result && result.data) {
            const user = result.data.user || result.data;
            const permissions = user.permissions || [];
            const roles = user.roles || [];

            set({
              user,
              token: get().token, // 保持現有 token
              permissions,
              roles,
              isAuthenticated: true,
            });
          } else {
            throw new Error('API 回應中缺少有效的 data 物件。');
          }
        } catch (error) {
          get().logout(); // 在這裡先執行登出清理
          throw error; // 向上拋出錯誤
        }
      },

      /**
       * 初始化認證狀態
       */
      initialize: async () => {
        set({ isLoading: true });
        const token = localStorage.getItem('auth_token');

        if (!token) {
          set({ isLoading: false, isAuthenticated: false });
          return;
        }

        try {
          await get().fetchUserInfo();
        } catch (error) {
          // 靜默處理初始化錯誤
        } finally {
          // 無論成功或失敗，都確保關閉 loading 狀態
          set({ isLoading: false });
        }
      },

      /**
       * 登入方法
       * 直接從 user 物件中獲取 roles 和 permissions
       */
      login: (user: User, token: string) => {
        try {
          // 一次性同步所有狀態，包含從 login API 獲取的權限
          set({
            user,
            token,
            permissions: user.permissions || [],
            roles: user.roles || [user.role],
            isAuthenticated: true,
            isLoading: false
          });
          
          // 同步到 localStorage
          localStorage.setItem('auth_token', token);
          
        } catch (error) {
          throw error; // 重新拋出錯誤讓調用方處理
        }
      },

      // 權限檢查方法 - V6.7 安全加固：添加用戶存在檢查
      hasPermission: (permission: string): boolean => {
        const { permissions, user } = get();
        
        // 🔒 安全檢查：必須有用戶才能檢查權限
        if (!user) {
          return false;
        }
        
        // 系統管理員擁有所有權限
        if (user.role === 'admin') {
          return true;
        }
        
        return permissions.includes(permission);
      },

      hasAnyPermission: (permissionList: string[]): boolean => {
        const { hasPermission } = get();
        return permissionList.some(permission => hasPermission(permission));
      },

      hasAllPermissions: (permissionList: string[]): boolean => {
        const { hasPermission } = get();
        return permissionList.every(permission => hasPermission(permission));
      },

      hasRole: (role: UserRole): boolean => {
        const { user, roles } = get();
        
        // 🔒 安全檢查：必須有用戶才能檢查角色
        if (!user) {
          return false;
        }
        
        // 檢查主要角色
        if (user.role === role) {
          return true;
        }
        
        // 檢查額外角色
        return roles.includes(role);
      },

      hasAnyRole: (roleList: UserRole[]): boolean => {
        const { hasRole } = get();
        return roleList.some(role => hasRole(role));
      },

      canAccessStore: (storeId: number): boolean => {
        const { user } = get();
        
        if (!user) {
          return false;
        }
        
        // 系統管理員可以存取所有門市
        if (user.role === 'admin') {
          return true;
        }
        
        // 其他角色只能存取自己的門市
        return user.store_id === storeId;
      },

      // 特殊權限檢查 - V6.7 安全加固：添加用戶存在檢查
      isAdmin: (): boolean => {
        const { user } = get();
        return user?.role === 'admin';
      },

      isStoreAdmin: (): boolean => {
        const { user } = get();
        return user?.role === 'store_admin' || user?.role === 'admin';
      },

      canManageUsers: (): boolean => {
        const { hasPermission, isAdmin, isStoreAdmin } = get();
        return isAdmin() || isStoreAdmin() || hasPermission('users.manage');
      },

      canViewUsers: (): boolean => {
        const { hasPermission } = get();
        return hasPermission('users.view');
      },

      canCreateUsers: (): boolean => {
        const { hasPermission } = get();
        return hasPermission('users.create');
      },

      canUpdateUsers: (): boolean => {
        const { hasPermission } = get();
        return hasPermission('users.update');
      },

      canDeleteUsers: (): boolean => {
        const { hasPermission } = get();
        return hasPermission('users.delete');
      },

      /**
       * 檢查是否需要 2FA
       */
      requires2FA: (): boolean => {
        const { user } = get();
        return user?.two_factor_enabled === true;
      },

      /**
       * 完整認證檢查
       * V6.7 新增：檢查是否完全認證（有 token 和 user）
       */
      isFullyAuthenticated: (): boolean => {
        const { user, token, isAuthenticated } = get();
        return !!(user && token && isAuthenticated);
      },
    }),
    {
      name: 'auth-storage',
      // V6.7 修復：持久化必要狀態
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        permissions: state.permissions, // ✅ 持久化權限狀態
        roles: state.roles,
        // 🔒 注意：不持久化 isAuthenticated，由 initialize() 重新計算
      }),
      version: 7, // 🔧 升級版本號至 V6.7
      // 狀態恢復後的安全檢查
      onRehydrateStorage: () => (state, error) => {
        if (error) {
          // 靜默處理恢復錯誤
          return;
        }
        
        // 狀態恢復成功，但不需要額外日誌
      },
    }
  )
);

/**
 * 認證工具類別
 * V6.7 保持不變：提供認證相關的工具方法
 */
class AuthUtils {
  /**
   * 檢查使用者是否具有指定角色層級
   */
  static hasRoleLevel(userRole: UserRole, requiredRole: UserRole): boolean {
    const userLevel = ROLE_HIERARCHY[userRole] || 0;
    const requiredLevel = ROLE_HIERARCHY[requiredRole] || 0;
    return userLevel >= requiredLevel;
  }

  /**
   * 格式化權限名稱為可讀格式
   */
  static formatPermissionName(permission: string): string {
    return permission
      .split('.')
      .map(part => part.charAt(0).toUpperCase() + part.slice(1))
      .join(' ');
  }

  /**
   * 檢查 token 是否為有效的 Laravel Sanctum Personal Access Token 格式
   * V6.4 新增：支援 Laravel Sanctum token 格式驗證
   */
  static isSanctumTokenValid(token: string): boolean {
    if (!token || typeof token !== 'string') {
      return false;
    }
    
    // Laravel Sanctum Personal Access Token 格式: {id}|{token}
    // 例如: 14|lnVyGoBId6o2ViqYeJMuJDHhexLEHCCPW7RP4DcL
    const sanctumPattern = /^\d+\|[a-zA-Z0-9]+$/;
    
    return sanctumPattern.test(token);
  }

  /**
   * 解析 JWT Token payload (現僅用於 JWT token)
   * 注意：Laravel Sanctum token 不使用 JWT 格式，此方法僅供未來擴展使用
   */
  static parseTokenPayload(token: string): any | null {
    try {
      // JWT 格式檢查
      const parts = token.split('.');
      if (parts.length !== 3) {
        return null;
      }
      
      const payload = parts[1];
      const decoded = atob(payload.replace(/-/g, '+').replace(/_/g, '/'));
      return JSON.parse(decoded);
    } catch (error) {
      return null;
    }
  }

  /**
   * 檢查 JWT Token 是否已過期
   * 注意：Laravel Sanctum token 過期由後端管理，此方法僅供 JWT token 使用
   */
  static isJwtTokenExpired(token: string): boolean {
    const payload = AuthUtils.parseTokenPayload(token);
    if (!payload || !payload.exp) {
      return true;
    }
    
    const currentTime = Math.floor(Date.now() / 1000);
    return payload.exp < currentTime;
  }

  /**
   * 通用 token 過期檢查
   * V6.4 優化：針對不同 token 類型使用不同的驗證邏輯
   */
  static isTokenExpired(token: string): boolean {
    if (!token) {
      return true;
    }
    
    // Laravel Sanctum token 永不在前端判斷過期，由後端管理
    if (AuthUtils.isSanctumTokenValid(token)) {
      return false; // Sanctum token 視為永不過期
    }
    
    // JWT token 過期檢查
    return AuthUtils.isJwtTokenExpired(token);
  }
}

// 匯出工具類別供外部使用
export { AuthUtils };

/**
 * 匯出 Store Hook
 */
export default useAuthStore; 