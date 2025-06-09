/**
 * @fileoverview 雙因子驗證 Hook
 * @description 使用 TanStack Query 處理雙因子驗證 (2FA) 流程
 * @author LomisX3 團隊
 * @version 1.0.0
 * @since 2025-01-07
 * 
 * @remarks
 * - 符合 LomisX3 架構標準手冊規範
 * - 職責單一：專門處理 2FA 驗證邏輯
 * - 使用 TanStack Query mutation 進行狀態管理
 * - 完整的錯誤處理和安全驗證
 * - 支援驗證碼和恢復碼兩種驗證方式
 */

import { useMutation } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import type { paths } from '@/types/api';

/**
 * 雙因子驗證請求參數型別
 */
type TwoFactorChallengeRequest = paths['/api/auth/2fa/challenge']['post']['requestBody']['content']['application/json'];

/**
 * 雙因子驗證回應型別
 */
type TwoFactorChallengeResponse = paths['/api/auth/2fa/challenge']['post']['responses'][200]['content']['application/json'];

/**
 * 雙因子驗證 Hook
 * 
 * @description
 * 處理雙因子驗證邏輯，包含：
 * - 6 位數驗證碼驗證
 * - 恢復碼驗證
 * - 完整的認證流程處理
 * - 安全的錯誤處理和狀態管理
 * 
 * @example
 * ```tsx
 * function TwoFactorForm() {
 *   const twoFactorMutation = useTwoFactorChallenge();
 * 
 *   const handleSubmit = async (data: { code: string }) => {
 *     try {
 *       const result = await twoFactorMutation.mutateAsync({ code: data.code });
 *       if (result?.success) {
 *         // 驗證成功，處理登入狀態
 *         navigate('/dashboard');
 *       }
 *     } catch (error) {
 *       console.error('2FA 驗證失敗:', error);
 *     }
 *   };
 * 
 *   return (
 *     <form onSubmit={handleSubmit}>
 *       <input 
 *         type="text" 
 *         placeholder="請輸入 6 位數驗證碼"
 *         maxLength={6}
 *       />
 *       <button 
 *         type="submit"
 *         disabled={twoFactorMutation.isPending}
 *       >
 *         {twoFactorMutation.isPending ? '驗證中...' : '驗證'}
 *       </button>
 *     </form>
 *   );
 * }
 * ```
 * 
 * @returns {UseMutationResult} TanStack Query mutation 結果物件
 * - mutate: 觸發 2FA 驗證請求函數
 * - mutateAsync: 觸發 2FA 驗證請求的 Promise 版本
 * - isPending: 驗證請求進行中狀態
 * - error: 驗證錯誤資訊
 * - isError: 錯誤狀態布林值
 * - isSuccess: 成功狀態布林值
 * - reset: 重置 mutation 狀態函數
 */
export function useTwoFactorChallenge() {
  return useMutation({
    /**
     * Mutation 鍵 - 用於快取和狀態管理
     */
    mutationKey: ['auth', 'two-factor-challenge'] as const,
    
    /**
     * Mutation 函數 - 執行 2FA 驗證
     */
    mutationFn: async (credentials: TwoFactorChallengeRequest): Promise<TwoFactorChallengeResponse> => {
      console.log('🔐 [useTwoFactorChallenge] 開始 2FA 驗證流程...');
      
      // 驗證輸入參數
      if (!credentials.code && !credentials.recovery_code) {
        throw new Error('驗證碼或恢復碼不能為空');
      }
      
      // 記錄驗證類型（不記錄實際驗證碼）
      const verificationType = credentials.code ? '驗證碼' : '恢復碼';
      console.log(`🔐 [useTwoFactorChallenge] 使用 ${verificationType} 進行驗證`);
      
      return await safeApiCall(async () => {
        // 調用 Laravel Fortify 2FA 驗證 API
        const response = await openapi.POST('/api/auth/2fa/challenge', {
          body: credentials
        });
        
        // 檢查 API 回應
        if (response.error) {
          console.error('❌ [useTwoFactorChallenge] API 錯誤:', {
            error: response.error
          });
          
          // 針對不同錯誤提供友好的錯誤訊息
          const errorMessage = response.error.message?.includes('422') || response.error.message?.includes('驗證')
            ? '驗證碼不正確，請檢查後重試'
            : response.error.message || '2FA 驗證失敗';
            
          throw new Error(errorMessage);
        }
        
        console.log('✅ [useTwoFactorChallenge] 2FA 驗證成功');
        return response.data;
      });
    },
    
    /**
     * 成功回調 - 2FA 驗證成功後的處理
     */
    onSuccess: (result: TwoFactorChallengeResponse) => {
      console.log('🎉 [useTwoFactorChallenge] 雙因子驗證完成成功');
      
      // 註：這裡不直接處理登入狀態
      // 由調用方（如 LoginPage.tsx）決定如何處理認證結果
      // 這樣保持 Hook 的職責單一性
    },
    
    /**
     * 錯誤回調 - 2FA 驗證失敗時的處理
     */
    onError: (error: Error) => {
      console.warn('⚠️ [useTwoFactorChallenge] 2FA 驗證失敗:', {
        message: error.message,
        name: error.name,
        timestamp: new Date().toISOString()
      });
      
      // 註：錯誤訊息處理由調用方決定
      // Hook 只負責狀態管理和 API 調用
    },
    
    /**
     * Mutation 配置選項
     */
    // 不自動重試 - 讓用戶手動重新輸入驗證碼
    retry: false,
    
    // 失效快取 - 確保每次驗證都是新的請求
    gcTime: 0,
  });
}

/**
 * Hook 預設導出
 */
export default useTwoFactorChallenge; 