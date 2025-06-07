/**
 * 使用者資料表格組件
 * 提供完整的使用者展示、搜尋、篩選、操作功能
 */
import React, { useState, useMemo } from 'react';
import { DataTable } from '@/components/common/data-table';
import type { DataTableColumn, TableAction } from '@/components/common/data-table';
import { ConfirmDialog, DeleteConfirmDialog, BatchConfirmDialog } from '@/components/common/confirm-dialog';
import { PermissionGuard } from '@/components/common/permission-guard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Edit,
  Trash2,
  Eye,
  UserCheck,
  UserX,
  Plus,
  Filter,
  Download,
  Upload,
} from 'lucide-react';
import { format } from 'date-fns';
import { zhTW } from 'date-fns/locale';
import { toast } from 'sonner';
import { useGetUsers } from '@/hooks/api/users/useGetUsers';
import { useUpdateUser } from '@/hooks/api/users/useUpdateUser';
import { useDeleteUser } from '@/hooks/api/users/useDeleteUser';
import { useBatchUpdateUserStatus } from '@/hooks/api/users/useBatchUpdateUsers';
import { useDebouncedSearch } from '@/hooks/common/useDebounce';
import { useFilters } from '@/hooks/common/useFilters';
import { usePagination } from '@/hooks/common/usePagination';
import type { components } from '@/types/api';

// API 類型定義
type User = components['schemas']['User'];
type UserStatus = components['schemas']['UserStatus'];

/**
 * 使用者狀態枚舉
 */
export const USER_STATUS_OPTIONS = [
  { label: '全部', value: '' },
  { label: '啟用', value: 'active' },
  { label: '停用', value: 'inactive' },
  { label: '暫停', value: 'suspended' },
] as const;

/**
 * 使用者篩選器介面
 */
interface UserFilters {
  search: string;
  status: UserStatus | '';
  role: string;
  store_id: number | '';
  has_2fa: boolean | '';
  email_verified: boolean | '';
}

/**
 * 使用者表格屬性
 */
export interface UserTableProps {
  /** 表格標題 */
  title?: string;
  /** 是否顯示工具列 */
  showToolbar?: boolean;
  /** 是否顯示批次操作 */
  showBatchActions?: boolean;
  /** 是否顯示搜尋 */
  showSearch?: boolean;
  /** 是否顯示篩選器 */
  showFilters?: boolean;
  /** 需要高亮的使用者 ID */
  highlightedUserId?: string | number | null;
  /** 自訂操作項 */
  customActions?: TableAction<User>[];
  /** 選擇變更回調 */
  onSelectionChange?: (selectedKeys: string[], selectedRows: User[]) => void;
}

/**
 * 使用者狀態標籤組件
 */
function UserStatusBadge({ status }: { status: UserStatus }) {
  const statusConfig = {
    active: { label: '啟用', variant: 'default' as const },
    inactive: { label: '停用', variant: 'secondary' as const },
    suspended: { label: '暫停', variant: 'destructive' as const },
  };

  const config = statusConfig[status] || statusConfig.inactive;
  
  return (
    <Badge variant={config.variant}>
      {config.label}
    </Badge>
  );
}

/**
 * 使用者頭像組件
 */
function UserAvatar({ user }: { user: User }) {
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <Avatar className="h-8 w-8">
      <AvatarImage src={user.avatar_url} alt={user.name} />
      <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
    </Avatar>
  );
}

/**
 * 使用者資料表格組件
 */
