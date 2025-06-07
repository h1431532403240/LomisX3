import React, { useMemo, useCallback } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { MoreHorizontal, Search, Edit, Eye, Trash2, Shield } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { DataTable } from '@/components/common/data-table';
import type { User } from '@/types/api';

/**
 * 使用者表格組件 V5.0
 * 
 * ✅ V5.0 重大架構更新：
 * - 完全移除硬編碼的角色和狀態翻譯
 * - 實現配置驅動UI，支援動態枚舉擴展
 * - 強制 API 合約驗證，只接受字串型別
 * - 符合 LomisX3 V4.0「API 合約絕對真理」原則
 * 
 * 架構改進：
 * - 依賴注入：翻譯函數通過 props 傳入
 * - 單一職責：組件只負責UI渲染
 * - 開放封閉：新增角色/狀態無需修改組件
 * 
 * @author LomisX3 開發團隊
 * @version V5.0
 */

/**
 * 格式化函數介面
 * 強制型別安全，實現依賴抽象原則
 */
export interface StatusFormatter {
  (status: string): { label: string; color: string };
}

export interface RoleFormatter {
  (roles: string[]): { label: string; color: string };
}

/**
 * UserTable 組件 Props
 */
interface UserTableProps {
  // 資料相關
  users?: User[];
  isLoading?: boolean;
  
  // 搜尋和篩選
  searchTerm?: string;
  onSearchChange?: (term: string) => void;
  
  // 選擇相關
  selectedUsers?: string[];
  onSelectionChange?: (selectedIds: string[]) => void;
  
  // 操作回調
  onEditUser?: (user: User) => void;
  onViewUser?: (user: User) => void;
  onDeleteUser?: (user: User) => void;
  onManagePermissions?: (user: User) => void;
  
  // ✅ V5.0 核心創新：配置驅動格式化函數
  formatStatus: StatusFormatter;
  formatRole: RoleFormatter;
  
  // 錯誤處理
  onFormatError?: (error: Error, context: string) => void;
}

