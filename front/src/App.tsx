import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { AppSidebar } from '@/components/app-sidebar';
import { SiteHeader } from '@/components/site-header';
import { ThemeProvider } from '@/components/theme';
import { Dashboard } from '@/pages/Dashboard';
import { Toaster } from '@/components/ui/toaster';
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

/**
 * 使用者管理頁面 (示例)
 */
function UsersPage() {
  return (
    <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
      <h1 className="text-2xl font-bold">使用者管理</h1>
      <p className="text-muted-foreground">使用者管理功能正在開發中...</p>
    </div>
  );
}

/**
 * LomisX3 主應用組件
 * 基於 shadcn/ui dashboard-01 官方架構
 * 提供完整的管理系統佈局和路由
 */
function App() {
  return (
    <ThemeProvider
      attribute="class"
      defaultTheme="light"
      enableSystem={false}
      disableTransitionOnChange
    >
      <Router>
        {/* 使用 dashboard-01 的官方架構 */}
        <SidebarProvider>
          <AppSidebar />
          <SidebarInset>
            {/* 固定頂部標題列 */}
            <SiteHeader />
            
            {/* 主要內容區域 */}
            <main className="flex flex-1 flex-col gap-4 p-4">
              <Routes>
                <Route path="/" element={<Dashboard />} />
                <Route path="/products" element={<ProductsPage />} />
                <Route path="/products/*" element={<ProductsPage />} />
                <Route path="/orders" element={<OrdersPage />} />
                <Route path="/orders/*" element={<OrdersPage />} />
                <Route path="/users" element={<UsersPage />} />
                <Route path="/users/*" element={<UsersPage />} />
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
              </Routes>
            </main>
            
            {/* Toast 通知 */}
            <Toaster />
          </SidebarInset>
        </SidebarProvider>
      </Router>
    </ThemeProvider>
  );
}

export default App;
