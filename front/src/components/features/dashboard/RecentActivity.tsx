/**
 * RecentActivity - 最近活動記錄組件
 * 
 * 顯示系統最近的活動記錄
 * - 使用者操作記錄
 * - 系統事件日誌
 * - 業務活動追蹤
 * - 即時更新機制
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React, { useMemo } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
  Activity, 
  Clock,
  Users,
  Settings,
  UserPlus,
  Edit,
  Trash2,
  Eye,
  RefreshCw,
  Bell,
  AlertCircle,
  CheckCircle,
  Info,
  XCircle,
  Filter
} from 'lucide-react';
import { PermissionGuard } from '@/components/common/permission-guard';

/**
 * 活動類型枚舉
 */
type ActivityType = 'create' | 'update' | 'delete' | 'view' | 'login' | 'logout' | 'system' | 'error';

/**
 * 活動記錄介面
 */
interface ActivityRecord {
  id: string;
  type: ActivityType;
  title: string;
  description: string;
  user: {
    name: string;
    avatar?: string;
    role: string;
  };
  timestamp: string;
  module: string;
  status: 'success' | 'warning' | 'error' | 'info';
}

/**
 * 組件屬性
 */
interface RecentActivityProps {
  /** 自定義 CSS 類別 */
  className?: string;
  /** 顯示項目數量 */
  limit?: number;
  /** 是否顯示操作按鈕 */
  showActions?: boolean;
  /** 是否顯示篩選功能 */
  showFilters?: boolean;
  /** 是否自動重新整理 */
  autoRefresh?: boolean;
}

/**
 * 最近活動記錄組件
 */
