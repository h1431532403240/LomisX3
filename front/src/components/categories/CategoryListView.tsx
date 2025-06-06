/**
 * 商品分類列表檢視組件
 * 提供表格形式的分類數據展示，支援拖拽排序、批次操作、行內編輯等功能
 */
import { useState, useMemo } from 'react';
import { 
  MoreHorizontal, 
  Edit, 
  Trash2, 
  Eye, 
  EyeOff, 
  ChevronUp, 
  ChevronDown,
  GripVertical,
  Copy,
  Plus
} from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

// 共用組件
import { useConfirmDialog } from '@/components/ui/confirm-dialog';

import { useToast } from '@/hooks/use-toast';
import { 
  useUpdateCategory, 
  useDeleteCategory, 
  useBatchUpdateStatus,
  useBatchDelete 
} from '@/hooks/use-product-categories';
import type { ProductCategory } from '@/types/api.fallback';

// 🆕 組件屬性定義
interface CategoryListViewProps {
  /** 分類數據列表 */
  categories: ProductCategory[];
  /** 載入狀態 */
  isLoading?: boolean;
  /** 錯誤訊息 */
  error?: Error | null;
  /** 選中的分類 ID 列表 */
  selectedIds?: number[];
  /** 選中變更事件 */
  onSelectionChange?: (ids: number[]) => void;
  /** 編輯分類事件 */
  onEdit?: (category: ProductCategory) => void;
  /** 查看詳情事件 */
  onView?: (category: ProductCategory) => void;
  /** 新增子分類事件 */
  onAddChild?: (parentCategory: ProductCategory) => void;
}

/**
 * 🆕 分類列表檢視組件
 */
