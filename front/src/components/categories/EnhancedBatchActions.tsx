import React, { useState, useCallback, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Progress } from '@/components/ui/progress';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  MoreHorizontal,
  Trash2,
  ToggleLeft,
  ToggleRight,
  Move,
  Download,
  History,
  Edit,
  Copy,
  Archive,
  AlertCircle,
  CheckCircle,
  Loader2,
} from 'lucide-react';
import type { ProductCategory } from '@/types/api.fallback';
import { cn } from '@/lib/utils';

/**
 * 批次操作類型
 */
export type BatchOperationType = 
  | 'activate'
  | 'deactivate'
  | 'delete'
  | 'move'
  | 'copy'
  | 'export'
  | 'archive'
  | 'edit';

/**
 * 批次操作狀態
 */
interface BatchOperationStatus {
  type: BatchOperationType;
  total: number;
  completed: number;
  failed: number;
  isRunning: boolean;
  errors: string[];
}

/**
 * 批次操作歷史記錄
 */
interface BatchOperationHistory {
  id: string;
  type: BatchOperationType;
  targetCount: number;
  successCount: number;
  failedCount: number;
  timestamp: string;
  duration: number;
  description: string;
}

/**
 * 增強批次操作組件 Props
 */
interface EnhancedBatchActionsProps {
  selectedItems: ProductCategory[];
  totalItems: number;
  onSelectAll: () => void;
  onDeselectAll: () => void;
  onBatchOperation: (type: BatchOperationType, options?: Record<string, unknown>) => Promise<void>;
  isLoading?: boolean;
  className?: string;
}

/**
 * 增強批次操作組件
 * 
 * 功能特色：
 * - 完整的批次操作選項
 * - 操作進度追蹤
 * - 歷史記錄管理
 * - 批次編輯模式
 * - 匯入/匯出功能
 */
