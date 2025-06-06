/**
 * 使用者表格組件 (V2.7 API 整合版)
 * 
 * @description 從父組件接收使用者資料和載入狀態，提供搜尋和操作功能。
 * @requires shadcn/ui 組件庫
 */
import { useState } from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Search, MoreHorizontal, Edit, Eye, Trash2, Loader2 } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { PermissionGuard } from '@/components/common/permission-guard';
import { useDebounce } from '@/hooks/common/use-debounce';
import type { components } from '@/types/api';

type User = components['schemas']['User'];

interface UserTableProps {
  users?: User[];
  isLoading: boolean;
  onEditUser?: (user: User) => void;
  onViewUser?: (user: User) => void;
  onCreateUser?: () => void;
  onSearchChange?: (term: string) => void;
}

/**
 * 使用者表格組件
 */
export const UserTable: React.FC<UserTableProps> = ({
  users = [],
  isLoading,
  onEditUser,
  onViewUser,
  onCreateUser,
  onSearchChange,
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const debouncedSearchTerm = useDebounce(searchTerm, 300);

  // 當防抖搜尋詞變化時，通知父組件
  useState(() => {
    onSearchChange?.(debouncedSearchTerm);
  });

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
  }

  return (
    <div className="space-y-4">
      {/* 搜尋列 */}
      <div className="flex items-center justify-between gap-4">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="搜尋使用者..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>
        
        <PermissionGuard permission="users.create">
          <Button onClick={onCreateUser}>
            新增使用者
          </Button>
        </PermissionGuard>
      </div>

      {/* 表格 */}
      <Card>
        <CardHeader>
          <CardTitle>使用者列表 ({users.length})</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>使用者名稱</TableHead>
                <TableHead>電子郵件</TableHead>
                <TableHead>角色</TableHead>
                <TableHead>狀態</TableHead>
                <TableHead className="w-[100px]">操作</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                <TableRow>
                  <TableCell colSpan={5} className="h-24 text-center">
                    <div className="flex justify-center items-center">
                      <Loader2 className="mr-2 h-6 w-6 animate-spin" />
                      正在載入資料...
                    </div>
                  </TableCell>
                </TableRow>
              ) : users.length > 0 ? (
                users.map((user) => (
                  <TableRow key={user.id}>
                    <TableCell className="font-medium">
                      {user.username}
                    </TableCell>
                    <TableCell>{user.email}</TableCell>
                    <TableCell>{formatRole(user.roles)}</TableCell>
                    <TableCell>
                      <Badge variant={getStatusVariant(user.status)}>
                        {getStatusText(user.status)}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="sm">
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem onClick={() => onViewUser?.(user)}>
                            <Eye className="h-4 w-4 mr-2" />
                            檢視
                          </DropdownMenuItem>
                          <PermissionGuard permission="users.update">
                            <DropdownMenuItem onClick={() => onEditUser?.(user)}>
                              <Edit className="h-4 w-4 mr-2" />
                              編輯
                            </DropdownMenuItem>
                          </PermissionGuard>
                          <PermissionGuard permission="users.delete">
                            <DropdownMenuItem className="text-destructive">
                              <Trash2 className="h-4 w-4 mr-2" />
                              刪除
                            </DropdownMenuItem>
                          </PermissionGuard>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                    {debouncedSearchTerm ? '沒有找到符合條件的使用者' : '暫無使用者資料'}
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}; 