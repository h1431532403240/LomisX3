import { toast } from 'sonner';

/**
 * 統一的 Toast 訊息管理系統
 * 
 * 提供語意化的通知方法，確保一致的用戶體驗
 */
export const useAppToast = () => {
  const { toast } = useToast();

  return {
    /**
     * 成功訊息
     */
    success: (title: string, description?: string) => {
      toast({
        title,
        description,
        variant: 'default',
        className: 'bg-green-50 text-green-900 border-green-200',
      });
    },

    /**
     * 錯誤訊息
     */
    error: (title: string, description?: string) => {
      toast({
        title,
        description,
        variant: 'destructive',
      });
    },

    /**
     * 警告訊息
     */
    warning: (title: string, description?: string) => {
      toast({
        title,
        description,
        className: 'bg-yellow-50 text-yellow-900 border-yellow-200',
      });
    },

    /**
     * 資訊訊息
     */
    info: (title: string, description?: string) => {
      toast({
        title,
        description,
        className: 'bg-blue-50 text-blue-900 border-blue-200',
      });
    },

    /**
     * 樂觀更新失敗回滾通知
     */
    rollback: (operation: string) => {
      toast({
        title: '操作已回復',
        description: `${operation}尚未儲存，已回復原始狀態`,
        className: 'bg-yellow-50 text-yellow-900 border-yellow-200',
      });
    },
  };
}; 