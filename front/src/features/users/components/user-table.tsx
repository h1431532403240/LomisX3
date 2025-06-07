/**
 * 使用者表格組件 (V4.2 - 容錯處理增強)
 * 
 * @description 使用 DataTable V2.0 完全受控組件，專注於 UI 展示邏輯
 * @requires shadcn/ui 組件庫 + DataTable V2.0
 * 
 * ✅ V4.2 容錯處理增強：
 * - 新增物件格式 status 的容錯處理，自動提取 value 字段
 * - 支援過渡期間的後端資料格式變更，提供調試警告
 * - 防止 React 渲染物件導致的錯誤
 * 
 * ✅ V4.1 狀態處理優化：
 * - 實現統一的 getStatusConfig 輔助函數，替代分離的 getStatusVariant/getStatusText
 * - 支援更多狀態類型：active, inactive, suspended, locked, pending
 * - 健壯的錯誤處理：未知狀態會顯示原始值
 * 
 * ✅ V4.0 重構記錄：
 * - 移除防抖邏輯，將搜尋副作用責任交還給容器組件
 * - 簡化 handleSearchChange，只負責通知父組件
 * - 提升組件性能，遵循單一職責原則
 * 
 * ✅ V3.1 更新記錄：
 * - 修正 formatRole 函數：支援後端返回的字串陣列格式 (roles: string[])
 * - 確保 status 欄位正確處理字串格式，精確匹配後端 API 回應
 * - 角色顯示邏輯：多個角色用逗號連接，提供完整角色資訊
 */
import { useState } from 'react';
import { DataTable } from '@/components/common/data-table';
import type { DataTableColumn, TableAction } from '@/components/common/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Edit, Eye, Trash2 } from 'lucide-react';
import { PermissionGuard } from '@/components/common/permission-guard';
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
    // ✅ V4.0 重構：直接通知父組件，不進行任何防抖或內部狀態設置
    onSearchChange?.(newValue);
  };

  /**
   * 格式化角色顯示 (V3.1 - 支援字串陣列格式)
   * @description 處理後端返回的角色字串陣列，將所有角色用逗號連接
   */
  const formatRole = (roles?: string[]): string => {
    if (!roles || roles.length === 0) return '無角色';
    
    const roleMap: { [key: string]: string } = {
      'admin': '系統管理員',
      'store_admin': '門市管理員',
      'manager': '經理',
      'staff': '員工',
      'guest': '訪客',
    };

    // ✅ 將每個角色名翻譯後，用逗號連接
    return roles.map(roleName => roleMap[roleName] || roleName).join(', ');
  };

  /**
   * 統一的狀態配置輔助函數 (V4.2 - 健壯設計 + 容錯處理)
   * @description 處理後端返回的狀態字串或物件，提供統一的標籤和樣式配置
   */
  const getStatusConfig = (status?: string | any) => {
    // ✅ 容錯處理：如果收到物件格式，提取 value 字段
    let statusValue = status;
    if (typeof status === 'object' && status !== null && 'value' in status) {
      console.warn('⚠️ 接收到物件格式的 status，自動提取 value 字段:', status);
      statusValue = status.value;
    }
    
    switch (statusValue) {
      case 'active':
        return { 
          variant: 'default' as const, 
          label: '啟用' 
        };
      case 'suspended':
        return { 
          variant: 'destructive' as const, 
          label: '停權' 
        };
      case 'inactive':
        return { 
          variant: 'secondary' as const, 
          label: '停用' 
        };
      case 'locked':
        return { 
          variant: 'destructive' as const, 
          label: '鎖定' 
        };
      case 'pending':
        return { 
          variant: 'outline' as const, 
          label: '待啟用' 
        };
      default:
        console.warn('⚠️ 未知的狀態值:', statusValue);
        return { 
          variant: 'secondary' as const, 
          label: String(statusValue) || '未知' 
        };
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
      render: (roles: string[]) => formatRole(roles),
    },
    {
      key: 'status',
      title: '狀態',
      dataIndex: 'status', // ✅ 確保 dataIndex 指向 status 字串
      sortable: true,
      render: (status: string) => { // ✅ 接收到的 status 就是字串
        const config = getStatusConfig(status);
        return <Badge variant={config.variant}>{config.label}</Badge>;
      },
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
        data={Array.isArray(users) ? users : []}
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
        title={`使用者列表 (${Array.isArray(users) ? users.length : 0})`}
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