export const RecentActivity: React.FC<RecentActivityProps> = ({
  className = '',
  limit = 8,
  showActions = true,
  showFilters = false,
  autoRefresh = false
}) => {
  /**
   * 模擬活動記錄資料
   */
  const activities = useMemo((): ActivityRecord[] => {
    return [
      {
        id: '1',
        type: 'create' as ActivityType,
        title: '新增使用者',
        description: '管理員 Alice 建立了新的使用者帳戶「李小明」',
        user: {
          name: 'Alice Chen',
          avatar: '/avatars/alice.jpg',
          role: '系統管理員'
        },
        timestamp: '2 分鐘前',
        module: '使用者管理',
        status: 'success' as const
      },
      {
        id: '2',
        type: 'update' as ActivityType,
        title: '更新商品分類',
        description: '店長 Bob 修改了「浴室家具」分類的排序和描述',
        user: {
          name: 'Bob Wang',
          avatar: '/avatars/bob.jpg',
          role: '店長'
        },
        timestamp: '15 分鐘前',
        module: '商品管理',
        status: 'success' as const
      },
      {
        id: '3',
        type: 'login' as ActivityType,
        title: '使用者登入',
        description: '新用戶 Charlie 首次登入系統',
        user: {
          name: 'Charlie Liu',
          avatar: '/avatars/charlie.jpg',
          role: '一般使用者'
        },
        timestamp: '23 分鐘前',
        module: '認證系統',
        status: 'info' as const
      },
      {
        id: '4',
        type: 'error' as ActivityType,
        title: '權限錯誤',
        description: '使用者 David 嘗試存取未授權的管理員功能',
        user: {
          name: 'David Chang',
          avatar: '/avatars/david.jpg',
          role: '一般使用者'
        },
        timestamp: '35 分鐘前',
        module: '權限控制',
        status: 'error' as const
      },
      {
        id: '5',
        type: 'delete' as ActivityType,
        title: '刪除過期資料',
        description: '系統自動清理了30天前的臨時檔案和日誌',
        user: {
          name: '系統排程',
          role: '自動化系統'
        },
        timestamp: '1 小時前',
        module: '系統維護',
        status: 'success' as const
      }
    ].slice(0, limit);
  }, [limit]);

  /**
   * 取得活動類型圖標
   */
  const getActivityIcon = (type: ActivityType) => {
    switch (type) {
      case 'create':
        return UserPlus;
      case 'update':
        return Edit;
      case 'delete':
        return Trash2;
      case 'view':
        return Eye;
      case 'login':
      case 'logout':
        return Users;
      case 'system':
        return Settings;
      case 'error':
        return AlertCircle;
      default:
        return Activity;
    }
  };

  /**
   * 取得狀態樣式
   */
  const getStatusStyle = (status: ActivityRecord['status']) => {
    switch (status) {
      case 'success':
        return {
          badge: 'bg-green-100 text-green-800 hover:bg-green-200',
          icon: CheckCircle,
          iconColor: 'text-green-600'
        };
      case 'warning':
        return {
          badge: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
          icon: AlertCircle,
          iconColor: 'text-yellow-600'
        };
      case 'error':
        return {
          badge: 'bg-red-100 text-red-800 hover:bg-red-200',
          icon: XCircle,
          iconColor: 'text-red-600'
        };
      case 'info':
      default:
        return {
          badge: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
          icon: Info,
          iconColor: 'text-blue-600'
        };
    }
  };

  return (
    <Card className={`transition-all hover:shadow-md ${className}`}>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4">
        <div className="space-y-1">
          <CardTitle className="text-lg font-semibold flex items-center gap-2">
            <Activity className="h-5 w-5" />
            最近活動
          </CardTitle>
          <CardDescription className="text-sm">
            系統最新的操作記錄和事件日誌
          </CardDescription>
        </div>
        
        {showActions && (
          <div className="flex items-center gap-2">
            {autoRefresh && (
              <Badge variant="outline" className="gap-1">
                <Clock className="h-3 w-3" />
                即時更新
              </Badge>
            )}
            
            {showFilters && (
              <Button variant="outline" size="sm">
                <Filter className="h-4 w-4 mr-2" />
                篩選
              </Button>
            )}
            
            <Button variant="outline" size="sm">
              <RefreshCw className="h-4 w-4 mr-2" />
              重新整理
            </Button>
            
            <PermissionGuard permission="activity.read">
              <Button variant="outline" size="sm">
                <Bell className="h-4 w-4 mr-2" />
                查看全部
              </Button>
            </PermissionGuard>
          </div>
        )}
      </CardHeader>
      
      <CardContent>
        <div className="space-y-4">
          {activities.length === 0 ? (
            <div className="text-center py-8">
              <Activity className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
              <p className="text-muted-foreground">暫無活動記錄</p>
            </div>
          ) : (
            activities.map((activity) => {
              const ActivityIcon = getActivityIcon(activity.type);
              const statusStyle = getStatusStyle(activity.status);
              const StatusIcon = statusStyle.icon;
              
              return (
                <div 
                  key={activity.id} 
                  className="flex items-start gap-3 p-3 rounded-lg hover:bg-muted/50 transition-colors"
                >
                  {/* 使用者頭像 */}
                  <Avatar className="h-8 w-8 flex-shrink-0">
                    <AvatarImage src={activity.user.avatar} alt={activity.user.name} />
                    <AvatarFallback>
                      {activity.user.name.charAt(0)}
                    </AvatarFallback>
                  </Avatar>
                  
                  {/* 活動內容 */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <ActivityIcon className="h-4 w-4 text-muted-foreground" />
                          <h4 className="text-sm font-medium truncate">
                            {activity.title}
                          </h4>
                          <StatusIcon className={`h-3 w-3 flex-shrink-0 ${statusStyle.iconColor}`} />
                        </div>
                        
                        <p className="text-sm text-muted-foreground line-clamp-2">
                          {activity.description}
                        </p>
                        
                        <div className="flex items-center gap-2 mt-2">
                          <Badge variant="secondary" className="text-xs">
                            {activity.module}
                          </Badge>
                          <Badge 
                            variant="secondary" 
                            className={`text-xs ${statusStyle.badge}`}
                          >
                            {activity.status}
                          </Badge>
                          <span className="text-xs text-muted-foreground">
                            {activity.user.role}
                          </span>
                        </div>
                      </div>
                      
                      {/* 時間戳記 */}
                      <div className="text-xs text-muted-foreground flex-shrink-0">
                        {activity.timestamp}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>
        
        {/* 頁腳資訊 */}
        {activities.length > 0 && (
          <div className="border-t pt-4 mt-4">
            <div className="flex items-center justify-between text-sm text-muted-foreground">
              <div>
                顯示最近 {activities.length} 筆記錄
              </div>
              <div className="flex items-center gap-4">
                <div className="flex items-center gap-1">
                  <CheckCircle className="h-3 w-3 text-green-600" />
                  <span>成功</span>
                </div>
                <div className="flex items-center gap-1">
                  <AlertCircle className="h-3 w-3 text-yellow-600" />
                  <span>警告</span>
                </div>
                <div className="flex items-center gap-1">
                  <XCircle className="h-3 w-3 text-red-600" />
                  <span>錯誤</span>
                </div>
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default RecentActivity; 