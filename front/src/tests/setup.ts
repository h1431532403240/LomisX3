/**
 * Vitest 測試環境設定檔
 * 
 * 此檔案在每個測試檔案執行前載入
 * 用於設定全域測試環境和擴展測試功能
 */

import '@testing-library/jest-dom';

/**
 * @testing-library/jest-dom 為 expect 提供了額外的 matchers，例如：
 * 
 * - toBeInTheDocument()
 * - toHaveClass()
 * - toHaveStyle()
 * - toHaveTextContent()
 * - toBeVisible()
 * - toBeDisabled()
 * - 等等...
 * 
 * 這些 matchers 讓 DOM 元素的測試更加直觀和易讀
 */

/**
 * 全域測試配置
 */

// 模擬 IntersectionObserver（某些 UI 組件可能需要）
global.IntersectionObserver = class IntersectionObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
};

// 模擬 ResizeObserver（某些 UI 組件可能需要）
global.ResizeObserver = class ResizeObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
};

// 模擬 matchMedia（響應式設計測試可能需要）
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => {},
  }),
});

/**
 * 測試環境變數配置
 */
process.env.NODE_ENV = 'test'; 