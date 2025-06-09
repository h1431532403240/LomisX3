/**
 * 權限守衛組件
 * 用於條件性渲染需要權限檢查的內容
 */
import React from 'react';
import { useAuthStore } from '@/stores/authStore';
import type { UserRole } from '@/types/user';

/**
 * 權限守衛屬性介面
 */
interface PermissionGuardProps {
  children: React.ReactNode;
  fallback?: React.ReactNode;
  
  // 權限檢查選項（至少需要一個）
  permission?: string;
  permissions?: string[];
  anyPermissions?: string[];
  allPermissions?: string[];
  
  // 角色檢查選項
  role?: UserRole;
  roles?: UserRole[];
  anyRoles?: UserRole[];
  
  // 門市檢查選項
  storeId?: number;
  
  // 自訂檢查函數
  when?: () => boolean;
  
  // 反向檢查（當條件不滿足時才顯示）
  unless?: boolean;
}

/**
 * 權限守衛組件
 */
export const PermissionGuard: React.FC<PermissionGuardProps> = ({
  children,
  fallback = null,
  permission,
  permissions = [],
  anyPermissions = [],
  allPermissions = [],
  role,
  roles = [],
  anyRoles = [],
  storeId,
  when,
  unless = false,
}) => {
  const {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    canAccessStore,
    isAuthenticated,
  } = useAuthStore();

  /**
   * 檢查是否滿足權限條件
   */
  const checkPermissions = (): boolean => {
    // 必須已登入
    if (!isAuthenticated) {
      return false;
    }

    // 自訂檢查函數優先
    if (when) {
      return when();
    }

    let hasRequiredPermissions = true;

    // 單一權限檢查
    if (permission) {
      hasRequiredPermissions = hasRequiredPermissions && hasPermission(permission);
    }

    // 必須擁有所有權限
    if (permissions.length > 0) {
      hasRequiredPermissions = hasRequiredPermissions && hasAllPermissions(permissions);
    }

    // 必須擁有任一權限
    if (anyPermissions.length > 0) {
      hasRequiredPermissions = hasRequiredPermissions && hasAnyPermission(anyPermissions);
    }

    // 必須擁有所有指定權限
    if (allPermissions.length > 0) {
      hasRequiredPermissions = hasRequiredPermissions && hasAllPermissions(allPermissions);
    }

    // 單一角色檢查
    if (role) {
      hasRequiredPermissions = hasRequiredPermissions && hasRole(role);
    }

    // 必須擁有所有角色
    if (roles.length > 0) {
      hasRequiredPermissions = hasRequiredPermissions && roles.every(r => hasRole(r));
    }

    // 必須擁有任一角色
    if (anyRoles.length > 0) {
      hasRequiredPermissions = hasRequiredPermissions && hasAnyRole(anyRoles);
    }

    // 門市存取檢查
    if (storeId !== undefined) {
      hasRequiredPermissions = hasRequiredPermissions && canAccessStore(storeId);
    }

    return hasRequiredPermissions;
  };

  const shouldRender = unless ? !checkPermissions() : checkPermissions();

  return shouldRender ? <>{children}</> : <>{fallback}</>;
};

/**
 * 高階組件：為組件添加權限檢查
 */
export function withPermissionGuard<P extends object>(
  Component: React.ComponentType<P>,
  guardProps: Omit<PermissionGuardProps, 'children' | 'fallback'>
) {
  return function GuardedComponent(props: P) {
    return (
      <PermissionGuard {...guardProps}>
        <Component {...props} />
      </PermissionGuard>
    );
  };
}

/**
 * Hook：權限組件檢查（內部使用）
 * 注意：此 hook 專門為 PermissionGuard 組件設計，提供內部權限檢查邏輯
 */
export const usePermissionGuardCheck = () => {
  const store = useAuthStore();

  return {
    /**
     * 檢查是否有權限
     */
    checkPermission: (permission: string): boolean => {
      return store.isAuthenticated && store.hasPermission(permission);
    },

    /**
     * 檢查是否有任一權限
     */
    checkAnyPermission: (permissions: string[]): boolean => {
      return store.isAuthenticated && store.hasAnyPermission(permissions);
    },

    /**
     * 檢查是否有所有權限
     */
    checkAllPermissions: (permissions: string[]): boolean => {
      return store.isAuthenticated && store.hasAllPermissions(permissions);
    },

    /**
     * 檢查是否有角色
     */
    checkRole: (role: UserRole): boolean => {
      return store.isAuthenticated && store.hasRole(role);
    },

    /**
     * 檢查是否有任一角色
     */
    checkAnyRole: (roles: UserRole[]): boolean => {
      return store.isAuthenticated && store.hasAnyRole(roles);
    },

    /**
     * 檢查是否可存取門市
     */
    checkStoreAccess: (storeId: number): boolean => {
      return store.isAuthenticated && store.canAccessStore(storeId);
    },

    /**
     * 檢查是否為管理員
     */
    isAdmin: (): boolean => {
      return store.isAuthenticated && store.isAdmin();
    },

    /**
     * 檢查是否為門市管理員
     */
    isStoreAdmin: (): boolean => {
      return store.isAuthenticated && store.isStoreAdmin();
    },

    /**
     * 檢查是否可管理使用者
     */
    canManageUsers: (): boolean => {
      return store.isAuthenticated && store.canManageUsers();
    },
  };
};