export function CategoryListView({
  categories = [],
  isLoading = false,
  error = null,
  selectedIds = [],
  onSelectionChange,
  onEdit,
  onView,
  onAddChild,
}: CategoryListViewProps) {
  const { toast } = useToast();
  const { confirm, ConfirmDialog } = useConfirmDialog();
  
  // 🎛️ 本地狀態
  const [sortBy, setSortBy] = useState<keyof ProductCategory>('position');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
  const [hoveredRow, setHoveredRow] = useState<number | null>(null);

  // 🔄 變更操作 hooks
  const updateCategoryMutation = useUpdateCategory();
  const deleteCategoryMutation = useDeleteCategory();
  const batchUpdateStatusMutation = useBatchUpdateStatus();
  const batchDeleteMutation = useBatchDelete();

  // 🧮 排序後的分類數據
  const sortedCategories = useMemo(() => {
    return [...categories].sort((a, b) => {
      const aValue = a[sortBy];
      const bValue = b[sortBy];
      
      // 處理 undefined 值
      if (aValue == null && bValue == null) return 0;
      if (aValue == null) return sortDirection === 'asc' ? 1 : -1;
      if (bValue == null) return sortDirection === 'asc' ? -1 : 1;
      
      if (aValue < bValue) return sortDirection === 'asc' ? -1 : 1;
      if (aValue > bValue) return sortDirection === 'asc' ? 1 : -1;
      return 0;
    });
  }, [categories, sortBy, sortDirection]);

  // 🔄 處理排序
  const handleSort = (column: keyof ProductCategory) => {
    if (sortBy === column) {
      setSortDirection(prev => prev === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(column);
      setSortDirection('asc');
    }
  };

  // ✅ 處理單個選擇
  const handleSelectOne = (id: number, checked: boolean) => {
    if (!onSelectionChange) return;
    
    if (checked) {
      onSelectionChange([...selectedIds, id]);
    } else {
      onSelectionChange(selectedIds.filter(selectedId => selectedId !== id));
    }
  };

  // ✅ 處理全選
  const handleSelectAll = (checked: boolean) => {
    if (!onSelectionChange) return;
    
    if (checked) {
      onSelectionChange(sortedCategories.map(cat => cat.id).filter((id): id is number => id != null));
    } else {
      onSelectionChange([]);
    }
  };

  // 🔄 處理狀態切換
  const handleToggleStatus = async (category: ProductCategory) => {
    if (!category.id) return;
    
    try {
      await updateCategoryMutation.mutateAsync({
        id: category.id,
        data: { status: !category.status }
      });
      
      toast({
        title: '狀態更新成功',
        description: `分類「${category.name}」已${category.status ? '停用' : '啟用'}`,
      });
    } catch {
      // 錯誤處理由 mutation 的錯誤處理器統一處理
    }
  };

  // 🗑️ 處理刪除
  const handleDelete = (category: ProductCategory) => {
    if (!category.id) return;
    
    confirm({
      title: '確認刪除',
      description: `確定要刪除分類「${category.name}」嗎？此操作無法復原。`,
      variant: 'destructive',
      confirmText: '刪除',
      cancelText: '取消',
      onConfirm: async () => {
        try {
          await deleteCategoryMutation.mutateAsync(category.id);
          
          toast({
            title: '刪除成功',
            description: `分類「${category.name}」已成功刪除`,
          });
        } catch {
          // 錯誤處理由 mutation 的錯誤處理器統一處理
        }
      },
    });
  };

  // 📋 處理批次狀態更新
  const handleBatchStatusUpdate = async (status: boolean) => {
    if (!selectedIds.length) return;

    try {
      await batchUpdateStatusMutation.mutateAsync({ ids: selectedIds, status });
      
      toast({
        title: '批次更新成功',
        description: `已${status ? '啟用' : '停用'} ${selectedIds.length} 個分類`,
      });
      
      onSelectionChange?.([]);
    } catch {
      // 錯誤處理由 mutation 的錯誤處理器統一處理
    }
  };

  // 🗑️ 處理批次刪除
  const handleBatchDelete = () => {
    if (!selectedIds.length) return;
    
    confirm({
      title: '確認批次刪除',
      description: `確定要刪除選中的 ${selectedIds.length} 個分類嗎？此操作無法復原。`,
      variant: 'destructive',
      confirmText: '刪除',
      cancelText: '取消',
      onConfirm: async () => {
        try {
          await batchDeleteMutation.mutateAsync(selectedIds);
          
          toast({
            title: '批次刪除成功',
            description: `已刪除 ${selectedIds.length} 個分類`,
          });
          
          onSelectionChange?.([]);
        } catch {
          // 錯誤處理由 mutation 的錯誤處理器統一處理
        }
      },
    });
  };

  // 🎨 獲取深度指示器
  const getDepthIndicator = (depth: number) => {
    return '　'.repeat(depth) + (depth > 0 ? '└ ' : '');
  };

  // 📱 載入狀態
  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="text-muted-foreground">載入分類資料中...</div>
      </div>
    );
  }

  // ❌ 錯誤狀態
  if (error) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="text-red-500">載入失敗: {error.message}</div>
      </div>
    );
  }

  // 📋 空狀態
  if (!sortedCategories.length) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="text-center space-y-2">
          <div className="text-muted-foreground">沒有找到任何分類</div>
          <div className="text-sm text-muted-foreground">點擊「新增分類」開始建立您的第一個分類</div>
        </div>
      </div>
    );
  }

  // 🆕 排序圖示組件
  const SortIcon = ({ column }: { column: keyof ProductCategory }) => {
    if (sortBy !== column) return null;
    return sortDirection === 'asc' ? 
      <ChevronUp className="h-4 w-4" /> : 
      <ChevronDown className="h-4 w-4" />;
  };

  const allSelected = selectedIds.length === sortedCategories.length;
  const someSelected = selectedIds.length > 0 && selectedIds.length < sortedCategories.length;

  return (
    <div className="space-y-4">
      {/* 🛠️ 批次操作工具列 */}
      {selectedIds.length > 0 && (
        <div className="flex items-center gap-2 p-4 bg-muted/50 rounded-lg border">
          <span className="text-sm text-muted-foreground">
            已選中 {selectedIds.length} 個分類
          </span>
          <div className="flex items-center gap-2 ml-auto">
            <Button
              size="sm"
              variant="outline"
              onClick={() => void handleBatchStatusUpdate(true)}
              disabled={batchUpdateStatusMutation.isPending}
            >
              <Eye className="h-4 w-4 mr-1" />
              批次啟用
            </Button>
            <Button
              size="sm"
              variant="outline"
              onClick={() => void handleBatchStatusUpdate(false)}
              disabled={batchUpdateStatusMutation.isPending}
            >
              <EyeOff className="h-4 w-4 mr-1" />
              批次停用
            </Button>
            <Button
              size="sm"
              variant="destructive"
              onClick={handleBatchDelete}
              disabled={batchDeleteMutation.isPending}
            >
              <Trash2 className="h-4 w-4 mr-1" />
              批次刪除
            </Button>
          </div>
        </div>
      )}

      {/* 📋 資料表格 */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-12">
                <Checkbox
                  checked={allSelected}
                  ref={(el) => {
                    if (el) {
                      (el as HTMLInputElement).indeterminate = someSelected;
                    }
                  }}
                  onCheckedChange={handleSelectAll}
                />
              </TableHead>
              <TableHead className="w-12">
                <GripVertical className="h-4 w-4 text-muted-foreground" />
              </TableHead>
              <TableHead 
                className="cursor-pointer hover:bg-muted/50"
                onClick={() => handleSort('name')}
              >
                <div className="flex items-center gap-1">
                  分類名稱
                  <SortIcon column="name" />
                </div>
              </TableHead>
              <TableHead>描述</TableHead>
              <TableHead 
                className="cursor-pointer hover:bg-muted/50"
                onClick={() => handleSort('position')}
              >
                <div className="flex items-center gap-1">
                  排序
                  <SortIcon column="position" />
                </div>
              </TableHead>
              <TableHead 
                className="cursor-pointer hover:bg-muted/50"
                onClick={() => handleSort('depth')}
              >
                <div className="flex items-center gap-1">
                  層級
                  <SortIcon column="depth" />
                </div>
              </TableHead>
              <TableHead>狀態</TableHead>
              <TableHead 
                className="cursor-pointer hover:bg-muted/50"
                onClick={() => handleSort('updated_at')}
              >
                <div className="flex items-center gap-1">
                  更新時間
                  <SortIcon column="updated_at" />
                </div>
              </TableHead>
              <TableHead className="text-right">操作</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {sortedCategories.map((category) => (
              <TableRow
                key={category.id ?? `category-${Math.random()}`}
                className={`${
                  category.id && selectedIds.includes(category.id) ? 'bg-muted/50' : ''
                } ${hoveredRow === category.id ? 'bg-muted/30' : ''}`}
                onMouseEnter={() => category.id && setHoveredRow(category.id)}
                onMouseLeave={() => setHoveredRow(null)}
              >
                <TableCell>
                  <Checkbox
                    checked={category.id ? selectedIds.includes(category.id) : false}
                    onCheckedChange={(checked) => category.id && handleSelectOne(category.id, checked === true)}
                  />
                </TableCell>
                <TableCell>
                  <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab active:cursor-grabbing" />
                </TableCell>
                <TableCell>
                  <div className="flex items-center gap-2">
                    <Avatar className="h-6 w-6">
                      <AvatarFallback className="text-xs">
                        {category.name?.charAt(0).toUpperCase() ?? 'C'}
                      </AvatarFallback>
                    </Avatar>
                    <div className="flex flex-col">
                      <span className="font-medium">
                        {getDepthIndicator(category.depth ?? 0)}{category.name}
                      </span>
                      <span className="text-xs text-muted-foreground">
                        /{category.slug}
                      </span>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <div className="max-w-48 truncate text-sm text-muted-foreground">
                    {category.description ?? '無描述'}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge variant="outline">{category.position ?? 0}</Badge>
                </TableCell>
                <TableCell>
                  <Badge variant="secondary">層級 {category.depth ?? 0}</Badge>
                </TableCell>
                <TableCell>
                  <Badge 
                    variant={category.status ? 'default' : 'secondary'}
                    className={`cursor-pointer ${
                      category.status ? 'bg-green-100 text-green-800 hover:bg-green-200' : ''
                    }`}
                    onClick={() => void handleToggleStatus(category)}
                  >
                    {category.status ? '啟用' : '停用'}
                  </Badge>
                </TableCell>
                <TableCell className="text-sm text-muted-foreground">
                  {category.updated_at ? new Date(category.updated_at).toLocaleDateString('zh-TW') : '-'}
                </TableCell>
                <TableCell className="text-right">
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" className="h-8 w-8 p-0">
                        <span className="sr-only">開啟選單</span>
                        <MoreHorizontal className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuLabel>操作</DropdownMenuLabel>
                      <DropdownMenuItem onClick={() => onView?.(category)}>
                        <Eye className="mr-2 h-4 w-4" />
                        查看詳情
                      </DropdownMenuItem>
                      <DropdownMenuItem onClick={() => onEdit?.(category)}>
                        <Edit className="mr-2 h-4 w-4" />
                        編輯
                      </DropdownMenuItem>
                      <DropdownMenuItem onClick={() => onAddChild?.(category)}>
                        <Plus className="mr-2 h-4 w-4" />
                        新增子分類
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem onClick={() => void handleToggleStatus(category)}>
                        {category.status ? (
                          <>
                            <EyeOff className="mr-2 h-4 w-4" />
                            停用
                          </>
                        ) : (
                          <>
                            <Eye className="mr-2 h-4 w-4" />
                            啟用
                          </>
                        )}
                      </DropdownMenuItem>
                      <DropdownMenuItem>
                        <Copy className="mr-2 h-4 w-4" />
                        複製
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem 
                        className="text-red-600"
                        onClick={() => handleDelete(category)}
                      >
                        <Trash2 className="mr-2 h-4 w-4" />
                        刪除
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
      
      {/* 確認對話框 */}
      {ConfirmDialog}
    </div>
  );
} 