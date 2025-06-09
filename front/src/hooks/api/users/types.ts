/**
 * Users API 型別定義
 * 
 * 🔥 重要：基於真實 OpenAPI 規範生成的型別
 * 與後端 UserResource 完全匹配
 * 
 * @author LomisX3 開發團隊  
 * @version 6.0.0 - 基於 paths['/api/users'] 實際結構
 */

import type { paths } from '@/types/api';

// ================================
// 🎯 基於真實 API Paths 的類型提取
// ================================

// Users 列表查詢參數（基於 OpenAPI 規範）
export type UserQueryParams = paths['/api/users']['get']['parameters']['query'];

// Users 列表響應格式
export type UsersListResponse = paths['/api/users']['get']['responses']['200']['content']['application/json'];

// 單一 User 項目類型（從列表 data 數組中提取）
export type User = NonNullable<UsersListResponse['data']>['data'][number];

// 單一 User 詳情響應格式
export type UserDetailResponse = paths['/api/users/{id}']['get']['responses']['200']['content']['application/json'];

// User 創建請求體
export type CreateUserRequest = paths['/api/users']['post']['requestBody']['content']['application/json'];

// User 更新請求體
export type UpdateUserRequest = paths['/api/users/{id}']['put']['requestBody']['content']['application/json'];

// 批量狀態更新請求體
export type BatchStatusUpdateRequest = paths['/api/users/batch-status']['post']['requestBody']['content']['application/json'];

// ================================
// 🧩 輔助型別定義
// ================================

// User 狀態枚舉
export type UserStatus = 'active' | 'inactive' | 'locked' | 'pending';

// Role 型別（從 User 中提取）
export type Role = NonNullable<User['roles']>[number];

// User 狀態對象型別
export type UserStatusObject = NonNullable<User['status']>;

// 雙因子驗證資訊
export type TwoFactorInfo = NonNullable<User['two_factor']>;

// 登入資訊
export type LoginInfo = NonNullable<User['login_info']>;

// 頭像資訊
export type AvatarInfo = NonNullable<User['avatar']>;

// 審計資訊
export type AuditInfo = NonNullable<User['audit']>;

// ================================
// 🔧 工具函數與型別守衛
// ================================

/**
 * 檢查是否為有效的使用者狀態
 */
export function isValidUserStatus(status: string): status is UserStatus {
  return ['active', 'inactive', 'locked', 'pending'].includes(status);
}

/**
 * 檢查使用者是否啟用
 */
export function isUserActive(user: User): boolean {
  return user.status?.is_active === true;
}

/**
 * 檢查使用者是否有頭像
 */
export function hasAvatar(user: User): boolean {
  return user.avatar?.has_avatar === true;
}

/**
 * 檢查使用者是否啟用雙因子驗證
 */
export function hasTwoFactorEnabled(user: User): boolean {
  return user.two_factor?.enabled === true;
}

/**
 * 檢查使用者是否已驗證信箱
 */
export function isEmailVerified(user: User): boolean {
  return user.is_email_verified === true;
}

/**
 * 獲取使用者主要角色
 */
export function getPrimaryRole(user: User): Role | null {
  const roles = user.roles;
  if (!roles || roles.length === 0) return null;
  
  // 找到 level 最高的角色，或第一個角色
  return roles.reduce((primary, current) => {
    if (!primary) return current;
    const primaryLevel = primary.level ?? 0;
    const currentLevel = current.level ?? 0;
    return currentLevel > primaryLevel ? current : primary;
  });
}

/**
 * 格式化使用者狀態顯示
 */
export function formatUserStatus(user: User): string {
  return user.status?.label ?? '未知';
}

/**
 * 格式化使用者角色顯示
 */
export function formatUserRoles(user: User): string {
  const roles = user.roles;
  if (!roles || roles.length === 0) return '無角色';
  
  return roles.map(role => role.display_name ?? role.name).join(', ');
}

// ================================
// 🔄 API 錯誤型別
// ================================

// API 基礎錯誤響應
export interface ApiErrorResponse {
  success: false;
  message: string;
  error_code?: string;
  errors?: Record<string, string[]>;
}

// 驗證錯誤響應（422）
export type ValidationErrorResponse = paths['/api/users']['get']['responses']['422']['content']['application/json'];

// 權限錯誤響應（403）
export type ForbiddenErrorResponse = paths['/api/users']['get']['responses']['403']['content']['application/json'];

// ================================
// 🎯 預設值和常數
// ================================

// 預設查詢參數
export const DEFAULT_USER_QUERY_PARAMS: UserQueryParams = {
  page: 1,
  per_page: 20,
};

// 使用者狀態選項
export const USER_STATUS_OPTIONS = [
  { value: 'active', label: '啟用', color: 'success' },
  { value: 'inactive', label: '停用', color: 'secondary' },
  { value: 'locked', label: '鎖定', color: 'destructive' },
  { value: 'pending', label: '待審核', color: 'warning' },
] as const;