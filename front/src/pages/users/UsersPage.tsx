import React, { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Plus, AlertTriangle } from 'lucide-react';
import { useFlowStateStore } from '@/stores/flowStateStore';
import { useDebounce } from '@/hooks/common/use-debounce';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { toast } from 'sonner';

import { UserTable } from '@/features/users/components/user-table';
import { PermissionGuard } from '@/components/common/permission-guard';
import { PageHeader } from '@/components/common/breadcrumb';
import { ResponsiveContainer } from '@/components/common/responsive-container';

// ✅ V4.0 統一戰爭成果：只從正統架構導入
import { 
  useGetUsers, 
  useDeleteUser,  // ✅ 導入我們的「刪除武器」
  type User 
} from '@/hooks/api/users';

/**
 * 使用者列表頁面 (V4.0 - 搜尋邏輯重構)
 * 
 * @description 主要的使用者管理頁面，提供完整的CRUD功能
 * 
 * ✅ V4.0 重構記錄：
 * - 將防抖邏輯從 UserTable 移回容器組件 (UsersPage)
 * - 實現搜尋狀態與 API 查詢參數的分離管理
 * - 提升整體效能，遵循單一職責原則
 * - handleSearchChange 只負責更新即時搜尋狀態，防抖由 useEffect 處理
 */
