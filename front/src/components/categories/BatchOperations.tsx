/**
 * 📦 批次操作組件
 * 提供分類的批次選擇和批次操作功能
 * 支援批次啟用/停用、批次刪除等操作
 */

import { useState, useMemo, useCallback } from 'react';
import { Eye, EyeOff, Trash2 } from 'lucide-react';

// 共用組件
import { BulkActionsBar } from '@/components/ui/bulk-actions-bar';
import { useConfirmDialog } from '@/components/ui/confirm-dialog';

// 類型和 hooks
import type { ProductCategory } from '@/types/api.fallback';
import { 
  useBatchUpdateStatus,
  useDeleteCategory,
} from '@/hooks/use-product-categories';
import { toast } from '@/hooks/use-toast';

/**
 * 🎯 批次操作類型
 */
type BatchOperationType = 'enable' | 'disable' | 'delete';

/**
 * 🎯 批次操作選項
 */
interface BatchOperationOptions {
  /** 操作說明 */
  description?: string;
  /** 額外參數 */
  extraParams?: Record<string, unknown>;
}

/**
 * 🎯 組件 Props 介面
 */
interface BatchOperationsProps {
  /** 所有分類項目 */
  items: ProductCategory[];
  /** 選中的項目 ID 列表 */
  selectedIds: number[];
  /** 選中狀態變更事件 */
  onSelectionChange: (selectedIds: number[]) => void;
  /** 是否顯示載入狀態 */
  isLoading?: boolean;
  /** 是否啟用批次操作 */
  enableBatchOperations?: boolean;
}

/**
 * 📊 批次操作工具列組件
 */
export function BatchOperations({
  items = [],
  selectedIds = [],
  onSelectionChange,
  isLoading = false,
  enableBatchOperations = true,
}: BatchOperationsProps) {
  // 🎣 變更操作 hooks
  const batchUpdateStatus = useBatchUpdateStatus();
  const deleteCategory = useDeleteCategory();
  const { confirm, ConfirmDialog } = useConfirmDialog();

  // 🎛️ 本地狀態
  const [operationInProgress, setOperationInProgress] = useState(false);

  // 📊 計算統計資訊
  const selectedCount = selectedIds.length;
  const totalCount = items.length;

  // 📋 取得選中項目的詳細資訊
  const selectedItems = useMemo(() => 
    items.filter(item => item.id && selectedIds.includes(item.id)),
    [items, selectedIds]
  );

  const selectedActiveCount = useMemo(() => 
    selectedItems.filter(item => item.status).length,
    [selectedItems]
  );

  const selectedInactiveCount = useMemo(() => 
    selectedItems.filter(item => !item.status).length,
    [selectedItems]
  );

  /**
   * 🔄 處理全選
   */
  const handleSelectAll = () => {
    const allIds = items.map(item => item.id).filter((id): id is number => id != null);
    onSelectionChange(allIds);
  };

  /**
   * 🔄 處理清除選擇
   */
  const handleClearSelection = useCallback(() => {
    onSelectionChange([]);
  }, [onSelectionChange]);

  // 🎯 批次操作執行器
  const executeBatchOperation = useCallback(async (type: BatchOperationType, _options: BatchOperationOptions = {}) => {
    setOperationInProgress(true);
    try {
      if (type === 'enable' || type === 'disable') {
        await batchUpdateStatus.mutateAsync({
          ids: selectedIds,
          status: type === 'enable',
        });

        toast({
          title: `批次 ${type === 'enable' ? '啟用' : '停用'} 成功`,
          description: `已 ${type === 'enable' ? '啟用' : '停用'} ${selectedCount} 個分類`,
        });
      } else if (type === 'delete') {
        // 執行批次刪除
        for (const id of selectedIds) {
          await deleteCategory.mutateAsync(id);
        }

        toast({
          title: '批次刪除成功',
          description: `已刪除 ${selectedCount} 個分類`,
        });
      }

      handleClearSelection();
    } catch {
      toast({
        title: '批次操作失敗',
        description: '操作執行時發生錯誤',
        variant: 'destructive',
      });
    } finally {
      setOperationInProgress(false);
    }
  }, [selectedIds, selectedCount, batchUpdateStatus, deleteCategory, handleClearSelection]);

  // 🟢 批次啟用操作
  const handleBatchEnable = useCallback(async () => {
    if (selectedInactiveCount === 0) {
      toast({
        title: '無需操作',
        description: '所選分類都已啟用',
        variant: 'default',
      });
      return;
    }
    await executeBatchOperation('enable');
  }, [executeBatchOperation, selectedInactiveCount]);

  // 🔴 批次停用操作  
  const handleBatchDisable = useCallback(async () => {
    if (selectedActiveCount === 0) {
      toast({
        title: '無需操作',
        description: '所選分類都已停用',
        variant: 'default',
      });
      return;
    }
    await executeBatchOperation('disable');
  }, [executeBatchOperation, selectedActiveCount]);

  // 🗑️ 批次刪除操作
  const handleBatchDelete = useCallback(() => {
    confirm({
      title: '確認批次刪除',
      description: `您確定要刪除選中的 ${selectedCount} 個分類嗎？此操作無法復原。`,
      variant: 'destructive',
      confirmText: '確認刪除',
      cancelText: '取消',
      onConfirm: async () => {
        await executeBatchOperation('delete');
      },
    });
  }, [selectedCount, confirm, executeBatchOperation]);

  /**
   * 🎯 批次操作配置
   */
  const bulkActions = useMemo(() => [
    {
      id: 'enable',
      label: `啟用 (${selectedInactiveCount})`,
      icon: Eye,
      variant: 'default' as const,
      disabled: selectedInactiveCount === 0,
      handler: handleBatchEnable,
    },
    {
      id: 'disable',
      label: `停用 (${selectedActiveCount})`,
      icon: EyeOff,
      variant: 'secondary' as const,
      disabled: selectedActiveCount === 0,
      handler: handleBatchDisable,
    },
    {
      id: 'delete',
      label: '刪除',
      icon: Trash2,
      variant: 'destructive' as const,
      handler: handleBatchDelete,
    },
  ], [selectedActiveCount, selectedInactiveCount, handleBatchEnable, handleBatchDisable, handleBatchDelete]);

  /**
   * 📝 自訂選擇狀態文字
   */
  const getSelectionText = (selectedCount: number, totalCount: number) => {
    if (selectedCount === 0) {
      return `共 ${totalCount} 個分類`;
    }
    
    const statusText = [];
    if (selectedActiveCount > 0) {
      statusText.push(`${selectedActiveCount} 個啟用`);
    }
    if (selectedInactiveCount > 0) {
      statusText.push(`${selectedInactiveCount} 個停用`);
    }
    
    return `已選擇 ${selectedCount}/${totalCount} 個分類 (${statusText.join('、')})`;
  };

  if (!enableBatchOperations || totalCount === 0) {
    return null;
  }

  return (
    <>
      <BulkActionsBar
        totalCount={totalCount}
        selectedIds={selectedIds}
        onSelectionChange={(ids) => onSelectionChange(ids as number[])}
        onSelectAll={handleSelectAll}
        onClearSelection={handleClearSelection}
        actions={bulkActions}
        loading={isLoading || operationInProgress}
        selectionText={getSelectionText}
        className="mb-4"
      />
      
      {/* 確認對話框 */}
      {ConfirmDialog}
    </>
  );
} 