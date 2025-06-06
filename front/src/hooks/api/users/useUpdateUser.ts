/**
 * 更新使用者 Hook
 * 使用 useMutation 實現更新使用者功能
 */
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import { toast } from 'sonner';
import type { components } from '@/types/api';

// 從 API 類型定義中提取更新使用者請求類型
type UpdateUserRequest = components['schemas']['UpdateUserRequest'];

/**
 * 更新使用者參數介面
 */
interface UpdateUserParams {
  id: number;
  data: UpdateUserRequest;
}

/**
 * 更新使用者 Hook
 * @returns useMutation 結果
 */
export function useUpdateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, data }: UpdateUserParams) => {
      const result = await safeApiCall(() =>
        openapi.PUT('/api/users/{id}', {
          params: { path: { id } },
          body: data
        })
      );

      if (result.error) {
        throw new Error(result.error.message || '更新使用者失敗');
      }

      return result.data;
    },
    onSuccess: (data, variables) => {
      // 成功後清除相關查詢快取
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.invalidateQueries({ queryKey: ['users', 'detail', variables.id] });
      
      // 顯示成功訊息 (Sonner API)
      toast.success(`使用者「${data?.data?.name}」資訊已更新`);
    },
    onError: (error: Error) => {
      // 顯示錯誤訊息 (Sonner API)
      toast.error(`更新失敗：${error.message}`);
    },
  });
} 