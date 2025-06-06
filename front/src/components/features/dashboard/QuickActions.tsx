/**
 * QuickActions - 快速操作面板組件
 * 
 * 提供常用功能的快速入口，根據使用者權限動態顯示
 * - 支援權限過濾
 * - 響應式佈局
 * - 圖標和描述
 * - 快速導航
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React from 'react';
import { Link } from 'react-router-dom';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { PermissionGuard } from '@/components/common/permission-guard';
import { 
  Users, 
  Package, 
  Settings, 
  Bell, 
  Plus,
  ShoppingCart,
  BarChart3,
  Shield,
  FileText,
  Database,
  Wrench,
  MessageSquare,
  type LucideIcon
} from 'lucide-react';
import type { UserRole } from '@/types/user';

/**
 * 快速操作項目介面
 */
interface QuickActionItem {
  /** 標題 */
  title: string;
  /** 描述 */
  description: string;
  /** 圖標組件 */
  icon: LucideIcon;
  /** 導航連結 */
  link: string;
  /** 背景顏色 */
  color: string;
  /** 權限要求 */
  permission?: string;
  /** 角色要求 */
  role?: UserRole;
  /** 是否為新功能 */
  isNew?: boolean;
}

/**
 * 快速操作項目配置
 */
const quickActionItems: QuickActionItem[] = [
  {
    title: '新增使用者',
    description: '建立新的使用者帳戶',
    icon: Users,
    link: '/users/create',
    color: 'bg-blue-500 hover:bg-blue-600',
    permission: 'users.create'
  },
  {
    title: '商品分類',
    description: '管理商品分類結構',
    icon: Package,
    link: '/categories',
    color: 'bg-green-500 hover:bg-green-600',
    permission: 'categories.read'
  },
  {
    title: '角色權限',
    description: '設定使用者角色和權限',
    icon: Shield,
    link: '/roles',
    color: 'bg-purple-500 hover:bg-purple-600',
    permission: 'roles.read'
  },
  {
    title: '系統設定',
    description: '配置系統參數和選項',
    icon: Settings,
    link: '/settings',
    color: 'bg-orange-500 hover:bg-orange-600',
    permission: 'settings.read'
  },
  {
    title: '訂單管理',
    description: '查看和處理訂單',
    icon: ShoppingCart,
    link: '/orders',
    color: 'bg-indigo-500 hover:bg-indigo-600',
    permission: 'orders.read'
  },
  {
    title: '數據分析',
    description: '查看業務統計報表',
    icon: BarChart3,
    link: '/analytics',
    color: 'bg-cyan-500 hover:bg-cyan-600',
    permission: 'analytics.read'
  },
  {
    title: '系統通知',
    description: '查看系統通知和警報',
    icon: Bell,
    link: '/notifications',
    color: 'bg-amber-500 hover:bg-amber-600'
  },
  {
    title: '備份管理',
    description: '資料備份和還原',
    icon: Database,
    link: '/backup',
    color: 'bg-slate-500 hover:bg-slate-600',
    permission: 'backup.read',
    role: 'admin'
  },
  {
    title: '系統工具',
    description: '維護和除錯工具',
    icon: Wrench,
    link: '/tools',
    color: 'bg-red-500 hover:bg-red-600',
    permission: 'tools.read',
    role: 'admin'
  },
  {
    title: '訊息中心',
    description: '使用者訊息和通知',
    icon: MessageSquare,
    link: '/messages',
    color: 'bg-pink-500 hover:bg-pink-600',
    isNew: true
  }
];

/**
 * QuickActions 組件屬性
 */
interface QuickActionsProps {
  /** 自訂類別名稱 */
  className?: string;
  /** 最大顯示項目數 */
  maxItems?: number;
  /** 佈局方式 */
  layout?: 'grid' | 'list';
  /** 是否顯示標題 */
  showTitle?: boolean;
}

/**
 * 快速操作面板組件
 */