export function UserTable({
  users = [],
  isLoading = false,
  searchTerm = '',
  onSearchChange,
  selectedUsers = [],
  onSelectionChange,
  onEditUser,
  onViewUser,
  onDeleteUser,
  onManagePermissions,
  formatStatus,
  formatRole,
  onFormatError
}: UserTableProps) {
  
  /**
   * 安全的狀態格式化
   * 包含 API 合約驗證和錯誤處理
   */
  const safeFormatStatus = useCallback((status: unknown): { label: string; color: string } => {
    try {
      // ✅ API 合約強制驗證：只接受字串
      if (typeof status !== 'string') {
        const error = new Error(`API 合約違反：status 必須為字串，收到 ${typeof status}`);
        onFormatError?.(error, 'status-formatting');
        throw error;
      }

      return formatStatus(status);
      
    } catch (error) {
      console.error('❌ UserTable: 狀態格式化失敗', error);
      
      // 錯誤回退機制
      return {
        label: '格式錯誤',
        color: 'destructive'
      };
    }
  }, [formatStatus, onFormatError]);

  /**
   * 安全的角色格式化
   * 包含 API 合約驗證和錯誤處理
   */
  const safeFormatRole = useCallback((roles: unknown): { label: string; color: string } => {
    try {
      // 正規化為陣列
      let roleArray: string[];
      
      if (typeof roles === 'string') {
        roleArray = [roles];
      } else if (Array.isArray(roles)) {
        roleArray = roles;
      } else {
        throw new Error(`API 合約違反：roles 必須為字串或字串陣列，收到 ${typeof roles}`);
      }

      // ✅ API 合約強制驗證：確保所有元素都是字串
      const invalidRoles = roleArray.filter(role => typeof role !== 'string');
      if (invalidRoles.length > 0) {
        const error = new Error(`API 合約違反：roles 陣列包含非字串元素`);
        onFormatError?.(error, 'role-formatting');
        throw error;
      }

      return formatRole(roleArray);
      
    } catch (error) {
      console.error('❌ UserTable: 角色格式化失敗', error);
      
      // 錯誤回退機制
      return {
        label: '格式錯誤',
        color: 'destructive'
      };
    }
  }, [formatRole, onFormatError]);

  /**
   * 表格欄位定義
   * 使用配置驅動的格式化函數
   */
  const columns = useMemo(() => [
    {
      id: 'select',
      header: ({ table }: any) => (
        <input
          type="checkbox"
          checked={table.getIsAllPageRowsSelected()}
          onChange={(e) => {
            table.toggleAllPageRowsSelected(e.target.checked);
            const selectedIds = e.target.checked 
              ? users.map(user => user.id.toString())
              : [];
            onSelectionChange?.(selectedIds);
          }}
          className="rounded border-gray-300"
        />
      ),
      cell: ({ row }: any) => (
        <input
          type="checkbox"
          checked={row.getIsSelected()}
          onChange={(e) => {
            row.toggleSelected(e.target.checked);
            const currentSelected = selectedUsers;
            const userId = row.original.id.toString();
            
            const newSelected = e.target.checked
              ? [...currentSelected, userId]
              : currentSelected.filter(id => id !== userId);
              
            onSelectionChange?.(newSelected);
          }}
          className="rounded border-gray-300"
        />
      ),
      enableSorting: false,
      enableHiding: false,
    },
    {
      accessorKey: 'username',
      header: '帳號',
      cell: ({ row }: any) => (
        <div className="font-medium">{row.getValue('username')}</div>
      ),
    },
    {
      accessorKey: 'name', 
      header: '姓名',
    },
    {
      accessorKey: 'email',
      header: '電子信箱',
    },
    {
      accessorKey: 'roles',
      header: '角色',
      cell: ({ row }: any) => {
        const roles = row.getValue('roles');
        const { label, color } = safeFormatRole(roles);
        
        return (
          <Badge variant={color as any} className="font-medium">
            {label}
          </Badge>
        );
      },
    },
    {
      accessorKey: 'status',
      header: '狀態',
      cell: ({ row }: any) => {
        const status = row.getValue('status');
        const { label, color } = safeFormatStatus(status);
        
        return (
          <Badge variant={color as any}>
            {label}
          </Badge>
        );
      },
    },
    {
      accessorKey: 'created_at',
      header: '建立時間',
      cell: ({ row }: any) => {
        const date = new Date(row.getValue('created_at'));
        return date.toLocaleDateString('zh-TW');
      },
    },
    {
      id: 'actions',
      header: '操作',
      cell: ({ row }: any) => {
        const user = row.original;
        
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">開啟選單</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>操作</DropdownMenuLabel>
              <DropdownMenuItem onClick={() => onViewUser?.(user)}>
                <Eye className="mr-2 h-4 w-4" />
                查看
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => onEditUser?.(user)}>
                <Edit className="mr-2 h-4 w-4" />
                編輯
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => onManagePermissions?.(user)}>
                <Shield className="mr-2 h-4 w-4" />
                權限管理
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem 
                onClick={() => onDeleteUser?.(user)}
                className="text-red-600"
              >
                <Trash2 className="mr-2 h-4 w-4" />
                刪除
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
      enableSorting: false,
      enableHiding: false,
    },
  ], [users, selectedUsers, onSelectionChange, onViewUser, onEditUser, onDeleteUser, onManagePermissions, safeFormatStatus, safeFormatRole]);

  /**
   * 工具列渲染
   */
  const renderToolbar = () => (
    <div className="flex items-center justify-between">
      <div className="flex items-center space-x-2">
        <div className="relative">
          <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="搜尋使用者..."
            value={searchTerm}
            onChange={(e) => onSearchChange?.(e.target.value)}
            className="pl-8 w-[250px]"
          />
        </div>
      </div>
      
      <div className="flex items-center space-x-2">
        <span className="text-sm text-muted-foreground">
          使用者列表 ({Array.isArray(users) ? users.length : 0})
        </span>
      </div>
    </div>
  );

  return (
    <div className="space-y-4">
      {renderToolbar()}
      
      <DataTable
        data={Array.isArray(users) ? users : []}
        columns={columns}
        isLoading={isLoading}
        emptyMessage="目前沒有使用者資料"
      />
    </div>
  );
}

export default UserTable; 