/**
 * 權限相關的實用組件
 */

/**
 * 僅管理員可見組件
 */
export const AdminOnly: React.FC<{ children: React.ReactNode; fallback?: React.ReactNode }> = ({
  children,
  fallback,
}) => (
  <PermissionGuard role="admin" fallback={fallback}>
    {children}
  </PermissionGuard>
);

/**
 * 僅門市管理員可見組件
 */
export const StoreAdminOnly: React.FC<{ children: React.ReactNode; fallback?: React.ReactNode }> = ({
  children,
  fallback,
}) => (
  <PermissionGuard anyRoles={['admin', 'store_admin']} fallback={fallback}>
    {children}
  </PermissionGuard>
);

/**
 * 使用者管理權限組件
 */
export const UserManagementOnly: React.FC<{ children: React.ReactNode; fallback?: React.ReactNode }> = ({
  children,
  fallback,
}) => (
  <PermissionGuard 
    anyPermissions={['users.manage', 'users.create', 'users.update', 'users.delete']}
    fallback={fallback}
  >
    {children}
  </PermissionGuard>
);

/**
 * 門市限制組件
 */
export const StoreRestricted: React.FC<{ 
  storeId: number; 
  children: React.ReactNode; 
  fallback?: React.ReactNode 
}> = ({ storeId, children, fallback }) => (
  <PermissionGuard storeId={storeId} fallback={fallback}>
    {children}
  </PermissionGuard>
);

/**
 * 管理員權限守衛
 * 只允許管理員級別以上的使用者存取
 */
export interface AdminGuardProps {
  children: React.ReactNode;
  fallback?: React.ReactNode;
  level?: 'admin'; // 只支援 admin，因為這是最高級別
}

export function AdminGuard({ 
  children, 
  fallback = null, 
  level = 'admin' 
}: AdminGuardProps) {
  // 基於現有的 UserRole 類型：admin 是最高級別
  const roles: UserRole[] = ['admin'];
  
  return (
    <PermissionGuard roles={roles} fallback={fallback}>
      {children}
    </PermissionGuard>
  );
}

/**
 * 功能權限守衛
 * 基於功能模組權限的守衛組件
 */
export interface FeatureGuardProps {
  feature: string;
  action: 'read' | 'create' | 'update' | 'delete';
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

export function FeatureGuard({ 
  feature, 
  action, 
  children, 
  fallback = null 
}: FeatureGuardProps) {
  const permission = `${feature}.${action}`;
  
  return (
    <PermissionGuard permission={permission} fallback={fallback}>
      {children}
    </PermissionGuard>
  );
}

/**
 * 條件權限守衛
 * 提供更靈活的權限檢查邏輯
 */
export interface ConditionalGuardProps {
  condition: () => boolean;
  children: React.ReactNode;
  fallback?: React.ReactNode;
  loading?: React.ReactNode;
}

export function ConditionalGuard({ 
  condition, 
  children, 
  fallback = null,
  loading = null 
}: ConditionalGuardProps) {
  try {
    const hasAccess = condition();
    
    if (hasAccess) {
      return <>{children}</>;
    }
    
    return <>{fallback}</>;
  } catch (error) {
    console.error('權限檢查條件執行失敗:', error);
    return <>{loading || fallback}</>;
  }
}

/**
 * 使用者權限守衛 Hook
 * 提供編程式的權限檢查
 * V6.6 更新：使用 authStore 的動態權限檢查
 */
export function usePermissionGuard() {
  const {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    canAccessStore,
    isAdmin,
    isStoreAdmin,
    canManageUsers,
    canViewUsers,
    canCreateUsers,
    canUpdateUsers,
    canDeleteUsers,
    isAuthenticated,
    user,
  } = useAuthStore();

  /**
   * 檢查是否有權限存取指定功能
   */
  const canAccess = (feature: string, action: string): boolean => {
    return isAuthenticated && hasPermission(`${feature}.${action}`);
  };

  /**
   * 檢查多個權限中的任一個
   */
  const canAccessAny = (permissions: string[]): boolean => {
    return isAuthenticated && hasAnyPermission(permissions);
  };

  /**
   * 檢查是否擁有所有權限
   */
  const canAccessAll = (permissions: string[]): boolean => {
    return isAuthenticated && hasAllPermissions(permissions);
  };

  return {
    // 編程式權限檢查
    canAccess,
    canAccessAny,
    canAccessAll,
    
    // 基礎權限檢查方法 (從 authStore)
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    canAccessStore,
    
    // 特殊權限檢查方法
    isAdmin,
    isStoreAdmin,
    canManageUsers,
    canViewUsers,
    canCreateUsers,
    canUpdateUsers,
    canDeleteUsers,
    
    // 狀態資訊
    isAuthenticated,
    currentUser: user,
  };
}

export default PermissionGuard; 