export function QuickActions({ 
  className = '',
  maxItems,
  layout = 'grid',
  showTitle = true
}: QuickActionsProps) {
  // 根據maxItems限制顯示項目數
  const displayItems = maxItems ? quickActionItems.slice(0, maxItems) : quickActionItems;

  /**
   * 渲染單個快速操作項目
   */
  const renderActionItem = (action: QuickActionItem, index: number) => {
    const IconComponent = action.icon;
    
    const actionContent = (
      <Card 
        key={index} 
        className={`
          transition-all duration-200 
          hover:shadow-lg hover:scale-105 
          cursor-pointer border-0 shadow-sm
          ${layout === 'list' ? 'hover:translate-x-1' : ''}
        `}
      >
        <CardContent className={`
          p-4 
          ${layout === 'list' ? 'flex items-center space-x-4' : ''}
        `}>
          <div className={`
            flex items-center
            ${layout === 'grid' ? 'space-x-3' : 'space-x-4'}
          `}>
            {/* 圖標區域 */}
            <div className={`
              p-3 rounded-lg transition-colors
              ${action.color}
              ${layout === 'list' ? 'flex-shrink-0' : ''}
            `}>
              <IconComponent className="h-5 w-5 text-white" />
            </div>
            
            {/* 內容區域 */}
            <div className={layout === 'list' ? 'flex-1' : ''}>
              <div className="flex items-center space-x-2">
                <h3 className="font-medium text-foreground">
                  {action.title}
                </h3>
                {action.isNew && (
                  <span className="px-2 py-1 text-xs bg-red-500 text-white rounded-full">
                    New
                  </span>
                )}
              </div>
              <p className="text-sm text-muted-foreground mt-1">
                {action.description}
              </p>
            </div>
          </div>
          
          {/* List 佈局的額外操作按鈕 */}
          {layout === 'list' && (
            <Button variant="ghost" size="sm" className="flex-shrink-0">
              前往
            </Button>
          )}
        </CardContent>
      </Card>
    );

    // 根據權限要求包裝組件
    if (action.permission || action.role) {
      return (
        <PermissionGuard
          key={index}
          permission={action.permission}
          role={action.role}
          fallback={null}
        >
          <Link to={action.link} className="block">
            {actionContent}
          </Link>
        </PermissionGuard>
      );
    }

    return (
      <Link key={index} to={action.link} className="block">
        {actionContent}
      </Link>
    );
  };

  return (
    <div className={`space-y-4 ${className}`}>
      {/* 標題區域 */}
      {showTitle && (
        <div className="flex items-center justify-between">
          <h2 className="text-xl font-semibold text-foreground">
            快速操作
          </h2>
          <Button variant="outline" size="sm" asChild>
            <Link to="/dashboard/actions">
              查看全部
            </Link>
          </Button>
        </div>
      )}

      {/* 操作項目網格/列表 */}
      <div className={`
        ${layout === 'grid' 
          ? 'grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' 
          : 'space-y-3'
        }
      `}>
        {displayItems.map(renderActionItem)}
      </div>

      {/* 更多操作提示 */}
      {maxItems && quickActionItems.length > maxItems && (
        <div className="text-center pt-4">
          <Button variant="outline" asChild>
            <Link to="/dashboard/actions">
              查看更多操作 ({quickActionItems.length - maxItems} 個)
            </Link>
          </Button>
        </div>
      )}
    </div>
  );
}

/**
 * 快速操作項目組件 (單獨使用)
 */
export function QuickActionItem({ 
  action,
  layout = 'grid' 
}: { 
  action: QuickActionItem;
  layout?: 'grid' | 'list';
}) {
  const IconComponent = action.icon;
  
  const content = (
    <Card className="transition-all hover:shadow-md hover:scale-105 cursor-pointer">
      <CardContent className={`p-4 ${layout === 'list' ? 'flex items-center space-x-4' : ''}`}>
        <div className="flex items-center space-x-3">
          <div className={`p-2 rounded-lg ${action.color}`}>
            <IconComponent className="h-5 w-5 text-white" />
          </div>
          <div>
            <h3 className="font-medium">{action.title}</h3>
            <p className="text-sm text-muted-foreground">
              {action.description}
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  );

  if (action.permission || action.role) {
    return (
      <PermissionGuard
        permission={action.permission}
        role={action.role}
        fallback={null}
      >
        <Link to={action.link}>
          {content}
        </Link>
      </PermissionGuard>
    );
  }

  return (
    <Link to={action.link}>
      {content}
    </Link>
  );
}

export default QuickActions; 