/**
 * 建立使用者 Hook
 * 使用 useMutation 實現新增使用者功能
 */
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import { toast } from 'sonner';
import type { components } from '@/types/api';

// 從 API 類型定義中提取建立使用者請求類型
type CreateUserRequest = components['schemas']['CreateUserRequest'];

/**
 * 建立使用者 Hook
 * @returns useMutation 結果
 */
export function useCreateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (userData: CreateUserRequest) => {
      const result = await safeApiCall(() =>
        openapi.POST('/api/users', {
          body: userData
        })
      );

      if (result.error) {
        throw new Error(result.error.message || '建立使用者失敗');
      }

      return result.data;
    },
    onSuccess: (data) => {
      // 成功後清除相關查詢快取
      queryClient.invalidateQueries({ queryKey: ['users'] });
      
      // 顯示成功訊息 (Sonner API)
      toast.success(`使用者「${data?.data?.name}」已成功建立`);
    },
    onError: (error: Error) => {
      // 顯示錯誤訊息 (Sonner API)
      toast.error(`建立失敗：${error.message}`);
    },
  });
} 