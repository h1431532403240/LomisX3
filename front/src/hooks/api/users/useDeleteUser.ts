/**
 * 刪除使用者 Hook
 * 使用 useMutation 實現刪除使用者功能
 */
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import { toast } from 'sonner';

/**
 * 刪除使用者 Hook
 * @returns useMutation 結果
 */
export function useDeleteUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      const result = await safeApiCall(() =>
        openapi.DELETE('/api/users/{id}', {
          params: { path: { id } }
        })
      );

      if (result.error) {
        throw new Error(result.error.message || '刪除使用者失敗');
      }

      return { id };
    },
    onSuccess: (data) => {
      // 成功後清除相關查詢快取
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.removeQueries({ queryKey: ['users', 'detail', data.id] });
      
      // 顯示成功訊息 (Sonner API)
      toast.success('使用者已成功刪除');
    },
    onError: (error: Error) => {
      // 顯示錯誤訊息 (Sonner API)
      toast.error(`刪除失敗：${error.message}`);
    },
  });
} 