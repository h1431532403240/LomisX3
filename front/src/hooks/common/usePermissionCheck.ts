/**
 * 權限檢查 Hook
 * 提供細粒度的權限檢查功能
 */
import { useMemo } from 'react';
import { useGetCurrentUser } from '@/hooks/api/auth/useLogin';
import type { components } from '@/types/api';

type User = components['schemas']['User'];
type Role = components['schemas']['Role'];

/**
 * 權限檢查 Hook
 * @returns 權限檢查相關函數
 */
export function usePermissionCheck() {
  const { data: currentUserResponse } = useGetCurrentUser();
  const currentUser = currentUserResponse?.data;

  const permissions = useMemo(() => {
    /**
     * 檢查使用者是否有指定權限
     * @param permission 權限名稱，如 'users.create'
     * @returns 是否有權限
     */
    const hasPermission = (permission: string): boolean => {
      if (!currentUser) return false;
      
      // 超級管理員擁有所有權限
      if (hasRole('super_admin')) return true;
      
      // TODO: 這裡需要根據實際的權限系統實現
      // 目前簡化為基於角色的權限檢查
      const [resource, action] = permission.split('.');
      
      return checkRolePermission(resource, action);
    };

    /**
     * 檢查使用者是否有指定角色
     * @param roleName 角色名稱
     * @returns 是否有該角色
     */
    const hasRole = (roleName: string): boolean => {
      if (!currentUser?.roles) return false;
      
      return currentUser.roles.some((role: Role) => role.name === roleName);
    };

    /**
     * 檢查使用者是否有多個角色中的任一個
     * @param roleNames 角色名稱陣列
     * @returns 是否有任一角色
     */
    const hasAnyRole = (roleNames: string[]): boolean => {
      return roleNames.some(roleName => hasRole(roleName));
    };

    /**
     * 檢查使用者是否有所有指定角色
     * @param roleNames 角色名稱陣列
     * @returns 是否有所有角色
     */
    const hasAllRoles = (roleNames: string[]): boolean => {
      return roleNames.every(roleName => hasRole(roleName));
    };

    /**
     * 檢查角色權限（簡化版本）
     * @param resource 資源名稱
     * @param action 動作名稱
     * @returns 是否有權限
     */
    const checkRolePermission = (resource: string, action: string): boolean => {
      const userRoles = currentUser?.roles?.map(role => role.name) || [];
      
      // 基於角色的權限映射（簡化版本）
      const rolePermissions: Record<string, string[]> = {
        'super_admin': ['*'], // 所有權限
        'store_admin': [
          'users.view', 'users.create', 'users.update', 'users.delete',
          'roles.view', 'roles.create', 'roles.update', 'roles.delete',
          'permissions.view', 'permissions.update'
        ],
        'user_manager': [
          'users.view', 'users.create', 'users.update',
          'roles.view',
          'permissions.view'
        ],
        'employee': [
          'users.view',
          'roles.view',
          'permissions.view'
        ],
      };

      for (const role of userRoles) {
        const permissions = rolePermissions[role as keyof typeof rolePermissions];
        if (permissions?.includes('*') || permissions?.includes(`${resource}.${action}`)) {
          return true;
        }
      }

      return false;
    };

    /**
     * 檢查是否為管理員（超級管理員或門市管理員）
     * @returns 是否為管理員
     */
    const isAdmin = (): boolean => {
      return hasAnyRole(['super_admin', 'store_admin']);
    };

    /**
     * 檢查是否為超級管理員
     * @returns 是否為超級管理員
     */
    const isSuperAdmin = (): boolean => {
      return hasRole('super_admin');
    };

    /**
     * 檢查是否為門市管理員
     * @returns 是否為門市管理員
     */
    const isStoreAdmin = (): boolean => {
      return hasRole('store_admin');
    };

    return {
      hasPermission,
      hasRole,
      hasAnyRole,
      hasAllRoles,
      isAdmin,
      isSuperAdmin,
      isStoreAdmin,
      currentUser,
    };
  }, [currentUser]);

  return permissions;
} 