export const EnhancedBatchActions: React.FC<EnhancedBatchActionsProps> = ({
  selectedItems,
  totalItems,
  onSelectAll,
  onDeselectAll,
  onBatchOperation,
  isLoading = false,
  className,
}) => {
  const [operationStatus, setOperationStatus] = useState<BatchOperationStatus | null>(null);
  const [confirmDialog, setConfirmDialog] = useState<{
    open: boolean;
    type: BatchOperationType;
    title: string;
    description: string;
    options?: Record<string, unknown>;
  }>({ open: false, type: 'delete', title: '', description: '' });
  const [moveTargetId, setMoveTargetId] = useState<number | null>(null);
  const [operationHistory, setOperationHistory] = useState<BatchOperationHistory[]>([]);
  const [showHistory, setShowHistory] = useState(false);
  const [batchEditMode, setBatchEditMode] = useState(false);

  const selectedCount = selectedItems.length;
  const isIndeterminate = selectedCount > 0 && selectedCount < totalItems;
  const isAllSelected = selectedCount === totalItems && totalItems > 0;

  /**
   * 批次操作選項配置
   */
  const batchOperations = useMemo(() => [
    {
      type: 'activate' as const,
      label: '批次啟用',
      icon: ToggleRight,
      variant: 'default' as const,
      description: '啟用所選分類',
      requiresConfirm: false,
    },
    {
      type: 'deactivate' as const,
      label: '批次停用',
      icon: ToggleLeft,
      variant: 'secondary' as const,
      description: '停用所選分類',
      requiresConfirm: true,
    },
    {
      type: 'move' as const,
      label: '移動至',
      icon: Move,
      variant: 'outline' as const,
      description: '移動所選分類到其他父分類',
      requiresConfirm: true,
    },
    {
      type: 'copy' as const,
      label: '複製',
      icon: Copy,
      variant: 'outline' as const,
      description: '複製所選分類',
      requiresConfirm: false,
    },
    {
      type: 'export' as const,
      label: '匯出',
      icon: Download,
      variant: 'outline' as const,
      description: '匯出所選分類資料',
      requiresConfirm: false,
    },
    {
      type: 'archive' as const,
      label: '封存',
      icon: Archive,
      variant: 'secondary' as const,
      description: '封存所選分類',
      requiresConfirm: true,
    },
    {
      type: 'delete' as const,
      label: '刪除',
      icon: Trash2,
      variant: 'destructive' as const,
      description: '永久刪除所選分類',
      requiresConfirm: true,
    },
  ], []);

  /**
   * 處理批次操作
   */
  const handleBatchOperation = useCallback(async (
    type: BatchOperationType,
    options?: Record<string, unknown>
  ) => {
    const operation = batchOperations.find(op => op.type === type);
    if (!operation) return;

    if (operation.requiresConfirm) {
      setConfirmDialog({
        open: true,
        type,
        title: `確認${operation.label}`,
        description: `確定要${operation.description}嗎？此操作將影響 ${selectedCount} 個分類。`,
        options,
      });
      return;
    }

    await executeBatchOperation(type, options);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [batchOperations, selectedCount]);

  /**
   * 執行批次操作
   */
  const executeBatchOperation = useCallback(async (
    type: BatchOperationType,
    options?: Record<string, unknown>
  ) => {
    const startTime = Date.now();
    
    setOperationStatus({
      type,
      total: selectedCount,
      completed: 0,
      failed: 0,
      isRunning: true,
      errors: [],
    });

    try {
      await onBatchOperation(type, options);
      
      // 記錄成功操作
      const duration = Date.now() - startTime;
      const operation = batchOperations.find(op => op.type === type);
      
      setOperationHistory(prev => [{
        id: Date.now().toString(),
        type,
        targetCount: selectedCount,
        successCount: selectedCount,
        failedCount: 0,
        timestamp: new Date().toISOString(),
        duration,
        description: operation?.description ?? '',
      }, ...prev.slice(0, 9)]); // 只保留最近 10 條記錄

      setOperationStatus({
        type,
        total: selectedCount,
        completed: selectedCount,
        failed: 0,
        isRunning: false,
        errors: [],
      });

      // 3 秒後清除狀態
      setTimeout(() => setOperationStatus(null), 3000);
      
    } catch (error) {
      setOperationStatus({
        type,
        total: selectedCount,
        completed: 0,
        failed: selectedCount,
        isRunning: false,
        errors: [error instanceof Error ? error.message : '未知錯誤'],
      });
    }
  }, [selectedCount, onBatchOperation, batchOperations]);

  /**
   * 確認對話框處理
   */
  const handleConfirmOperation = useCallback(async () => {
    const { type, options } = confirmDialog;
    setConfirmDialog({ ...confirmDialog, open: false });
    await executeBatchOperation(type, options);
  }, [confirmDialog, executeBatchOperation]);

  // 如果沒有選中項目，不顯示批次操作
  if (selectedCount === 0) {
    return null;
  }

  return (
    <div className={cn('bg-muted/50 border border-border rounded-lg p-4', className)}>
      {/* 批次選擇狀態 */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          <Checkbox
            checked={isAllSelected}
            {...(isIndeterminate && { 'data-state': 'indeterminate' })}
            onCheckedChange={(checked) => {
              if (checked) {
                onSelectAll();
              } else {
                onDeselectAll();
              }
            }}
          />
          <div>
            <span className="font-medium">
              已選擇 {selectedCount} 項
            </span>
            {totalItems > 0 && (
              <span className="text-muted-foreground ml-2">
                共 {totalItems} 項
              </span>
            )}
          </div>
          {isIndeterminate && (
            <Badge variant="outline" className="text-xs">
              部分選擇
            </Badge>
          )}
        </div>

        <div className="flex items-center gap-2">
          {/* 批次編輯模式切換 */}
          <Button
            variant="outline"
            size="sm"
            onClick={() => setBatchEditMode(!batchEditMode)}
            className={cn(batchEditMode && 'bg-primary text-primary-foreground')}
          >
            <Edit className="h-4 w-4 mr-2" />
            批次編輯
          </Button>

          {/* 操作歷史 */}
          <Button
            variant="outline"
            size="sm"
            onClick={() => setShowHistory(!showHistory)}
          >
            <History className="h-4 w-4 mr-2" />
            歷史
            {operationHistory.length > 0 && (
              <Badge variant="secondary" className="ml-2 h-5 w-5 rounded-full p-0 text-xs">
                {operationHistory.length}
              </Badge>
            )}
          </Button>

          <Button variant="outline" size="sm" onClick={onDeselectAll}>
            清除選擇
          </Button>
        </div>
      </div>

      {/* 操作進度顯示 */}
      {operationStatus && (
        <div className="mb-4 p-3 bg-background rounded-md border">
          <div className="flex items-center justify-between mb-2">
            <div className="flex items-center gap-2">
              {operationStatus.isRunning ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : operationStatus.failed > 0 ? (
                <AlertCircle className="h-4 w-4 text-destructive" />
              ) : (
                <CheckCircle className="h-4 w-4 text-green-600" />
              )}
              <span className="font-medium">
                {batchOperations.find(op => op.type === operationStatus.type)?.label}
              </span>
            </div>
            <span className="text-sm text-muted-foreground">
              {operationStatus.completed} / {operationStatus.total}
            </span>
          </div>
          <Progress 
            value={(operationStatus.completed / operationStatus.total) * 100} 
            className="h-2"
          />
          {operationStatus.errors.length > 0 && (
            <div className="mt-2 text-sm text-destructive">
              錯誤: {operationStatus.errors.join(', ')}
            </div>
          )}
        </div>
      )}

      {/* 批次操作按鈕 */}
      <div className="flex flex-wrap items-center gap-2">
        {/* 快速操作按鈕 */}
        <Button
          variant="default"
          size="sm"
          onClick={() => {
            void handleBatchOperation('activate');
          }}
          disabled={isLoading}
        >
          <ToggleRight className="h-4 w-4 mr-2" />
          啟用
        </Button>

        <Button
          variant="secondary"
          size="sm"
          onClick={() => {
            void handleBatchOperation('deactivate');
          }}
          disabled={isLoading}
        >
          <ToggleLeft className="h-4 w-4 mr-2" />
          停用
        </Button>

        <Button
          variant="outline"
          size="sm"
          onClick={() => {
            void handleBatchOperation('export');
          }}
          disabled={isLoading}
        >
          <Download className="h-4 w-4 mr-2" />
          匯出
        </Button>

        {/* 更多操作下拉選單 */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="outline" size="sm">
              <MoreHorizontal className="h-4 w-4 mr-2" />
              更多操作
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-48">
            <DropdownMenuItem onClick={() => {
              void handleBatchOperation('move');
            }}>
              <Move className="h-4 w-4 mr-2" />
              移動分類
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => {
              void handleBatchOperation('copy');
            }}>
              <Copy className="h-4 w-4 mr-2" />
              複製分類
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => {
              void handleBatchOperation('archive');
            }}>
              <Archive className="h-4 w-4 mr-2" />
              封存分類
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem 
              onClick={() => {
                void handleBatchOperation('delete');
              }}
              className="text-destructive focus:text-destructive"
            >
              <Trash2 className="h-4 w-4 mr-2" />
              刪除分類
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      {/* 操作歷史面板 */}
      {showHistory && operationHistory.length > 0 && (
        <div className="mt-4 p-3 bg-background rounded-md border">
          <h4 className="font-medium mb-3">操作歷史</h4>
          <div className="space-y-2 max-h-60 overflow-y-auto">
            {operationHistory.map((record) => (
              <div key={record.id} className="flex items-center justify-between py-2 border-b last:border-b-0">
                <div>
                  <div className="text-sm font-medium">
                    {batchOperations.find(op => op.type === record.type)?.label}
                  </div>
                  <div className="text-xs text-muted-foreground">
                    {record.successCount} 成功, {record.failedCount} 失敗 · 
                    {Math.round(record.duration / 1000)}s · 
                    {new Date(record.timestamp).toLocaleString()}
                  </div>
                </div>
                <Badge 
                  variant={record.failedCount > 0 ? 'destructive' : 'default'}
                  className="text-xs"
                >
                  {record.failedCount > 0 ? '有錯誤' : '成功'}
                </Badge>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* 確認對話框 */}
      <Dialog open={confirmDialog.open} onOpenChange={(open) => 
        setConfirmDialog({ ...confirmDialog, open })
      }>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{confirmDialog.title}</DialogTitle>
            <DialogDescription>
              {confirmDialog.description}
            </DialogDescription>
          </DialogHeader>

          {/* 移動操作的目標選擇 */}
          {confirmDialog.type === 'move' && (
            <div className="py-4">
              <label className="text-sm font-medium mb-2 block">
                選擇目標父分類
              </label>
              <Select
                value={moveTargetId?.toString() ?? ''}
                onValueChange={(value) => setMoveTargetId(value ? parseInt(value) : null)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="選擇父分類" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">根分類</SelectItem>
                  {/* 這裡需要根據實際的分類資料來填充 */}
                </SelectContent>
              </Select>
            </div>
          )}

          <DialogFooter>
            <Button 
              variant="outline" 
              onClick={() => setConfirmDialog({ ...confirmDialog, open: false })}
            >
              取消
            </Button>
            <Button 
              variant={confirmDialog.type === 'delete' ? 'destructive' : 'default'}
              onClick={() => {
                void handleConfirmOperation();
              }}
              disabled={confirmDialog.type === 'move' && moveTargetId === null}
            >
              確認執行
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}; 