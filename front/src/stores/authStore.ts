/**
 * 認證狀態管理 Store
 * 使用 Zustand 實現輕量級狀態管理
 * 
 * V6.7 版本更新：
 * - 🚀 基於 API 回應的動態權限載入
 * - 🔒 移除獨立權限 API 調用
 * - ⚡ 優化權限同步機制
 * - 🛡️ 增強認證狀態邏輯
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
  login: (user: User, token: string, permissions: string[]) => void;
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
        console.log('👤 使用者資訊已設置:', user.display_name);
        console.log('🔒 認證狀態:', !!(token && user));
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
          console.log('🔧 Token 已設置到 localStorage:', token.substring(0, 20) + '...');
        } catch (error) {
          console.error('❌ localStorage 設置失敗:', error);
        }
      },

      setPermissions: (permissions: string[]) => 
        set({ permissions }),

      setRoles: (roles: string[]) => 
        set({ roles }),

      setLoading: (isLoading: boolean) => {
        console.log('⏳ 設置載入狀態:', isLoading);
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
          console.log('🔓 使用者已登出，所有狀態已清除');
        } catch (error) {
          console.error('❌ 登出過程發生錯誤:', error);
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
       * V6.7 重構：從 user 物件中獲取權限，無獨立權限 API 調用
       */
      fetchUserInfo: async () => {
        try {
          console.log('👤 開始獲取用戶資訊...');
          const token = get().token;
          
          if (!token) {
            console.warn('⚠️ 無 token，跳過用戶資訊獲取');
            return;
          }

          // 使用統一的 API 客戶端調用 /api/auth/me
          const result = await safeApiCall(() =>
            openapi.GET('/api/auth/me' as any, {})
          );
          
          if (result.data) {
            const userData = result.data;
            
            // 🔒 修復：需要檢查 API 回應格式，並正確轉換 user 資料
            let user: User;
            let permissions: string[] = [];
            let roles: string[] = [];
            
            if (userData.user) {
              // API 回應格式為 { success: true, data: { user: ApiUser } }
              const apiUser = userData.user;
              console.log('📊 收到 API 使用者資料:', {
                username: apiUser.username,
                role: apiUser.role,
                permissions_count: apiUser.permissions?.length || 0,
                roles_count: apiUser.roles?.length || 0
              });
              
              // 轉換 API User 為前端 User 型別
              user = {
                id: apiUser.id,
                username: apiUser.username,
                email: apiUser.email,
                first_name: apiUser.first_name,
                last_name: apiUser.last_name,
                full_name: apiUser.full_name,
                display_name: apiUser.display_name,
                role: apiUser.role,
                store_id: apiUser.store_id,
                store: apiUser.store,
                status: (apiUser.status?.value || 'active') as UserStatus,
                permissions: [], // 稍後會正確設置
                phone: apiUser.phone,
                avatar_url: apiUser.avatar_url,
                timezone: apiUser.timezone,
                locale: apiUser.locale,
                email_verified_at: apiUser.email_verified_at,
                two_factor_enabled: apiUser.two_factor_enabled,
                last_login_at: apiUser.last_login_at,
                created_at: apiUser.created_at,
                updated_at: apiUser.updated_at,
                created_by: apiUser.created_by,
                updated_by: apiUser.updated_by
              };
              
              // 🚀 V6.7 關鍵修復：從 user 物件中獲取權限
              permissions = Array.from(new Set([
                ...(apiUser.permissions || []),
                ...(apiUser.roles?.flatMap((role: any) => role.permissions || []) || [])
              ]));
              
              roles = apiUser.roles?.map((role: any) => role.name) || [];
              
            } else {
              // 直接的 User 格式
              user = userData as User;
              console.log('📊 收到直接使用者資料:', user.display_name);
              
              // 🚀 V6.7 關鍵修復：從 user 物件的 permissions 屬性獲取權限
              permissions = user.permissions || [];
              roles = [user.role];
              
              // 管理員自動擁有所有權限
              if (user.role === 'admin') {
                permissions = ['*']; // 通配符表示所有權限
              }
            }
            
            console.log('✅ 用戶資訊獲取成功:', user.display_name);
            console.log('🔐 權限同步完成:', {
              roles: roles,
              permissions_count: permissions.length,
              is_admin: user.role === 'admin',
              permissions: permissions.slice(0, 5) // 顯示前 5 個權限
            });
            
            // 🚀 V6.7 關鍵更新：一次性設置所有狀態，包含從 user 獲取的權限
            set({
              user,
              permissions, // ✅ 從 user 物件中獲取的權限
              roles,
              isAuthenticated: true,
              isLoading: false
            });
            
            console.log('🎉 認證狀態完全恢復，權限已從 user 資料同步');
            
          } else {
            throw new Error('API 回應中沒有 data 欄位');
          }
          
        } catch (error) {
          console.error('❌ 獲取用戶資訊失敗:', error);
          console.warn('🔒 Token 可能已失效，執行強制登出保護...');
          
          // 🔒 安全機制：API 失敗時強制登出，防止假登入狀態
          get().logout();
          
          // 重定向到登入頁
          if (typeof window !== 'undefined') {
            window.location.href = '/login';
          }
        }
      },

      /**
       * 初始化認證狀態
       * V6.7 優化：移除獨立權限載入，依賴 fetchUserInfo 獲取完整資料
       */
      initialize: async () => {
        try {
          console.log('🔄 開始初始化認證狀態...');
          
          const localToken = localStorage.getItem('auth_token');
          const state = get();
          
          console.log('📊 初始化狀態檢查:');
          console.log('  - localStorage token:', localToken ? localToken.substring(0, 20) + '...' : 'null');
          console.log('  - persist user:', state.user ? state.user.display_name : 'undefined');
          console.log('  - persist token:', state.token ? state.token.substring(0, 20) + '...' : 'null');
          console.log('  - isAuthenticated:', state.isAuthenticated);
          console.log('  - isLoading:', state.isLoading);
          
          // V6.7 安全修復：檢查 token 是否為 Laravel Sanctum 格式且有效
          if (localToken && AuthUtils.isSanctumTokenValid(localToken)) {
            console.log('✅ localStorage Token 有效 (Laravel Sanctum 格式)');
            
            // 檢查 Zustand persist 中是否有完整的使用者資訊
            if (state.user && state.token === localToken) {
              // 狀態完整，直接恢復認證
              set({ 
                isAuthenticated: true,
                isLoading: false,
                token: localToken
              });
              console.log('✅ 認證狀態完整恢復 - 使用者:', state.user.display_name);
              console.log('✅ 應該保持登入狀態，不會跳轉到登入頁');
              
              // V6.7 優化：如果有完整狀態，不重複調用 API（權限已在狀態中）
              
            } else if (state.user && !state.token) {
              // 有使用者資訊但 token 不同步，補全狀態
              set({
                token: localToken,
                isAuthenticated: true,
                isLoading: false
              });
              console.log('🔄 Token 已補全，認證狀態恢復 - 使用者:', state.user.display_name);
              console.log('✅ 應該保持登入狀態，不會跳轉到登入頁');
              
            } else if (!state.user && localToken) {
              // 🔒 V6.7 安全修復：有效 token 但無使用者資訊，設置臨時狀態並獲取用戶資訊
              console.log('⚠️ 檢測到有效 token 但缺少使用者資訊');
              console.log('🔄 設置臨時狀態，準備獲取使用者資訊...');
              
              set({
                token: localToken,
                isAuthenticated: false,
                isLoading: true
              });
              
              console.log('📡 開始自動獲取使用者資訊（包含權限）...');
              await get().fetchUserInfo(); // ✅ 自動獲取用戶資訊（內部包含權限獲取）
              
            } else {
              // 狀態不一致，清除重新開始
              console.log('⚠️ 認證狀態不一致，清除狀態並重新登入');
              get().logout();
            }
          } else if (localToken) {
            // Token 無效，清除狀態
            console.log('⏰ Token 無效或格式錯誤，清除認證狀態');
            console.log('🔍 Token 格式:', localToken ? localToken.substring(0, 30) + '...' : 'null');
            get().logout();
          } else {
            // 沒有 token，確保未認證狀態
            set({ 
              isAuthenticated: false, 
              isLoading: false 
            });
            console.log('🔓 未找到 Token，設置為未認證狀態');
          }
          
          // 最終狀態檢查
          const finalState = get();
          console.log('🏁 初始化完成，最終狀態:');
          console.log('  - isAuthenticated:', finalState.isAuthenticated);
          console.log('  - isLoading:', finalState.isLoading);
          console.log('  - user:', finalState.user ? finalState.user.display_name : 'null');
          console.log('  - token:', finalState.token ? 'exists' : 'null');
          console.log('  - permissions:', finalState.permissions.length);
          console.log('  - 預期行為:', finalState.isAuthenticated ? '保持登入，不跳轉' : '跳轉到登入頁');
          
        } catch (error) {
          console.error('❌ 認證狀態初始化失敗:', error);
          // 發生錯誤時，設置為安全的未認證狀態
          set({
            user: null,
            token: null,
            permissions: [],
            roles: [],
            isAuthenticated: false,
            isLoading: false,
          });
        }
      },

      /**
       * 登入方法
       * V6.7 重構：直接從 login API 回應中獲取權限
       */
      login: (user: User, token: string, permissions: string[] = []) => {
        try {
          console.log('🔐 開始登入流程 - 使用者:', user.display_name);
          console.log('🔐 權限資料:', {
            count: permissions.length,
            permissions: permissions.slice(0, 5) // 顯示前 5 個權限
          });
          
          // 🚀 V6.7 關鍵更新：一次性同步所有狀態，包含從 login API 獲取的權限
          set({
            user,
            token,
            permissions, // ✅ 直接使用從 login API 回應中獲取的權限
            roles: [user.role],
            isAuthenticated: true,
            isLoading: false
          });
          
          // 同步到 localStorage
          localStorage.setItem('auth_token', token);
          console.log('✅ 登入成功，狀態已同步:', user.display_name);
          console.log('  - Token 已存入 localStorage');
          console.log('  - 狀態已存入 Zustand persist');
          console.log('  - 權限已同步:', permissions.length, '個權限');
          
        } catch (error) {
          console.error('❌ 登入狀態設置失敗:', error);
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
      // V6.7 安全加固：狀態恢復後的安全檢查
      onRehydrateStorage: () => (state, error) => {
        if (error) {
          console.error('❌ Zustand persist 恢復失敗:', error);
          return;
        }
        
        if (state) {
          console.log('💾 Zustand persist 狀態已載入:');
          console.log('  - user:', state.user ? state.user.display_name : 'undefined');
          console.log('  - token:', state.token ? state.token.substring(0, 20) + '...' : 'null');
          console.log('  - permissions:', state.permissions?.length || 0);
          console.log('  - roles:', state.roles?.length || 0);
          console.log('🔄 準備進行認證狀態初始化...');
        }
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
    
    if (sanctumPattern.test(token)) {
      console.log('✅ Token 格式有效: Laravel Sanctum Personal Access Token');
      return true;
    }
    
    console.log('❌ Token 格式無效: 不符合 Laravel Sanctum 格式');
    return false;
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