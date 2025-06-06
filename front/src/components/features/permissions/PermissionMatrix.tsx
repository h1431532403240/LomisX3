/**
 * PermissionMatrix - 權限矩陣組件
 * 
 * 顯示角色和權限的矩陣表格
 * 支援批次編輯和視覺化權限分配
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { 
  Table, 
  TableBody, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { 
  Shield, 
  Settings, 
  Eye, 
  Edit, 
  Trash2, 
  Plus, 
  Save,
  Search,
  Filter,
  RotateCcw
} from 'lucide-react';
import { toast } from 'sonner';
import { PermissionGuard } from '@/components/common/permission-guard';

/**
 * 權限資料介面
 */
interface Permission {
  id: string;
  name: string;
  description: string;
  module: string;
  action: 'read' | 'create' | 'update' | 'delete';
}

/**
 * 角色資料介面
 */
interface Role {
  id: number;
  name: string;
  slug: string;
  level: 'admin' | 'manager' | 'staff' | 'guest';
  permissions: string[];
}

/**
 * 權限矩陣組件屬性
 */
interface PermissionMatrixProps {
  /** 是否為唯讀模式 */
  readonly?: boolean;
  /** 自定義 CSS 類別 */
  className?: string;
  /** 角色資料 */
  roles?: Role[];
  /** 權限資料 */
  permissions?: Permission[];
  /** 權限變更回調 */
  onPermissionChange?: (roleId: number, permissionId: string, granted: boolean) => void;
  /** 批次儲存回調 */
  onBatchSave?: (changes: Record<number, string[]>) => void;
}

/**
 * 模擬權限資料
 */
const mockPermissions: Permission[] = [
  // 使用者管理權限
  { id: 'users.read', name: '查看使用者', description: '查看使用者列表和詳情', module: 'users', action: 'read' },
  { id: 'users.create', name: '建立使用者', description: '建立新使用者帳號', module: 'users', action: 'create' },
  { id: 'users.update', name: '編輯使用者', description: '編輯使用者資訊', module: 'users', action: 'update' },
  { id: 'users.delete', name: '刪除使用者', description: '刪除使用者帳號', module: 'users', action: 'delete' },
  
  // 角色管理權限
  { id: 'roles.read', name: '查看角色', description: '查看角色列表和詳情', module: 'roles', action: 'read' },
  { id: 'roles.create', name: '建立角色', description: '建立新角色', module: 'roles', action: 'create' },
  { id: 'roles.update', name: '編輯角色', description: '編輯角色資訊和權限', module: 'roles', action: 'update' },
  { id: 'roles.delete', name: '刪除角色', description: '刪除角色', module: 'roles', action: 'delete' },
  
  // 商品分類權限
  { id: 'categories.read', name: '查看分類', description: '查看商品分類', module: 'categories', action: 'read' },
  { id: 'categories.create', name: '建立分類', description: '建立商品分類', module: 'categories', action: 'create' },
  { id: 'categories.update', name: '編輯分類', description: '編輯商品分類', module: 'categories', action: 'update' },
  { id: 'categories.delete', name: '刪除分類', description: '刪除商品分類', module: 'categories', action: 'delete' },
];

/**
 * 模擬角色資料
 */
const mockRoles: Role[] = [
  { 
    id: 1, 
    name: '系統管理員', 
    slug: 'admin', 
    level: 'admin',
    permissions: mockPermissions.map(p => p.id)
  },
  { 
    id: 2, 
    name: '門市經理', 
    slug: 'manager', 
    level: 'manager',
    permissions: [
      'users.read', 'users.create', 'users.update',
      'roles.read',
      'categories.read', 'categories.create', 'categories.update'
    ]
  },
  { 
    id: 3, 
    name: '一般員工', 
    slug: 'staff', 
    level: 'staff',
    permissions: ['users.read', 'categories.read']
  },
];

/**
 * 權限矩陣組件
 */
