import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { LoadingSpinner } from '@/components/common/loading-spinner';

/**
 * 受保護路由組件
 * 確保只有已認證的使用者可以存取特定頁面
 * 
 * V2.1 版本更新：
 * - 新增詳細調試日誌來診斷認證檢查問題
 * - 改善邏輯流程和狀態判斷
 */
interface ProtectedRouteProps {
  children: React.ReactNode;
  requireAuth?: boolean;
  fallbackPath?: string;
}

export const ProtectedRoute: React.FC<ProtectedRouteProps> = ({
  children,
  requireAuth = true,
  fallbackPath = '/login',
}) => {
  const { isAuthenticated, isLoading, user, isFullyAuthenticated } = useAuthStore();
  const location = useLocation();

  // V2.2 安全加固：詳細的狀態調試日誌
  console.log('🛡️ ProtectedRoute 檢查 - 路徑:', location.pathname);
  console.log('  - requireAuth:', requireAuth);
  console.log('  - isLoading:', isLoading);
  console.log('  - isAuthenticated:', isAuthenticated);
  console.log('  - user:', user ? user.display_name : 'null');
  console.log('  - isFullyAuthenticated:', isFullyAuthenticated());
  console.log('  - fallbackPath:', fallbackPath);

  // 載入中狀態
  if (isLoading) {
    console.log('⏳ ProtectedRoute: 認證狀態載入中，顯示載入畫面');
    return (
      <div className="flex items-center justify-center min-h-screen">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  // 需要認證但未完全認證 (V2.2 安全加固：使用完整認證檢查)
  if (requireAuth && !isFullyAuthenticated()) {
    console.log('❌ ProtectedRoute: 需要認證但未完全認證，重導向到登入頁');
    console.log('  - 從:', location.pathname);
    console.log('  - 到:', fallbackPath);
    console.log('  - 原因: isFullyAuthenticated =', isFullyAuthenticated());
    console.log('  - 詳細: user =', user ? 'exists' : 'null', ', isAuthenticated =', isAuthenticated);
    return (
      <Navigate
        to={fallbackPath}
        state={{ from: location.pathname }}
        replace
      />
    );
  }

  // 已完全認證但存取登入頁面，重導向到儀表板 (V2.2 安全加固)
  if (isFullyAuthenticated() && location.pathname === '/login') {
    console.log('✅ ProtectedRoute: 已完全認證用戶訪問登入頁，重導向到儀表板');
    return <Navigate to="/dashboard" replace />;
  }

  // 通過認證檢查，渲染子組件
  if (requireAuth) {
    console.log('✅ ProtectedRoute: 認證檢查通過，渲染受保護內容');
    console.log('  - 使用者:', user?.display_name);
    console.log('  - 路徑:', location.pathname);
  } else {
    console.log('✅ ProtectedRoute: 公開路由，直接渲染內容');
  }

  return <>{children}</>;
}; 