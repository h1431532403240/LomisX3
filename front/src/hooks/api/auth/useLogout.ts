/**
 * @fileoverview 使用者登出 Hook - 企業級健壯實現
 * @description 提供強健的登出機制，確保客戶端在任何情況下都能完成登出流程
 * @author LomisX3 團隊
 * @version 2.0.0
 * @since 2025-01-07
 * 
 * @remarks
 * - 符合 LomisX3 架構標準手冊規範 V2.5
 * - 容錯設計：無論後端 API 是否成功，都能完成客戶端登出
 * - 使用 onSettled 確保狀態清理的執行順序和完整性
 * - 支援冪等性操作，可重複調用而不會產生副作用
 * - 完整的錯誤處理和日誌追蹤機制
 */

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { openapi, safeApiCall } from '@/lib/openapi-client';

/**
 * 企業級登出 Hook
 * 
 * @description
 * 實現強健的使用者登出流程，具備以下特性：
 * 
 * **容錯機制：**
 * - 後端 API 失敗時不影響客戶端登出流程
 * - 自動清理所有認證相關的本地狀態
 * - 確保用戶體驗的一致性和流暢性
 * 
 * **安全特性：**
 * - 嘗試撤銷伺服器端 Sanctum Token
 * - 完整清除客戶端認證狀態和權限
 * - 清空所有 React Query 快取避免資料洩漏
 * - 強制導向到登入頁防止未授權存取
 * 
 * **執行順序：**
 * 1. 嘗試調用後端登出 API（容錯處理）
 * 2. 清理 AuthStore 認證狀態和權限資料
 * 3. 清空 React Query 所有快取
 * 4. 導向到登入頁面
 * 
 * @example
 * ```tsx
 * function LogoutButton() {
 *   const logoutMutation = useLogout();
 * 
 *   const handleLogout = () => {
 *     // 直接調用，無需錯誤處理
 *     logoutMutation.mutate();
 *   };
 * 
 *   return (
 *     <Button 
 *       onClick={handleLogout}
 *       disabled={logoutMutation.isPending}
 *     >
 *       {logoutMutation.isPending ? '登出中...' : '登出'}
 *     </Button>
 *   );
 * }
 * ```
 * 
 * @example
 * ```tsx
 * // 自動登出（如 Token 過期）
 * function useTokenExpiredHandler() {
 *   const logoutMutation = useLogout();
 *   
 *   useEffect(() => {
 *     if (tokenExpired) {
 *       logoutMutation.mutate(); // 自動清理狀態並導向登入
 *     }
 *   }, [tokenExpired]);
 * }
 * ```
 * 
 * @returns {UseMutationResult} TanStack Query mutation 結果物件
 * - mutate: 觸發登出流程（推薦使用）
 * - mutateAsync: 觸發登出流程的 Promise 版本
 * - isPending: 登出流程進行中狀態
 * - isSuccess: 登出流程完成狀態（不代表 API 成功）
 * - reset: 重置 mutation 狀態函數
 * 
 * @note
 * 此 Hook 設計為「永不失敗」的登出機制：
 * - 不會拋出錯誤到呼叫方
 * - 即使後端 API 失敗也會完成客戶端登出
 * - 提供最佳的使用者體驗和系統穩定性
 */
export function useLogout() {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const logout = useAuthStore((state) => state.logout);

  return useMutation({
    /**
     * Mutation 鍵 - 用於快取和狀態管理
     */
    mutationKey: ['auth', 'logout'] as const,
    
    /**
     * Mutation 函數 - 嘗試撤銷後端 Token
     * 
     * @description
     * 使用容錯機制嘗試調用後端登出 API，
     * 失敗時不會拋出錯誤，確保客戶端清理流程能夠繼續執行。
     */
    mutationFn: async (): Promise<void> => {
      console.log('🔓 [useLogout] 開始執行登出流程...');
      
      try {
        // 嘗試撤銷伺服器端 Token
        await safeApiCall(() => openapi.POST('/api/auth/logout'));
        console.log('✅ [useLogout] 後端 Token 撤銷成功');
      } catch (error) {
        // 容錯處理：後端失敗不影響客戶端登出
        console.warn('⚠️ 後端 token 撤銷請求失敗（客戶端將繼續執行登出）:', error);
        // 不重新拋出錯誤，確保 onSettled 能正常執行
      }
    },
    
    /**
     * 完成回調 - 無論成功或失敗都執行清理
     * 
     * @description
     * 使用 onSettled 確保無論 API 調用成功與否，
     * 都會執行完整的客戶端狀態清理和導向流程。
     * 
     * 執行順序嚴格按照安全性要求：
     * 1. 清理認證狀態（AuthStore）
     * 2. 清空查詢快取（React Query）
     * 3. 導向登入頁（React Router）
     */
    onSettled: () => {
      console.log('🧹 [useLogout] 開始執行客戶端狀態清理...');
      
      try {
        // a. 清理 AuthStore 認證狀態和權限
        logout();
        console.log('✅ [useLogout] AuthStore 狀態已清理');
        
        // b. 注意：沒有單獨的 permissionsStore，權限已在 AuthStore.logout() 中清理
        console.log('✅ [useLogout] 權限狀態已清理（包含在 AuthStore 中）');
        
        // c. 清空所有 React Query 快取
        queryClient.clear();
        console.log('✅ [useLogout] React Query 快取已清空');
        
        // d. 導向到登入頁面
        navigate('/login', { replace: true });
        console.log('✅ [useLogout] 已導向到登入頁面');
        
        console.log('🎉 [useLogout] 登出流程完全完成');
        
      } catch (cleanupError) {
        // 容錯處理：即使清理過程出錯也要確保基本的登出操作
        console.error('❌ [useLogout] 清理過程發生錯誤:', cleanupError);
        
        // 確保最基本的清理操作
        try {
          logout(); // 強制清理認證狀態
          navigate('/login', { replace: true }); // 強制導向登入頁
          console.log('🔧 [useLogout] 已執行緊急清理和導向');
        } catch (emergencyError) {
          console.error('💥 [useLogout] 緊急清理也失敗:', emergencyError);
          // 最後手段：直接重新整理頁面
          window.location.href = '/login';
        }
      }
    },
    
    /**
     * Mutation 配置選項
     */
    // 不自動重試 - 登出是一次性操作
    retry: false,
    
    // 不設置 timeout - 確保清理流程有足夠時間完成
    // 不使用 onSuccess/onError - 統一使用 onSettled 處理所有情況
  });
}

/**
 * Hook 預設導出
 */
export default useLogout; 