/**
 * RoleSelector - 角色選擇器組件
 * 
 * 用於選擇和管理使用者角色
 * 支援多選、單選、角色層級限制等功能
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { 
  Crown, 
  Shield, 
  Users, 
  UserCheck, 
  Check, 
  ChevronsUpDown,
  Search,
  X
} from 'lucide-react';
import { useAuthStore } from '@/stores/authStore';
import type { UserRole } from '@/types/user';

/**
 * 角色選項介面
 */
interface RoleOption {
  value: string;
  label: string;
  level: 'admin' | 'manager' | 'staff' | 'guest';
  description: string;
  permissions_count: number;
  disabled?: boolean;
}

/**
 * 角色選擇器組件屬性
 */
interface RoleSelectorProps {
  /** 當前選中的角色（單選模式） */
  value?: string;
  /** 當前選中的角色列表（多選模式） */
  values?: string[];
  /** 是否為多選模式 */
  multiple?: boolean;
  /** 是否禁用 */
  disabled?: boolean;
  /** 佔位符文字 */
  placeholder?: string;
  /** 錯誤訊息 */
  error?: string;
  /** 自定義 CSS 類別 */
  className?: string;
  /** 角色選項資料 */
  options?: RoleOption[];
  /** 角色選擇變更回調（單選） */
  onValueChange?: (value: string) => void;
  /** 角色選擇變更回調（多選） */
  onValuesChange?: (values: string[]) => void;
  /** 是否顯示權限數量 */
  showPermissionCount?: boolean;
  /** 是否顯示角色描述 */
  showDescription?: boolean;
  /** 最大選擇數量（多選模式） */
  maxSelections?: number;
  /** 角色層級限制 */
  allowedLevels?: Array<'admin' | 'manager' | 'staff' | 'guest'>;
}

/**
 * 模擬角色選項資料
 */
const defaultRoleOptions: RoleOption[] = [
  {
    value: 'admin',
    label: '系統管理員',
    level: 'admin',
    description: '擁有系統所有權限的超級管理員',
    permissions_count: 45,
  },
  {
    value: 'store_manager',
    label: '門市經理',
    level: 'manager',
    description: '負責門市整體營運管理',
    permissions_count: 32,
  },
  {
    value: 'inventory_manager',
    label: '庫存管理員',
    level: 'manager',
    description: '負責商品庫存和採購管理',
    permissions_count: 25,
  },
  {
    value: 'cashier',
    label: '收銀員',
    level: 'staff',
    description: '負責收銀和客戶服務',
    permissions_count: 18,
  },
  {
    value: 'sales_staff',
    label: '銷售人員',
    level: 'staff',
    description: '負責商品銷售和客戶服務',
    permissions_count: 15,
  },
  {
    value: 'guest',
    label: '訪客',
    level: 'guest',
    description: '擁有基本查看權限的訪客用戶',
    permissions_count: 5,
  },
];

/**
 * 角色層級圖標映射
 */
const levelIcons = {
  admin: Crown,
  manager: Shield,
  staff: Users,
  guest: UserCheck,
};

/**
 * 角色層級顏色映射
 */
const levelColors = {
  admin: 'text-red-600 bg-red-50 border-red-200',
  manager: 'text-blue-600 bg-blue-50 border-blue-200',
  staff: 'text-green-600 bg-green-50 border-green-200',
  guest: 'text-gray-600 bg-gray-50 border-gray-200',
};

/**
 * 角色Badge變體映射
 */
const levelBadgeVariants = {
  admin: 'destructive' as const,
  manager: 'default' as const,
  staff: 'secondary' as const,
  guest: 'outline' as const,
};

/**
 * 角色選擇器組件
 */
