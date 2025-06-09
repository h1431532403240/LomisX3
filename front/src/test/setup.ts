/**
 * 測試環境設定
 * 配置 MSW、DOM 測試工具和全域設定
 */
import '@testing-library/jest-dom';
import { beforeAll, afterEach, afterAll, vi } from 'vitest';
import { setupServer } from 'msw/node';
import { handlers } from './mocks/handlers';

// 🆕 設定 MSW 伺服器
export const server = setupServer(...handlers);

// 🆕 測試生命週期設定
beforeAll(() => {
  // 啟動模擬伺服器
  server.listen({
    onUnhandledRequest: 'warn',
  });
});

afterEach(() => {
  // 重置處理器狀態
  server.resetHandlers();
});

afterAll(() => {
  // 關閉模擬伺服器
  server.close();
});

// 🆕 全域測試工具
declare global {
  // eslint-disable-next-line no-var
  var testServer: typeof server;
}

globalThis.testServer = server;

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(), // Deprecated
    removeListener: vi.fn(), // Deprecated
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock ResizeObserver
const mockResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}));

global.ResizeObserver = mockResizeObserver; 