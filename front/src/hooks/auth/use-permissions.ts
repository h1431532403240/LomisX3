/**
 * 權限檢查 Hook - 遵循單一職責原則
 * 
 * V2.7 架構改進：權限系統職責分離
 * - authStore 專注於認證狀態管理
 * - usePermissions 專注於權限邏輯檢查
 * 
 * 符合 SOLID 原則：
 * - Single Responsibility Principle (SRP)
 * - Separation of Concerns
 * 
 * @version V2.7 - 生產加固版
 * @requires authStore - 認證狀態來源
 */

import { useAuthStore } from '@/stores/authStore';
import type { UserRole } from '@/types/user';

/**
 * 權限檢查 Hook
 * 
 * 提供細粒度的權限檢查功能，與 authStore 分離
 * 所有權限邏輯統一在此處處理
 */
export function usePermissions() {
  const { user, isAuthenticated } = useAuthStore();

  /**
   * 檢查是否擁有特定權限
   * @param permission - 權限字串，格式：module.action
   */
  const hasPermission = (permission: string): boolean => {
    if (!isAuthenticated || !user) return false;
    
    // 管理員擁有所有權限
    if (user.role === 'admin') return true;
    
    // 檢查使用者權限列表
    return user.permissions?.includes(permission) ?? false;
  };

  /**
   * 檢查是否擁有任一權限
   * @param permissions - 權限陣列
   */
  const hasAnyPermission = (permissions: string[]): boolean => {
    if (!isAuthenticated || !user) return false;
    if (user.role === 'admin') return true;
    
    return permissions.some(permission => 
      user.permissions?.includes(permission) ?? false
    );
  };

  /**
   * 檢查是否擁有所有權限
   * @param permissions - 權限陣列
   */
  const hasAllPermissions = (permissions: string[]): boolean => {
    if (!isAuthenticated || !user) return false;
    if (user.role === 'admin') return true;
    
    return permissions.every(permission => 
      user.permissions?.includes(permission) ?? false
    );
  };

  /**
   * 角色檢查
   */
  const isAdmin = (): boolean => {
    return user?.role === 'admin';
  };

  const isStoreAdmin = (): boolean => {
    return user?.role === 'store_admin';
  };

  /**
   * 檢查角色等級
   * @param requiredRole - 需要的最低角色
   */
  const hasRoleLevel = (requiredRole: UserRole): boolean => {
    if (!user) return false;
    
    const roleHierarchy: Record<UserRole, number> = {
      admin: 100,
      store_admin: 80,
      manager: 60,
      staff: 40,
      guest: 20,
    };
    
    const userLevel = roleHierarchy[user.role] ?? 0;
    const requiredLevel = roleHierarchy[requiredRole] ?? 0;
    
    return userLevel >= requiredLevel;
  };

  /**
   * 業務權限檢查 - 使用者管理
   */
  const canManageUsers = (): boolean => {
    return hasAnyPermission([
      'users.view',
      'users.create', 
      'users.update',
      'users.delete'
    ]);
  };

  const canDeleteUsers = (): boolean => {
    return hasPermission('users.delete');
  };

  const canCreateUsers = (): boolean => {
    return hasPermission('users.create');
  };

  const canUpdateUsers = (): boolean => {
    return hasPermission('users.update');
  };

  const canViewUsers = (): boolean => {
    return hasPermission('users.view');
  };

  /**
   * 業務權限檢查 - 商品分類
   */
  const canManageCategories = (): boolean => {
    return hasAnyPermission([
      'categories.view',
      'categories.create',
      'categories.update', 
      'categories.delete'
    ]);
  };

  /**
   * 檢查是否可以編輯特定使用者
   * @param targetUser - 目標使用者
   */
  const canEditUser = (targetUser: { id: number; role: UserRole; store_id: number }): boolean => {
    if (!user || !canUpdateUsers()) return false;
    
    // 管理員可以編輯所有使用者
    if (isAdmin()) return true;
    
    // 不能編輯自己
    if (targetUser.id === user.id) return false;
    
    // 門市管理員只能編輯同門市的非管理員使用者
    if (isStoreAdmin()) {
      return targetUser.store_id === user.store_id && 
             !['admin', 'store_admin'].includes(targetUser.role);
    }
    
    return false;
  };

  /**
   * 檢查是否可以刪除特定使用者
   * @param targetUser - 目標使用者
   */
  const canDeleteUser = (targetUser: { id: number; role: UserRole; store_id: number }): boolean => {
    if (!user || !canDeleteUsers()) return false;
    
    // 不能刪除自己
    if (targetUser.id === user.id) return false;
    
    // 管理員可以刪除非管理員使用者
    if (isAdmin()) {
      return targetUser.role !== 'admin';
    }
    
    // 門市管理員只能刪除同門市的非管理員使用者
    if (isStoreAdmin()) {
      return targetUser.store_id === user.store_id && 
             !['admin', 'store_admin'].includes(targetUser.role);
    }
    
    return false;
  };

  return {
    // 基礎權限檢查
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    
    // 角色檢查
    isAdmin,
    isStoreAdmin,
    hasRoleLevel,
    
    // 業務權限檢查 - 使用者管理
    canManageUsers,
    canDeleteUsers,
    canCreateUsers,
    canUpdateUsers,
    canViewUsers,
    canEditUser,
    canDeleteUser,
    
    // 業務權限檢查 - 其他模組
    canManageCategories,
  };
} 