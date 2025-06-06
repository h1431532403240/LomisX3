/**
 * 📊 批次操作工具列組件
 * 基於 shadcn/ui 封裝的可重用批次操作工具列
 * 提供統一的批次選擇和操作介面
 */

import React from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { 
  CheckSquare, 
  Square, 
  Minus, 
  Trash2, 
  Eye, 
  EyeOff, 
  MoreHorizontal,
  X,
} from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

/**
 * 🎯 批次操作項目介面
 */
export interface BulkAction {
  /** 操作 ID */
  id: string;
  /** 操作標籤 */
  label: string;
  /** 操作圖示 */
  icon?: React.ComponentType<{ className?: string }>;
  /** 操作變體 */
  variant?: 'default' | 'destructive' | 'secondary';
  /** 是否禁用 */
  disabled?: boolean;
  /** 操作處理函數 */
  handler: (selectedIds: (string | number)[]) => void | Promise<void>;
}

/**
 * 🔧 BulkActionsBar Props 介面
 */
interface BulkActionsBarProps {
  /** 總項目數量 */
  totalCount: number;
  /** 已選擇的項目 ID 陣列 */
  selectedIds: (string | number)[];
  /** 選擇狀態變更事件 */
  onSelectionChange?: (selectedIds: (string | number)[]) => void;
  /** 全選事件 */
  onSelectAll: () => void;
  /** 清除選擇事件 */
  onClearSelection: () => void;
  /** 批次操作項目 */
  actions: BulkAction[];
  /** 是否顯示 */
  visible?: boolean;
  /** 額外的 CSS 類名 */
  className?: string;
  /** 載入狀態 */
  loading?: boolean;
  /** 自訂選擇狀態文字 */
  selectionText?: (selectedCount: number, totalCount: number) => string;
}

/**
 * 📊 批次操作工具列組件
 */
export function BulkActionsBar({
  totalCount,
  selectedIds,
  onSelectAll,
  onClearSelection,
  actions,
  visible = true,
  className,
  loading = false,
  selectionText,
}: BulkActionsBarProps) {
  const selectedCount = selectedIds.length;
  const isAllSelected = selectedCount === totalCount && totalCount > 0;
  const isPartiallySelected = selectedCount > 0 && selectedCount < totalCount;

  /**
   * 🔄 處理全選/取消全選
   */
  const handleSelectAllToggle = () => {
    if (isAllSelected) {
      onClearSelection();
    } else {
      onSelectAll();
    }
  };

  /**
   * 🎨 取得選擇狀態圖示
   */
  const getSelectionIcon = () => {
    if (isAllSelected) {
      return <CheckSquare className="h-4 w-4" />;
    } else if (isPartiallySelected) {
      return <Minus className="h-4 w-4" />;
    } else {
      return <Square className="h-4 w-4" />;
    }
  };

  /**
   * 📝 取得選擇狀態文字
   */
  const getSelectionText = () => {
    if (selectionText) {
      return selectionText(selectedCount, totalCount);
    }
    
    if (selectedCount === 0) {
      return `共 ${totalCount} 個項目`;
    } else if (isAllSelected) {
      return `已選擇全部 ${totalCount} 個項目`;
    } else {
      return `已選擇 ${selectedCount} / ${totalCount} 個項目`;
    }
  };

  /**
   * 🎯 執行批次操作
   */
  const handleActionClick = async (action: BulkAction) => {
    if ((action.disabled ?? false) || loading || selectedCount === 0) return;
    
    try {
      await action.handler(selectedIds);
    } catch (error) {
      console.error(`批次操作 ${action.id} 執行失敗:`, error);
    }
  };

  if (!visible || totalCount === 0) {
    return null;
  }

  return (
    <div className={cn(
      'flex items-center justify-between p-3 bg-muted/50 border rounded-lg',
      'transition-all duration-200 ease-in-out',
      selectedCount > 0 && 'bg-primary/5 border-primary/20',
      className
    )}>
      {/* 🎯 選擇狀態區域 */}
      <div className="flex items-center gap-3">
        <Button
          variant="ghost"
          size="sm"
          onClick={handleSelectAllToggle}
          disabled={loading}
          className="h-8 w-8 p-0"
        >
          {getSelectionIcon()}
        </Button>
        
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">
            {getSelectionText()}
          </span>
          
          {selectedCount > 0 && (
            <Badge variant="secondary" className="text-xs">
              {selectedCount}
            </Badge>
          )}
        </div>
      </div>

      {/* 🎯 操作按鈕區域 */}
      {selectedCount > 0 && (
        <div className="flex items-center gap-2">
          {/* 主要操作按鈕 */}
          {actions.slice(0, 3).map((action) => (
            <Button
              key={action.id}
              variant={action.variant ?? 'outline'}
              size="sm"
              onClick={() => void handleActionClick(action)}
              disabled={action.disabled ?? loading}
              className="h-8"
            >
              {action.icon && <action.icon className="h-3.5 w-3.5 mr-1.5" />}
              {action.label}
            </Button>
          ))}

          {/* 更多操作下拉選單 */}
          {actions.length > 3 && (
            <>
              <Separator orientation="vertical" className="h-6" />
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={loading}
                    className="h-8 w-8 p-0"
                  >
                    <MoreHorizontal className="h-3.5 w-3.5" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  {actions.slice(3).map((action, index) => (
                    <React.Fragment key={action.id}>
                      {index > 0 && action.variant === 'destructive' && (
                        <DropdownMenuSeparator />
                      )}
                      <DropdownMenuItem
                        onClick={() => void handleActionClick(action)}
                        disabled={action.disabled ?? loading}
                        className={cn(
                          action.variant === 'destructive' && 
                          'text-destructive focus:text-destructive'
                        )}
                      >
                        {action.icon && <action.icon className="h-4 w-4 mr-2" />}
                        {action.label}
                      </DropdownMenuItem>
                    </React.Fragment>
                  ))}
                </DropdownMenuContent>
              </DropdownMenu>
            </>
          )}

          <Separator orientation="vertical" className="h-6" />
          
          {/* 清除選擇按鈕 */}
          <Button
            variant="ghost"
            size="sm"
            onClick={onClearSelection}
            disabled={loading}
            className="h-8 w-8 p-0"
          >
            <X className="h-3.5 w-3.5" />
          </Button>
        </div>
      )}
    </div>
  );
}

/**
 * 🎯 預設批次操作配置
 */
export const defaultBulkActions = {
  /**
   * 🗑️ 批次刪除操作
   */
  delete: (onDelete: (ids: (string | number)[]) => void): BulkAction => ({
    id: 'delete',
    label: '刪除',
    icon: Trash2,
    variant: 'destructive',
    handler: onDelete,
  }),

  /**
   * 👁️ 批次顯示操作
   */
  show: (onShow: (ids: (string | number)[]) => void): BulkAction => ({
    id: 'show',
    label: '顯示',
    icon: Eye,
    variant: 'default',
    handler: onShow,
  }),

  /**
   * 🙈 批次隱藏操作
   */
  hide: (onHide: (ids: (string | number)[]) => void): BulkAction => ({
    id: 'hide',
    label: '隱藏',
    icon: EyeOff,
    variant: 'secondary',
    handler: onHide,
  }),
}; 