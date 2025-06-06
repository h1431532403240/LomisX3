import React from 'react';
import { 
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';

/**
 * 確認對話框屬性介面
 */
interface ConfirmDialogProps {
  /** 是否開啟對話框 */
  open: boolean;
  
  /** 開啟狀態變更回調 */
  onOpenChange: (open: boolean) => void;
  
  /** 對話框標題 */
  title: string;
  
  /** 對話框描述內容 */
  description: string;
  
  /** 確認按鈕文字 */
  confirmText?: string;
  
  /** 取消按鈕文字 */
  cancelText?: string;
  
  /** 確認操作回調 */
  onConfirm: () => void | Promise<void>;
  
  /** 取消操作回調 */
  onCancel?: () => void;
  
  /** 是否正在載入 */
  loading?: boolean;
  
  /** 按鈕變體樣式 */
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  
  /** 是否禁用確認按鈕 */
  disabled?: boolean;
  
  /** 自訂確認按鈕樣式類別 */
  confirmButtonClassName?: string;
  
  /** 自訂取消按鈕樣式類別 */
  cancelButtonClassName?: string;
}

/**
 * 確認對話框組件
 * 用於需要使用者確認的操作
 * 
 * 功能包含：
 * - 可自訂標題和描述
 * - 載入狀態指示器
 * - 多種按鈕樣式變體
 * - 鍵盤快捷鍵支援
 * - 無障礙性支援
 */
export const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
  open,
  onOpenChange,
  title,
  description,
  confirmText = '確認',
  cancelText = '取消',
  onConfirm,
  onCancel,
  loading = false,
  variant = 'default',
  disabled = false,
  confirmButtonClassName,
  cancelButtonClassName,
}) => {
  /**
   * 處理確認操作
   */
  const handleConfirm = async () => {
    try {
      await onConfirm();
      onOpenChange(false);
    } catch (error) {
      // 錯誤處理交由父組件處理
      console.error('Confirm action failed:', error);
    }
  };

  /**
   * 處理取消操作
   */
  const handleCancel = () => {
    if (onCancel) {
      onCancel();
    }
    onOpenChange(false);
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          <AlertDialogDescription>
            {description}
          </AlertDialogDescription>
        </AlertDialogHeader>
        
        <AlertDialogFooter>
          <AlertDialogCancel 
            onClick={handleCancel}
            disabled={loading}
            className={cancelButtonClassName}
          >
            {cancelText}
          </AlertDialogCancel>
          
          <AlertDialogAction 
            onClick={handleConfirm}
            disabled={disabled || loading}
            className={confirmButtonClassName}
            asChild
          >
            <Button 
              variant={variant}
              disabled={disabled || loading}
            >
              {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {confirmText}
            </Button>
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
};

/**
 * 簡化的刪除確認對話框
 */
interface DeleteConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  itemName: string;
  itemType?: string;
  onConfirm: () => void | Promise<void>;
  loading?: boolean;
}

export const DeleteConfirmDialog: React.FC<DeleteConfirmDialogProps> = ({
  open,
  onOpenChange,
  itemName,
  itemType = '項目',
  onConfirm,
  loading = false,
}) => {
  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      title={`確認刪除${itemType}`}
      description={`您確定要刪除「${itemName}」嗎？此操作無法復原。`}
      confirmText="刪除"
      cancelText="取消"
      onConfirm={onConfirm}
      loading={loading}
      variant="destructive"
    />
  );
};

/**
 * 簡化的狀態變更確認對話框
 */
interface StatusChangeConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  action: '啟用' | '停用' | '封鎖' | '解封';
  itemName: string;
  itemType?: string;
  onConfirm: () => void | Promise<void>;
  loading?: boolean;
}

export const StatusChangeConfirmDialog: React.FC<StatusChangeConfirmDialogProps> = ({
  open,
  onOpenChange,
  action,
  itemName,
  itemType = '項目',
  onConfirm,
  loading = false,
}) => {
  const getVariant = (): 'default' | 'destructive' => {
    switch (action) {
      case '停用':
      case '封鎖':
        return 'destructive';
      default:
        return 'default';
    }
  };

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      title={`確認${action}${itemType}`}
      description={`您確定要${action}「${itemName}」嗎？`}
      confirmText={action}
      cancelText="取消"
      onConfirm={onConfirm}
      loading={loading}
      variant={getVariant()}
    />
  );
};

/**
 * 批次操作確認對話框
 */
interface BatchConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  action: string;
  count: number;
  itemType?: string;
  onConfirm: () => void | Promise<void>;
  loading?: boolean;
  variant?: 'default' | 'destructive';
}

export const BatchConfirmDialog: React.FC<BatchConfirmDialogProps> = ({
  open,
  onOpenChange,
  action,
  count,
  itemType = '項目',
  onConfirm,
  loading = false,
  variant = 'default',
}) => {
  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      title={`批次${action}`}
      description={`您確定要對 ${count} 個${itemType}執行「${action}」操作嗎？`}
      confirmText={`${action} ${count} 個${itemType}`}
      cancelText="取消"
      onConfirm={onConfirm}
      loading={loading}
      variant={variant}
    />
  );
}; 