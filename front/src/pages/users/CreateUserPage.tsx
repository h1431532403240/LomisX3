import React from 'react';
import { useNavigate } from 'react-router-dom';
import { ArrowLeft, UserPlus, AlertTriangle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { UserForm } from '@/components/forms/UserForm';
import { PermissionGuard } from '@/components/common/permission-guard';
import type { components } from '@/types/api';

// API 類型定義
type User = components['schemas']['User'];

/**
 * 新增使用者頁面組件
 */
export function CreateUserPage() {
  const navigate = useNavigate();

  /**
   * 處理返回使用者列表
   */
  const handleBack = () => {
    navigate('/users');
  };

  /**
   * 處理建立成功
   */
  const handleSuccess = (user: User) => {
    // 建立成功後導航到使用者詳情頁或列表頁
    navigate(`/users/${user.id}`, {
      state: { 
        message: '使用者建立成功',
        user
      }
    });
  };

  /**
   * 處理取消操作
   */
  const handleCancel = () => {
    navigate('/users');
  };

  return (
    <PermissionGuard 
      permission="users.create"
      fallback={
        <Alert className="border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-200">
          <AlertTriangle className="h-4 w-4" />
          <AlertDescription>
            您沒有建立使用者的權限。請聯絡管理員申請相關權限。
          </AlertDescription>
        </Alert>
      }
    >
      <div className="space-y-6">
        {/* 頁面標題區塊 */}
        <div className="flex items-center space-x-4">
          <Button 
            variant="ghost" 
            size="sm" 
            onClick={handleBack}
            className="flex items-center space-x-2"
          >
            <ArrowLeft className="h-4 w-4" />
            <span>返回</span>
          </Button>
          
          <div className="space-y-1">
            <div className="flex items-center space-x-2">
              <UserPlus className="h-6 w-6" />
              <h1 className="text-2xl font-bold tracking-tight">新增使用者</h1>
            </div>
            <p className="text-muted-foreground">
              建立新的系統使用者帳戶
            </p>
          </div>
        </div>

        {/* 新增使用者表單 */}
        <Card>
          <CardHeader>
            <CardTitle>使用者資訊</CardTitle>
            <CardDescription>
              請填寫新使用者的基本資訊、密碼和角色權限
            </CardDescription>
          </CardHeader>
          <CardContent>
            <UserForm
              mode="create"
              onSuccess={handleSuccess}
              onCancel={handleCancel}
              showCard={false}
            />
          </CardContent>
        </Card>

        {/* 說明區塊 */}
        <Card>
          <CardHeader>
            <CardTitle>建立說明</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <h4 className="font-medium">必填欄位</h4>
                <p className="text-sm text-muted-foreground">
                  標有 * 號的欄位為必填項目，請確實填寫
                </p>
              </div>
              
              <div className="space-y-2">
                <h4 className="font-medium">密碼要求</h4>
                <p className="text-sm text-muted-foreground">
                  密碼至少需要 8 個字元，包含大小寫字母、數字和特殊字元
                </p>
              </div>
              
              <div className="space-y-2">
                <h4 className="font-medium">角色權限</h4>
                <p className="text-sm text-muted-foreground">
                  每個使用者必須分配至少一個角色，角色決定了使用者的權限範圍
                </p>
              </div>
              
              <div className="space-y-2">
                <h4 className="font-medium">帳戶狀態</h4>
                <p className="text-sm text-muted-foreground">
                  新建立的帳戶預設為啟用狀態，您可以根據需要調整
                </p>
              </div>
              
              <div className="space-y-2">
                <h4 className="font-medium">Email 驗證</h4>
                <p className="text-sm text-muted-foreground">
                  勾選「發送歡迎郵件」會自動寄送帳戶資訊給使用者
                </p>
              </div>
              
              <div className="space-y-2">
                <h4 className="font-medium">安全設定</h4>
                <p className="text-sm text-muted-foreground">
                  可以設定使用者首次登入時必須變更密碼
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </PermissionGuard>
  );
} 