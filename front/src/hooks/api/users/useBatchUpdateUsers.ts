/**
 * 批次更新使用者 Hook
 * 支援批次更新使用者狀態
 */
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import { toast } from 'sonner';
import type { components } from '@/types/api';

// 從 API 類型定義中提取批次狀態更新請求類型
type BatchStatusUserRequest = components['schemas']['BatchStatusUserRequest'];

/**
 * 批次更新使用者狀態 Hook
 * @returns useMutation 結果
 */
export function useBatchUpdateUserStatus() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: BatchStatusUserRequest) => {
      const result = await safeApiCall(() =>
        openapi.PATCH('/api/users/batch/status', {
          body: data
        })
      );

      if (result.error) {
        throw new Error(result.error.message || '批次更新使用者狀態失敗');
      }

      return result.data;
    },
    onSuccess: (data, variables) => {
      // 成功後清除相關查詢快取
      queryClient.invalidateQueries({ queryKey: ['users'] });
      
      // 顯示成功訊息 (Sonner API)
      toast.success(`已成功更新 ${variables.ids.length} 個使用者的狀態為「${getStatusDisplayName(variables.status)}」`);
    },
    onError: (error: Error) => {
      // 顯示錯誤訊息 (Sonner API)
      toast.error(`批次更新失敗：${error.message}`);
    },
  });
}

/**
 * 重設使用者密碼 Hook
 * @returns useMutation 結果
 */
export function useResetUserPassword() {
  return useMutation({
    mutationFn: async (id: number) => {
      const result = await safeApiCall(() =>
        openapi.POST('/api/users/{id}/reset-password', {
          params: { path: { id } }
        })
      );

      if (result.error) {
        throw new Error(result.error.message || '重設密碼失敗');
      }

      return result.data;
    },
    onSuccess: () => {
      // 顯示成功訊息 (Sonner API)
      toast.success('密碼重設郵件已發送到使用者信箱');
    },
    onError: (error: Error) => {
      // 顯示錯誤訊息 (Sonner API)
      toast.error(`重設密碼失敗：${error.message}`);
    },
  });
}

/**
 * 獲取狀態顯示名稱
 * @param status 狀態值
 * @returns 顯示名稱
 */
function getStatusDisplayName(status: string): string {
  switch (status) {
    case 'active':
      return '啟用';
    case 'inactive':
      return '停用';
    case 'suspended':
      return '暫停';
    default:
      return status;
  }
} 