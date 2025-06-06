import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Users, Plus, AlertTriangle } from 'lucide-react';
import { useFlowStateStore } from '@/stores/flowStateStore';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { toast } from 'sonner';

import { UserTable } from '@/components/features/users/UserTable';
import { PermissionGuard } from '@/components/common/permission-guard';
import { PageHeader } from '@/components/common/breadcrumb';
import { ResponsiveContainer, ResponsiveGrid } from '@/components/common/responsive-container';
import type { components } from '@/types/api';

// API 類型定義
type User = components['schemas']['User'];

/**
 * 使用者列表頁面
 * 主要的使用者管理頁面，提供完整的CRUD功能
 */
export function UsersPage() {
  const navigate = useNavigate();
  
  // 高亮狀態管理
  const [highlightedUserId, setHighlightedUserId] = useState<string | number | null>(null);
  const consumeHighlight = useFlowStateStore((state) => state.consumeHighlight);

  // 處理高亮狀態消費
  useEffect(() => {
    const userId = consumeHighlight('user');
    if (userId) {
      setHighlightedUserId(userId);
      // 3秒後清除高亮狀態
      setTimeout(() => setHighlightedUserId(null), 3000);
    }
  }, [consumeHighlight]);

  /**
   * 處理新增使用者
   */
  const handleCreateUser = () => {
    navigate('/users/create');
  };

  /**
   * 處理使用者選擇變更
   */
  const handleSelectionChange = (selectedKeys: string[], selectedUsers: User[]) => {
    // 可以在這裡處理選擇狀態變更，比如更新頁面狀態
    console.log('選中的使用者:', selectedKeys, selectedUsers);
  };

  /**
   * 處理批次操作成功
   */
  const handleBatchSuccess = (action: string, count: number) => {
    // 顯示批次操作成功訊息 (Sonner API)
    toast.success(`已成功${action} ${count} 個使用者`);
  };

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
              title=""
              showToolbar={true}
              showBatchActions={true}
              showSearch={true}
              showFilters={true}
              highlightedUserId={highlightedUserId}
              onSelectionChange={handleSelectionChange}
              customActions={[
                // 可以在這裡添加自訂操作，例如：
                // {
                //   key: 'export-user',
                //   label: '匯出資料',
                //   icon: <Download className="h-4 w-4" />,
                //   onClick: (user) => {
                //     // 處理匯出邏輯
                //   },
                // },
              ]}
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
    </ResponsiveContainer>
  );
} 