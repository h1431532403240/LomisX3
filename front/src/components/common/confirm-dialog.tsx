/**
 * 確認對話框組件
 * 提供統一的確認操作 UI 和邏輯
 */
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
import { Loader2, AlertTriangle, Trash2, CheckCircle } from 'lucide-react';

/**
 * 確認對話框類型
 */
export type ConfirmDialogType = 'default' | 'delete' | 'warning' | 'success';

/**
 * 確認對話框屬性
 */
export interface ConfirmDialogProps {
  /** 是否顯示對話框 */
  open: boolean;
  /** 關閉對話框回調 */
  onOpenChange: (open: boolean) => void;
  /** 確認回調 */
  onConfirm: () => void | Promise<void>;
  /** 對話框類型 */
  type?: ConfirmDialogType;
  /** 標題 */
  title?: string;
  /** 描述內容 */
  description?: string;
  /** 確認按鈕文字 */
  confirmText?: string;
  /** 取消按鈕文字 */
  cancelText?: string;
  /** 是否正在載入 */
  loading?: boolean;
  /** 是否禁用確認按鈕 */
  disabled?: boolean;
  /** 自訂圖標 */
  icon?: React.ReactNode;
}

/**
 * 根據類型獲取預設配置
 */
function getDefaultConfig(type: ConfirmDialogType) {
  switch (type) {
    case 'delete':
      return {
        title: '確認刪除',
        description: '此操作無法復原，確定要刪除嗎？',
        confirmText: '刪除',
        icon: <Trash2 className="h-6 w-6 text-destructive" />,
        confirmVariant: 'destructive' as const,
      };
    case 'warning':
      return {
        title: '警告',
        description: '此操作可能有風險，請確認是否繼續？',
        confirmText: '繼續',
        icon: <AlertTriangle className="h-6 w-6 text-orange-500" />,
        confirmVariant: 'default' as const,
      };
    case 'success':
      return {
        title: '確認操作',
        description: '確定要執行此操作嗎？',
        confirmText: '確認',
        icon: <CheckCircle className="h-6 w-6 text-green-500" />,
        confirmVariant: 'default' as const,
      };
    default:
      return {
        title: '確認',
        description: '確定要執行此操作嗎？',
        confirmText: '確認',
        icon: null,
        confirmVariant: 'default' as const,
      };
  }
}

/**
 * 確認對話框組件
 */
export function ConfirmDialog({
  open,
  onOpenChange,
  onConfirm,
  type = 'default',
  title,
  description,
  confirmText,
  cancelText = '取消',
  loading = false,
  disabled = false,
  icon,
}: ConfirmDialogProps) {
  const defaultConfig = getDefaultConfig(type);
  const finalTitle = title || defaultConfig.title;
  const finalDescription = description || defaultConfig.description;
  const finalConfirmText = confirmText || defaultConfig.confirmText;
  const finalIcon = icon !== undefined ? icon : defaultConfig.icon;

  /**
   * 處理確認操作
   */
  const handleConfirm = async () => {
    try {
      await onConfirm();
      onOpenChange(false);
    } catch (error) {
      console.error('確認操作失敗:', error);
      // 錯誤情況下不關閉對話框，讓使用者看到錯誤提示
    }
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent className="sm:max-w-md">
        <AlertDialogHeader>
          <AlertDialogTitle className="flex items-center gap-3">
            {finalIcon}
            {finalTitle}
          </AlertDialogTitle>
          <AlertDialogDescription>
            {finalDescription}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={loading}>
            {cancelText}
          </AlertDialogCancel>
          <AlertDialogAction asChild>
            <Button
              variant={defaultConfig.confirmVariant}
              onClick={handleConfirm}
              disabled={disabled || loading}
            >
              {loading && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
              {finalConfirmText}
            </Button>
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}

/**
 * 刪除確認對話框
 * 專門用於刪除操作的簡化版本
 */
export interface DeleteConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: () => void | Promise<void>;
  title?: string;
  description?: string;
  loading?: boolean;
  itemName?: string;
}

export function DeleteConfirmDialog({
  open,
  onOpenChange,
  onConfirm,
  title,
  description,
  loading = false,
  itemName,
}: DeleteConfirmDialogProps) {
  const finalDescription = description || 
    (itemName ? `確定要刪除「${itemName}」嗎？此操作無法復原。` : '確定要刪除此項目嗎？此操作無法復原。');

  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      onConfirm={onConfirm}
      type="delete"
      title={title}
      description={finalDescription}
      loading={loading}
    />
  );
}

/**
 * 批次操作確認對話框
 */
export interface BatchConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: () => void | Promise<void>;
  action: string;
  selectedCount: number;
  loading?: boolean;
}

export function BatchConfirmDialog({
  open,
  onOpenChange,
  onConfirm,
  action,
  selectedCount,
  loading = false,
}: BatchConfirmDialogProps) {
  return (
    <ConfirmDialog
      open={open}
      onOpenChange={onOpenChange}
      onConfirm={onConfirm}
      type="warning"
      title={`批次${action}`}
      description={`確定要對選中的 ${selectedCount} 個項目執行「${action}」操作嗎？`}
      confirmText={`批次${action}`}
      loading={loading}
    />
  );
} 