/**
 * 認證狀態管理 Store
 * 使用 Zustand 實現輕量級狀態管理
 */
import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { User, UserRole } from '@/types/user';

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
  initialize: () => void;
  login: (user: User, token: string, permissions?: string[]) => void;
  
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
      // 初始狀態
      user: null,
      token: null,
      permissions: [],
      roles: [],
      isAuthenticated: false,
      isLoading: false,

      // Actions
      setUser: (user: User) => 
        set({ 
          user, 
          isAuthenticated: true 
        }),

      setToken: (token: string) => {
        set({ token });
        // 同步到 localStorage 供 API 中間件使用
        localStorage.setItem('auth_token', token);
      },

      setPermissions: (permissions: string[]) => 
        set({ permissions }),

      setRoles: (roles: string[]) => 
        set({ roles }),

      setLoading: (isLoading: boolean) => 
        set({ isLoading }),

      logout: () => {
        set({
          user: null,
          token: null,
          permissions: [],
          roles: [],
          isAuthenticated: false,
          isLoading: false,
        });
        localStorage.removeItem('auth_token');
      },

      /**
       * 初始化認證狀態
       */
      initialize: () => {
        const token = localStorage.getItem('auth_token');
        if (token && !AuthUtils.isTokenExpired(token)) {
          get().setToken(token);
          // 這裡可以調用 API 獲取使用者資訊
          // const user = await getUserInfo();
          // get().setUser(user);
        } else if (token) {
          // Token 過期，清除它
          get().logout();
        }
      },

      /**
       * 登入
       */
      login: (user: User, token: string, permissions: string[] = []) => {
        get().setUser(user);
        get().setToken(token);
        get().setPermissions(permissions);
        get().setRoles([user.role]);
      },

      // 權限檢查方法
      hasPermission: (permission: string): boolean => {
        const { permissions, user } = get();
        
        // 系統管理員擁有所有權限
        if (user?.role === 'admin') {
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
        
        // 檢查主要角色
        if (user?.role === role) {
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

      // 特殊權限檢查
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
    }),
    {
      name: 'auth-storage',
      // 只持久化必要的資料
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        permissions: state.permissions,
        roles: state.roles,
        isAuthenticated: state.isAuthenticated,
      }),
      // 版本控制
      version: 1,
    }
  )
);

/**
 * 權限相關的輔助函數
 */
export const AuthUtils = {
  /**
   * 檢查角色層級
   */
  hasRoleLevel(userRole: UserRole, requiredRole: UserRole): boolean {
    const userLevel = ROLE_HIERARCHY[userRole] || 0;
    const requiredLevel = ROLE_HIERARCHY[requiredRole] || 0;
    return userLevel >= requiredLevel;
  },

  /**
   * 格式化權限名稱
   */
  formatPermissionName(permission: string): string {
    return permission
      .split('.')
      .map(part => part.charAt(0).toUpperCase() + part.slice(1))
      .join(' ');
  },

  /**
   * 從 JWT Token 解析使用者資訊 (簡化版)
   */
  parseTokenPayload(token: string): any | null {
    try {
      const base64Url = token.split('.')[1];
      const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
      const jsonPayload = decodeURIComponent(
        window.atob(base64)
          .split('')
          .map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
          .join('')
      );
      return JSON.parse(jsonPayload);
    } catch (error) {
      console.error('Token 解析失敗:', error);
      return null;
    }
  },

  /**
   * 檢查 Token 是否過期
   */
  isTokenExpired(token: string): boolean {
    const payload = this.parseTokenPayload(token);
    if (!payload || !payload.exp) {
      return true;
    }
    return Date.now() >= payload.exp * 1000;
  },
};

/**
 * 注意：useAuth hook 已移除，請直接使用 useAuthStore()
 * 
 * 修復說明：
 * - 舊版本的 useAuth hook 在每次渲染時都會重新創建 initialize 和 login 方法
 * - 這導致 App.tsx 中的 useEffect 無限觸發，造成 "Maximum update depth exceeded" 錯誤
 * - 現在所有 initialize、login 等方法都直接在 Zustand store 中定義
 * - 請使用 useAuthStore() 或 useAuthStore((state) => state.methodName) 來存取
 */ 