export const RoleSelector: React.FC<RoleSelectorProps> = ({
  value,
  values = [],
  multiple = false,
  disabled = false,
  placeholder = '請選擇角色',
  error,
  className = '',
  options = defaultRoleOptions,
  onValueChange,
  onValuesChange,
  showPermissionCount = true,
  showDescription = true,
  maxSelections,
  allowedLevels,
}) => {
  const [open, setOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const { user, hasRole } = useAuthStore();

  /**
   * 篩選可用的角色選項
   */
  const filteredOptions = options.filter(option => {
    // 權限篩選：只有管理員可以選擇管理員角色
    if (option.level === 'admin' && !hasRole('admin')) {
      return false;
    }

    // 角色層級限制
    if (allowedLevels && !allowedLevels.includes(option.level)) {
      return false;
    }

    // 搜尋篩選
    if (searchTerm) {
      const searchLower = searchTerm.toLowerCase();
      return (
        option.label.toLowerCase().includes(searchLower) ||
        option.description.toLowerCase().includes(searchLower)
      );
    }

    return true;
  });

  /**
   * 處理單選角色變更
   */
  const handleSingleSelect = (selectedValue: string) => {
    onValueChange?.(selectedValue);
    setOpen(false);
  };

  /**
   * 處理多選角色變更
   */
  const handleMultipleSelect = (selectedValue: string) => {
    const newValues = values.includes(selectedValue)
      ? values.filter(v => v !== selectedValue)
      : [...values, selectedValue];

    // 檢查最大選擇數量限制
    if (maxSelections && newValues.length > maxSelections) {
      return;
    }

    onValuesChange?.(newValues);
  };

  /**
   * 移除選中的角色
   */
  const removeRole = (roleValue: string) => {
    if (multiple) {
      onValuesChange?.(values.filter(v => v !== roleValue));
    } else {
      onValueChange?.('');
    }
  };

  /**
   * 取得選中角色的顯示標籤
   */
  const getSelectedRoleLabels = () => {
    if (multiple) {
      return values.map(v => options.find(opt => opt.value === v)?.label).filter(Boolean);
    } else {
      return value ? [options.find(opt => opt.value === value)?.label].filter(Boolean) : [];
    }
  };

  /**
   * 渲染角色選項
   */
  const renderRoleOption = (option: RoleOption) => {
    const LevelIcon = levelIcons[option.level];
    const isSelected = multiple ? values.includes(option.value) : value === option.value;

    return (
      <div
        key={option.value}
        className={`flex items-center space-x-3 p-3 rounded-lg border cursor-pointer transition-all hover:bg-accent ${
          isSelected ? 'ring-2 ring-primary' : ''
        } ${option.disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
        onClick={() => {
          if (option.disabled) return;
          if (multiple) {
            handleMultipleSelect(option.value);
          } else {
            handleSingleSelect(option.value);
          }
        }}
      >
        {multiple && (
          <Checkbox
            checked={isSelected}
            disabled={option.disabled}
            className="pointer-events-none"
          />
        )}
        
        <div className={`p-2 rounded-full ${levelColors[option.level]}`}>
          <LevelIcon className="h-4 w-4" />
        </div>
        
        <div className="flex-1 min-w-0">
          <div className="flex items-center space-x-2">
            <span className="font-medium">{option.label}</span>
            <Badge variant={levelBadgeVariants[option.level]} className="text-xs">
              {option.level}
            </Badge>
          </div>
          
          {showDescription && (
            <p className="text-sm text-muted-foreground mt-1">
              {option.description}
            </p>
          )}
          
          {showPermissionCount && (
            <p className="text-xs text-muted-foreground mt-1">
              {option.permissions_count} 個權限
            </p>
          )}
        </div>
        
        {isSelected && <Check className="h-4 w-4 text-primary" />}
      </div>
    );
  };

  return (
    <div className={className}>
      {/* 單選模式 */}
      {!multiple ? (
        <Select value={value} onValueChange={onValueChange} disabled={disabled}>
          <SelectTrigger className={error ? 'border-red-500' : ''}>
            <SelectValue placeholder={placeholder} />
          </SelectTrigger>
          <SelectContent>
            {filteredOptions.map(option => (
              <SelectItem 
                key={option.value} 
                value={option.value}
                disabled={option.disabled}
              >
                <div className="flex items-center space-x-2">
                  <div className={`p-1 rounded-full ${levelColors[option.level]}`}>
                    {React.createElement(levelIcons[option.level], { className: "h-3 w-3" })}
                  </div>
                  <span>{option.label}</span>
                  <Badge variant={levelBadgeVariants[option.level]} className="text-xs">
                    {option.level}
                  </Badge>
                </div>
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      ) : (
        /* 多選模式 */
        <Popover open={open} onOpenChange={setOpen}>
          <PopoverTrigger asChild>
            <Button
              variant="outline"
              role="combobox"
              aria-expanded={open}
              className={`w-full justify-between ${error ? 'border-red-500' : ''}`}
              disabled={disabled}
            >
              <div className="flex flex-wrap gap-1">
                {values.length === 0 ? (
                  <span className="text-muted-foreground">{placeholder}</span>
                ) : (
                  getSelectedRoleLabels().map((label, index) => (
                    <Badge key={index} variant="secondary" className="text-xs">
                      {label}
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          removeRole(values[index]);
                        }}
                        className="ml-1 hover:bg-secondary-foreground/20 rounded-full p-0.5"
                      >
                        <X className="h-3 w-3" />
                      </button>
                    </Badge>
                  ))
                )}
              </div>
              <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
          </PopoverTrigger>
          
          <PopoverContent className="w-80 p-0" align="start">
            <div className="p-3 border-b">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="搜尋角色..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>
              
              {maxSelections && (
                <div className="mt-2 text-xs text-muted-foreground">
                  已選擇 {values.length} / {maxSelections} 個角色
                </div>
              )}
            </div>
            
            <div className="max-h-64 overflow-y-auto p-2 space-y-1">
              {filteredOptions.length === 0 ? (
                <div className="p-3 text-center text-muted-foreground">
                  沒有找到匹配的角色
                </div>
              ) : (
                filteredOptions.map(option => renderRoleOption(option))
              )}
            </div>
          </PopoverContent>
        </Popover>
      )}

      {/* 錯誤訊息 */}
      {error && (
        <p className="text-sm text-red-500 mt-1">{error}</p>
      )}
      
      {/* 選中角色資訊 */}
      {(multiple && values.length > 0) && (
        <div className="mt-3 space-y-2">
          <Label className="text-sm font-medium">已選擇的角色：</Label>
          <div className="space-y-2">
            {values.map(roleValue => {
              const role = options.find(opt => opt.value === roleValue);
              if (!role) return null;
              
              const LevelIcon = levelIcons[role.level];
              return (
                <Card key={roleValue} className="p-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      <div className={`p-2 rounded-full ${levelColors[role.level]}`}>
                        <LevelIcon className="h-4 w-4" />
                      </div>
                      <div>
                        <div className="flex items-center space-x-2">
                          <span className="font-medium">{role.label}</span>
                          <Badge variant={levelBadgeVariants[role.level]} className="text-xs">
                            {role.level}
                          </Badge>
                        </div>
                        {showPermissionCount && (
                          <p className="text-xs text-muted-foreground">
                            {role.permissions_count} 個權限
                          </p>
                        )}
                      </div>
                    </div>
                    
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => removeRole(roleValue)}
                      className="text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  </div>
                </Card>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
};

export default RoleSelector; 