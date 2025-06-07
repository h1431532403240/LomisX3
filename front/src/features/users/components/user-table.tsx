/**
 * 使用者表格組件 (V3.0 - DataTable V2.0 完全受控模式)
 * 
 * @description 使用 DataTable V2.0 完全受控組件，管理所有表格狀態
 * @requires shadcn/ui 組件庫 + DataTable V2.0
 */
import { useState } from 'react';
import { DataTable } from '@/components/common/data-table';
import type { DataTableColumn, TableAction } from '@/components/common/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Edit, Eye, Trash2 } from 'lucide-react';
import { PermissionGuard } from '@/components/common/permission-guard';
import { useDebounce } from '@/hooks/common/use-debounce';
import type { components } from '@/types/api';

type User = components['schemas']['User'];

interface UserTableProps {
  users?: User[];
  isLoading: boolean;
  onEditUser?: (user: User) => void;
  onViewUser?: (user: User) => void;
  onDeleteUser?: (user: User) => void;
  onCreateUser?: () => void;
  onSearchChange?: (term: string) => void;
  onSelectionChange?: (selectedKeys: string[], selectedRows: User[]) => void;
}

/**
 * 使用者表格組件 (V3.0 - 完全受控模式)
 */
export const UserTable: React.FC<UserTableProps> = ({
  users = [],
  isLoading,
  onEditUser,
  onViewUser,
  onDeleteUser,
  onCreateUser,
  onSearchChange,
  onSelectionChange,
}) => {
  // ✅ DataTable V2.0 狀態管理 (由 UserTable 全權負責)
  const [sortState, setSortState] = useState<{ field: string | null; order: 'asc' | 'desc' }>({ 
    field: 'created_at', 
    order: 'desc' 
  });
  const [selectionState, setSelectionState] = useState<{ selectedKeys: string[] }>({ 
    selectedKeys: [] 
  });
  const [searchState, setSearchState] = useState<{ value: string }>({ 
    value: '' 
  });

  // 保留原有的防抖搜尋邏輯
  const debouncedSearchTerm = useDebounce(searchState.value, 300);

  // 當防抖搜尋詞變化時，通知父組件
  useState(() => {
    onSearchChange?.(debouncedSearchTerm);
  });

  // ✅ DataTable V2.0 事件回調函數
  const handleSortChange = (field: string, order: 'asc' | 'desc') => {
    setSortState({ field, order });
    // 可選：在這裡觸發 API 重新獲取數據
    // setQueryParams(prev => ({ ...prev, sort: field, order }));
  };

  const handleSelectionChange = (newSelectedKeys: string[], selectedRows: User[]) => {
    setSelectionState({ selectedKeys: newSelectedKeys });
    onSelectionChange?.(newSelectedKeys, selectedRows); // 如果父組件還需要，繼續傳遞
  };

  const handleSearchChange = (newValue: string) => {
    setSearchState({ value: newValue });
    // 內部狀態更新後，防抖邏輯會自動觸發 onSearchChange
  };

  /**
   * 格式化角色顯示
   */
  const formatRole = (roles?: User['roles']): string => {
    if (!roles || roles.length === 0) return '無角色';
    const roleName = roles[0]?.name;
    if (!roleName) return '未知角色';
    
    const roleMap: { [key: string]: string } = {
      admin: '系統管理員',
      store_admin: '門市管理員',
      manager: '經理',
      staff: '員工',
      guest: '訪客',
    };
    return roleMap[roleName] || roleName;
  };

  const getStatusVariant = (status?: 'active' | 'inactive' | 'suspended'): 'default' | 'secondary' | 'destructive' => {
    switch (status) {
      case 'active':
        return 'default';
      case 'suspended':
        return 'destructive';
      case 'inactive':
      default:
        return 'secondary';
    }
  };
  
  const getStatusText = (status?: 'active' | 'inactive' | 'suspended'): string => {
     switch (status) {
      case 'active':
        return '啟用';
      case 'suspended':
        return '停權';
      case 'inactive':
      default:
        return '停用';
    }
  };

  // ✅ 定義 DataTable 欄位配置
  const columns: DataTableColumn<User>[] = [
    {
      key: 'username',
      title: '使用者名稱',
      dataIndex: 'username',
      sortable: true,
      render: (value: string) => (
        <span className="font-medium">{value}</span>
      ),
    },
    {
      key: 'email',
      title: '電子郵件',
      dataIndex: 'email',
      sortable: true,
    },
    {
      key: 'role',
      title: '角色',
      dataIndex: 'roles',
      render: (roles: User['roles']) => formatRole(roles),
    },
    {
      key: 'status',
      title: '狀態',
      dataIndex: 'status',
      sortable: true,
      render: (status: User['status']) => (
        <Badge variant={getStatusVariant(status)}>
          {getStatusText(status)}
        </Badge>
      ),
    },
    {
      key: 'created_at',
      title: '建立時間',
      dataIndex: 'created_at',
      sortable: true,
      render: (value: string) => {
        if (!value) return '-';
        return new Date(value).toLocaleDateString('zh-TW');
      },
    },
  ];

  // ✅ 定義表格操作
  const actions: TableAction<User>[] = [
    {
      key: 'view',
      label: '檢視',
      icon: <Eye className="h-4 w-4" />,
      onClick: (user: User) => onViewUser?.(user),
    },
    {
      key: 'edit',
      label: '編輯',
      icon: <Edit className="h-4 w-4" />,
      onClick: (user: User) => onEditUser?.(user),
      // 使用 PermissionGuard 邏輯檢查權限
      disabled: () => false, // 在這裡可以添加權限檢查邏輯
    },
    {
      key: 'delete',
      label: '刪除',
      icon: <Trash2 className="h-4 w-4" />,
      type: 'danger',
      onClick: (user: User) => onDeleteUser?.(user),
      // 使用 PermissionGuard 邏輯檢查權限
      disabled: () => false, // 在這裡可以添加權限檢查邏輯
    },
  ];

  return (
    <div className="space-y-4">
      {/* ✅ 使用 DataTable V2.0 完全受控組件 */}
      <DataTable<User>
        data={users}
        columns={columns}
        actions={actions}
        loading={isLoading}
        
        // ✅ 受控狀態
        sortState={sortState}
        selectionState={selectionState}
        searchState={searchState}
        
        // ✅ 事件回調
        onSortChange={handleSortChange}
        onSelectionChange={handleSelectionChange}
        onSearchChange={handleSearchChange}
        
        // UI 配置
        title={`使用者列表 (${users.length})`}
        emptyText={searchState.value ? '沒有找到符合條件的使用者' : '暫無使用者資料'}
        searchPlaceholder="搜尋使用者..."
        toolbar={
          <PermissionGuard permission="users.create">
            <Button onClick={onCreateUser}>
              新增使用者
            </Button>
          </PermissionGuard>
        }
        rowKey="id"
      />
    </div>
  );
}; 