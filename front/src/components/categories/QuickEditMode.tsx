import React, { useState, useCallback, useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Edit3,
  Check,
  X,
  Save,
  Undo,
  KeyboardIcon,
  Zap,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ProductCategory } from '@/types/api.fallback';
import { toast } from '@/hooks/use-toast';

/**
 * 編輯項目介面
 */
interface EditableItem extends ProductCategory {
  /** 是否正在編輯 */
  isEditing: boolean;
  /** 原始名稱 */
  originalName: string;
  /** 編輯後的名稱 */
  editedName: string;
  /** 是否有變更 */
  hasChanges: boolean;
}

/**
 * 編輯狀態介面
 */
interface EditState {
  /** 是否正在編輯 */
  isEditing: boolean;
  /** 編輯後的名稱 */
  editedName: string;
  /** 是否有變更 */
  hasChanges: boolean;
}

/**
 * 快速編輯模式組件 Props
 */
interface QuickEditModeProps {
  /** 分類項目 */
  items: ProductCategory[];
  /** 是否啟用 */
  enabled?: boolean;
  /** 狀態變更事件 */
  onEnabledChange?: (enabled: boolean) => void;
  /** 保存變更事件 */
  onSaveChanges?: (changes: ProductCategory[]) => Promise<void>;
  /** 是否載入中 */
  isLoading?: boolean;
  className?: string;
}

/**
 * 快速編輯模式組件
 * 
 * 功能特色：
 * - 行內編輯分類名稱
 * - 批次編輯模式
 * - 鍵盤快捷鍵支援
 * - 自動儲存機制
 * - 變更追蹤和回滾
 */
