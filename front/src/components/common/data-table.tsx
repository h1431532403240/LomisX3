/**
 * 數據表格組件 (V2.0 企業級重構 - 完全受控模式)
 * 提供完整的表格展示、排序、篩選、分頁功能
 * 徹底解決 "setState in render" 問題，實現完全受控的組件設計
 */
import React from 'react';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  ChevronDown,
  ChevronUp,
  MoreHorizontal,
  Search,
  Filter,
  X,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from 'lucide-react';

/**
 * 表格欄位定義
 */
export interface DataTableColumn<T = any> {
  /** 欄位唯一鍵 */
  key: string;
  /** 欄位標題 */
  title: string;
  /** 資料存取器 */
  dataIndex?: keyof T;
  /** 自訂渲染函數 */
  render?: (value: any, record: T, index: number) => React.ReactNode;
  /** 是否可排序 */
  sortable?: boolean;
  /** 是否可篩選 */
  filterable?: boolean;
  /** 欄位寬度 */
  width?: string | number;
  /** 對齊方式 */
  align?: 'left' | 'center' | 'right';
  /** 是否固定欄位 */
  fixed?: 'left' | 'right';
  /** 篩選選項 */
  filterOptions?: Array<{ label: string; value: any }>;
}

/**
 * 表格操作項
 */
export interface TableAction<T = any> {
  /** 操作唯一鍵 */
  key: string;
  /** 操作標題 */
  label: string;
  /** 操作圖標 */
  icon?: React.ReactNode;
  /** 點擊處理函數 */
  onClick: (record: T) => void;
  /** 是否禁用 */
  disabled?: (record: T) => boolean;
  /** 操作類型 */
  type?: 'default' | 'primary' | 'danger';
  /** 是否需要確認 */
  confirm?: boolean;
  /** 確認訊息 */
  confirmMessage?: string;
}

/**
 * 分頁配置
 */
export interface PaginationConfig {
  current: number;
  pageSize: number;
  total: number;
  showSizeChanger?: boolean;
  pageSizeOptions?: number[];
  showQuickJumper?: boolean;
  showTotal?: boolean;
  onChange?: (page: number, pageSize: number) => void;
}

/**
 * 數據表格屬性 (V2.0 - 完全受控模式)
 */
export interface DataTableProps<T = any> {
  // --- 核心資料 ---
  data: T[];
  columns: DataTableColumn<T>[];
  actions?: TableAction<T>[];

  // --- 狀態控制 (由父組件傳入) ---
  loading?: boolean;
  pagination?: PaginationConfig | false;
  rowKey?: keyof T | ((record: T) => string);
  
  // ✅ 排序狀態 (受控)
  sortState?: { field: string | null; order: 'asc' | 'desc' };
  // ✅ 選擇狀態 (受控)
  selectionState?: { selectedKeys: string[] };
  // ✅ 搜尋狀態 (受控)
  searchState?: { value: string };

  // --- 事件回調 (通知父組件更新) ---
  // ✅ 排序變更回調
  onSortChange?: (field: string, order: 'asc' | 'desc') => void;
  // ✅ 選擇變更回調
  onSelectionChange?: (newSelectedKeys: string[], selectedRows: T[]) => void;
  // ✅ 搜尋變更回調
  onSearchChange?: (newValue: string) => void;

  // --- UI 顯示與配置 ---
  title?: string;
  toolbar?: React.ReactNode;
  emptyText?: string;
  searchPlaceholder?: string;
  rowClassName?: (record: T, index: number) => string;
}

/**
 * 數據表格組件
 */
