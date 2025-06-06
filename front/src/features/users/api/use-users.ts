/**
 * 用戶管理模組 - 統一 Hooks 導出
 * 整合所有用戶相關的 API hooks，提供一致的介面
 * 
 * 符合 LomisX3 架構標準：
 * - 統一命名規範
 * - 型別安全
 * - 錯誤處理
 * - 快取策略
 */

import type { operations } from "@/types/api";

/**
 * @description Query keys for user-related data.
 * Follows TanStack Query's recommended structure for query key factories.
 * @see https://tanstack.com/query/v5/docs/react/guides/query-keys
 */
export const userQueryKeys = {
  all: ['users'] as const,
  lists: () => [...userQueryKeys.all, 'list'] as const,
  list: (params: operations['listUsers']['parameters']['query']) => [...userQueryKeys.lists(), params] as const,
  details: () => [...userQueryKeys.all, 'detail'] as const,
  detail: (id: number) => [...userQueryKeys.details(), id] as const,
};

// 重新導出所有用戶CRUD hooks
export {
  useUsers,
  useUser,
  useCreateUser,
  useUpdateUser,
  useDeleteUser,
  useBatchUpdateUserStatus,
  useResetPassword,
  type User,
  type PaginatedUsers,
  type CreateUserRequest,
  type UpdateUserRequest,
  type UserQueryParams,
  type BatchStatusUpdateRequest,
} from './user-crud';

// 重新導出所有統計相關 hooks
export {
  useUserStatistics,
  useUserCount,
  useActiveUserCount,
  useTwoFactorStats,
  useUserActivity,
  useUserStatsByStore,
  type UserStatsData,
  type UserCountData,
  type TwoFactorStatsData,
  type UserActivityData,
} from './user-stats';

// 重新導出認證相關 hooks (如果有的話)
// export {
//   useLogin,
//   useLogout,
//   useCurrentUser,
//   useUpdateProfile,
//   type LoginRequest,
//   type ProfileUpdateRequest,
// } from './user-auth';

// 重新導出雙因子認證 hooks (如果有的話)  
// export {
//   useEnable2FA,
//   useDisable2FA,
//   useVerify2FA,
//   useGenerate2FASecret,
//   useGetRecoveryCodes,
//   type TwoFactorSetupData,
//   type TwoFactorVerifyRequest,
// } from './user-2fa';

// 重新導出活動記錄 hooks (如果有的話)
// export {
//   useUserActivities,
//   useUserLoginHistory,
//   type UserActivity,
//   type LoginHistory,
//   type ActivityQueryParams,
// } from './user-activity';

/**
 * 便利性 Hook - 組合多個查詢
 * 適用於需要同時顯示用戶列表和統計的頁面
 */
import { useUsers, useUser } from './user-crud';
import { useUserStatistics } from './user-stats';
import type { UserQueryParams } from './user-crud';

/**
 * 用戶管理頁面的組合 Hook
 * 同時獲取用戶列表和統計數據，優化載入體驗
 * 
 * @param queryParams - 用戶查詢參數
 * @returns 包含用戶列表和統計數據的組合結果
 */
export function useUsersWithStats(queryParams: UserQueryParams = {}) {
  const usersQuery = useUsers(queryParams);
  const statsQuery = useUserStatistics();

  return {
    // 用戶列表相關
    users: usersQuery.data,
    usersLoading: usersQuery.isLoading,
    usersError: usersQuery.error,
    usersRefetch: usersQuery.refetch,
    
    // 統計數據相關
    stats: statsQuery.data,
    statsLoading: statsQuery.isLoading,
    statsError: statsQuery.error,
    statsRefetch: statsQuery.refetch,
    
    // 整體狀態
    isLoading: usersQuery.isLoading || statsQuery.isLoading,
    hasError: !!usersQuery.error || !!statsQuery.error,
    
    // 整體重新獲取
    refetchAll: () => {
      usersQuery.refetch();
      statsQuery.refetch();
    },
  };
}

/**
 * 用戶詳情頁面的組合 Hook  
 * 同時獲取用戶詳情和相關活動記錄
 * 
 * @param userId - 用戶ID
 * @returns 包含用戶詳情和活動記錄的組合結果
 */
export function useUserDetails(userId: number) {
  const userQuery = useUser(userId);
  // const activitiesQuery = useUserActivities({ user_id: userId });

  return {
    // 用戶詳情
    user: userQuery.data,
    userLoading: userQuery.isLoading,
    userError: userQuery.error,
    
    // 活動記錄 (暫時註解，等待實現)
    // activities: activitiesQuery.data,
    // activitiesLoading: activitiesQuery.isLoading,
    // activitiesError: activitiesQuery.error,
    
    // 整體狀態
    isLoading: userQuery.isLoading, // || activitiesQuery.isLoading,
    hasError: !!userQuery.error, // || !!activitiesQuery.error,
    
    // 重新獲取
    refetch: () => {
      userQuery.refetch();
      // activitiesQuery.refetch();
    },
  };
}

/**
 * 用戶表單的驗證 Hook
 * 提供即時驗證功能，檢查用戶名和電子郵件是否重複
 * 
 * @param excludeUserId - 更新時排除的用戶ID
 * @returns 驗證函數集合
 */
export function useUserValidation(excludeUserId?: number) {
  // TODO: 實現即時驗證功能
  // const checkUsername = useMutation({...});
  // const checkEmail = useMutation({...});

  return {
    // 驗證用戶名是否可用
    validateUsername: async (username: string): Promise<boolean> => {
      // TODO: 實現用戶名驗證 API 調用
      console.log('驗證用戶名:', username, '排除用戶:', excludeUserId);
      return true; // 暫時返回 true
    },
    
    // 驗證電子郵件是否可用
    validateEmail: async (email: string): Promise<boolean> => {
      // TODO: 實現電子郵件驗證 API 調用
      console.log('驗證電子郵件:', email, '排除用戶:', excludeUserId);
      return true; // 暫時返回 true
    },
  };
}

/**
 * 導出常用的預設查詢參數
 */
export const DEFAULT_USER_QUERY_PARAMS: UserQueryParams = {
  page: 1,
  per_page: 20,
  sort: 'created_at',
  order: 'desc',
};

/**
 * 導出常用的狀態選項
 */
export const USER_STATUS_OPTIONS = [
  { value: 'all', label: '全部狀態' },
  { value: 'active', label: '啟用' },
  { value: 'inactive', label: '停用' },
  { value: 'suspended', label: '暫停' },
] as const;

/**
 * 導出排序選項
 */
export const USER_SORT_OPTIONS = [
  { value: 'name', label: '姓名' },
  { value: 'email', label: '電子郵件' },
  { value: 'created_at', label: '建立時間' },
] as const; 