export function QuickEditMode({
  items = [],
  enabled: _enabled = false,
  onEnabledChange: _onEnabledChange,
  onSaveChanges,
  isLoading = false,
  className,
}: QuickEditModeProps) {
  // 🎛️ 狀態管理 - 使用 Map 來管理編輯狀態
  const [editingStates, setEditingStates] = useState<Map<number, EditState>>(new Map());
  const [isProcessing, setIsProcessing] = useState(false);
  const [showKeyboardHelp, setShowKeyboardHelp] = useState(false);
  const [autoSaveEnabled, setAutoSaveEnabled] = useState(true);
  const autoSaveTimeout = useRef<NodeJS.Timeout | null>(null);

  /**
   * 開始編輯單個項目
   */
  const startEditing = useCallback((id: number) => {
    const item = items.find(item => item.id === id);
    if (!item) return;

    setEditingStates(prev => new Map(prev.set(id, {
      isEditing: true,
      editedName: item.name,
      hasChanges: false,
    })));
  }, [items]);

  /**
   * 取消編輯
   */
  const cancelEditing = useCallback((id: number) => {
    setEditingStates(prev => {
      const newMap = new Map(prev);
      newMap.delete(id);
      return newMap;
    });
  }, []);

  /**
   * 儲存所有變更
   */
  const saveChanges = useCallback(async () => {
    if (editingStates.size === 0) return;

    setIsProcessing(true);
    try {
      const changedItems: ProductCategory[] = [];
      
      editingStates.forEach((editState, itemId) => {
        if (editState.hasChanges) {
          const originalItem = items.find(item => item.id === itemId);
          if (originalItem) {
            changedItems.push({
              ...originalItem,
              name: editState.editedName,
            });
          }
        }
      });

      if (changedItems.length > 0) {
        await onSaveChanges?.(changedItems);
        setEditingStates(new Map());
      }
    } catch {
      toast({
        title: '更新失敗',
        description: '分類資料更新時發生錯誤',
        variant: 'destructive',
      });
    } finally {
      setIsProcessing(false);
    }
  }, [editingStates, items, onSaveChanges]);

  /**
   * 更新編輯內容
   */
  const updateEditedName = useCallback((id: number, name: string) => {
    const item = items.find(item => item.id === id);
    if (!item) return;

    setEditingStates(prev => new Map(prev.set(id, {
      isEditing: true,
      editedName: name,
      hasChanges: name !== item.name,
    })));

    // 自動儲存邏輯
    if (autoSaveEnabled) {
      if (autoSaveTimeout.current) {
        clearTimeout(autoSaveTimeout.current);
      }
      autoSaveTimeout.current = setTimeout(() => {
        void saveChanges();
      }, 2000);
    }
  }, [items, autoSaveEnabled, saveChanges]);

  /**
   * 回滾所有變更
   */
  const revertAllChanges = useCallback(() => {
    setEditingStates(new Map());
  }, []);

  /**
   * 鍵盤快捷鍵處理
   */
  useEffect(() => {
    const handleKeydown = (event: KeyboardEvent) => {
      // Ctrl/Cmd + S: 儲存所有變更
      if ((event.ctrlKey || event.metaKey) && event.key === 's') {
        event.preventDefault();
        void saveChanges();
      }

      // Ctrl/Cmd + Z: 回滾變更
      if ((event.ctrlKey || event.metaKey) && event.key === 'z') {
        event.preventDefault();
        revertAllChanges();
      }

      // Escape: 取消編輯
      if (event.key === 'Escape') {
        const editingItemId = Array.from(editingStates.keys())[0];
        if (editingItemId) {
          cancelEditing(editingItemId);
        }
      }

      // F2: 開始編輯第一個項目
      if (event.key === 'F2') {
        event.preventDefault();
        const firstItemId = items[0]?.id;
        if (firstItemId) {
          startEditing(firstItemId);
        }
      }
    };

    document.addEventListener('keydown', handleKeydown);
    return () => document.removeEventListener('keydown', handleKeydown);
  }, [editingStates, items, saveChanges, revertAllChanges, cancelEditing, startEditing]);

  const changedItemsCount = Array.from(editingStates.values()).filter(state => state.hasChanges).length;
  // const _hasUnsavedChanges = useMemo(() => changedItemsCount > 0, [changedItemsCount]); // 暫時不使用

  return (
    <div className={cn('space-y-4', className)}>
      {/* 快速編輯工具列 */}
      <div className="flex items-center justify-between p-4 bg-muted/50 rounded-lg border">
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2">
            <Zap className="h-4 w-4 text-primary" />
            <span className="font-medium">快速編輯模式</span>
          </div>
          
          {changedItemsCount > 0 && (
            <Badge variant="secondary" className="gap-1">
              <Edit3 className="h-3 w-3" />
              {changedItemsCount} 項變更
            </Badge>
          )}

          <div className="flex items-center gap-2">
            <Checkbox
              checked={autoSaveEnabled}
              onCheckedChange={(checked) => setAutoSaveEnabled(!!checked)}
            />
            <span className="text-sm text-muted-foreground">自動儲存</span>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setShowKeyboardHelp(true)}
          >
            <KeyboardIcon className="h-4 w-4 mr-2" />
            快捷鍵
          </Button>

          {changedItemsCount > 0 && (
            <>
              <Button
                variant="outline"
                size="sm"
                onClick={revertAllChanges}
              >
                <Undo className="h-4 w-4 mr-2" />
                回滾
              </Button>
              <Button
                size="sm"
                onClick={() => void saveChanges()}
                disabled={isLoading}
              >
                <Save className="h-4 w-4 mr-2" />
                儲存全部
              </Button>
            </>
          )}
        </div>
      </div>

      {/* 編輯項目列表 */}
      <div className="space-y-2">
        {items.map((item) => {
          const editState = editingStates.get(item.id);
          const enhancedItem: EditableItem = {
            ...item,
            isEditing: editState?.isEditing ?? false,
            originalName: item.name,
            editedName: editState?.editedName ?? item.name,
            hasChanges: editState?.hasChanges ?? false,
          };

          return (
            <QuickEditItem
              key={item.id}
              item={enhancedItem}
              onStartEdit={() => startEditing(item.id)}
              onCancelEdit={() => cancelEditing(item.id)}
              onUpdateName={(name) => updateEditedName(item.id, name)}
              onSave={() => void saveChanges()}
              isLoading={isProcessing}
            />
          );
        })}
      </div>

      {/* 鍵盤快捷鍵說明對話框 */}
      <Dialog open={showKeyboardHelp} onOpenChange={setShowKeyboardHelp}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <KeyboardIcon className="h-4 w-4" />
              鍵盤快捷鍵
            </DialogTitle>
            <DialogDescription>
              使用快捷鍵提升編輯效率
            </DialogDescription>
          </DialogHeader>
          
          <div className="space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-sm">儲存所有變更</span>
              <Badge variant="outline" className="font-mono">Ctrl+S</Badge>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm">回滾變更</span>
              <Badge variant="outline" className="font-mono">Ctrl+Z</Badge>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm">取消編輯</span>
              <Badge variant="outline" className="font-mono">Esc</Badge>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm">開始編輯</span>
              <Badge variant="outline" className="font-mono">F2</Badge>
            </div>
          </div>

          <DialogFooter>
            <Button onClick={() => setShowKeyboardHelp(false)}>
              知道了
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/**
 * 快速編輯項目組件 Props
 */
interface QuickEditItemProps {
  item: EditableItem;
  onStartEdit: () => void;
  onCancelEdit: () => void;
  onUpdateName: (name: string) => void;
  onSave: () => void;
  isLoading?: boolean;
}

/**
 * 快速編輯項目組件
 */
const QuickEditItem: React.FC<QuickEditItemProps> = ({
  item,
  onStartEdit,
  onCancelEdit,
  onUpdateName,
  onSave,
  isLoading = false,
}) => {
  const inputRef = useRef<HTMLInputElement>(null);

  // 當開始編輯時自動聚焦
  useEffect(() => {
    if (item.isEditing && inputRef.current) {
      inputRef.current.focus();
      inputRef.current.select();
    }
  }, [item.isEditing]);

  const handleKeyDown = (event: React.KeyboardEvent) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      onSave();
    } else if (event.key === 'Escape') {
      event.preventDefault();
      onCancelEdit();
    }
  };

  return (
    <div className={cn(
      'flex items-center gap-3 p-3 border border-border rounded-lg',
      'hover:bg-muted/50 transition-colors',
      item.isEditing && 'border-primary bg-primary/10'
    )}>
      {/* 分類 ID */}
      <span className="text-xs text-muted-foreground w-12">
        #{item.id}
      </span>

      {/* 編輯區域 */}
      <div className="flex-1">
        {item.isEditing ? (
          <Input
            ref={inputRef}
            value={item.editedName}
            onChange={(e) => onUpdateName(e.target.value)}
            onKeyDown={handleKeyDown}
            onBlur={onSave}
            disabled={isLoading}
            className="h-8"
          />
        ) : (
          <button
            onClick={onStartEdit}
            className="text-left w-full h-8 px-2 py-1 hover:bg-muted rounded text-sm"
          >
            <span className={cn(
              item.hasChanges && 'font-medium text-primary'
            )}>
              {item.name}
            </span>
          </button>
        )}
      </div>

      {/* 狀態指示 */}
      <div className="flex items-center gap-2">
        {item.isEditing && (
          <div className="flex items-center gap-1">
            <Button
              variant="ghost"
              size="sm"
              onClick={onSave}
              disabled={isLoading}
              className="h-6 w-6 p-0"
            >
              <Check className="h-3 w-3" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={onCancelEdit}
              className="h-6 w-6 p-0"
            >
              <X className="h-3 w-3" />
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}; 