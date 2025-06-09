/**
 * 使用者管理流程 Hook
 * 
 * 處理使用者管理相關的操作流程，包括創建和編輯完成後的反饋與頁面跳轉。
 * 使用 flowStateStore 來管理跨頁面的高亮狀態，使用 sonner 來顯示操作反饋。
 * 
 * @example
 * ```typescript
 * const { completeCreateUser, completeEditUser } = useUserManagementFlow();
 * 
 * // 創建用戶完成後
 * completeCreateUser(newUser.id, newUser.name);
 * 
 * // 編輯用戶完成後
 * completeEditUser(editedUser.id, editedUser.name);
 * ```
 */

import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { useFlowStateStore } from '@/stores/flowStateStore';

/**
 * 使用者管理流程 Hook 回傳值介面
 */
interface UseUserManagementFlowReturn {
  /**
   * 完成創建使用者的流程處理
   * 
   * @param userId - 新創建的使用者 ID
   * @param userName - 新創建的使用者名稱
   */
  completeCreateUser: (userId: string | number, userName: string) => void;

  /**
   * 完成編輯使用者的流程處理
   * 
   * @param userId - 編輯的使用者 ID
   * @param userName - 編輯的使用者名稱
   */
  completeEditUser: (userId: string | number, userName: string) => void;
}

/**
 * 使用者管理流程 Hook
 * 
 * 提供使用者管理操作完成後的統一流程處理，包括：
 * 1. 使用 sonner 顯示成功通知
 * 2. 使用 flowStateStore 設置高亮狀態
 * 3. 執行頁面跳轉
 */
export const useUserManagementFlow = (): UseUserManagementFlowReturn => {
  const navigate = useNavigate();
  const setHighlight = useFlowStateStore((state) => state.setHighlight);

  /**
   * 完成創建使用者的流程處理
   * 
   * 執行以下步驟：
   * 1. 顯示創建成功的 toast 通知
   * 2. 設置高亮狀態以便在列表頁面中突出顯示新創建的使用者
   * 3. 跳轉到使用者列表頁面
   * 
   * @param userId - 新創建的使用者 ID
   * @param userName - 新創建的使用者名稱
   */
  const completeCreateUser = (userId: string | number, userName: string): void => {
    // 第一步：顯示成功通知
    toast.success(`使用者「${userName}」已成功建立！`);
    
    // 第二步：設置高亮狀態
    setHighlight('user', userId);
    
    // 第三步：跳轉到使用者列表頁面（純粹的頁面跳轉，不帶 state）
    navigate('/users');
  };

  /**
   * 完成編輯使用者的流程處理
   * 
   * 執行以下步驟：
   * 1. 顯示更新成功的 toast 通知
   * 2. 設置高亮狀態以便突出顯示更新的使用者
   * 3. 跳轉到使用者列表頁面
   * 
   * @param userId - 編輯的使用者 ID
   * @param userName - 編輯的使用者名稱
   */
  const completeEditUser = (userId: string | number, userName: string): void => {
    // 第一步：顯示更新成功通知
    toast.success(`使用者「${userName}」資訊已成功更新！`);
    
    // 第二步：設置高亮狀態
    setHighlight('user', userId);
    
    // 第三步：跳轉到使用者列表頁面（純粹的頁面跳轉，不帶 state）
    navigate('/users');
  };

  return {
    completeCreateUser,
    completeEditUser,
  };
};

/**
 * 導出類型定義供其他模組使用
 */
export type { UseUserManagementFlowReturn }; 