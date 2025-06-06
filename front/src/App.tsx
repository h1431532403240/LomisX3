// React import removed for production build
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { AppSidebar } from '@/components/app-sidebar';
import { SiteHeader } from '@/components/site-header';
import { ThemeProvider } from '@/components/theme/theme-provider';
import { Toaster } from '@/components/ui/sonner';
import DashboardPage from '@/pages/dashboard/DashboardPage';
import { CategoriesPage } from '@/pages/categories';
import { UsersPage as ActualUsersPage } from '@/pages/users';
import { CreateUserPage } from '@/pages/users/CreateUserPage';
import { EditUserPage } from '@/pages/users/EditUserPage';
import { UserDetailPage } from '@/pages/users/UserDetailPage';
import PermissionsPage from '@/pages/users/PermissionsPage';
import RolesPage from '@/pages/roles/RolesPage';
import ProfilePage from '@/pages/profile/ProfilePage';
import LoginPage from '@/pages/auth/LoginPage';
import { ProtectedRoute } from '@/components/guards/ProtectedRoute';
import { useAuthStore } from '@/stores/authStore';
import { useEffect } from 'react';
import { initializeCsrfToken } from '@/lib/openapi-client';
import {
  SidebarInset,
  SidebarProvider,
} from '@/components/ui/sidebar';

/**
 * 商品管理頁面 (示例)
 */
function ProductsPage() {
  return (
    <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
      <h1 className="text-2xl font-bold">商品管理</h1>
      <p className="text-muted-foreground">商品管理功能正在開發中...</p>
    </div>
  );
}

/**
 * 訂單管理頁面 (示例)
 */
function OrdersPage() {
  return (
    <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
      <h1 className="text-2xl font-bold">訂單管理</h1>
      <p className="text-muted-foreground">訂單管理功能正在開發中...</p>
    </div>
  );
}

// Removed unused UsersPage placeholder component

/**
 * API 錯誤類型定義
 */
interface ApiError {
  status?: number;
  message?: string;
}

// React Query 客戶端配置
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5分鐘
      gcTime: 1000 * 60 * 10,   // 10分鐘
      retry: (failureCount, error: unknown) => {
        // 智慧重試策略：404和403不重試
        const apiError = error as ApiError;
        if (apiError?.status === 404 || apiError?.status === 403) {
          return false;
        }
        return failureCount < 3;
      },
    },
  },
});

/**
 * 受保護的應用程式佈局
 * 包含側邊欄和導航，僅供已認證用戶使用
 */
function AuthenticatedLayout({ children }: { children: React.ReactNode }) {
  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <SiteHeader />
        <main className="flex flex-1 flex-col gap-4 p-4">
          {children}
        </main>
      </SidebarInset>
    </SidebarProvider>
  );
}

/**
 * 應用程式根組件
 * 提供全域上下文和路由管理，整合認證流程和 CSRF 保護
 * 
 * @author LomisX3 開發團隊
 * @version 4.1.0 (CSRF 初始化支援)
 */
function App() {
  const initialize = useAuthStore((state) => state.initialize);

  // 應用程式啟動時初始化認證狀態和 CSRF 保護
  useEffect(() => {
    const initializeApp = async () => {
      try {
        console.log('🚀 開始 LomisX3 應用程式初始化...');
        
        // 1. 先初始化認證狀態 (異步操作)
        console.log('1️⃣ 初始化認證狀態...');
        await initialize();
        
        // 2. 等待一個 tick 確保狀態已更新
        await new Promise(resolve => setTimeout(resolve, 0));
        
        // 3. 初始化 CSRF token (Laravel Sanctum SPA 認證必需)
        console.log('2️⃣ 初始化 CSRF token...');
        await initializeCsrfToken();
        
        console.log('✅ LomisX3 應用程式初始化完成');
      } catch (error) {
        console.error('❌ 應用程式初始化失敗:', error);
      }
    };

    initializeApp();
  }, [initialize]);

  return (
    <ThemeProvider
      attribute="class"
      defaultTheme="system"
      enableSystem
      disableTransitionOnChange
      storageKey="vite-ui-theme"
    >
      <QueryClientProvider client={queryClient}>
        <Router>
          <Routes>
            {/* 公開路由 - 不需要認證 */}
            <Route path="/login" element={
              <ProtectedRoute requireAuth={false}>
                <LoginPage />
              </ProtectedRoute>
            } />

            {/* 受保護路由 - 需要認證 */}
            <Route path="/*" element={
              <ProtectedRoute requireAuth={true}>
                <AuthenticatedLayout>
                  <Routes>
                    <Route path="/" element={<Navigate to="/dashboard" replace />} />
                    <Route path="/dashboard" element={<DashboardPage />} />
                    
                    {/* 商品管理 */}
                    <Route path="/products" element={<ProductsPage />} />
                    <Route path="/products/*" element={<ProductsPage />} />
                    <Route path="/products/categories" element={<CategoriesPage />} />
                    
                    {/* 訂單管理 */}
                    <Route path="/orders" element={<OrdersPage />} />
                    <Route path="/orders/*" element={<OrdersPage />} />
                    
                    {/* 使用者管理 */}
                    <Route path="/users" element={<ActualUsersPage />} />
                    <Route path="/users/create" element={<CreateUserPage />} />
                    <Route path="/users/:id/edit" element={<EditUserPage />} />
                    <Route path="/users/:id" element={<UserDetailPage />} />
                    <Route path="/users/permissions" element={<PermissionsPage />} />
                    <Route path="/users/*" element={<ActualUsersPage />} />
                    
                    {/* 角色權限 */}
                    <Route path="/roles" element={<RolesPage />} />
                    
                    {/* 個人資料 */}
                    <Route path="/profile" element={<ProfilePage />} />
                    
                    {/* 其他模組 */}
                    <Route path="/analytics/*" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">分析報表</div>} />
                    <Route path="/marketing/*" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">行銷工具</div>} />
                    <Route path="/content/*" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">內容管理</div>} />
                    <Route path="/settings/*" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">系統設定</div>} />
                    <Route path="/support" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">技術支援</div>} />
                    <Route path="/search" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">搜尋</div>} />
                    <Route path="/quick-stats" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">快速統計</div>} />
                    <Route path="/hot-products" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">熱銷商品</div>} />
                    <Route path="/today-orders" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">今日訂單</div>} />
                    <Route path="/account" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">帳號設定</div>} />
                    <Route path="/account/*" element={<div className="flex flex-1 flex-col gap-4 p-4 pt-0">帳號設定</div>} />
                    
                    {/* 404 頁面 */}
                    <Route path="*" element={<div className="flex items-center justify-center min-h-screen text-muted-foreground">頁面未找到</div>} />
                  </Routes>
                </AuthenticatedLayout>
              </ProtectedRoute>
            } />
          </Routes>
        </Router>
        
        {/* 全域 Toast 通知組件 - 支援所有頁面 */}
        <Toaster />
        

        
        {/* React Query DevTools（僅開發環境） */}
        {import.meta.env.DEV && <ReactQueryDevtools initialIsOpen={false} />}
      </QueryClientProvider>
    </ThemeProvider>
  );
}

export default App;
