/**
 * @fileoverview 當前使用者資訊查詢 Hook
 * @description 使用 TanStack Query 獲取當前登入使用者的完整資訊
 * @author LomisX3 團隊
 * @version 1.0.0
 * @since 2025-01-07
 * 
 * @remarks
 * - 符合 LomisX3 架構標準手冊規範
 * - 使用 TanStack Query 進行伺服器狀態管理
 * - 自動快取和背景更新
 * - 包含權限角色資訊
 * - 門市隔離機制支援
 */

import { useQuery } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import type { paths } from '@/types/api';

/**
 * 當前使用者 API 回應型別
 */
type GetCurrentUserResponse = paths['/api/auth/me']['get']['responses'][200]['content']['application/json'];

/**
 * 當前使用者查詢 Hook
 * 
 * @description
 * 獲取當前認證使用者的完整資訊，包含：
 * - 基本使用者資料 (id, username, email, name)
 * - 權限角色資訊 (roles, permissions)
 * - 門市關聯資訊 (store_id, store_name)
 * - 帳號狀態 (is_active, email_verified_at)
 * 
 * @example
 * ```tsx
 * function UserProfile() {
 *   const { 
 *     data: userResponse, 
 *     isLoading, 
 *     error,
 *     refetch 
 *   } = useGetCurrentUser();
 * 
 *   if (isLoading) return <LoadingSpinner />;
 *   if (error) return <ErrorMessage error={error} />;
 * 
 *   const user = userResponse?.data;
 *   return <div>歡迎，{user?.name}！</div>;
 * }
 * ```
 * 
 * @returns {UseQueryResult} TanStack Query 結果物件
 * - data: 當前使用者完整資訊
 * - isLoading: 載入狀態
 * - error: 錯誤資訊
 * - refetch: 手動重新查詢函數
 * - isError: 錯誤狀態布林值
 * - isSuccess: 成功狀態布林值
 */
export function useGetCurrentUser() {
  return useQuery({
    /**
     * 查詢鍵 - 用於快取和失效控制
     * @see https://tanstack.com/query/latest/docs/react/guides/query-keys
     */
    queryKey: ['auth', 'current-user'] as const,
    
    /**
     * 查詢函數 - 調用 API 獲取當前使用者資訊
     */
    queryFn: async (): Promise<GetCurrentUserResponse> => {
      return await safeApiCall(async () => {
        const response = await openapi.GET('/api/auth/me');
        
        // 檢查 API 回應是否成功
        if (response.error) {
          throw new Error(response.error.message || '獲取使用者資訊失敗');
        }
        
        return response.data;
      });
    },
    
    /**
     * 查詢配置選項
     */
    // 5分鐘快取時間 - 使用者資訊相對穩定
    staleTime: 5 * 60 * 1000, // 5 minutes
    
    // 10分鐘垃圾回收時間
    gcTime: 10 * 60 * 1000, // 10 minutes
    
    // 失焦時重新驗證 - 確保權限資訊為最新
    refetchOnWindowFocus: true,
    
    // 重連時重新查詢 - 網路恢復時更新狀態
    refetchOnReconnect: true,
    
    // 背景重新查詢間隔 - 30秒檢查一次權限變更
    refetchInterval: 30 * 1000, // 30 seconds
    
    // 只在有認證 token 時執行查詢
    enabled: !!localStorage.getItem('auth_token'),
    
    /**
     * 重試配置 - 網路錯誤時自動重試
     */
    retry: (failureCount, error) => {
      // 401 未授權錯誤不重試 (需要重新登入)
      if (error instanceof Error && error.message.includes('401')) {
        return false;
      }
      
      // 最多重試 2 次
      return failureCount < 2;
    },
    
    /**
     * 重試延遲 - 指數退避策略
     */
    retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
  });
}

/**
 * Hook 預設導出
 */
export default useGetCurrentUser; 