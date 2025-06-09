import React from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { 
  ChevronRight, 
  Home, 
  ArrowLeft, 
  Users, 
  Package, 
  ShoppingCart, 
  Settings,
  BarChart3,
  Layout,
  Shield,
  User
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * 麵包屑項目介面
 */
export interface BreadcrumbItem {
  /**
   * 顯示標籤
   */
  label: string;
  /**
   * 路由路徑
   */
  href?: string;
  /**
   * 圖標組件
   */
  icon?: React.ComponentType<{ className?: string }>;
  /**
   * 是否為當前頁面
   */
  isActive?: boolean;
}

/**
 * 麵包屑組件 Props
 */
export interface BreadcrumbProps {
  /**
   * 自訂麵包屑項目
   */
  items?: BreadcrumbItem[];
  /**
   * 是否顯示首頁連結
   * @default true
   */
  showHome?: boolean;
  /**
   * 是否顯示返回按鈕
   * @default false
   */
  showBack?: boolean;
  /**
   * 返回按鈕點擊處理
   */
  onBack?: () => void;
  /**
   * 自訂類名
   */
  className?: string;
  /**
   * 分隔符
   * @default ChevronRight
   */
  separator?: React.ReactNode;
}

/**
 * 路由到圖標的映射
 */
const routeIconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  '/dashboard': BarChart3,
  '/users': Users,
  '/roles': Shield,
  '/profile': User,
  '/products': Package,
  '/orders': ShoppingCart,
  '/settings': Settings,
  '/analytics': BarChart3,
  '/categories': Layout,
};

/**
 * 路由到標籤的映射
 */
const routeLabelMap: Record<string, string> = {
  '/dashboard': '控制台',
  '/users': '使用者管理',
  '/users/create': '新增使用者',
  '/users/permissions': '權限管理',
  '/roles': '角色管理',
  '/profile': '個人資料',
  '/products': '商品管理',
  '/products/categories': '商品分類',
  '/products/new': '新增商品',
  '/products/inventory': '庫存管理',
  '/orders': '訂單管理',
  '/orders/pending': '待處理訂單',
  '/orders/completed': '已完成訂單',
  '/orders/refunds': '退款申請',
  '/settings': '系統設定',
  '/analytics': '分析報表',
  '/marketing': '行銷工具',
  '/content': '內容管理',
  '/support': '技術支援',
};

/**
 * 從路徑生成麵包屑項目
 */
function generateBreadcrumbsFromPath(pathname: string): BreadcrumbItem[] {
  const segments = pathname.split('/').filter(Boolean);
  const breadcrumbs: BreadcrumbItem[] = [];

  let currentPath = '';
  
  segments.forEach((segment, index) => {
    currentPath += `/${segment}`;
    const isLast = index === segments.length - 1;
    
    // 檢查是否有對應的標籤
    const label = routeLabelMap[currentPath];
    if (label) {
      breadcrumbs.push({
        label,
        href: isLast ? undefined : currentPath,
        icon: routeIconMap[currentPath],
        isActive: isLast,
      });
    } else {
      // 如果沒有映射，嘗試美化 segment
      const prettyLabel = segment
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
      
      breadcrumbs.push({
        label: prettyLabel,
        href: isLast ? undefined : currentPath,
        isActive: isLast,
      });
    }
  });

  return breadcrumbs;
}

/**
 * 麵包屑組件
 * 支援自動路由解析和自訂路徑的動態導航組件
 */
