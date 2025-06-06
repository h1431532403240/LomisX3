/**
 * @file User statistics and data aggregation hooks.
 * This file provides hooks for fetching various user-related statistics,
 * such as total user count, active user count, 2FA status distribution,
 * and user activity metrics. It leverages TanStack Query for caching and data fetching.
 *
 * All statistical data is designed to be displayed on dashboards or in administrative reports.
 * Each hook is optimized for performance and provides clear loading and error states.
 *
 * @module features/users/api/user-stats
 */
import { useQuery } from '@tanstack/react-query';
import apiClient from '@/lib/openapi-client';
import type { operations } from '@/types/api';
import { toast } from 'sonner';
import { userQueryKeys } from './use-users';
import { useEffect } from 'react';

// =================================================================================
// TYPE DEFINITIONS
// =================================================================================

/**
 * @description The full response type for user statistics.
 */
type UserStatsResponse = operations['getUserStatistics']['responses']['200']['content']['application/json'];

/**
 * @description The structure of the main statistics data object.
 */
export type UserStatsData = NonNullable<UserStatsResponse['data']>;
/**
 * @description The structure of the user count data.
 */
export type UserCountData = NonNullable<UserStatsData['counts']>;
/**
 * @description The structure of the 2FA statistics data.
 */
export type TwoFactorStatsData = NonNullable<UserStatsData['two_factor']>;
/**
 * @description The structure of the user activity data.
 */
export type UserActivityData = NonNullable<UserStatsData['activity']>;

// =================================================================================
// QUERY KEYS
// =================================================================================

/**
 * @description Query keys for user statistics, extending the main userQueryKeys.
 */
export const userStatsQueryKeys = {
  all: () => [...userQueryKeys.all, 'statistics'] as const,
  byStore: (storeId?: number) => [...userStatsQueryKeys.all(), { storeId }] as const,
};

// =================================================================================
// HOOKS
// =================================================================================

/**
 * @description Fetches all user statistics from the API.
 * @param params - Optional query parameters for filtering statistics (e.g., by store_id).
 * @returns {QueryResult<UserStatsResponse, Error>} The result of the query.
 */
export const useUserStatistics = (
  params?: operations['getUserStatistics']['parameters']['query']
) => {
  const queryResult = useQuery({
    queryKey: userStatsQueryKeys.byStore(params?.store_id),
    queryFn: async () => {
      const { data, error } = await apiClient.GET('/api/users/statistics', { params: { query: params } });
      if (error) {
        throw new Error('Failed to fetch user statistics');
      }
      return data;
    },
    staleTime: 1000 * 60 * 5, // 5 minutes
    refetchOnWindowFocus: true,
  });

  useEffect(() => {
    if (queryResult.isError && queryResult.error) {
      toast.error('統計資料載入失敗', {
        description: queryResult.error.message,
      });
    }
  }, [queryResult.isError, queryResult.error]);

  return queryResult;
};

/**
 * @description Hook to fetch only the total user count.
 * @param params - Optional query parameters.
 * @returns An object containing the user count data, and query status.
 */
export const useUserCount = (
  params?: operations['getUserStatistics']['parameters']['query']
) => {
  const { data, ...rest } = useUserStatistics(params);
  return {
    data: data?.data?.counts,
    ...rest,
  };
};

/**
 * @description Hook to fetch only the active user count.
 * @param params - Optional query parameters.
 * @returns An object containing the active user count, and query status.
 */
export const useActiveUserCount = (
  params?: operations['getUserStatistics']['parameters']['query']
) => {
  const { data, ...rest } = useUserStatistics(params);
  return {
    data: data?.data?.counts?.active,
    ...rest,
  };
};

/**
 * @description Hook to fetch 2FA (Two-Factor Authentication) statistics.
 * @param params - Optional query parameters.
 * @returns An object containing 2FA statistics data, and query status.
 */
export const useTwoFactorStats = (
  params?: operations['getUserStatistics']['parameters']['query']
) => {
  const { data, ...rest } = useUserStatistics(params);
  return {
    data: data?.data?.two_factor,
    ...rest,
  };
};

/**
 * @description Hook to fetch user activity statistics.
 * @param params - Optional query parameters.
 * @returns An object containing user activity data, and query status.
 */
export const useUserActivity = (
  params?: operations['getUserStatistics']['parameters']['query']
) => {
  const { data, ...rest } = useUserStatistics(params);
  return {
    data: data?.data?.activity,
    ...rest,
  };
};

/**
 * @description A specific hook for fetching statistics for a given store.
 * @param storeId - The ID of the store to fetch statistics for.
 * @returns The result of the useUserStatistics query.
 */
export const useUserStatsByStore = (storeId: number) => {
  return useUserStatistics({ store_id: storeId });
}; 