export function DataTable<T extends Record<string, any>>({
  data,
  columns,
  actions = [],
  loading = false,
  pagination = false,
  rowKey = 'id',
  sortState,
  selectionState,
  searchState,
  onSortChange,
  onSelectionChange,
  onSearchChange,
  title,
  toolbar,
  emptyText = '暫無資料',
  searchPlaceholder = '搜尋...',
  rowClassName,
}: DataTableProps<T>) {
  // 🛡️ 終極安全保護：確保 data 永遠是陣列
  const safeData: T[] = Array.isArray(data) ? data : [];
  
  if (!Array.isArray(data)) {
    console.warn('[DataTable] 收到非陣列的 data prop:', data, '已自動轉換為空陣列');
  }

  // ✅ 從 props 取得狀態值 (完全受控)
  const selectedKeys = selectionState?.selectedKeys || [];
  const sortField = sortState?.field || null;
  const sortOrder = sortState?.order || 'asc';
  const searchValue = searchState?.value || '';
  const hasRowSelection = Boolean(selectionState); // 根據是否傳入 selectionState 來判斷是否顯示選擇框

  /**
   * 獲取行唯一鍵
   */
  const getRowKey = (record: T, index: number): string => {
    if (typeof rowKey === 'function') {
      return rowKey(record);
    }
    return String(record[rowKey] || index);
  };

  /**
   * 處理排序 - 通知父組件
   */
  const handleSort = (column: DataTableColumn<T>) => {
    if (!column.sortable || !onSortChange) return;

    const field = column.dataIndex as string || column.key;
    
    if (sortField === field) {
      const newOrder = sortOrder === 'asc' ? 'desc' : 'asc';
      onSortChange(field, newOrder);
    } else {
      onSortChange(field, 'asc');
    }
  };

  /**
   * 處理全選 - 通知父組件
   */
  const handleSelectAll = (checked: boolean) => {
    if (!onSelectionChange) return;

    if (checked) {
      const allKeys = safeData.map((record, index) => getRowKey(record, index));
      onSelectionChange(allKeys, safeData);
    } else {
      onSelectionChange([], []);
    }
  };

  /**
   * 處理單行選擇 - 通知父組件
   */
  const handleSelectRow = (record: T, index: number, checked: boolean) => {
    if (!onSelectionChange) return;

    const key = getRowKey(record, index);
    let newSelectedKeys: string[];
    
    if (checked) {
      newSelectedKeys = [...selectedKeys, key];
    } else {
      newSelectedKeys = selectedKeys.filter(k => k !== key);
    }
    
    const selectedRows = safeData.filter((_, idx) => 
      newSelectedKeys.includes(getRowKey(_, idx))
    );
    onSelectionChange(newSelectedKeys, selectedRows);
  };

  /**
   * 處理搜尋 - 通知父組件
   */
  const handleSearch = (value: string) => {
    onSearchChange?.(value);
  };

  /**
   * 渲染表格標頭
   */
  const renderTableHeader = () => (
    <TableHeader>
      <TableRow>
        {hasRowSelection && (
          <TableHead className="w-12">
            <Checkbox
              checked={selectedKeys.length === safeData.length && safeData.length > 0}
              onCheckedChange={handleSelectAll}
            />
          </TableHead>
        )}
        {columns.map((column) => (
          <TableHead
            key={column.key}
            className={`${column.align ? `text-${column.align}` : ''} ${
              column.sortable ? 'cursor-pointer hover:bg-muted/50' : ''
            }`}
            style={{ width: column.width }}
            onClick={() => handleSort(column)}
          >
            <div className="flex items-center gap-2">
              {column.title}
              {column.sortable && (
                <div className="flex flex-col">
                  <ChevronUp 
                    className={`h-3 w-3 ${
                      sortField === (column.dataIndex as string || column.key) && sortOrder === 'asc'
                        ? 'text-primary' 
                        : 'text-muted-foreground'
                    }`}
                  />
                  <ChevronDown 
                    className={`h-3 w-3 ${
                      sortField === (column.dataIndex as string || column.key) && sortOrder === 'desc'
                        ? 'text-primary' 
                        : 'text-muted-foreground'
                    }`}
                  />
                </div>
              )}
            </div>
          </TableHead>
        ))}
        {actions.length > 0 && (
          <TableHead className="text-right">操作</TableHead>
        )}
      </TableRow>
    </TableHeader>
  );

  /**
   * 渲染表格內容
   */
  const renderTableBody = () => (
    <TableBody>
      {safeData.length === 0 ? (
        <TableRow>
          <TableCell 
            colSpan={columns.length + (hasRowSelection ? 1 : 0) + (actions.length > 0 ? 1 : 0)}
            className="text-center py-8 text-muted-foreground"
          >
            {emptyText}
          </TableCell>
        </TableRow>
      ) : (
        safeData.map((record, index) => {
          const key = getRowKey(record, index);
          const isSelected = selectedKeys.includes(key);
          const customRowClass = rowClassName ? rowClassName(record, index) : '';
          
          return (
            <TableRow 
              key={key}
              className={`${isSelected ? 'bg-muted/50' : ''} ${customRowClass}`}
            >
              {hasRowSelection && (
                <TableCell>
                  <Checkbox
                    checked={isSelected}
                    onCheckedChange={(checked) => handleSelectRow(record, index, checked as boolean)}
                  />
                </TableCell>
              )}
              {columns.map((column) => {
                const value = column.dataIndex ? record[column.dataIndex] : undefined;
                const content = column.render 
                  ? column.render(value, record, index)
                  : value;
                
                return (
                  <TableCell 
                    key={column.key}
                    className={column.align ? `text-${column.align}` : ''}
                  >
                    {content}
                  </TableCell>
                );
              })}
              {actions.length > 0 && (
                <TableCell className="text-right">
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" className="h-8 w-8 p-0">
                        <MoreHorizontal className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      {actions.map((action, actionIndex) => (
                        <DropdownMenuItem
                          key={action.key}
                          onClick={() => action.onClick(record)}
                          disabled={action.disabled?.(record)}
                          className={action.type === 'danger' ? 'text-destructive' : ''}
                        >
                          {action.icon && <span className="mr-2">{action.icon}</span>}
                          {action.label}
                        </DropdownMenuItem>
                      ))}
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              )}
            </TableRow>
          );
        })
      )}
    </TableBody>
  );

  /**
   * 渲染分頁
   */
  const renderPagination = () => {
    if (!pagination) return null;

    const { current, pageSize, total, showSizeChanger, pageSizeOptions = [10, 20, 50, 100] } = pagination;
    const totalPages = Math.ceil(total / pageSize);

    return (
      <div className="flex items-center justify-between px-2 py-4">
        <div className="flex items-center space-x-2">
          {showSizeChanger && (
            <>
              <span className="text-sm text-muted-foreground">每頁顯示</span>
              <Select
                value={String(pageSize)}
                onValueChange={(value) => pagination.onChange?.(1, Number(value))}
              >
                <SelectTrigger className="w-16">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {pageSizeOptions.map((size) => (
                    <SelectItem key={size} value={String(size)}>
                      {size}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <span className="text-sm text-muted-foreground">條</span>
            </>
          )}
        </div>
        
        <div className="flex items-center space-x-2">
          <span className="text-sm text-muted-foreground">
            第 {(current - 1) * pageSize + 1} - {Math.min(current * pageSize, total)} 條，共 {total} 條
          </span>
          
          <div className="flex items-center space-x-1">
            <Button
              variant="outline"
              size="sm"
              onClick={() => pagination.onChange?.(1, pageSize)}
              disabled={current === 1}
            >
              <ChevronsLeft className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => pagination.onChange?.(current - 1, pageSize)}
              disabled={current === 1}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            
            <span className="text-sm font-medium px-2">
              {current} / {totalPages}
            </span>
            
            <Button
              variant="outline"
              size="sm"
              onClick={() => pagination.onChange?.(current + 1, pageSize)}
              disabled={current === totalPages}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => pagination.onChange?.(totalPages, pageSize)}
              disabled={current === totalPages}
            >
              <ChevronsRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="space-y-4">
      {/* 標題和工具列 */}
      {(title || toolbar || searchState) && (
        <div className="flex items-center justify-between">
          <div>
            {title && <h2 className="text-lg font-semibold">{title}</h2>}
          </div>
          <div className="flex items-center space-x-2">
            {searchState && (
              <div className="relative">
                <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder={searchPlaceholder}
                  value={searchValue}
                  onChange={(e) => handleSearch(e.target.value)}
                  className="pl-8 w-64"
                />
              </div>
            )}
            {toolbar}
          </div>
        </div>
      )}

      {/* 選中項提示 */}
      {hasRowSelection && selectedKeys.length > 0 && (
        <div className="flex items-center space-x-2 text-sm text-muted-foreground">
          <span>已選中 {selectedKeys.length} 項</span>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              onSelectionChange?.([], []);
            }}
          >
            <X className="h-3 w-3 mr-1" />
            清除選擇
          </Button>
        </div>
      )}

      {/* 表格 */}
      <div className="rounded-md border">
        <Table>
          {renderTableHeader()}
          {renderTableBody()}
        </Table>
      </div>

      {/* 分頁 */}
      {renderPagination()}
    </div>
  );
} 