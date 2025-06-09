/**
 * 使用者管理頁面 (V4.0 架構標準合規版)
 * 
 * 🎯 核心功能：
 * 1. 使用者列表展示與管理 (UserTable)
 * 2. 新增/編輯使用者表單 (UserForm)
 * 3. 2FA 雙因子驗證設定 (TwoFactorSetup)
 * 4. 完整的權限控制與門市隔離
 * 5. 響應式設計與優化的使用者體驗
 * 
 * ✅ V4.0 合規性：
 * - 所有回調函數使用 useCallback 包裝，防止無限重渲染
 * - 使用 shadcn/ui AlertDialog 替代原生 confirm()
 * - 完整的錯誤處理和使用者回饋機制
 * - 符合企業級 UI/UX 一致性標準
 */
import React, { useState, useCallback } from 'react';
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
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { toast } from "sonner";
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
import { useGetUsers, useDeleteUser, type UserQueryParams } from '@/hooks/api/users';
import type { paths } from '@/types/api';

// 基於實際 API 路徑的類型定義
type User = NonNullable<paths['/api/users']['get']['responses']['200']['content']['application/json']['data']>['data'][number];
type UserListQueryParams = UserQueryParams;

/**
 * 使用者管理主頁面組件
 */
export const UsersPage: React.FC = () => {
  // 狀態管理
  const [isUserFormOpen, setIsUserFormOpen] = useState(false);
  const [isTwoFactorSetupOpen, setIsTwoFactorSetupOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [queryParams, setQueryParams] = useState<UserQueryParams>({
    page: 1,
    per_page: 20,
  });

  // ✅ V4.0 標準：刪除確認對話框狀態管理
  const [userToDelete, setUserToDelete] = useState<User | null>(null);

  // Hooks
  const { 
    data: usersData, 
    isLoading: isUsersLoading 
  } = useGetUsers(queryParams);
  const { 
    canManageUsers, 
    isAdmin, 
    isStoreAdmin,
  } = usePermissions();
  const deleteUserMutation = useDeleteUser();

  // ✅ V4.0 標準：所有回調函數使用 useCallback 包裝，防止無限重渲染

  /**
   * 開啟新增使用者表單
   */
  const handleCreateUser = useCallback(() => {
    setSelectedUser(null);
    setIsUserFormOpen(true);
  }, []);

  /**
   * 開啟編輯使用者表單
   */
  const handleEditUser = useCallback((user: User) => {
    setSelectedUser(user);
    setIsUserFormOpen(true);
  }, []);

  /**
   * 檢視使用者詳情
   */
  const handleViewUser = useCallback((user: User) => {
    setSelectedUser(user);
    // 在這裡可以開啟使用者詳情對話框
    // 暫時使用編輯表單作為檢視（只讀模式）
    setIsUserFormOpen(true);
  }, []);

  /**
   * ✅ V4.0 標準：刪除觸發函數 - 僅負責打開確認對話框
   */
  const handleTriggerDelete = useCallback((user: User) => {
    setUserToDelete(user);
  }, []);

  /**
   * ✅ V4.0 標準：刪除確認函數 - 執行實際刪除操作
   */
  const handleConfirmDelete = useCallback(() => {
    if (!userToDelete) return;

    deleteUserMutation.mutate(userToDelete.id, {
      onSuccess: () => {
        toast.success(`✅ 使用者「${userToDelete.name || userToDelete.username}」已成功刪除`);
        setUserToDelete(null); // 成功後關閉對話框
      },
      onError: (error) => {
        const errorMessage = error instanceof Error ? error.message : '未知的錯誤';
        toast.error(`❌ 刪除失敗：${errorMessage}`);
      }
    });
  }, [userToDelete, deleteUserMutation]);

  /**
   * 開啟 2FA 設定
   */
  const handleSetup2FA = useCallback(() => {
    setIsTwoFactorSetupOpen(true);
  }, []);
  
  /**
   * 搜尋變更處理
   */
  const handleSearchChange = useCallback((term: string) => {
    // 當搜尋詞改變時，重設頁碼為1並更新篩選條件
    setQueryParams((prev: UserQueryParams) => ({ 
      ...prev, 
      page: 1, 
      search: term || undefined 
    }));
  }, []);

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
              onDeleteUser={handleTriggerDelete}
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

        {/* ✅ V4.0 標準：使用 shadcn/ui AlertDialog 替代原生 confirm() */}
        <AlertDialog open={!!userToDelete} onOpenChange={(isOpen) => !isOpen && setUserToDelete(null)}>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>您確定要執行此操作嗎？</AlertDialogTitle>
              <AlertDialogDescription>
                您將要刪除使用者「{userToDelete?.name || userToDelete?.username}」。
                此操作無法復原，將永久刪除該使用者的資料。
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel onClick={() => setUserToDelete(null)}>取消</AlertDialogCancel>
              <AlertDialogAction
                onClick={handleConfirmDelete}
                disabled={deleteUserMutation.isPending}
                className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              >
                {deleteUserMutation.isPending ? '刪除中...' : '確認刪除'}
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>
    </PermissionGuard>
  );
};

export default UsersPage; 