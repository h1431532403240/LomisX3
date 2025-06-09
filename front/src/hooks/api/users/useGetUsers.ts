/**
 * 使用者列表查詢 Hook
 * 支援分頁、搜尋、篩選功能
 */
import { useQuery } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';

/**
 * 使用者列表查詢參數介面
 * 基於 API 規範定義的參數類型
 */
export interface UserListParams {
  search?: string;
  status?: "active" | "inactive" | "suspended";
  role?: string;
  store_id?: number;
  per_page?: number;
  page?: number;
  has_2fa?: boolean;
  email_verified?: boolean;
  created_from?: string;
  created_to?: string;
  sort?: "name" | "email" | "created_at";
  order?: "asc" | "desc";
  include?: string;
  with_count?: string;
}

/**
 * 使用者列表查詢 Hook
 * @param params 查詢參數
 * @returns TanStack Query 查詢結果
 */
export function useGetUsers(params: UserListParams = {}) {
  return useQuery({
    queryKey: ['users', 'list', params],
    queryFn: async () => {
      const result = await safeApiCall(() =>
        openapi.GET('/api/users', {
          params: { query: params }
        })
      );
      
      if (result.error) {
        throw new Error(result.error.message || '取得使用者列表失敗');
      }
      
      return result.data;
    },
    // 5分鐘快取時間
    staleTime: 5 * 60 * 1000,
    // 10分鐘後快取過期
    gcTime: 10 * 60 * 1000,
    // 重試機制
    retry: (failureCount, error) => {
      // 401/403 不重試
      if (error.message.includes('401') || error.message.includes('403')) {
        return false;
      }
      return failureCount < 3;
    },
    // 只在有認證時執行
    enabled: !!localStorage.getItem('auth_token'),
  });
}

/**
 * 使用者統計資料查詢 Hook
 * @returns 使用者統計資料
 */
export function useGetUserStatistics() {
  return useQuery({
    queryKey: ['users', 'statistics'],
    queryFn: async () => {
      const result = await safeApiCall(() =>
        openapi.GET('/api/users/statistics')
      );
      
      if (result.error) {
        throw new Error(result.error.message || '取得使用者統計失敗');
      }
      
      return result.data;
    },
    staleTime: 2 * 60 * 1000, // 2分鐘快取
    enabled: !!localStorage.getItem('auth_token'),
  });
} 