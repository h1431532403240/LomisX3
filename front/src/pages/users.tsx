/**
 * 使用者管理頁面 (V2.7 生產加固版)
 * 
 * 🎯 核心功能：
 * 1. 使用者列表展示與管理 (UserTable)
 * 2. 新增/編輯使用者表單 (UserForm)
 * 3. 2FA 雙因子驗證設定 (TwoFactorSetup)
 * 4. 完整的權限控制與門市隔離
 * 5. 響應式設計與優化的使用者體驗
 */
import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { 
  Card, 
  CardContent, 
  CardDescription, 
  CardHeader, 
  CardTitle
} from '@/components/ui/card';
import { 
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator
} from '@/components/ui/breadcrumb';
import { 
  Users, 
  Plus, 
  Shield, 
  Home
} from 'lucide-react';
import { PermissionGuard } from '@/components/common/permission-guard';
import { usePermissions } from '@/hooks/auth/use-permissions';
import { UserTable } from '@/features/users/components/user-table';
import { UserForm } from '@/features/users/components/user-form';
import { TwoFactorSetup } from '@/features/users/components/two-factor-setup';
import { useUsers, DEFAULT_USER_QUERY_PARAMS } from '@/features/users/api/use-users';
import type { components, operations } from '@/types/api';

type User = components['schemas']['User'];
type UserListQueryParams = operations['listUsers']['parameters']['query'];

/**
 * 使用者管理主頁面組件
 */
export const UsersPage: React.FC = () => {
  // 狀態管理
  const [isUserFormOpen, setIsUserFormOpen] = useState(false);
  const [isTwoFactorSetupOpen, setIsTwoFactorSetupOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [queryParams, setQueryParams] = useState<UserListQueryParams>(DEFAULT_USER_QUERY_PARAMS);

  // Hooks
  const { 
    data: usersData, 
    isLoading: isUsersLoading 
  } = useUsers(queryParams);
  const { 
    canManageUsers, 
    isAdmin, 
    isStoreAdmin,
  } = usePermissions();

  /**
   * 開啟新增使用者表單
   */
  const handleCreateUser = () => {
    setSelectedUser(null);
    setIsUserFormOpen(true);
  };

  /**
   * 開啟編輯使用者表單
   */
  const handleEditUser = (user: User) => {
    setSelectedUser(user);
    setIsUserFormOpen(true);
  };

  /**
   * 檢視使用者詳情
   */
  const handleViewUser = (user: User) => {
    setSelectedUser(user);
    // 在這裡可以開啟使用者詳情對話框
    // 暫時使用編輯表單作為檢視（只讀模式）
    setIsUserFormOpen(true);
  };

  /**
   * 開啟 2FA 設定
   */
  const handleSetup2FA = () => {
    setIsTwoFactorSetupOpen(true);
  };
  
  const handleSearchChange = (term: string) => {
    // 當搜尋詞改變時，重設頁碼為1並更新篩選條件
    setQueryParams((prev: UserListQueryParams) => ({ 
      ...prev, 
      page: 1, 
      'filter[search]': term || undefined 
    }));
  };

  /**
   * 表單操作成功回調
   */
  const handleFormSuccess = () => {
    // TanStack Query 會自動處理快取刷新
    setIsUserFormOpen(false);
  };

  /**
   * 2FA 設定成功回調
   */
  const handle2FASuccess = () => {
    // TanStack Query 會自動處理快取刷新
    setIsTwoFactorSetupOpen(false);
  };

  return (
    <PermissionGuard permission="users.view">
      <div className="container mx-auto py-6 space-y-6">
        {/* 頁面標題和麵包屑 */}
        <div className="space-y-4">
          <Breadcrumb>
            <BreadcrumbList>
              <BreadcrumbItem>
                <BreadcrumbLink href="/dashboard">
                  <Home className="h-4 w-4" />
                  儀表板
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator />
              <BreadcrumbItem>
                <BreadcrumbPage>使用者管理</BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>

          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold tracking-tight">使用者管理</h1>
              <p className="text-muted-foreground">
                管理系統使用者帳號、角色權限和安全設定
              </p>
            </div>

            {/* 頁面操作按鈕 */}
            <div className="flex items-center space-x-2">
              {/* 2FA 設定按鈕 */}
              <PermissionGuard anyPermissions={['users.update', 'profile.manage']}>
                <Button
                  variant="outline"
                  onClick={handleSetup2FA}
                  className="flex items-center gap-2"
                >
                  <Shield className="h-4 w-4" />
                  設定 2FA
                </Button>
              </PermissionGuard>

              {/* 新增使用者按鈕 */}
              <PermissionGuard permission="users.create">
                <Button
                  onClick={handleCreateUser}
                  className="flex items-center gap-2"
                >
                  <Plus className="h-4 w-4" />
                  新增使用者
                </Button>
              </PermissionGuard>
            </div>
          </div>
        </div>

        {/* 使用者表格 */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Users className="h-5 w-5" />
              使用者列表
            </CardTitle>
            <CardDescription>
              {isAdmin() && '管理所有使用者帳號和權限'}
              {isStoreAdmin() && !isAdmin() && '管理本門市使用者帳號和權限'}
              {!canManageUsers() && '檢視使用者列表'}
            </CardDescription>
          </CardHeader>
          <CardContent className="p-0">
            <UserTable
              users={usersData?.data?.data || []}
              isLoading={isUsersLoading}
              onEditUser={handleEditUser}
              onViewUser={handleViewUser}
              onCreateUser={handleCreateUser}
              onSearchChange={handleSearchChange}
            />
          </CardContent>
        </Card>

        {/* 新增/編輯使用者對話框 */}
        {isUserFormOpen && (
           <UserForm
            user={selectedUser}
            isEdit={!!selectedUser}
            isOpen={isUserFormOpen}
            onClose={() => setIsUserFormOpen(false)}
            onSuccess={handleFormSuccess}
          />
        )}
       
        {/* 2FA 設定對話框 */}
        <TwoFactorSetup
          isOpen={isTwoFactorSetupOpen}
          onClose={() => setIsTwoFactorSetupOpen(false)}
          onSuccess={handle2FASuccess}
        />
      </div>
    </PermissionGuard>
  );
};

export default UsersPage; 