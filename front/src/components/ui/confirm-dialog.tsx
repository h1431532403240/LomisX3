/**
 * 🔔 確認對話框組件
 * 提供標準化的確認對話框 UI，支援多種變體樣式
 * 包含完整的無障礙功能和載入狀態處理
 */

'use client';

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
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { AlertTriangle, Trash2, Info, HelpCircle } from 'lucide-react';

/**
 * 🎨 確認對話框變體類型
 */
export type ConfirmDialogVariant = 'default' | 'destructive' | 'warning' | 'info';

/**
 * 🔧 確認對話框 Props 介面
 */
interface ConfirmDialogProps {
  /** 是否開啟對話框 */
  open?: boolean;
  /** 開啟狀態變更事件 */
  onOpenChange?: (open: boolean) => void;
  /** 對話框標題 */
  title: string;
  /** 對話框描述內容 */
  description: string;
  /** 確認按鈕文字 */
  confirmText?: string;
  /** 取消按鈕文字 */
  cancelText?: string;
  /** 對話框變體 */
  variant?: ConfirmDialogVariant;
  /** 確認事件 */
  onConfirm: () => void | Promise<void>;
  /** 取消事件 */
  onCancel?: () => void;
  /** 是否顯示載入狀態 */
  loading?: boolean;
  /** 觸發器元素 */
  children?: React.ReactNode;
}

/**
 * 🎭 取得變體對應的圖示
 */
const getVariantIcon = (variant: ConfirmDialogVariant) => {
  switch (variant) {
    case 'destructive':
      return <Trash2 className="h-4 w-4" />;
    case 'warning':
      return <AlertTriangle className="h-4 w-4" />;
    case 'info':
      return <Info className="h-4 w-4" />;
    default:
      return <HelpCircle className="h-4 w-4" />;
  }
};

/**
 * 🎨 取得變體對應的樣式
 */
const getVariantStyles = (variant: ConfirmDialogVariant) => {
  switch (variant) {
    case 'destructive':
      return {
        confirmButton: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
        icon: 'text-destructive',
      };
    case 'warning':
      return {
        confirmButton: 'bg-orange-600 text-white hover:bg-orange-700',
        icon: 'text-orange-600',
      };
    case 'info':
      return {
        confirmButton: 'bg-blue-600 text-white hover:bg-blue-700',
        icon: 'text-blue-600',
      };
    default:
      return {
        confirmButton: '',
        icon: 'text-muted-foreground',
      };
  }
};

/**
 * 🔔 確認對話框組件
 */
export function ConfirmDialog({
  open,
  onOpenChange,
  title,
  description,
  confirmText = '確認',
  cancelText = '取消',
  variant = 'default',
  onConfirm,
  onCancel,
  loading = false,
  children,
}: ConfirmDialogProps) {
  const styles = getVariantStyles(variant);
  const icon = getVariantIcon(variant);

  /**
   * 🔄 處理確認事件
   */
  const handleConfirm = async () => {
    try {
      await onConfirm();
      onOpenChange?.(false);
    } catch (error) {
      // 錯誤處理由調用方負責，使用 void 明確忽略錯誤
      void error;
    }
  };

  /**
   * 🔄 處理取消事件
   */
  const handleCancel = () => {
    onCancel?.();
    onOpenChange?.(false);
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      {children && <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>}
      
      <AlertDialogContent className="sm:max-w-[425px]">
        <AlertDialogHeader>
          <AlertDialogTitle className="flex items-center gap-2">
            <span className={styles.icon}>{icon}</span>
            {title}
          </AlertDialogTitle>
          <AlertDialogDescription className="text-left">
            {description}
          </AlertDialogDescription>
        </AlertDialogHeader>
        
        <AlertDialogFooter>
          <AlertDialogCancel onClick={handleCancel} disabled={loading}>
            {cancelText}
          </AlertDialogCancel>
          <AlertDialogAction
            onClick={() => void handleConfirm()}
            disabled={loading}
            className={styles.confirmButton}
          >
            {loading ? (
              <>
                <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                處理中...
              </>
            ) : (
              confirmText
            )}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}

/**
 * 🎯 Hook 形式的確認對話框
 * 提供更簡潔的使用方式
 */
export function useConfirmDialog() {
  const [dialogState, setDialogState] = React.useState<{
    open: boolean;
    title: string;
    description: string;
    variant: ConfirmDialogVariant;
    onConfirm: () => void | Promise<void>;
    confirmText?: string;
    cancelText?: string;
  } | null>(null);

  /**
   * 🔔 顯示確認對話框
   */
  const confirm = React.useCallback((options: {
    title: string;
    description: string;
    variant?: ConfirmDialogVariant;
    confirmText?: string;
    cancelText?: string;
    onConfirm: () => void | Promise<void>;
  }) => {
    setDialogState({
      open: true,
      ...options,
      variant: options.variant ?? 'default',
    });
  }, []);

  /**
   * 🔄 關閉對話框
   */
  const closeDialog = React.useCallback(() => {
    setDialogState(null);
  }, []);

  /**
   * 🎯 對話框組件
   */
  const ConfirmDialogComponent = dialogState ? (
    <ConfirmDialog
      open={dialogState.open}
      onOpenChange={(open) => {
        if (!open) closeDialog();
      }}
      title={dialogState.title}
      description={dialogState.description}
      variant={dialogState.variant}
      confirmText={dialogState.confirmText}
      cancelText={dialogState.cancelText}
      onConfirm={dialogState.onConfirm}
    />
  ) : null;

  return {
    confirm,
    closeDialog,
    ConfirmDialog: ConfirmDialogComponent,
  };
} 