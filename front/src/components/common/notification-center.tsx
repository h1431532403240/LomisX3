import React from 'react';
import { 
  Bell, 
  X, 
  CheckCircle, 
  AlertCircle, 
  AlertTriangle, 
  Info,
  Settings,
  Trash2
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { useToast } from '@/components/ui/use-toast';
import { cn } from '@/lib/utils';

/**
 * 通知類型
 */
export type NotificationType = 'info' | 'success' | 'warning' | 'error';

/**
 * 通知項目介面
 */
export interface NotificationItem {
  /**
   * 通知 ID
   */
  id: string;
  /**
   * 通知類型
   */
  type: NotificationType;
  /**
   * 通知標題
   */
  title: string;
  /**
   * 通知內容
   */
  message?: string;
  /**
   * 創建時間
   */
  timestamp: Date;
  /**
   * 是否已讀
   */
  read: boolean;
  /**
   * 操作按鈕
   */
  actions?: {
    label: string;
    onClick: () => void;
    variant?: 'default' | 'destructive' | 'outline';
  }[];
  /**
   * 點擊處理
   */
  onClick?: () => void;
}

/**
 * 通知中心 Props
 */
export interface NotificationCenterProps {
  /**
   * 通知列表
   */
  notifications?: NotificationItem[];
  /**
   * 未讀數量
   */
  unreadCount?: number;
  /**
   * 標記全部已讀回調
   */
  onMarkAllRead?: () => void;
  /**
   * 清除所有通知回調
   */
  onClearAll?: () => void;
  /**
   * 刪除通知回調
   */
  onDeleteNotification?: (id: string) => void;
  /**
   * 標記已讀回調
   */
  onMarkAsRead?: (id: string) => void;
  /**
   * 自訂類名
   */
  className?: string;
}

/**
 * 獲取通知圖標
 */
function getNotificationIcon(type: NotificationType) {
  switch (type) {
    case 'success':
      return <CheckCircle className="h-4 w-4 text-green-500" />;
    case 'warning':
      return <AlertTriangle className="h-4 w-4 text-amber-500" />;
    case 'error':
      return <AlertCircle className="h-4 w-4 text-red-500" />;
    default:
      return <Info className="h-4 w-4 text-blue-500" />;
  }
}

/**
 * 格式化時間顯示
 */
function formatTimestamp(timestamp: Date): string {
  const now = new Date();
  const diff = now.getTime() - timestamp.getTime();
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return '剛剛';
  if (minutes < 60) return `${minutes} 分鐘前`;
  if (hours < 24) return `${hours} 小時前`;
  if (days < 7) return `${days} 天前`;
  
  return timestamp.toLocaleDateString('zh-TW', {
    month: 'short',
    day: 'numeric',
  });
}

/**
 * 通知項目組件
 */
function NotificationItemComponent({
  notification,
  onDelete,
  onMarkAsRead,
}: {
  notification: NotificationItem;
  onDelete?: (id: string) => void;
  onMarkAsRead?: (id: string) => void;
}) {
  const handleClick = () => {
    if (!notification.read && onMarkAsRead) {
      onMarkAsRead(notification.id);
    }
    if (notification.onClick) {
      notification.onClick();
    }
  };

  return (
    <div
      className={cn(
        'p-4 hover:bg-accent/50 transition-colors cursor-pointer',
        !notification.read && 'bg-accent/20'
      )}
      onClick={handleClick}
    >
      <div className="flex items-start gap-3">
        {/* 通知圖標 */}
        <div className="flex-shrink-0 mt-0.5">
          {getNotificationIcon(notification.type)}
        </div>

        {/* 通知內容 */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <h4 className={cn(
              'text-sm font-medium truncate',
              !notification.read && 'font-semibold'
            )}>
              {notification.title}
            </h4>
            {!notification.read && (
              <div className="w-2 h-2 bg-blue-500 rounded-full" />
            )}
          </div>
          
          {notification.message && (
            <p className="text-xs text-muted-foreground line-clamp-2 mb-2">
              {notification.message}
            </p>
          )}

          <div className="flex items-center justify-between">
            <span className="text-xs text-muted-foreground">
              {formatTimestamp(notification.timestamp)}
            </span>

            {/* 操作按鈕 */}
            {notification.actions && (
              <div className="flex gap-1">
                {notification.actions.map((action, index) => (
                  <Button
                    key={index}
                    variant={action.variant || 'outline'}
                    size="sm"
                    className="h-6 text-xs px-2"
                    onClick={(e) => {
                      e.stopPropagation();
                      action.onClick();
                    }}
                  >
                    {action.label}
                  </Button>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* 刪除按鈕 */}
        {onDelete && (
          <Button
            variant="ghost"
            size="sm"
            className="h-6 w-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
            onClick={(e) => {
              e.stopPropagation();
              onDelete(notification.id);
            }}
          >
            <X className="h-3 w-3" />
          </Button>
        )}
      </div>
    </div>
  );
}

/**
 * 通知中心組件
 * 提供完整的通知管理功能，包含歷史記錄和操作
 */
export function NotificationCenter({
  notifications = [],
  unreadCount = 0,
  onMarkAllRead,
  onClearAll,
  onDeleteNotification,
  onMarkAsRead,
  className,
}: NotificationCenterProps) {
  const [isOpen, setIsOpen] = React.useState(false);

  return (
    <Popover open={isOpen} onOpenChange={setIsOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="ghost"
          size="sm"
          className={cn('relative h-9 w-9 p-0', className)}
        >
          <Bell className="h-4 w-4" />
          {unreadCount > 0 && (
            <Badge
              variant="destructive"
              className="absolute -top-1 -right-1 h-5 w-5 rounded-full p-0 text-xs flex items-center justify-center"
            >
              {unreadCount > 99 ? '99+' : unreadCount}
            </Badge>
          )}
        </Button>
      </PopoverTrigger>

      <PopoverContent
        className="w-80 p-0"
        align="end"
        sideOffset={8}
      >
        {/* 標題列 */}
        <div className="flex items-center justify-between p-4 border-b">
          <h3 className="font-semibold">通知</h3>
          <div className="flex items-center gap-2">
            {unreadCount > 0 && onMarkAllRead && (
              <Button
                variant="ghost"
                size="sm"
                className="h-7 text-xs"
                onClick={onMarkAllRead}
              >
                全部已讀
              </Button>
            )}
            {notifications.length > 0 && onClearAll && (
              <Button
                variant="ghost"
                size="sm"
                className="h-7 w-7 p-0"
                onClick={onClearAll}
              >
                <Trash2 className="h-3 w-3" />
              </Button>
            )}
          </div>
        </div>

        {/* 通知列表 */}
        {notifications.length > 0 ? (
          <ScrollArea className="h-96">
            <div className="divide-y">
              {notifications.map((notification) => (
                <div key={notification.id} className="group">
                  <NotificationItemComponent
                    notification={notification}
                    onDelete={onDeleteNotification}
                    onMarkAsRead={onMarkAsRead}
                  />
                </div>
              ))}
            </div>
          </ScrollArea>
        ) : (
          <div className="p-8 text-center">
            <Bell className="h-8 w-8 text-muted-foreground mx-auto mb-2" />
            <p className="text-sm text-muted-foreground">暫無通知</p>
          </div>
        )}

        {/* 設定按鈕 */}
        <div className="border-t p-2">
          <Button variant="ghost" className="w-full justify-start h-8" size="sm">
            <Settings className="h-4 w-4 mr-2" />
            通知設定
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  );
}

/**
 * 快速通知功能
 * 提供便捷的通知發送方法
 */
export function useNotifications() {
  const { toast } = useToast();

  const showSuccess = (title: string, description?: string) => {
    toast({
      title,
      description,
      variant: 'default',
    });
  };

  const showError = (title: string, description?: string) => {
    toast({
      title,
      description,
      variant: 'destructive',
    });
  };

  const showWarning = (title: string, description?: string) => {
    toast({
      title: `⚠️ ${title}`,
      description,
      variant: 'default',
    });
  };

  const showInfo = (title: string, description?: string) => {
    toast({
      title: `ℹ️ ${title}`,
      description,
      variant: 'default',
    });
  };

  return {
    showSuccess,
    showError,
    showWarning,
    showInfo,
  };
}

/**
 * 通知管理 Hook
 * 提供完整的通知狀態管理
 */
export function useNotificationManager() {
  const [notifications, setNotifications] = React.useState<NotificationItem[]>([]);

  const addNotification = (notification: Omit<NotificationItem, 'id' | 'timestamp' | 'read'>) => {
    const newNotification: NotificationItem = {
      ...notification,
      id: Date.now().toString(),
      timestamp: new Date(),
      read: false,
    };
    
    setNotifications(prev => [newNotification, ...prev]);
  };

  const markAsRead = (id: string) => {
    setNotifications(prev =>
      prev.map(notification =>
        notification.id === id
          ? { ...notification, read: true }
          : notification
      )
    );
  };

  const markAllRead = () => {
    setNotifications(prev =>
      prev.map(notification => ({ ...notification, read: true }))
    );
  };

  const deleteNotification = (id: string) => {
    setNotifications(prev => prev.filter(notification => notification.id !== id));
  };

  const clearAll = () => {
    setNotifications([]);
  };

  const unreadCount = notifications.filter(n => !n.read).length;

  return {
    notifications,
    unreadCount,
    addNotification,
    markAsRead,
    markAllRead,
    deleteNotification,
    clearAll,
  };
} 