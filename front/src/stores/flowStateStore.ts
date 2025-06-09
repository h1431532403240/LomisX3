/**
 * 流程狀態管理 Store
 * 
 * 用於管理跨頁面跳轉時需要持久化的流程狀態，例如高亮顯示特定項目。
 * 提供「讀後即焚」機制，確保狀態只被消費一次。
 * 
 * @example
 * ```typescript
 * // 設置高亮
 * useFlowStateStore.getState().setHighlight('user', 123);
 * 
 * // 消費高亮（讀後即焚）
 * const userId = useFlowStateStore.getState().consumeHighlight('user');
 * ```
 */

import { create } from 'zustand';

/**
 * 流程狀態介面定義
 */
interface FlowStateStore {
  /**
   * 高亮項目記錄
   * 鍵為資源類型（如 'user', 'category'），值為該資源的 ID
   */
  highlightedItems: Record<string, string | number>;

  /**
   * 設置或更新高亮項目
   * 
   * @param type - 資源類型（如 'user', 'category', 'product' 等）
   * @param id - 資源 ID，支援字串或數字類型
   * 
   * @example
   * ```typescript
   * setHighlight('user', 123);
   * setHighlight('category', 'electronics');
   * ```
   */
  setHighlight: (type: string, id: string | number) => void;

  /**
   * 消費高亮項目（讀後即焚）
   * 
   * 返回指定類型的高亮 ID，並立即從狀態中移除該項目，
   * 確保每個高亮狀態只能被消費一次。
   * 
   * @param type - 要消費的資源類型
   * @returns 對應的資源 ID，如果不存在則返回 undefined
   * 
   * @example
   * ```typescript
   * const userId = consumeHighlight('user'); // 返回 ID 並清除狀態
   * const categoryId = consumeHighlight('category'); // 如果不存在返回 undefined
   * ```
   */
  consumeHighlight: (type: string) => string | number | undefined;
}

/**
 * 流程狀態管理 Store
 * 
 * 使用 Zustand 實現的流程狀態管理器，支援跨頁面的狀態持久化。
 * 主要用於管理需要在頁面切換後保持的臨時狀態，如高亮顯示。
 */
export const useFlowStateStore = create<FlowStateStore>((set, get) => ({
  // 初始狀態
  highlightedItems: {},

  // 設置高亮項目
  setHighlight: (type: string, id: string | number) => {
    set((state) => ({
      highlightedItems: {
        ...state.highlightedItems,
        [type]: id,
      },
    }));
  },

  // 消費高亮項目（讀後即焚）
  consumeHighlight: (type: string) => {
    const currentState = get();
    const highlightId = currentState.highlightedItems[type];
    
    // 如果存在該類型的高亮項目，則返回並從狀態中移除
    if (highlightId !== undefined) {
      set((state) => {
        const newHighlightedItems = { ...state.highlightedItems };
        delete newHighlightedItems[type];
        return {
          highlightedItems: newHighlightedItems,
        };
      });
      
      return highlightId;
    }
    
    // 如果不存在則返回 undefined
    return undefined;
  },
}));

/**
 * 導出類型定義供其他模組使用
 */
export type { FlowStateStore }; 