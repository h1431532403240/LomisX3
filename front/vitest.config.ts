/**
 * Vitest 單元測試配置
 * 
 * 基於 Vite 配置，專為 React 元件測試優化
 * 使用 jsdom 環境模擬瀏覽器環境
 * 
 * 參考文檔：https://vitest.dev/config/
 */

import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  
  /**
   * 測試環境配置
   */
  test: {
    /**
     * 啟用全域測試 API
     * 讓 describe, it, expect 等函數在全域可用
     */
    globals: true,
    
    /**
     * 測試運行環境
     * jsdom: 模擬瀏覽器環境，適合 React 元件測試
     */
    environment: 'jsdom',
    
    /**
     * 測試設定檔
     * 在每個測試檔案執行前載入的設定
     */
    setupFiles: ['./src/tests/setup.ts'],
    
    /**
     * 覆蓋率配置
     */
    coverage: {
      /**
       * 覆蓋率提供者
       */
      provider: 'v8',
      
      /**
       * 報告格式
       */
      reporter: ['text', 'json', 'html'],
      
      /**
       * 排除檔案
       */
      exclude: [
        'node_modules/',
        'src/test/',
        'tests/',
        '**/*.d.ts',
        '**/*.config.*',
        '**/vite.config.*',
        '**/vitest.config.*',
        '**/playwright.config.*',
      ],
      
      /**
       * 覆蓋率門檻
       */
      thresholds: {
        statements: 80,
        branches: 70,
        functions: 80,
        lines: 80,
      },
    },
    
    /**
     * 測試包含和排除模式
     */
    include: [
      'src/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'src/**/__tests__/**/*.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
    ],
    
    exclude: [
      'node_modules',
      'dist',
      '.idea',
      '.git',
      '.cache',
      'tests/e2e/**',
    ],
  },
  
  /**
   * 模組解析配置
   */
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
}); 