export function UserTable({
  title = '使用者管理',
  showToolbar = true,
  showBatchActions = true,
  showSearch = true,
  showFilters = true,
  highlightedUserId = null,
  customActions = [],
  onSelectionChange,
}: UserTableProps) {
  
  // 搜尋和篩選狀態
  const { searchTerm, debouncedSearchTerm, setSearch } = useDebouncedSearch('', 300);
  const { filters, actions: filterActions } = useFilters<UserFilters>({
    initialFilters: {
      search: '',
      status: '',
      role: '',
      store_id: '',
      has_2fa: '',
      email_verified: '',
    },
  });

  // 分頁狀態
  const { state: paginationState, actions: paginationActions } = usePagination({
    initialPerPage: 20,
  });

  // 選擇狀態
  const [selectedKeys, setSelectedKeys] = useState<string[]>([]);
  const [selectedUsers, setSelectedUsers] = useState<User[]>([]);

  // 確認對話框狀態
  const [deleteDialog, setDeleteDialog] = useState<{
    open: boolean;
    user?: User;
  }>({ open: false });
  const [statusDialog, setStatusDialog] = useState<{
    open: boolean;
    user?: User;
    status: UserStatus;
  }>({ open: false, status: 'active' });
  const [batchDialog, setBatchDialog] = useState<{
    open: boolean;
    action: string;
    status?: UserStatus;
  }>({ open: false, action: '' });

  // API Hooks
  const {
    data: usersResponse,
    isLoading,
    error,
    refetch
  } = useGetUsers({
    search: debouncedSearchTerm,
    status: filters.status || undefined,
    role: filters.role || undefined,
    store_id: filters.store_id || undefined,
    has_2fa: filters.has_2fa || undefined,
    email_verified: filters.email_verified || undefined,
    page: paginationState.currentPage,
    per_page: paginationState.perPage,
  });

  const updateUserMutation = useUpdateUser();
  const deleteUserMutation = useDeleteUser();
  const batchUpdateMutation = useBatchUpdateUserStatus();

  // 更新分頁總數
  React.useEffect(() => {
    if (usersResponse?.data?.meta?.total) {
      paginationActions.setTotal(usersResponse.data.meta.total);
    }
  }, [usersResponse?.data?.meta?.total, paginationActions]);

  // 處理搜尋
  const handleSearch = (value: string) => {
    setSearch(value);
    filterActions.setFilter('search', value);
    paginationActions.setPage(1);
  };

  // 處理篩選
  const handleFilter = (key: keyof UserFilters, value: any) => {
    filterActions.setFilter(key, value);
    paginationActions.setPage(1);
  };

  // 處理選擇變更
  const handleSelectionChange = (keys: string[], rows: User[]) => {
    setSelectedKeys(keys);
    setSelectedUsers(rows);
    onSelectionChange?.(keys, rows);
  };

  // 處理單個使用者狀態變更
  const handleStatusChange = async (user: User, status: UserStatus) => {
    try {
      await updateUserMutation.mutateAsync({
        id: user.id,
        data: { status }
      });
      toast({
        title: '狀態更新成功',
        description: `已將使用者「${user.name}」狀態更新為${
          status === 'active' ? '啟用' : status === 'inactive' ? '停用' : '暫停'
        }`,
      });
      refetch();
    } catch (error) {
      toast({
        title: '狀態更新失敗',
        description: error instanceof Error ? error.message : '未知錯誤',
        variant: 'destructive',
      });
    }
  };

  // 處理刪除使用者
  const handleDeleteUser = async (user: User) => {
    try {
      await deleteUserMutation.mutateAsync(user.id);
      toast({
        title: '刪除成功',
        description: `已刪除使用者「${user.name}」`,
      });
      refetch();
    } catch (error) {
      toast({
        title: '刪除失敗',
        description: error instanceof Error ? error.message : '未知錯誤',
        variant: 'destructive',
      });
    }
  };

  // 處理批次操作
  const handleBatchAction = async (action: string, status?: UserStatus) => {
    if (selectedKeys.length === 0) return;

    try {
      if (action === 'delete') {
        // 批次刪除邏輯（需要後端API支援）
        toast({
          title: '批次刪除',
          description: '批次刪除功能開發中...',
        });
      } else if (status) {
        await batchUpdateMutation.mutateAsync({
          user_ids: selectedKeys.map(Number),
          status,
        });
        toast({
          title: '批次更新成功',
          description: `已批次更新 ${selectedKeys.length} 個使用者狀態`,
        });
        setSelectedKeys([]);
        setSelectedUsers([]);
        refetch();
      }
    } catch (error) {
      toast({
        title: '批次操作失敗',
        description: error instanceof Error ? error.message : '未知錯誤',
        variant: 'destructive',
      });
    }
  };

  // 表格欄位定義
  const columns: DataTableColumn<User>[] = useMemo(() => [
    {
      key: 'user',
      title: '使用者',
      render: (_, user) => (
        <div className="flex items-center space-x-3">
          <UserAvatar user={user} />
          <div>
            <div className="font-medium">{user.name}</div>
            <div className="text-sm text-muted-foreground">{user.email}</div>
          </div>
        </div>
      ),
      width: 250,
    },
    {
      key: 'username',
      title: '用戶名',
      dataIndex: 'username',
      sortable: true,
    },
    {
      key: 'role',
      title: '角色',
      render: (_, user) => (
        <div className="space-y-1">
          {user.roles?.map((role) => (
            <Badge key={role.id} variant="outline">
              {role.display_name || role.name}
            </Badge>
          ))}
        </div>
      ),
    },
    {
      key: 'status',
      title: '狀態',
      dataIndex: 'status',
      render: (status) => <UserStatusBadge status={status} />,
      sortable: true,
      filterable: true,
      filterOptions: USER_STATUS_OPTIONS.slice(1),
    },
    {
      key: 'two_factor_enabled',
      title: '2FA',
      render: (_, user) => (
        <Badge variant={user.two_factor_enabled ? 'default' : 'secondary'}>
          {user.two_factor_enabled ? '已啟用' : '未啟用'}
        </Badge>
      ),
    },
    {
      key: 'email_verified_at',
      title: 'Email 驗證',
      render: (_, user) => (
        <Badge variant={user.email_verified_at ? 'default' : 'secondary'}>
          {user.email_verified_at ? '已驗證' : '未驗證'}
        </Badge>
      ),
    },
    {
      key: 'last_login_at',
      title: '最後登入',
      render: (_, user) => (
        user.last_login_at ? (
          <div className="text-sm">
            <div>{format(new Date(user.last_login_at), 'yyyy-MM-dd', { locale: zhTW })}</div>
            <div className="text-muted-foreground">
              {format(new Date(user.last_login_at), 'HH:mm', { locale: zhTW })}
            </div>
          </div>
        ) : (
          <span className="text-muted-foreground">從未登入</span>
        )
      ),
      sortable: true,
    },
    {
      key: 'created_at',
      title: '建立時間',
      render: (_, user) => (
        <div className="text-sm">
          {format(new Date(user.created_at), 'yyyy-MM-dd HH:mm', { locale: zhTW })}
        </div>
      ),
      sortable: true,
    },
  ], []);

  // 表格操作定義
  const actions: TableAction<User>[] = useMemo(() => [
    {
      key: 'view',
      label: '查看詳情',
      icon: <Eye className="h-4 w-4" />,
      onClick: (user) => {
        // TODO: 導航到使用者詳情頁
        window.location.href = `/users/${user.id}`;
      },
    },
    {
      key: 'edit',
      label: '編輯',
      icon: <Edit className="h-4 w-4" />,
      onClick: (user) => {
        // TODO: 導航到編輯頁面
        window.location.href = `/users/${user.id}/edit`;
      },
    },
    {
      key: 'toggle-status',
      label: (user) => user.status === 'active' ? '停用' : '啟用',
      icon: (user) => user.status === 'active' ? 
        <UserX className="h-4 w-4" /> : 
        <UserCheck className="h-4 w-4" />,
      onClick: (user) => {
        const newStatus: UserStatus = user.status === 'active' ? 'inactive' : 'active';
        setStatusDialog({ open: true, user, status: newStatus });
      },
      type: (user) => user.status === 'active' ? 'default' : 'primary',
    },
    {
      key: 'delete',
      label: '刪除',
      icon: <Trash2 className="h-4 w-4" />,
      onClick: (user) => {
        setDeleteDialog({ open: true, user });
      },
      type: 'danger',
      confirm: true,
    },
    ...customActions,
  ], [customActions]);

  // 工具列
  const toolbar = (
    <div className="flex items-center space-x-2">
      {showBatchActions && selectedKeys.length > 0 && (
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setBatchDialog({ 
              open: true, 
              action: 'enable',
              status: 'active'
            })}
          >
            <UserCheck className="h-4 w-4 mr-1" />
            批次啟用
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => setBatchDialog({ 
              open: true, 
              action: 'disable',
              status: 'inactive'
            })}
          >
            <UserX className="h-4 w-4 mr-1" />
            批次停用
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => setBatchDialog({ 
              open: true, 
              action: 'delete'
            })}
          >
            <Trash2 className="h-4 w-4 mr-1" />
            批次刪除
          </Button>
        </div>
      )}
      
      <PermissionGuard permission="users.create">
        <Button onClick={() => window.location.href = '/users/create'}>
          <Plus className="h-4 w-4 mr-2" />
          新增使用者
        </Button>
      </PermissionGuard>
      
      <Button variant="outline" size="sm">
        <Download className="h-4 w-4 mr-1" />
        匯出
      </Button>
      
      <Button variant="outline" size="sm">
        <Upload className="h-4 w-4 mr-1" />
        匯入
      </Button>
    </div>
  );

  // 篩選器
  const filtersComponent = showFilters && (
    <div className="flex items-center space-x-4 p-4 bg-muted/50 rounded-lg">
      <div className="flex items-center space-x-2">
        <Filter className="h-4 w-4 text-muted-foreground" />
        <span className="text-sm font-medium">篩選：</span>
      </div>
      
      <Select
        value={filters.status}
        onValueChange={(value) => handleFilter('status', value)}
      >
        <SelectTrigger className="w-32">
          <SelectValue placeholder="狀態" />
        </SelectTrigger>
        <SelectContent>
          {USER_STATUS_OPTIONS.map((option) => (
            <SelectItem key={option.value} value={option.value}>
              {option.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <Select
        value={String(filters.has_2fa)}
        onValueChange={(value) => handleFilter('has_2fa', value === '' ? '' : value === 'true')}
      >
        <SelectTrigger className="w-32">
          <SelectValue placeholder="2FA" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="">全部</SelectItem>
          <SelectItem value="true">已啟用</SelectItem>
          <SelectItem value="false">未啟用</SelectItem>
        </SelectContent>
      </Select>

      <Select
        value={String(filters.email_verified)}
        onValueChange={(value) => handleFilter('email_verified', value === '' ? '' : value === 'true')}
      >
        <SelectTrigger className="w-32">
          <SelectValue placeholder="Email驗證" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="">全部</SelectItem>
          <SelectItem value="true">已驗證</SelectItem>
          <SelectItem value="false">未驗證</SelectItem>
        </SelectContent>
      </Select>

      {filterActions.hasActiveFilters && (
        <Button
          variant="ghost"
          size="sm"
          onClick={filterActions.clearFilters}
        >
          清除篩選
        </Button>
      )}
    </div>
  );

  return (
    <div className="space-y-4">
      {filtersComponent}
      
      <DataTable
        title={title}
        data={Array.isArray(usersResponse?.data?.data) ? usersResponse.data.data : []}
        columns={columns}
        actions={actions}
        rowSelection={showBatchActions}
        onSelectionChange={handleSelectionChange}
        pagination={{
          current: paginationState.currentPage,
          pageSize: paginationState.perPage,
          total: paginationState.total,
          showSizeChanger: true,
          onChange: paginationActions.setPage,
        }}
        loading={isLoading}
        toolbar={showToolbar ? toolbar : undefined}
        searchable={showSearch}
        searchPlaceholder="搜尋使用者名稱或 Email..."
        onSearch={handleSearch}
        rowClassName={(record, index) => {
          // 檢查是否為需要高亮的使用者
          const isHighlighted = highlightedUserId && String(record.id) === String(highlightedUserId);
          return isHighlighted ? 'bg-yellow-100 dark:bg-yellow-900/50 transition-all duration-500' : '';
        }}
      />

      {/* 確認對話框 */}
      <DeleteConfirmDialog
        open={deleteDialog.open}
        onOpenChange={(open) => setDeleteDialog({ ...deleteDialog, open })}
        onConfirm={() => deleteDialog.user && handleDeleteUser(deleteDialog.user)}
        loading={deleteUserMutation.isPending}
        itemName={deleteDialog.user?.name}
      />

      <ConfirmDialog
        open={statusDialog.open}
        onOpenChange={(open) => setStatusDialog({ ...statusDialog, open })}
        onConfirm={() => statusDialog.user && handleStatusChange(statusDialog.user, statusDialog.status)}
        loading={updateUserMutation.isPending}
        title="變更使用者狀態"
        description={`確定要將使用者「${statusDialog.user?.name}」狀態變更為${
          statusDialog.status === 'active' ? '啟用' : 
          statusDialog.status === 'inactive' ? '停用' : '暫停'
        }嗎？`}
        confirmText="確認變更"
      />

      <BatchConfirmDialog
        open={batchDialog.open}
        onOpenChange={(open) => setBatchDialog({ ...batchDialog, open })}
        onConfirm={() => handleBatchAction(batchDialog.action, batchDialog.status)}
        loading={batchUpdateMutation.isPending}
        action={batchDialog.action === 'enable' ? '啟用' : 
                batchDialog.action === 'disable' ? '停用' : '刪除'}
        selectedCount={selectedKeys.length}
      />
    </div>
  );
} 