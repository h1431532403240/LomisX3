/**
 * E2E 測試示例
 * 
 * 測試 LomisX3 基本導航和登入功能
 */

import { test, expect } from '@playwright/test';

test.describe('LomisX3 基本功能測試', () => {
  test('應該能夠載入首頁', async ({ page }) => {
    // 導航到首頁
    await page.goto('/');
    
    // 檢查頁面標題
    await expect(page).toHaveTitle(/LomisX3/);
    
    // 檢查是否存在登入相關元素
    await expect(page.locator('text=登入')).toBeVisible();
  });
  
  test('應該能夠導航到登入頁面', async ({ page }) => {
    // 導航到登入頁面
    await page.goto('/login');
    
    // 檢查登入表單元素
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });
  
  test('應該顯示正確的錯誤訊息當登入資訊不正確時', async ({ page }) => {
    // 導航到登入頁面
    await page.goto('/login');
    
    // 填入錯誤的登入資訊
    await page.fill('input[type="email"]', 'invalid@example.com');
    await page.fill('input[type="password"]', 'wrongpassword');
    
    // 點擊登入按鈕
    await page.click('button[type="submit"]');
    
    // 等待並檢查錯誤訊息
    await expect(page.locator('text=帳號或密碼錯誤')).toBeVisible({ timeout: 5000 });
  });
}); 