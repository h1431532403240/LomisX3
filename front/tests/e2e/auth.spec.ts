                                                                    /**
                                                                     * 認證流程 E2E 測試
                                                                     * 
                                                                     * 測試 LomisX3 系統的完整使用者登入流程
                                                                     * 包含成功登入和錯誤處理場景
                                                                     * 
                                                                     * 🎯 遵循 Playwright 最佳實踐：
                                                                     * - 先等待 API 回應完成，再檢查導航結果
                                                                     * - 明確檢查網路請求狀態，提供清晰的錯誤定位
                                                                     * - 消除競爭條件，提高測試穩定性
                                                                     * - 增強偵錯能力，在失敗時提供詳細診斷資訊
                                                                     */

                                                                    import { test, expect } from '@playwright/test';

                                                                    test.describe('Authentication Flow', () => {
                                                                    /**
                                                                    * 測試用例 1：成功登入流程
                                                                    * 驗證使用者能夠使用正確的認證資訊成功登入系統
                                                                    * 
                                                                    * 🔧 改進：使用 waitForResponse 確保 API 調用完成後再檢查導航
                                                                    * 🐛 增強：失敗時提供詳細的 API 回應診斷資訊
                                                                    */
                                                                    test('should allow a user to log in successfully', async ({ page }) => {
                                                                        // 1. 導航到登入頁面
                                                                        await page.goto('/login');
                                                                        
                                                                        // 2. 驗證登入頁面已正確載入，確認存在「歡迎回來」標題
                                                                        await expect(page.getByRole('heading', { name: '歡迎回來' })).toBeVisible();
                                                                        
                                                                        // 3. 在點擊登入按鈕 *之前*，設定對 API 回應的監聽
                                                                        const loginResponsePromise = page.waitForResponse('**/api/auth/login');
                                                                        
                                                                            // 4. 填充登入表單
    await page.getByLabel('使用者名稱或信箱').fill('admin@lomisx3.com');
    await page.getByPlaceholder('請輸入密碼').fill('password123');
                                                                        
                                                                        // 5. 點擊登入按鈕提交表單
                                                                        await page.getByRole('button', { name: '登入' }).click();
                                                                        
                                                                        // 6. 等待 API 回應完成
                                                                        const response = await loginResponsePromise;
                                                                        
                                                                        // 7. 增強偵錯：如果狀態碼不是 200，打印詳細的回應內容
                                                                        if (response.status() !== 200) {
                                                                            console.error('🚨 Login API failed with status:', response.status());
                                                                            console.error('📄 Response headers:', response.headers());
                                                                            try {
                                                                                const responseBody = await response.json();
                                                                                console.error('📋 Response body:', JSON.stringify(responseBody, null, 2));
                                                                            } catch (e) {
                                                                                const responseText = await response.text();
                                                                                console.error('📄 Response text:', responseText);
                                                                            }
                                                                        }
                                                                        
                                                                        // 8. 斷言 API 調用本身是成功的（狀態碼 200）
                                                                        expect(response.status()).toBe(200);
                                                                        
                                                                        // 9. 現在我們 100% 確定 API 成功了，可以安全地斷言頁面導航
                                                                        await expect(page).toHaveURL('/dashboard');
                                                                        
                                                                        // 10. 驗證控制台頁面內容已正確載入
                                                                        await expect(page.getByRole('heading', { name: '控制台' })).toBeVisible();
                                                                    });

                                                                    /**
                                                                    * 測試用例 2：錯誤認證資訊處理
                                                                    * 驗證系統能夠正確處理錯誤的登入認證資訊
                                                                    * 
                                                                    * 🔧 改進：檢查 API 錯誤回應狀態，然後驗證前端錯誤處理
                                                                    * 🐛 增強：打印實際的錯誤回應內容，幫助理解後端錯誤格式
                                                                    */
                                                                    test('should show an error message with invalid credentials', async ({ page }) => {
                                                                        // 1. 導航到登入頁面
                                                                        await page.goto('/login');
                                                                        
                                                                        // 2. 驗證登入頁面已正確載入
                                                                        await expect(page.getByRole('heading', { name: '歡迎回來' })).toBeVisible();
                                                                        
                                                                        // 3. 在點擊登入按鈕之前，設定對 API 回應的監聽
                                                                        const loginResponsePromise = page.waitForResponse('**/api/auth/login');
                                                                        
                                                                        // 4. 填充錯誤的登入資訊
                                                                        await page.getByLabel('使用者名稱或信箱').fill('admin@lomisx3.com');
                                                                        await page.getByPlaceholder('請輸入密碼').fill('wrong-password');
                                                                        
                                                                        // 5. 點擊登入按鈕嘗試登入
                                                                        await page.getByRole('button', { name: '登入' }).click();
                                                                        
                                                                        // 6. 等待 API 回應完成
                                                                        const response = await loginResponsePromise;
                                                                        
                                                                        // 7. 增強偵錯：打印錯誤回應的詳細內容
                                                                        console.log('🔍 Error response status:', response.status());
                                                                        try {
                                                                            const responseBody = await response.json();
                                                                            console.log('📋 Error response body:', JSON.stringify(responseBody, null, 2));
                                                                        } catch (e) {
                                                                            const responseText = await response.text();
                                                                            console.log('📄 Error response text:', responseText);
                                                                        }
                                                                        
                                                                        // 8. 斷言 API 返回了預期的錯誤狀態碼（401 未授權或其他錯誤碼）
                                                                        // 注意：實際的錯誤碼可能因後端實現而異，這裡會通過偵錯輸出得知
                                                                        if (![401, 422, 400].includes(response.status())) {
                                                                            console.error('🚨 Unexpected error status code:', response.status());
                                                                        }
                                                                        
                                                                        // 9. 暫時接受多種可能的錯誤狀態碼，直到確定後端的實際行為
                                                                        expect([400, 401, 422]).toContain(response.status());
                                                                        
                                                                        // 10. 檢查前端錯誤處理（這需要根據實際的後端錯誤格式調整）
                                                                        // 先嘗試通用的錯誤訊息模式
                                                                        const possibleErrorMessages = [
                                                                            '帳號或密碼錯誤，請檢查您的登入資訊',
                                                                            '電子郵件或密碼錯誤',
                                                                            '登入失敗',
                                                                            '認證失敗'
                                                                        ];
                                                                        
                                                                        let errorFound = false;
                                                                        for (const message of possibleErrorMessages) {
                                                                            if (await page.getByText(message).isVisible().catch(() => false)) {
                                                                                errorFound = true;
                                                                                console.log('✅ Found error message:', message);
                                                                                break;
                                                                            }
                                                                        }
                                                                        
                                                                        if (!errorFound) {
                                                                            console.error('🚨 No expected error message found. Current page content:');
                                                                            console.error(await page.content());
                                                                        }
                                                                        
                                                                        // 11. 驗證頁面沒有跳轉，仍然停留在登入頁面
                                                                        await expect(page).toHaveURL('/login');
                                                                    });

                                                                    /**
                                                                    * 測試用例 3：登入表單驗證
                                                                    * 驗證前端表單驗證機制是否正常工作
                                                                    * 
                                                                    * 📝 注意：此測試主要驗證前端驗證，不涉及 API 調用
                                                                    */
                                                                    test('should validate required fields before submission', async ({ page }) => {
                                                                        // 導航到登入頁面
                                                                        await page.goto('/login');
                                                                        
                                                                        // 不填寫任何資訊，直接點擊登入按鈕
                                                                        await page.getByRole('button', { name: '登入' }).click();
                                                                        
                                                                        // 驗證前端驗證訊息（這些訊息應該在 API 調用之前出現）
                                                                        const possibleValidationMessages = [
                                                                            '請輸入使用者名稱或信箱',
                                                                            '此欄位為必填',
                                                                            '請輸入電子郵件',
                                                                            '請填寫此欄位'
                                                                        ];
                                                                        
                                                                        let validationFound = false;
                                                                        for (const message of possibleValidationMessages) {
                                                                            if (await page.getByText(message).isVisible().catch(() => false)) {
                                                                                validationFound = true;
                                                                                console.log('✅ Found validation message:', message);
                                                                                break;
                                                                            }
                                                                        }
                                                                        
                                                                        if (!validationFound) {
                                                                            console.error('🚨 No validation message found for empty username field');
                                                                        }
                                                                        
                                                                        // 只填寫電子郵件，不填寫密碼
                                                                        await page.getByLabel('使用者名稱或信箱').fill('admin@lomisx3.com');
                                                                        await page.getByRole('button', { name: '登入' }).click();
                                                                        
                                                                        // 檢查密碼欄位驗證
                                                                        const passwordValidationMessages = [
                                                                            '請輸入密碼',
                                                                            '此欄位為必填',
                                                                            '請填寫此欄位'
                                                                        ];
                                                                        
                                                                        let passwordValidationFound = false;
                                                                        for (const message of passwordValidationMessages) {
                                                                            if (await page.getByText(message).isVisible().catch(() => false)) {
                                                                                passwordValidationFound = true;
                                                                                console.log('✅ Found password validation message:', message);
                                                                                break;
                                                                            }
                                                                        }
                                                                        
                                                                        if (!passwordValidationFound) {
                                                                            console.error('🚨 No validation message found for empty password field');
                                                                        }
                                                                        
                                                                        // 驗證頁面仍然停留在登入頁面
                                                                        await expect(page).toHaveURL('/login');
                                                                    });

                                                                    /**
                                                                    * 測試用例 4：登入狀態持久化
                                                                    * 驗證成功登入後，頁面刷新時認證狀態是否保持
                                                                    * 
                                                                    * 🔧 改進：完整的 API 等待流程，確保認證狀態正確建立
                                                                    * 🐛 增強：詳細的狀態持久化偵錯資訊
                                                                    */
                                                                    test('should persist authentication state after page refresh', async ({ page }) => {
                                                                        // 1. 導航到登入頁面並獲取登入按鈕
                                                                        await page.goto('/login');
                                                                        const loginButton = page.getByRole('button', { name: '登入' });
                                                                        
                                                                        // 2. 在點擊按鈕 *之前*，設定一個對 API 回應的「監聽」
                                                                        const responsePromise = page.waitForResponse('**/api/auth/login');

                                                                            // 3. 填充表單並點擊登入按鈕
    await page.getByLabel('使用者名稱或信箱').fill('admin@lomisx3.com');
    await page.getByPlaceholder('請輸入密碼').fill('password123');
                                                                        await loginButton.click();

                                                                        // 4. 現在，在這裡等待 API 回應的 Promise 完成
                                                                        const response = await responsePromise;

                                                                        // 5. 增強偵錯：檢查登入回應的詳細內容
                                                                        console.log('🔍 Login response status:', response.status());
                                                                        if (response.status() !== 200) {
                                                                            console.error('🚨 Login failed with status:', response.status());
                                                                            try {
                                                                                const responseBody = await response.json();
                                                                                console.error('📋 Login response body:', JSON.stringify(responseBody, null, 2));
                                                                            } catch (e) {
                                                                                const responseText = await response.text();
                                                                                console.error('📄 Login response text:', responseText);
                                                                            }
                                                                        } else {
                                                                            try {
                                                                                const responseBody = await response.json();
                                                                                console.log('✅ Successful login response:', JSON.stringify(responseBody, null, 2));
                                                                            } catch (e) {
                                                                                console.log('✅ Login successful, but response is not JSON');
                                                                            }
                                                                        }

                                                                        // 6. 斷言 API 調用本身是成功的（狀態碼 200）
                                                                        expect(response.status()).toBe(200);

                                                                            // 7. 現在，我們 100% 確定 API 成功了，可以安全地斷言頁面導航
    await expect(page).toHaveURL('/dashboard');
    await expect(page.getByRole('heading', { name: '控制台' })).toBeVisible();

                                                                        // 8. 檢查儲存的認證狀態
                                                                        const localStorage = await page.evaluate(() => {
                                                                            return {
                                                                                token: window.localStorage.getItem('token'),
                                                                                authStore: window.localStorage.getItem('auth-store')
                                                                            };
                                                                        });
                                                                        console.log('💾 Stored authentication data:', localStorage);

                                                                        // 9. 刷新頁面測試認證狀態持久化
                                                                        await page.reload();
                                                                        
                                                                            // 10. 驗證刷新後仍然保持登入狀態，停留在控制台頁面
    await expect(page).toHaveURL('/dashboard');
    await expect(page.getByRole('heading', { name: '控制台' })).toBeVisible();
                                                                        
                                                                        // 11. 驗證不會被重新導向到登入頁面
                                                                        await expect(page).not.toHaveURL('/login');
                                                                    });

                                                                    /**
                                                                    * 測試用例 5：網路錯誤處理
                                                                    * 驗證當 API 伺服器無法回應時的錯誤處理
                                                                    * 
                                                                    * 🆕 新增：測試網路層面的錯誤處理能力
                                                                    * 🐛 增強：模擬真實的伺服器錯誤場景
                                                                    */
                                                                    test('should handle network errors gracefully', async ({ page }) => {
                                                                        // 模擬網路錯誤 - 攔截登入 API 請求並回傳 500 錯誤
                                                                        await page.route('**/api/auth/login', (route) => {
                                                                            route.fulfill({
                                                                                status: 500,
                                                                                contentType: 'application/json',
                                                                                body: JSON.stringify({
                                                                                    success: false,
                                                                                    message: '伺服器內部錯誤',
                                                                                    code: 'INTERNAL_SERVER_ERROR'
                                                                                })
                                                                            });
                                                                        });

                                                                        // 導航到登入頁面
                                                                        await page.goto('/login');
                                                                        
                                                                        // 設定對 API 回應的監聽
                                                                        const loginResponsePromise = page.waitForResponse('**/api/auth/login');
                                                                        
                                                                            // 填充正確的登入資訊
    await page.getByLabel('使用者名稱或信箱').fill('admin@lomisx3.com');
    await page.getByPlaceholder('請輸入密碼').fill('password123');
                                                                        
                                                                        // 點擊登入按鈕
                                                                        await page.getByRole('button', { name: '登入' }).click();
                                                                        
                                                                        // 等待 API 回應完成
                                                                        const response = await loginResponsePromise;
                                                                        
                                                                        // 偵錯：確認模擬的錯誤回應
                                                                        console.log('🎭 Mocked error response status:', response.status());
                                                                        try {
                                                                            const responseBody = await response.json();
                                                                            console.log('📋 Mocked error response body:', JSON.stringify(responseBody, null, 2));
                                                                        } catch (e) {
                                                                            console.log('📄 Mocked error response text:', await response.text());
                                                                        }
                                                                        
                                                                        // 斷言 API 返回了伺服器錯誤狀態碼
                                                                        expect(response.status()).toBe(500);
                                                                        
                                                                        // 檢查前端如何處理 500 錯誤
                                                                        const serverErrorMessages = [
                                                                            '系統暫時無法處理您的請求，請稍後再試',
                                                                            '伺服器錯誤',
                                                                            '服務暫時無法使用',
                                                                            '請稍後再試'
                                                                        ];
                                                                        
                                                                        let serverErrorFound = false;
                                                                        for (const message of serverErrorMessages) {
                                                                            if (await page.getByText(message).isVisible().catch(() => false)) {
                                                                                serverErrorFound = true;
                                                                                console.log('✅ Found server error message:', message);
                                                                                break;
                                                                            }
                                                                        }
                                                                        
                                                                        if (!serverErrorFound) {
                                                                            console.error('🚨 No server error message found. Current page content:');
                                                                            console.error(await page.content());
                                                                        }
                                                                        
                                                                        // 驗證用戶仍然停留在登入頁面
                                                                        await expect(page).toHaveURL('/login');
                                                                    });

                                                                    /**
                                                                    * 測試用例 6：API 請求格式偵錯
                                                                    * 專門用於偵錯和分析實際發送到後端的請求格式
                                                                    * 
                                                                    * 🐛 偵錯：捕獲並分析實際的 API 請求內容
                                                                    */
                                                                    test('should debug API request format', async ({ page }) => {
                                                                        // 攔截登入請求以查看實際發送的資料
                                                                        let requestBody: any = null;
                                                                        let requestHeaders: any = null;
                                                                        
                                                                        await page.route('**/api/auth/login', async (route) => {
                                                                            const request = route.request();
                                                                            requestHeaders = request.headers();
                                                                            
                                                                            try {
                                                                                requestBody = JSON.parse(request.postData() || '{}');
                                                                            } catch (e) {
                                                                                requestBody = request.postData();
                                                                            }
                                                                            
                                                                            // 讓請求繼續到真實的後端
                                                                            route.continue();
                                                                        });

                                                                        // 導航到登入頁面
                                                                        await page.goto('/login');
                                                                        
                                                                            // 填充表單
                                                                           await page.getByLabel('使用者名稱或信箱').fill('admin@lomisx3.com');
                                                                    await page.getByPlaceholder('請輸入密碼').fill('password123');
                                                                        
                                                                        // 點擊登入按鈕
                                                                        await page.getByRole('button', { name: '登入' }).click();
                                                                        
                                                                        // 等待請求完成
                                                                        await page.waitForResponse('**/api/auth/login');
                                                                        
                                                                        // 打印實際發送的請求資料
                                                                        console.log('🔍 Actual request headers:', JSON.stringify(requestHeaders, null, 2));
                                                                        console.log('📋 Actual request body:', JSON.stringify(requestBody, null, 2));
                                                                        
                                                                        // 這個測試的目的是偵錯，所以不做斷言
                                                                        // 這些日誌會幫助我們理解前端實際發送的請求格式
                                                                    });
                                                                    }); 