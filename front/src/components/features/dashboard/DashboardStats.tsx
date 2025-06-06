/**
 * DashboardStats - 儀表板統計卡片組件
 * 
 * 顯示系統關鍵指標和統計資料
 * 支援不同角色的資料顯示權限
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
  Users, 
  UserPlus, 
  UserCheck, 
  ShoppingCart, 
  Package, 
  TrendingUp, 
  TrendingDown,
  Activity,
  DollarSign,
  Eye,
  BarChart3
} from 'lucide-react';
import { PermissionGuard } from '@/components/common/permission-guard';
import { useAuthStore } from '@/stores/authStore';

/**
 * 統計卡片資料介面
 */
interface StatCard {
  id: string;
  title: string;
  value: string | number;
  change?: {
    value: number;
    type: 'increase' | 'decrease';
    period: string;
  };
  icon: React.ComponentType<{ className?: string }>;
  color: string;
  description?: string;
  permission?: string;
  onClick?: () => void;
}

/**
 * 儀表板統計組件屬性
 */
interface DashboardStatsProps {
  /** 自定義 CSS 類別 */
  className?: string;
  /** 統計資料 */
  stats?: StatCard[];
  /** 佈局方式 */
  layout?: 'grid-2' | 'grid-3' | 'grid-4';
  /** 是否顯示詳細資訊 */
  showDetails?: boolean;
  /** 是否顯示趨勢 */
  showTrends?: boolean;
}

/**
 * 模擬統計資料
 */
const mockStats: StatCard[] = [
  {
    id: 'total-users',
    title: '總用戶數',
    value: 1248,
    change: {
      value: 12.5,
      type: 'increase',
      period: '較上月'
    },
    icon: Users,
    color: 'text-blue-600 bg-blue-50',
    description: '系統註冊用戶總數',
    permission: 'users.read',
    onClick: () => console.log('查看用戶詳情')
  },
  {
    id: 'active-users',
    title: '活躍用戶',
    value: 892,
    change: {
      value: 8.2,
      type: 'increase',
      period: '較上週'
    },
    icon: UserCheck,
    color: 'text-green-600 bg-green-50',
    description: '本週活躍用戶數',
    permission: 'users.read'
  },
  {
    id: 'new-users',
    title: '新增用戶',
    value: 156,
    change: {
      value: 3.1,
      type: 'decrease',
      period: '較上月'
    },
    icon: UserPlus,
    color: 'text-purple-600 bg-purple-50',
    description: '本月新註冊用戶',
    permission: 'users.read'
  },
  {
    id: 'total-categories',
    title: '商品分類',
    value: 98,
    change: {
      value: 5.7,
      type: 'increase',
      period: '較上月'
    },
    icon: Package,
    color: 'text-orange-600 bg-orange-50',
    description: '活躍商品分類總數',
    permission: 'categories.read'
  },
  {
    id: 'orders-today',
    title: '今日訂單',
    value: 247,
    change: {
      value: 15.3,
      type: 'increase',
      period: '較昨日'
    },
    icon: ShoppingCart,
    color: 'text-cyan-600 bg-cyan-50',
    description: '今日訂單數量',
    permission: 'orders.read'
  },
  {
    id: 'revenue-today',
    title: '今日營收',
    value: 'NT$ 125,840',
    change: {
      value: 22.8,
      type: 'increase',
      period: '較昨日'
    },
    icon: DollarSign,
    color: 'text-emerald-600 bg-emerald-50',
    description: '今日總營收金額',
    permission: 'revenue.read'
  },
  {
    id: 'system-activity',
    title: '系統活動',
    value: '99.8%',
    change: {
      value: 0.2,
      type: 'increase',
      period: '正常運行'
    },
    icon: Activity,
    color: 'text-red-600 bg-red-50',
    description: '系統正常運行時間',
    permission: 'system.monitor'
  },
  {
    id: 'page-views',
    title: '頁面瀏覽',
    value: '58.2K',
    change: {
      value: 18.7,
      type: 'increase',
      period: '較上週'
    },
    icon: Eye,
    color: 'text-indigo-600 bg-indigo-50',
    description: '本週頁面瀏覽量',
    permission: 'analytics.read'
  }
];

