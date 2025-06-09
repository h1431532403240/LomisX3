/**
 * Playwright 端對端測試配置
 * 
 * 配置說明：
 * - 自動啟動 Vite 開發伺服器進行測試
 * - 支援主流瀏覽器 (Chromium, Firefox, WebKit)
 * - 啟用詳細的錯誤追蹤和除錯功能
 * - 針對 LomisX3 前端應用優化配置
 */

import { defineConfig, devices } from '@playwright/test';

/**
 * 查看完整配置選項：https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  /**
   * 測試目錄
   */
  testDir: './tests/e2e',
  
  /**
   * 在 CI 環境中並行執行測試
   */
  fullyParallel: true,
  
  /**
   * 在 CI 中如果有測試失敗則退出
   */
  forbidOnly: !!process.env.CI,
  
  /**
   * CI 中重試失敗的測試
   */
  retries: process.env.CI ? 2 : 0,
  
  /**
   * 在 CI 中不使用並行 worker，本地開發使用並行
   */
  workers: process.env.CI ? 1 : undefined,
  
  /**
   * 測試報告器配置
   */
  reporter: 'html',
  
  /**
   * 全域測試配置
   */
  use: {
    /**
     * 測試的基礎 URL
     */
    baseURL: 'http://localhost:5173',
    
    /**
     * 在首次重試時收集追蹤資訊
     */
    trace: 'on-first-retry',
    
    /**
     * 截圖配置
     */
    screenshot: 'only-on-failure',
    
    /**
     * 錄影配置
     */
    video: 'retain-on-failure',
  },

  /**
   * 測試專案配置 - 支援多種瀏覽器
   */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },

    /**
     * 移動端瀏覽器測試 (可選)
     */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: { ...devices['iPhone 12'] },
    // },

    /**
     * 品牌瀏覽器測試 (可選)
     */
    // {
    //   name: 'Microsoft Edge',
    //   use: { ...devices['Desktop Edge'], channel: 'msedge' },
    // },
    // {
    //   name: 'Google Chrome',
    //   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
    // },
  ],

  /**
   * 開發伺服器配置
   * 自動啟動 Vite 開發伺服器並等待可用
   */
  webServer: {
    /**
     * 啟動命令
     */
    command: 'npm run dev',
    
    /**
     * 等待服務啟動的 URL
     */
    url: 'http://localhost:5173',
    
    /**
     * 是否重用現有的開發伺服器實例
     */
    reuseExistingServer: !process.env.CI,
    
    /**
     * 等待伺服器啟動的超時時間 (毫秒)
     */
    timeout: 120 * 1000,
  },
}); 