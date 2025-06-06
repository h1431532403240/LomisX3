/**
 * UserDetailPage - 使用者詳情檢視頁面
 * 
 * 使用者資訊詳情展示頁面，包含：
 * - 使用者基本資訊展示
 * - 帳戶狀態和角色資訊
 * - 最近登入記錄
 * - 權限清單
 * 
 * 遵循 LomisX3 架構標準：
 * - 只讀檢視模式
 * - 響應式設計
 * - 權限控制
 * - 載入和錯誤狀態處理
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { 
  ArrowLeft, 
  Edit, 
  Mail, 
  Phone, 
  Calendar, 
  Clock, 
  Shield, 
  User as UserIcon,
  Activity,
  Lock,
  CheckCircle,
  XCircle
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import { PermissionGuard } from '@/components/common/permission-guard';
import { useUser } from '@/features/users/api/user-crud';
import type { User } from '@/features/users/api/user-crud';

/**
 * 格式化最後登入時間
 */
const formatLastLogin = (lastLogin: string | null | undefined): string => {
  if (!lastLogin) return '從未登入';
  
  try {
    const date = new Date(lastLogin);
    return date.toLocaleString('zh-TW', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '時間格式錯誤';
  }
};

/**
 * 格式化註冊時間
 */
const formatCreatedAt = (createdAt: string | undefined): string => {
  if (!createdAt) return '未知';
  
  try {
    const date = new Date(createdAt);
    return date.toLocaleDateString('zh-TW', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    });
  } catch {
    return '時間格式錯誤';
  }
};

/**
 * 取得狀態顏色
 */
const getStatusColor = (status: string | undefined): 'default' | 'secondary' | 'destructive' => {
  switch (status) {
    case 'active': return 'default';
    case 'inactive': return 'secondary';
    case 'suspended': return 'destructive';
    default: return 'secondary';
  }
};

/**
 * 取得狀態文字
 */
const getStatusText = (status: string | undefined): string => {
  switch (status) {
    case 'active': return '啟用';
    case 'inactive': return '停用';
    case 'suspended': return '暫停';
    default: return status || '未知';
  }
};

/**
 * 取得顯示名稱
 */
const getDisplayName = (user: User): string => {
  return user.name || user.username || user.email || '未知使用者';
};

/**
 * 取得角色文字
 */
const getRoleText = (roles: any[] | undefined): string => {
  if (!roles || roles.length === 0) return '無角色';
  
  return roles.map(role => role.display_name || role.name || '未知角色').join(', ');
};

/**
 * 使用者詳情頁面組件
 */
