/**
 * 單一使用者查詢 Hook
 * 根據 ID 取得使用者詳細資訊
 */
import { useQuery } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';

/**
 * 單一使用者查詢 Hook
 * @param id 使用者 ID
 * @returns TanStack Query 查詢結果
 */
export function useGetUser(id: number | null | undefined) {
  return useQuery({
    queryKey: ['users', 'detail', id],
    queryFn: async () => {
      if (!id) {
        throw new Error('使用者 ID 不能為空');
      }
      
      const result = await safeApiCall(() =>
        openapi.GET('/api/users/{id}', {
          params: { path: { id } }
        })
      );
      
      if (result.error) {
        throw new Error(result.error.message || '取得使用者資訊失敗');
      }
      
      return result.data;
    },
    // 只在有 ID 且有認證時執行
    enabled: !!id && !!localStorage.getItem('auth_token'),
    // 5分鐘快取時間
    staleTime: 5 * 60 * 1000,
    // 重試機制
    retry: (failureCount, error) => {
      // 404 不重試
      if (error.message.includes('404')) {
        return false;
      }
      // 401/403 不重試
      if (error.message.includes('401') || error.message.includes('403')) {
        return false;
      }
      return failureCount < 3;
    },
  });
} 