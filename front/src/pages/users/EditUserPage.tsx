/**
 * EditUserPage - 編輯使用者頁面
 * 
 * 企業級使用者編輯功能，包含：
 * - 使用者基本資訊編輯
 * - 角色和權限管理
 * - 狀態管理
 * - 密碼重設
 * 
 * 遵循 LomisX3 架構標準：
 * - 使用 React Hook Form + Zod 驗證
 * - TanStack Query 狀態管理
 * - shadcn/ui 組件庫
 * - 型別安全的 API 調用
 * - 權限控制和門市隔離
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export const EditUserPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const handleGoBack = () => {
    navigate('/users');
  };

  return (
    <div className="container mx-auto py-6">
      <div className="flex items-center space-x-4 mb-6">
        <Button variant="ghost" size="sm" onClick={handleGoBack}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          返回用戶列表
        </Button>
      </div>
      
      <Card>
        <CardHeader>
          <CardTitle>編輯使用者 #{id}</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-muted-foreground">編輯功能正在開發中...</p>
        </CardContent>
      </Card>
    </div>
  );
};

export default EditUserPage; 