/**
 * 儀表板統計組件
 */
export const DashboardStats: React.FC<DashboardStatsProps> = ({
  className = '',
  stats = mockStats,
  layout = 'grid-4',
  showDetails = true,
  showTrends = true,
}) => {
  const { hasPermission } = useAuthStore();

  /**
   * 篩選有權限查看的統計資料
   */
  const visibleStats = stats.filter(stat => {
    if (!stat.permission) return true;
    return hasPermission(stat.permission);
  });

  /**
   * 取得網格佈局類別
   */
  const getGridClass = () => {
    switch (layout) {
      case 'grid-2': return 'grid-cols-1 md:grid-cols-2';
      case 'grid-3': return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3';
      case 'grid-4': return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4';
      default: return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4';
    }
  };

  /**
   * 渲染趨勢指標
   */
  const renderTrend = (change?: StatCard['change']) => {
    if (!change || !showTrends) return null;

    const TrendIcon = change.type === 'increase' ? TrendingUp : TrendingDown;
    const colorClass = change.type === 'increase' ? 'text-green-600' : 'text-red-600';

    return (
      <div className={`flex items-center space-x-1 ${colorClass}`}>
        <TrendIcon className="h-3 w-3" />
        <span className="text-xs font-medium">
          {change.value}%
        </span>
        <span className="text-xs text-muted-foreground">
          {change.period}
        </span>
      </div>
    );
  };

  if (visibleStats.length === 0) {
    return (
      <Card className={className}>
        <CardContent className="flex items-center justify-center p-6">
          <div className="text-center space-y-2">
            <BarChart3 className="h-8 w-8 text-muted-foreground mx-auto" />
            <p className="text-muted-foreground">沒有可顯示的統計資料</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className={`grid gap-4 ${getGridClass()} ${className}`}>
      {visibleStats.map((stat) => {
        const IconComponent = stat.icon;
        
        return (
          <Card 
            key={stat.id} 
            className={`hover:shadow-md transition-shadow ${
              stat.onClick ? 'cursor-pointer' : ''
            }`}
            onClick={stat.onClick}
          >
            <CardContent className="p-6">
              <div className="flex items-center justify-between space-y-0 pb-2">
                <div className="space-y-1">
                  <p className="text-sm font-medium leading-none">
                    {stat.title}
                  </p>
                  {showDetails && stat.description && (
                    <p className="text-xs text-muted-foreground">
                      {stat.description}
                    </p>
                  )}
                </div>
                <div className={`p-2 rounded-full ${stat.color}`}>
                  <IconComponent className="h-4 w-4" />
                </div>
              </div>
              
              <div className="space-y-1">
                <div className="text-2xl font-bold">
                  {typeof stat.value === 'number' ? stat.value.toLocaleString() : stat.value}
                </div>
                
                {renderTrend(stat.change)}
              </div>
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
};

/**
 * 快速操作面板組件
 */
export const QuickActions: React.FC<{ className?: string }> = ({ className = '' }) => {
  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Activity className="h-5 w-5 text-blue-600" />
          快速操作
        </CardTitle>
        <CardDescription>
          常用功能快速存取
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        <PermissionGuard permissions={['users.create']}>
          <Button variant="outline" className="w-full justify-start">
            <UserPlus className="h-4 w-4 mr-2" />
            新增使用者
          </Button>
        </PermissionGuard>
        
        <PermissionGuard permissions={['categories.create']}>
          <Button variant="outline" className="w-full justify-start">
            <Package className="h-4 w-4 mr-2" />
            新增商品分類
          </Button>
        </PermissionGuard>
        
        <PermissionGuard permissions={['system.monitor']}>
          <Button variant="outline" className="w-full justify-start">
            <BarChart3 className="h-4 w-4 mr-2" />
            查看系統報告
          </Button>
        </PermissionGuard>
        
        <Button variant="outline" className="w-full justify-start">
          <Eye className="h-4 w-4 mr-2" />
          查看活動記錄
        </Button>
      </CardContent>
    </Card>
  );
};

export default DashboardStats; 