export function UsersPage() {
  const navigate = useNavigate();
  
  // 高亮狀態管理
  const [highlightedUserId, setHighlightedUserId] = useState<string | number | null>(null);
  const consumeHighlight = useFlowStateStore((state) => state.consumeHighlight);

  // ✅ 刪除確認對話框狀態管理
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [userToDelete, setUserToDelete] = useState<User | null>(null);

  // ✅ V4.0 重構：搜尋狀態與防抖管理
  const [searchTerm, setSearchTerm] = useState('');
  const debouncedSearchTerm = useDebounce(searchTerm, 300); // 防抖延遲 300ms

  // ✅ 獲取使用者數據 (使用新的 API Hook)
  const [queryParams, setQueryParams] = useState({
    page: 1,
    per_page: 50,
    sort: 'created_at' as const,
    order: 'desc' as const,
    search: undefined as string | undefined,
  });
  
  const { data: usersData, isLoading: isUsersLoading } = useGetUsers(queryParams);
  
  // ✅ 實例化我們的「刪除武器」
  const deleteUserMutation = useDeleteUser();

  // 處理高亮狀態消費
  useEffect(() => {
    const userId = consumeHighlight('user');
    if (userId) {
      setHighlightedUserId(userId);
      // 3秒後清除高亮狀態
      setTimeout(() => setHighlightedUserId(null), 3000);
    }
  }, [consumeHighlight]);

  // ✅ V4.0 重構：監聽防抖搜尋詞，更新 API 查詢參數
  useEffect(() => {
    // 當防抖後的搜尋詞變化時，才去更新真正的 API 查詢參數
    setQueryParams(prev => ({
      ...prev,
      page: 1,
      search: debouncedSearchTerm || undefined,
    }));
  }, [debouncedSearchTerm]); // 依賴項只有 debouncedSearchTerm

  // 🔧 診斷：API 數據載入狀態追蹤
  useEffect(() => {
    console.log('🚀 UsersPage 初始化 - API 狀態:', {
      isLoading: isUsersLoading,
      hasData: !!usersData,
      dataStructure: usersData ? Object.keys(usersData) : null,
      dataContent: (usersData as any)?.data ? `Array(${(usersData as any).data.length})` : null,
      queryParams,
    });
  }, [isUsersLoading, usersData, queryParams]);

  /**
   * 處理新增使用者 (useCallback 穩定化)
   */
  const handleCreateUser = useCallback(() => {
    navigate('/users/create');
  }, [navigate]);

  /**
   * 處理使用者選擇變更 (useCallback 穩定化)
   */
  const handleSelectionChange = useCallback((selectedKeys: string[], selectedUsers: User[]) => {
    // 可以在這裡處理選擇狀態變更，比如更新頁面狀態
    console.log('📋 選中的使用者:', selectedKeys, selectedUsers);
  }, []);

  /**
   * 處理編輯使用者 (useCallback 穩定化)
   */
  const handleEditUser = useCallback((user: User) => {
    console.log('✏️ 編輯使用者:', user);
    navigate(`/users/${user.id}/edit`);
  }, [navigate]);

  /**
   * 處理檢視使用者 (useCallback 穩定化)
   */
  const handleViewUser = useCallback((user: User) => {
    console.log('👁️ 檢視使用者:', user);
    navigate(`/users/${user.id}`);
  }, [navigate]);

  /**
   * 處理刪除使用者 - 觸發確認對話框
   * ✅ V4.0 升級：使用 shadcn/ui AlertDialog 替代原生 confirm
   */
  const handleDeleteUser = useCallback((user: User) => {
    setUserToDelete(user);
    setDeleteDialogOpen(true);
  }, []);

  /**
   * 確認刪除使用者 - 實際執行刪除操作
   */
  const confirmDeleteUser = useCallback(() => {
    if (!userToDelete) return;
    
    // 下達開火指令！
    deleteUserMutation.mutate(userToDelete.id, {
      onSuccess: () => {
        // 成功後的提示
        toast.success(`✅ 使用者「${userToDelete.name || userToDelete.email}」已成功刪除`);
        console.log(`✅ 使用者 ID: ${userToDelete.id} 刪除成功`);
        // 關閉對話框並清理狀態
        setDeleteDialogOpen(false);
        setUserToDelete(null);
        // TanStack Query 會自動刷新相關數據
      },
      onError: (error) => {
        // 錯誤處理
        const errorMessage = error instanceof Error ? error.message : '刪除操作失敗';
        toast.error(`❌ 刪除使用者失敗：${errorMessage}`);
        console.error(`❌ 刪除使用者 ID: ${userToDelete.id} 失敗`, error);
        // 關閉對話框但保持用戶信息用於重試
        setDeleteDialogOpen(false);
      }
    });
  }, [userToDelete, deleteUserMutation]);

  /**
   * 取消刪除操作
   */
  const cancelDeleteUser = useCallback(() => {
    setDeleteDialogOpen(false);
    setUserToDelete(null);
  }, []); // 依賴項是穩定的 mutation 函數

  /**
   * 處理搜尋變更 (V4.0 重構 - useCallback 穩定化)
   * @description UserTable 傳回的 onSearchChange 回調，現在只負責更新即時的搜尋詞狀態
   */
  const handleSearchChange = useCallback((term: string) => {
    setSearchTerm(term);
  }, []);

  /**
   * 處理批次操作成功 (useCallback 穩定化)
   */
  const handleBatchSuccess = useCallback((action: string, count: number) => {
    // 顯示批次操作成功訊息 (Sonner API)
    toast.success(`已成功${action} ${count} 個使用者`);
  }, []);

  return (
    <ResponsiveContainer maxWidth="7xl" padding="default">
      <div className="space-y-6">
        {/* 頁面標題和麵包屑 */}
        <PageHeader
          title="使用者管理"
          description="管理系統使用者，設定角色權限，控制帳戶狀態"
          actions={
            <div className="flex items-center space-x-2">
              <PermissionGuard permission="users.create">
                <Button onClick={handleCreateUser}>
                  <Plus className="h-4 w-4 mr-2" />
                  新增使用者
                </Button>
              </PermissionGuard>
              
              <PermissionGuard permission="users.permissions">
                <Button 
                  variant="outline" 
                  onClick={() => navigate('/users/permissions')}
                >
                  權限管理
                </Button>
              </PermissionGuard>
            </div>
          }
        />

      {/* 權限檢查警告 */}
      <PermissionGuard 
        permission="users.read"
        fallback={
          <Alert className="border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-200">
            <AlertTriangle className="h-4 w-4" />
            <AlertDescription>
              您沒有檢視使用者列表的權限。請聯絡管理員申請相關權限。
            </AlertDescription>
          </Alert>
        }
      >
        {/* 使用者管理表格 */}
        <Card>
          <CardHeader>
            <CardTitle>所有使用者</CardTitle>
            <CardDescription>
              系統中的所有使用者帳戶，您可以進行搜尋、篩選和管理操作
            </CardDescription>
          </CardHeader>
          <CardContent>
            <UserTable
              users={(usersData as any)?.data || []}
              isLoading={isUsersLoading}
              onSelectionChange={handleSelectionChange}
              onCreateUser={handleCreateUser}
              onEditUser={handleEditUser}
              onViewUser={handleViewUser}
              onDeleteUser={handleDeleteUser}
              onSearchChange={handleSearchChange}
            />
          </CardContent>
        </Card>
      </PermissionGuard>

      {/* 說明區塊 */}
      <Card>
        <CardHeader>
          <CardTitle>使用說明</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div className="space-y-2">
              <h4 className="font-medium">搜尋功能</h4>
              <p className="text-sm text-muted-foreground">
                可以按姓名、Email 或用戶名搜尋使用者
              </p>
            </div>
            
            <div className="space-y-2">
              <h4 className="font-medium">篩選功能</h4>
              <p className="text-sm text-muted-foreground">
                支援按狀態、角色、2FA 啟用狀態等條件篩選
              </p>
            </div>
            
            <div className="space-y-2">
              <h4 className="font-medium">批次操作</h4>
              <p className="text-sm text-muted-foreground">
                選擇多個使用者後可進行批次啟用、停用或刪除
              </p>
            </div>
            
            <div className="space-y-2">
              <h4 className="font-medium">快速操作</h4>
              <p className="text-sm text-muted-foreground">
                點擊操作選單可快速編輯、查看或刪除使用者
              </p>
            </div>
            
            <div className="space-y-2">
              <h4 className="font-medium">狀態管理</h4>
              <p className="text-sm text-muted-foreground">
                可以快速切換使用者的啟用/停用狀態
              </p>
            </div>
            
            <div className="space-y-2">
              <h4 className="font-medium">權限控制</h4>
              <p className="text-sm text-muted-foreground">
                所有操作都會根據您的權限進行顯示和控制
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    {/* ✅ 刪除確認對話框 - shadcn/ui AlertDialog */}
    <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>刪除使用者確認</AlertDialogTitle>
          <AlertDialogDescription>
            您確定要刪除使用者「<strong>{userToDelete?.name || userToDelete?.email}</strong>」嗎？
            <br />
            <br />
            此操作無法復原，該使用者的所有數據將被永久刪除。
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel onClick={cancelDeleteUser}>
            取消
          </AlertDialogCancel>
          <AlertDialogAction 
            onClick={confirmDeleteUser}
            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            disabled={deleteUserMutation.isPending}
          >
            {deleteUserMutation.isPending ? '刪除中...' : '確認刪除'}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
    </ResponsiveContainer>
  );
} 