export const UserDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const userId = Number(id);

  // API Hooks
  const { data: user, isLoading, error } = useUser(userId);

  // 處理返回
  const handleGoBack = () => {
    navigate('/users');
  };

  // 處理編輯
  const handleEdit = () => {
    navigate(`/users/${userId}/edit`);
  };

  // 載入中狀態
  if (isLoading) {
    return (
      <div className="container mx-auto py-6">
        <div className="flex items-center space-x-4 mb-6">
          <Button variant="ghost" size="sm" onClick={handleGoBack}>
            <ArrowLeft className="h-4 w-4 mr-2" />
            返回用戶列表
          </Button>
        </div>
        
        <div className="grid gap-6">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-center h-32">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

  // 錯誤狀態
  if (error || !user) {
    return (
      <div className="container mx-auto py-6">
        <div className="flex items-center space-x-4 mb-6">
          <Button variant="ghost" size="sm" onClick={handleGoBack}>
            <ArrowLeft className="h-4 w-4 mr-2" />
            返回用戶列表
          </Button>
        </div>
        
        <Card>
          <CardContent className="p-6">
            <div className="text-center">
              <h3 className="text-lg font-medium text-destructive">載入失敗</h3>
              <p className="text-muted-foreground mt-2">
                {error?.message || '找不到指定的使用者'}
              </p>
              <Button 
                variant="outline" 
                className="mt-4"
                onClick={() => window.location.reload()}
              >
                重試
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <PermissionGuard permission="users.read">
      <div className="container mx-auto py-6">
        {/* 頁面標題和操作 */}
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center space-x-4">
            <Button variant="ghost" size="sm" onClick={handleGoBack}>
              <ArrowLeft className="h-4 w-4 mr-2" />
              返回用戶列表
            </Button>
            <Separator orientation="vertical" className="h-6" />
            <div>
              <h1 className="text-2xl font-bold tracking-tight">使用者詳情</h1>
              <p className="text-muted-foreground">
                檢視 {getDisplayName(user)} 的詳細資訊
              </p>
            </div>
          </div>
          
          <PermissionGuard permission="users.update">
            <Button onClick={handleEdit}>
              <Edit className="h-4 w-4 mr-2" />
              編輯使用者
            </Button>
          </PermissionGuard>
        </div>

        {/* 使用者概覽卡片 */}
        <Card className="mb-6">
          <CardContent className="p-6">
            <div className="flex items-center space-x-6">
              {/* 頭像 */}
              <Avatar className="h-20 w-20">
                <AvatarImage src={user.avatar || undefined} alt={user.username} />
                <AvatarFallback className="text-lg">
                  {getDisplayName(user)[0]?.toUpperCase() || 'U'}
                </AvatarFallback>
              </Avatar>
              
              {/* 基本資訊 */}
              <div className="flex-1">
                <div className="flex items-center space-x-3 mb-2">
                  <h2 className="text-xl font-semibold">
                    {getDisplayName(user)}
                    {user.username && (
                      <span className="text-muted-foreground ml-2">(@{user.username})</span>
                    )}
                  </h2>
                  <Badge variant={getStatusColor(user.status)}>
                    {getStatusText(user.status)}
                  </Badge>
                  <Badge variant="outline">
                    {getRoleText(user.roles)}
                  </Badge>
                </div>
                
                <div className="grid grid-cols-2 gap-4 text-sm text-muted-foreground">
                  {user.email && (
                    <div className="flex items-center space-x-2">
                      <Mail className="h-4 w-4" />
                      <span>{user.email}</span>
                    </div>
                  )}
                  {user.phone && (
                    <div className="flex items-center space-x-2">
                      <Phone className="h-4 w-4" />
                      <span>{user.phone}</span>
                    </div>
                  )}
                  <div className="flex items-center space-x-2">
                    <Calendar className="h-4 w-4" />
                    <span>註冊於 {formatCreatedAt(user.created_at)}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Clock className="h-4 w-4" />
                    <span>最後更新 {formatCreatedAt(user.updated_at)}</span>
                  </div>
                </div>
              </div>
              
              {/* 雙因子認證狀態 */}
              <div className="text-center">
                <div className="flex items-center justify-center mb-2">
                  {user.two_factor_enabled ? (
                    <CheckCircle className="h-8 w-8 text-green-500" />
                  ) : (
                    <XCircle className="h-8 w-8 text-muted-foreground" />
                  )}
                </div>
                <p className="text-xs text-muted-foreground">
                  雙因子認證
                  <br />
                  {user.two_factor_enabled ? '已啟用' : '未啟用'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* 詳細資訊標籤 */}
        <Tabs defaultValue="info" className="w-full">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="info" className="flex items-center space-x-2">
              <UserIcon className="h-4 w-4" />
              <span>詳細資訊</span>
            </TabsTrigger>
            <TabsTrigger value="security" className="flex items-center space-x-2">
              <Lock className="h-4 w-4" />
              <span>安全設定</span>
            </TabsTrigger>
            <TabsTrigger value="activity" className="flex items-center space-x-2">
              <Activity className="h-4 w-4" />
              <span>活動記錄</span>
            </TabsTrigger>
          </TabsList>

          {/* 詳細資訊 */}
          <TabsContent value="info" className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
              {/* 個人資訊 */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center space-x-2">
                    <UserIcon className="h-5 w-5" />
                    <span>個人資訊</span>
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid gap-3">
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">姓名</label>
                      <p className="text-sm">{user.name || '未設定'}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">使用者名稱</label>
                      <p className="text-sm">{user.username || '未設定'}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">電子郵件</label>
                      <p className="text-sm">{user.email || '未設定'}</p>
                    </div>
                    {user.phone && (
                      <div>
                        <label className="text-sm font-medium text-muted-foreground">電話</label>
                        <p className="text-sm">{user.phone}</p>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* 系統資訊 */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center space-x-2">
                    <Shield className="h-5 w-5" />
                    <span>系統資訊</span>
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid gap-3">
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">角色</label>
                      <p className="text-sm">{getRoleText(user.roles)}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">狀態</label>
                      <p className="text-sm">{getStatusText(user.status)}</p>
                    </div>
                    {user.store && (
                      <div>
                        <label className="text-sm font-medium text-muted-foreground">門市</label>
                        <p className="text-sm">{user.store.name}</p>
                      </div>
                    )}
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">註冊時間</label>
                      <p className="text-sm">{formatCreatedAt(user.created_at)}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* 安全設定 */}
          <TabsContent value="security" className="space-y-6">
            <div className="grid gap-6">
              <Card>
                <CardHeader>
                  <CardTitle>安全狀態</CardTitle>
                  <CardDescription>使用者帳戶的安全設定和狀態</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid gap-4">
                    <div className="flex items-center justify-between p-4 border rounded-lg">
                      <div>
                        <h4 className="font-medium">雙因子認證</h4>
                        <p className="text-sm text-muted-foreground">
                          使用 TOTP 應用程式的雙因子認證
                        </p>
                      </div>
                      <Badge variant={user.two_factor_enabled ? 'default' : 'secondary'}>
                        {user.two_factor_enabled ? '已啟用' : '未啟用'}
                      </Badge>
                    </div>
                    
                    <div className="flex items-center justify-between p-4 border rounded-lg">
                      <div>
                        <h4 className="font-medium">電子郵件驗證</h4>
                        <p className="text-sm text-muted-foreground">
                          電子郵件地址的驗證狀態
                        </p>
                      </div>
                      <Badge variant={user.email_verified_at ? 'default' : 'destructive'}>
                        {user.email_verified_at ? '已驗證' : '未驗證'}
                      </Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* 活動記錄 */}
          <TabsContent value="activity" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  <Activity className="h-5 w-5" />
                  <span>活動記錄</span>
                </CardTitle>
                <CardDescription>
                  使用者的操作記錄和登入歷史
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-center py-8 text-muted-foreground">
                  <Activity className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <h3 className="font-medium mb-2">活動記錄功能開發中</h3>
                  <p className="text-sm">
                    此功能將在下一個版本中提供，敬請期待
                  </p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </PermissionGuard>
  );
};

export default UserDetailPage; 