export const PermissionMatrix: React.FC<PermissionMatrixProps> = ({
  readonly = false,
  className = '',
  roles = mockRoles,
  permissions = mockPermissions,
  onPermissionChange,
  onBatchSave,
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedModule, setSelectedModule] = useState<string>('all');
  const [changes, setChanges] = useState<Record<number, string[]>>({});
  const [isEditMode, setIsEditMode] = useState(false);
  const { toast } = useToast();

  /**
   * 取得所有模組列表
   */
  const modules = Array.from(new Set(permissions.map(p => p.module)));

  /**
   * 篩選權限
   */
  const filteredPermissions = permissions.filter(permission => {
    const matchesSearch = permission.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         permission.description.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesModule = selectedModule === 'all' || permission.module === selectedModule;
    return matchesSearch && matchesModule;
  });

  /**
   * 檢查角色是否擁有權限
   */
  const hasPermission = (roleId: number, permissionId: string): boolean => {
    if (changes[roleId]) {
      return changes[roleId].includes(permissionId);
    }
    const role = roles.find(r => r.id === roleId);
    return role?.permissions.includes(permissionId) || false;
  };

  /**
   * 切換權限
   */
  const togglePermission = (roleId: number, permissionId: string) => {
    if (readonly) return;

    const role = roles.find(r => r.id === roleId);
    if (!role) return;

    let currentPermissions = changes[roleId] || [...role.permissions];
    
    if (currentPermissions.includes(permissionId)) {
      currentPermissions = currentPermissions.filter(p => p !== permissionId);
    } else {
      currentPermissions = [...currentPermissions, permissionId];
    }

    setChanges(prev => ({
      ...prev,
      [roleId]: currentPermissions
    }));

    onPermissionChange?.(roleId, permissionId, !hasPermission(roleId, permissionId));
  };

  /**
   * 儲存變更
   */
  const handleSave = () => {
    if (Object.keys(changes).length === 0) {
      toast({
        title: "無變更",
        description: "沒有需要儲存的權限變更",
        variant: "default",
      });
      return;
    }

    onBatchSave?.(changes);
    setChanges({});
    setIsEditMode(false);
    
    toast({
      title: "權限已更新",
      description: "權限矩陣變更已成功儲存",
    });
  };

  return (
    <Card className={className}>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="flex items-center gap-2">
              <Shield className="h-5 w-5 text-purple-600" />
              權限矩陣
            </CardTitle>
            <CardDescription>
              管理角色和權限的對應關係
            </CardDescription>
          </div>
          
          <PermissionGuard permissions={['roles.update']}>
            <div className="flex items-center gap-2">
              {isEditMode && (
                <>
                  <Button variant="outline" size="sm" onClick={() => setChanges({})}>
                    <RotateCcw className="h-4 w-4 mr-2" />
                    重設
                  </Button>
                  <Button size="sm" onClick={handleSave}>
                    <Save className="h-4 w-4 mr-2" />
                    儲存變更
                  </Button>
                </>
              )}
              {!readonly && (
                <Button 
                  variant={isEditMode ? "default" : "outline"} 
                  size="sm"
                  onClick={() => setIsEditMode(!isEditMode)}
                >
                  <Edit className="h-4 w-4 mr-2" />
                  {isEditMode ? '完成編輯' : '編輯權限'}
                </Button>
              )}
            </div>
          </PermissionGuard>
        </div>

        <Separator />

        {/* 篩選和搜尋 */}
        <div className="flex items-center gap-4">
          <div className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="搜尋權限..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
          
          <div className="flex items-center gap-2">
            <Filter className="h-4 w-4 text-muted-foreground" />
            <select
              value={selectedModule}
              onChange={(e) => setSelectedModule(e.target.value)}
              className="px-3 py-2 border border-input rounded-md bg-background text-sm"
            >
              <option value="all">所有模組</option>
              {modules.map(module => (
                <option key={module} value={module}>
                  {module}
                </option>
              ))}
            </select>
          </div>
        </div>
      </CardHeader>

      <CardContent>
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[300px]">權限</TableHead>
                {roles.map(role => (
                  <TableHead key={role.id} className="text-center">
                    <div className="flex flex-col items-center gap-1">
                      <span className="font-medium">{role.name}</span>
                      <Badge 
                        variant={
                          role.level === 'admin' ? 'destructive' :
                          role.level === 'manager' ? 'default' : 'secondary'
                        }
                        className="text-xs"
                      >
                        {role.level}
                      </Badge>
                    </div>
                  </TableHead>
                ))}
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredPermissions.map(permission => (
                <TableRow key={permission.id}>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <div>
                        <div className="font-medium">{permission.name}</div>
                        <div className="text-sm text-muted-foreground">
                          {permission.description}
                        </div>
                        <Badge variant="outline" className="mt-1 text-xs">
                          {permission.module}.{permission.action}
                        </Badge>
                      </div>
                    </div>
                  </TableCell>
                  
                  {roles.map(role => (
                    <TableCell key={role.id} className="text-center">
                      <Checkbox
                        checked={hasPermission(role.id, permission.id)}
                        onCheckedChange={() => togglePermission(role.id, permission.id)}
                        disabled={readonly || (!isEditMode && !readonly)}
                        className="mx-auto"
                      />
                    </TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>

        {Object.keys(changes).length > 0 && (
          <div className="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div className="flex items-center gap-2 text-blue-800">
              <Settings className="h-4 w-4" />
              <span className="font-medium">
                有 {Object.keys(changes).length} 個角色的權限已變更但尚未儲存
              </span>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default PermissionMatrix; 