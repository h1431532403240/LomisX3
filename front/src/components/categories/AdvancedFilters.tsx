/**
 * 🔍 進階搜尋篩選組件
 * 提供多維度的分類篩選功能，包含狀態、層級、排序等選項
 */

import { useState, useCallback } from 'react';

// UI 組件
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';

// 圖示
import {
  Search,
  Filter,
  X,
  SlidersHorizontal,
  RotateCcw,
  Check,
} from 'lucide-react';

// 類型定義
import type { CategoryListParams } from '@/types/api.fallback';

/**
 * 🎯 組件 Props 介面
 */
interface AdvancedFiltersProps {
  /** 當前篩選條件 */
  filters: Partial<CategoryListParams>;
  /** 篩選變更事件 */
  onFiltersChange: (filters: Partial<CategoryListParams>) => void;
  /** 是否載入中 */
  isLoading?: boolean;
  /** 是否顯示進階選項 */
  showAdvanced?: boolean;
  /** 分類資料（保留供未來使用） */
  // categories?: ProductCategory[];
}

/**
 * 🏷️ 篩選標籤組件
 */
interface FilterTagProps {
  label: string;
  value: string;
  onRemove: () => void;
}

function FilterTag({ label, value, onRemove }: FilterTagProps) {
  return (
    <Badge variant="secondary" className="flex items-center gap-1 pr-1">
      <span className="text-xs">
        <strong>{label}:</strong> {value}
      </span>
      <Button
        variant="ghost"
        size="sm"
        className="h-4 w-4 p-0 hover:bg-muted-foreground/20"
        onClick={onRemove}
      >
        <X className="h-3 w-3" />
      </Button>
    </Badge>
  );
}

/**
 * 🔍 進階搜尋篩選主組件
 */
