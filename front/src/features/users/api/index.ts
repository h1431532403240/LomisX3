/**
 * 用戶管理模組 API 層統一導出
 * 提供乾淨的導入介面，方便其他模組使用
 * 
 * 使用方式：
 * import { useUsers, useCreateUser, User } from '@/features/users/api';
 */

// 重新導出所有用戶相關 hooks 和類型
export * from './use-users';

// 如果有其他 API 文件，也在這裡導出
// export * from './user-auth';
// export * from './user-2fa';
// export * from './user-activity';

/**
 * 便利性常數導出
 * 提供預設配置和選項，方便組件直接使用
 */
export const USER_API_ENDPOINTS = {
  list: '/api/users',
  detail: (id: number) => `/api/users/${id}`,
  create: '/api/users',
  update: (id: number) => `/api/users/${id}`,
  delete: (id: number) => `/api/users/${id}`,
  batchStatus: '/api/users/batch/status',
  resetPassword: (id: number) => `/api/users/${id}/reset-password`,
  statistics: '/api/users/statistics',
  count: '/api/users/count',
  activeCount: '/api/users/active/count',
  twoFactorStats: '/api/users/two-factor/statistics',
  activityStats: '/api/users/activity/statistics',
} as const;

/**
 * 查詢金鑰常數
 * 統一管理 TanStack Query 的查詢金鑰，避免重複和錯誤
 */
export const USER_QUERY_KEYS = {
  all: ['users'] as const,
  lists: () => [...USER_QUERY_KEYS.all, 'list'] as const,
  list: (params: object) => [...USER_QUERY_KEYS.lists(), params] as const,
  details: () => [...USER_QUERY_KEYS.all, 'detail'] as const,
  detail: (id: number) => [...USER_QUERY_KEYS.details(), id] as const,
  stats: () => [...USER_QUERY_KEYS.all, 'stats'] as const,
  count: () => [...USER_QUERY_KEYS.all, 'count'] as const,
  activeCount: () => [...USER_QUERY_KEYS.all, 'active-count'] as const,
  twoFactorStats: () => [...USER_QUERY_KEYS.all, 'two-factor-stats'] as const,
  activityStats: () => [...USER_QUERY_KEYS.all, 'activity-stats'] as const,
  byStore: (storeId: number | null) => [...USER_QUERY_KEYS.all, 'by-store', storeId] as const,
} as const; 