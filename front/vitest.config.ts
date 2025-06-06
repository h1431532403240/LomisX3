/**
 * Vitest 測試配置
 * 配置測試環境、模組解析和設定檔案
 */
import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  test: {
    // 🆕 測試環境設定
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    globals: true,
    
    // 🆕 覆蓋率設定
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'src/test/',
        '**/*.d.ts',
        '**/*.config.*',
        '**/coverage/**',
      ],
    },
    
    // 🆕 測試超時設定
    testTimeout: 10000,
    hookTimeout: 10000,
  },
  
  // 🆕 模組解析
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
}); 