export function AdvancedFilters({
  filters = {},
  onFiltersChange,
  isLoading = false,
  // categories = [], // 移除未使用的參數
}: AdvancedFiltersProps) {
  // 🎛️ 狀態管理
  const [isPopoverOpen, setIsPopoverOpen] = useState(false);
  const [searchValue, setSearchValue] = useState(filters.search ?? '');
  const [statusFilter, setStatusFilter] = useState<string>(
    filters.status === undefined ? 'all' : (filters.status ? 'active' : 'inactive')
  );
  const [parentFilter, setParentFilter] = useState<string>(
    filters.parent_id === 'root' ? 'root' : 'all'
  );
  const [sortField, setSortField] = useState(filters.sort ?? 'position');
  const [sortDirection, setSortDirection] = useState(filters.direction ?? 'asc');

  // 🎯 套用篩選
  const handleApplyFilters = useCallback(() => {
    const newFilters: Partial<CategoryListParams> = {};

    // 搜尋關鍵字
    if (searchValue?.trim()) {
      newFilters.search = searchValue.trim();
    }

    // 狀態篩選
    if (statusFilter !== 'all') {
      newFilters.status = statusFilter === 'active';
    }

    // 父分類篩選
    if (parentFilter === 'root') {
      newFilters.parent_id = 'root';
    }

    // 排序
    newFilters.sort = sortField as CategoryListParams['sort'];
    newFilters.direction = sortDirection as CategoryListParams['direction'];

    onFiltersChange(newFilters);
    setIsPopoverOpen(false);
  }, [searchValue, statusFilter, parentFilter, sortField, sortDirection, onFiltersChange]);

  // 🔄 重置篩選
  const handleResetFilters = useCallback(() => {
    setSearchValue('');
    setStatusFilter('all');
    setParentFilter('all');
    setSortField('position');
    setSortDirection('asc');
    onFiltersChange({});
    setIsPopoverOpen(false);
  }, [onFiltersChange]);

  // 🔍 即時搜尋
  const handleSearchChange = useCallback((value: string) => {
    setSearchValue(value);
    const newFilters = { ...filters };
    if (value.trim()) {
      newFilters.search = value.trim();
    } else {
      delete newFilters.search;
    }
    onFiltersChange(newFilters);
  }, [filters, onFiltersChange]);

  // 🏷️ 生成篩選標籤
  const generateFilterTags = useCallback(() => {
    const tags: { key: string; label: string; value: string; onRemove: () => void }[] = [];

    if (filters.search) {
      tags.push({
        key: 'search',
        label: '搜尋',
        value: filters.search,
        onRemove: () => onFiltersChange({ ...filters, search: undefined }),
      });
    }

    if (filters.status !== undefined) {
      tags.push({
        key: 'status',
        label: '狀態',
        value: filters.status ? '啟用' : '停用',
        onRemove: () => onFiltersChange({ ...filters, status: undefined }),
      });
    }

    if (filters.parent_id === 'root') {
      tags.push({
        key: 'parent_id',
        label: '層級',
        value: '根分類',
        onRemove: () => onFiltersChange({ ...filters, parent_id: undefined }),
      });
    }

    if (filters.sort && filters.sort !== 'position') {
      const sortLabels = {
        name: '名稱',
        created_at: '建立時間',
        position: '位置',
      };
      tags.push({
        key: 'sort',
        label: '排序',
        value: `${sortLabels[filters.sort] || filters.sort} (${filters.direction === 'desc' ? '降冪' : '升冪'})`,
        onRemove: () => onFiltersChange({ ...filters, sort: 'position', direction: 'asc' }),
      });
    }

    return tags;
  }, [filters, onFiltersChange]);

  const filterTags = generateFilterTags();
  const hasActiveFilters = filterTags.length > 0;

  return (
    <Card>
      <CardHeader className="pb-3">
        <CardTitle className="flex items-center justify-between text-base">
          <div className="flex items-center gap-2">
            <SlidersHorizontal className="h-4 w-4" />
            搜尋和篩選
          </div>
          
          {/* 🎮 快速搜尋 */}
          <div className="flex items-center gap-2">
            <div className="relative">
              <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="快速搜尋分類..."
                value={searchValue}
                onChange={(e) => handleSearchChange(e.target.value)}
                className="pl-10 w-64"
                disabled={isLoading}
              />
            </div>

            {/* 🔧 進階篩選按鈕 */}
            <Popover open={isPopoverOpen} onOpenChange={setIsPopoverOpen}>
              <PopoverTrigger asChild>
                <Button variant={hasActiveFilters ? 'default' : 'outline'} size="sm">
                  <Filter className="h-4 w-4 mr-2" />
                  進階篩選
                  {hasActiveFilters && (
                    <Badge variant="secondary" className="ml-2 text-xs">
                      {filterTags.length}
                    </Badge>
                  )}
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-96 p-4" align="end">
                <div className="space-y-4">
                  {/* 📊 狀態篩選 */}
                  <div className="space-y-2">
                    <Label>狀態</Label>
                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">全部</SelectItem>
                        <SelectItem value="active">啟用</SelectItem>
                        <SelectItem value="inactive">停用</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  {/* 🏗️ 層級篩選 */}
                  <div className="space-y-2">
                    <Label>層級</Label>
                    <Select value={parentFilter} onValueChange={setParentFilter}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">全部層級</SelectItem>
                        <SelectItem value="root">根分類</SelectItem>
                        <SelectItem value="children">子分類</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  {/* 📈 排序選項 */}
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>排序欄位</Label>
                      <Select value={sortField} onValueChange={(value) => setSortField(value as 'name' | 'position' | 'created_at')}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="position">位置</SelectItem>
                          <SelectItem value="name">名稱</SelectItem>
                          <SelectItem value="created_at">建立時間</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label>排序方向</Label>
                      <Select value={sortDirection} onValueChange={(value) => setSortDirection(value as 'asc' | 'desc')}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="asc">升冪</SelectItem>
                          <SelectItem value="desc">降冪</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  {/* ⚡ 操作按鈕 */}
                  <div className="flex justify-between pt-4 border-t">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={handleResetFilters}
                      className="flex items-center gap-2"
                    >
                      <RotateCcw className="h-3 w-3" />
                      重置
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      disabled={isLoading}
                      onClick={handleApplyFilters}
                      className="flex items-center gap-2"
                    >
                      <Check className="h-3 w-3" />
                      套用篩選
                    </Button>
                  </div>
                </div>
              </PopoverContent>
            </Popover>
          </div>
        </CardTitle>
      </CardHeader>

      {/* 🏷️ 已套用的篩選標籤 */}
      {hasActiveFilters && (
        <CardContent className="pt-0 pb-4">
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-sm text-muted-foreground">已套用篩選：</span>
            {filterTags.map((tag) => (
              <FilterTag
                key={tag.key}
                label={tag.label}
                value={tag.value}
                onRemove={tag.onRemove}
              />
            ))}
            <Button
              variant="ghost"
              size="sm"
              onClick={handleResetFilters}
              className="h-6 px-2 text-xs"
            >
              清除全部
            </Button>
          </div>
        </CardContent>
      )}
    </Card>
  );
} 