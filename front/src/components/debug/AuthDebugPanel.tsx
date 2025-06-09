import { useAuthStore } from '@/stores/authStore';
import { useEffect, useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

/**
 * 認證狀態調試面板
 * 用於實時監控和調試認證狀態問題
 */
export function AuthDebugPanel() {
  const {
    user,
    token,
    permissions,
    roles,
    isAuthenticated,
    isLoading,
    initialize,
    logout
  } = useAuthStore();

  const [localStorageToken, setLocalStorageToken] = useState<string | null>(null);
  const [refreshCount, setRefreshCount] = useState(0);

  // 定期檢查 localStorage token
  useEffect(() => {
    const checkLocalStorage = () => {
      const token = localStorage.getItem('auth_token');
      setLocalStorageToken(token);
    };

    checkLocalStorage();
    const interval = setInterval(checkLocalStorage, 1000);

    return () => clearInterval(interval);
  }, []);

  const handleForceInitialize = () => {
    console.log('🔄 手動觸發認證狀態初始化');
    initialize();
    setRefreshCount(prev => prev + 1);
  };

  const handleForceLogout = () => {
    console.log('🔓 手動觸發登出');
    logout();
    setRefreshCount(prev => prev + 1);
  };

  const getStatusColor = () => {
    if (isLoading) return 'bg-yellow-500';
    if (isAuthenticated) return 'bg-green-500';
    return 'bg-red-500';
  };

  const getStatusText = () => {
    if (isLoading) return '載入中';
    if (isAuthenticated) return '已認證';
    return '未認證';
  };

  return (
    <Card className="fixed bottom-4 right-4 w-96 z-50 bg-background/95 backdrop-blur">
      <CardHeader className="pb-2">
        <CardTitle className="text-sm flex items-center gap-2">
          <div className={`w-3 h-3 rounded-full ${getStatusColor()}`} />
          認證狀態調試面板
          <Badge variant="outline" className="ml-auto">
            V6.3
          </Badge>
        </CardTitle>
        <CardDescription className="text-xs">
          實時監控認證狀態變化 (刷新: {refreshCount})
        </CardDescription>
      </CardHeader>
      
      <CardContent className="space-y-3 text-xs">
        {/* 基礎狀態 */}
        <div className="grid grid-cols-2 gap-2">
          <div>
            <strong>狀態:</strong> 
            <Badge variant={isAuthenticated ? 'default' : 'destructive'} className="ml-1 text-xs">
              {getStatusText()}
            </Badge>
          </div>
          <div>
            <strong>載入:</strong> 
            <Badge variant={isLoading ? 'secondary' : 'outline'} className="ml-1 text-xs">
              {isLoading ? '是' : '否'}
            </Badge>
          </div>
        </div>

        {/* 使用者資訊 */}
        <div>
          <strong>使用者:</strong> 
          <span className="ml-1 text-muted-foreground">
            {user ? user.display_name : '無'}
          </span>
        </div>

        {/* Token 狀態 */}
        <div className="space-y-1">
          <div>
            <strong>Zustand Token:</strong>
            <div className="text-muted-foreground break-all">
              {token ? `${token.substring(0, 30)}...` : '無'}
            </div>
          </div>
          <div>
            <strong>localStorage Token:</strong>
            <div className="text-muted-foreground break-all">
              {localStorageToken ? `${localStorageToken.substring(0, 30)}...` : '無'}
            </div>
          </div>
          <div>
            <strong>Token 同步:</strong>
            <Badge 
              variant={token === localStorageToken ? 'default' : 'destructive'}
              className="ml-1 text-xs"
            >
              {token === localStorageToken ? '同步' : '不同步'}
            </Badge>
          </div>
        </div>

        {/* 權限和角色 */}
        {permissions.length > 0 && (
          <div>
            <strong>權限:</strong>
            <div className="text-muted-foreground">
              {permissions.slice(0, 3).join(', ')}
              {permissions.length > 3 && '...'}
            </div>
          </div>
        )}

        {roles.length > 0 && (
          <div>
            <strong>角色:</strong>
            <div className="text-muted-foreground">
              {roles.join(', ')}
            </div>
          </div>
        )}

        {/* 操作按鈕 */}
        <div className="flex gap-2 pt-2">
          <Button 
            variant="outline" 
            size="sm" 
            onClick={handleForceInitialize}
            className="text-xs"
          >
            重新初始化
          </Button>
          <Button 
            variant="outline" 
            size="sm" 
            onClick={handleForceLogout}
            className="text-xs"
          >
            強制登出
          </Button>
        </div>

        {/* 診斷訊息 */}
        <div className="pt-2 border-t text-xs">
          <strong>診斷:</strong>
          <div className="text-muted-foreground mt-1">
            {isLoading && '🔄 正在載入認證狀態...'}
            {!isLoading && isAuthenticated && '✅ 認證狀態正常'}
            {!isLoading && !isAuthenticated && '❌ 未認證或認證失效'}
            {token !== localStorageToken && '⚠️ Token 不同步'}
          </div>
        </div>
      </CardContent>
    </Card>
  );
} 