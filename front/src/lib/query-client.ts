/**
 * React Query 客戶端配置
 * 提供統一的查詢和快取管理
 */
import { QueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { hookErrorHandlers } from './error-handlers';
import type { ApiError } from './openapi-client';

/**
 * 🆕 建立 React Query 客戶端
 * 配置全域錯誤處理和快取策略
 */
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // 🔧 查詢預設配置
      staleTime: 5 * 60 * 1000, // 5 分鐘內數據視為新鮮
      gcTime: 10 * 60 * 1000, // 10 分鐘後清理快取
      retry: (failureCount, error) => {
        // 🚨 使用新的錯誤分類決定是否重試
        const { category } = hookErrorHandlers.background(error as unknown as ApiError);
        
        // 不重試的錯誤類型
        const noRetryCategories = ['authentication', 'authorization', 'validation'];
        if (noRetryCategories.includes(category)) {
          return false;
        }
        
        // 最多重試 3 次
        return failureCount < 3;
      },
      retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
      refetchOnWindowFocus: false, // 視窗焦點時不自動重新取得
      refetchOnMount: true, // 組件掛載時重新取得
      refetchOnReconnect: true, // 網路重連時重新取得
    },
    mutations: {
      // 🔧 變更預設配置
      retry: (failureCount, error) => {
        // 🚨 變更操作的重試策略
        const { category } = hookErrorHandlers.background(error as unknown as ApiError);
        
        // 僅網路錯誤才重試，且最多 1 次
        return category === 'network' && failureCount < 1;
      },
      onError: (error) => {
        // 🚨 使用新的變更錯誤處理器
        hookErrorHandlers.mutation(error as unknown as ApiError);
      },
      onSuccess: (_data, _variables, context) => {
        // 🎉 成功提示（僅特定操作顯示）
        const shouldShowSuccess = (context as any)?.showSuccessToast;
        if (shouldShowSuccess) {
          toast.success('操作成功', {
            description: (context as any)?.successMessage ?? '操作已完成',
          });
        }
      },
    },
  },
});

/**
 * 🆕 帶有樂觀更新回滾提示的變更 wrapper
 * @param mutationFn 原始變更函數
 * @param rollbackMessage 回滾提示訊息
 */
export function createOptimisticMutation<TData, TError, TVariables, TContext>(
  mutationFn: (variables: TVariables) => Promise<TData>,
  rollbackMessage = '操作已回復原狀'
) {
  return {
    mutationFn,
    onMutate: async (_variables: TVariables) => {
      // 🎯 樂觀更新開始提示
      toast.info('處理中...', {
        description: '正在執行操作',
        duration: 1000,
      });
      
      return { _variables };
    },
    onError: (error: TError, _variables: TVariables, _context: TContext) => {
      // 🚨 使用新的錯誤處理
      hookErrorHandlers.mutation(error as unknown as ApiError);
      
      // 🔄 回滾提示
      toast.info('回復原狀', {
        description: rollbackMessage,
        duration: 2000,
      });
    },
    onSuccess: (_data: TData, _variables: TVariables, _context: TContext) => {
      // 🎉 成功提示
      toast.success('操作完成', {
        description: '變更已儲存',
        duration: 2000,
      });
    },
  };
}

/**
 * 🆕 查詢錯誤處理器
 * 用於處理查詢級別的錯誤（靜默處理）
 */
export function handleQueryError(error: ApiError) {
  return hookErrorHandlers.query(error);
}

/**
 * 🆕 預設導出
 */
export default queryClient; 