export function Breadcrumb({
  items,
  showHome = true,
  showBack = false,
  onBack,
  className,
  separator = <ChevronRight className="h-4 w-4 text-muted-foreground" />,
}: BreadcrumbProps) {
  const location = useLocation();
  const navigate = useNavigate();

  // 如果沒有提供自訂項目，則從當前路徑生成
  const breadcrumbItems = items || generateBreadcrumbsFromPath(location.pathname);

  /**
   * 處理返回按鈕點擊
   */
  const handleBack = () => {
    if (onBack) {
      onBack();
    } else {
      navigate(-1);
    }
  };

  return (
    <nav 
      className={cn('flex items-center space-x-2 text-sm', className)}
      aria-label="麵包屑導航"
    >
      {/* 返回按鈕 */}
      {showBack && (
        <Button
          variant="ghost"
          size="sm"
          onClick={handleBack}
          className="h-8 w-8 p-0 mr-2"
          aria-label="返回上一頁"
        >
          <ArrowLeft className="h-4 w-4" />
        </Button>
      )}

      {/* 首頁連結 */}
      {showHome && (
        <>
          <Link
            to="/dashboard"
            className="flex items-center space-x-1 text-muted-foreground hover:text-foreground transition-colors"
            aria-label="返回首頁"
          >
            <Home className="h-4 w-4" />
            <span>首頁</span>
          </Link>
          {breadcrumbItems.length > 0 && separator}
        </>
      )}

      {/* 麵包屑項目 */}
      {breadcrumbItems.map((item, index) => {
        const isLast = index === breadcrumbItems.length - 1;
        const Icon = item.icon;

        return (
          <React.Fragment key={`${item.href || item.label}-${index}`}>
            {item.href ? (
              <Link
                to={item.href}
                className="flex items-center space-x-1 text-muted-foreground hover:text-foreground transition-colors"
              >
                {Icon && <Icon className="h-4 w-4" />}
                <span>{item.label}</span>
              </Link>
            ) : (
              <span 
                className={cn(
                  'flex items-center space-x-1',
                  item.isActive 
                    ? 'text-foreground font-medium' 
                    : 'text-muted-foreground'
                )}
              >
                {Icon && <Icon className="h-4 w-4" />}
                <span>{item.label}</span>
              </span>
            )}
            {!isLast && separator}
          </React.Fragment>
        );
      })}
    </nav>
  );
}

/**
 * 頁面標題和麵包屑組合組件
 */
export interface PageHeaderProps {
  /**
   * 頁面標題
   */
  title: string;
  /**
   * 頁面描述
   */
  description?: string;
  /**
   * 麵包屑配置
   */
  breadcrumb?: BreadcrumbProps;
  /**
   * 右側操作區域
   */
  actions?: React.ReactNode;
  /**
   * 自訂類名
   */
  className?: string;
}

/**
 * 頁面標題組件
 * 整合麵包屑導航和頁面標題的完整標題區域
 */
export function PageHeader({
  title,
  description,
  breadcrumb,
  actions,
  className,
}: PageHeaderProps) {
  return (
    <div className={cn('space-y-4 pb-6 border-b', className)}>
      {/* 麵包屑導航 */}
      <Breadcrumb {...breadcrumb} />

      {/* 標題和操作區域 */}
      <div className="flex items-center justify-between">
        <div className="space-y-1">
          <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
          {description && (
            <p className="text-sm text-muted-foreground">{description}</p>
          )}
        </div>
        {actions && (
          <div className="flex items-center space-x-2">
            {actions}
          </div>
        )}
      </div>
    </div>
  );
}

/**
 * Hook: 使用麵包屑
 * 提供便捷的麵包屑狀態管理
 */
export function useBreadcrumb() {
  const location = useLocation();
  const [customItems, setCustomItems] = React.useState<BreadcrumbItem[]>([]);

  /**
   * 設置自訂麵包屑項目
   */
  const setBreadcrumb = (items: BreadcrumbItem[]) => {
    setCustomItems(items);
  };

  /**
   * 清除自訂麵包屑項目
   */
  const clearBreadcrumb = () => {
    setCustomItems([]);
  };

  /**
   * 獲取當前麵包屑項目
   */
  const breadcrumbItems = customItems.length > 0 
    ? customItems 
    : generateBreadcrumbsFromPath(location.pathname);

  return {
    breadcrumbItems,
    setBreadcrumb,
    clearBreadcrumb,
  };
} 