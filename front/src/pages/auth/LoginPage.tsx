/**
 * LoginPage - 登入頁面 (符合 LomisX3 架構標準)
 * 
 * 使用者身份驗證頁面
 * - 帳號密碼登入
 * - 記住我功能
 * - 忘記密碼
 * - 雙因子驗證
 * - 企業級設計界面
 * 
 * @author LomisX3 開發團隊
 * @version 3.0.0 (符合架構標準手冊)
 */

import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { cn } from "@/lib/utils"
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { toast } from 'sonner';
import { useAuthStore } from '@/stores/authStore';
import type { User } from '@/types/user';
import { 
  Eye,
  EyeOff,
  Building2,
  Shield,
  Key,
  Smartphone,
  Loader2
} from 'lucide-react';

/**
 * 登入頁面組件
 */
export default function LoginPage() {
  const [formData, setFormData] = useState({
    username: '',
    password: '',
  });
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [showTwoFactor, setShowTwoFactor] = useState(false);
  const [verificationCode, setVerificationCode] = useState('');

  const login = useAuthStore((state) => state.login);
  const navigate = useNavigate();
  const location = useLocation();

  // 取得重導向路徑（登入前使用者想要存取的頁面）
  const from = ((location.state as { from?: string })?.from) ?? '/dashboard';

  /**
   * 處理表單輸入變更
   */
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  /**
   * 處理登入表單提交
   */
  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.username || !formData.password) {
      toast.error("請輸入完整資訊：帳號和密碼都是必填欄位");
      return;
    }

    setIsLoading(true);
    
    // 使用 setTimeout 模擬異步 API 調用
    setTimeout(() => {
      try {
        // TODO: 實現真實的 API 登入調用
        // 暫時使用模擬資料來演示流程
        const mockUser: User = {
          id: 1,
          email: `${formData.username}@lomis.com`,
          username: formData.username,
          first_name: '管理員',
          last_name: '用戶',
          full_name: '管理員用戶',
          display_name: '管理員用戶',
          role: 'admin' as const,
          permissions: ['users.*', 'categories.*', 'products.*'],
          status: 'active' as const,
          email_verified_at: new Date().toISOString(),
          two_factor_enabled: false,
          last_login_at: null,
          store_id: 1,
          store: {
            id: 1,
            name: 'LomisX3 總部',
            code: 'HQ',
            address: '台北市信義區信義路五段7號89樓',
            phone: '02-2345-6789',
            email: 'admin@lomis.com',
            status: true,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
          },
          phone: null,
          avatar_url: null,
          timezone: 'Asia/Taipei',
          locale: 'zh-TW',
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
          created_by: null,
          updated_by: null,
        };
        
        const mockToken = 'lomis-jwt-token-' + Date.now();
        const mockPermissions = ['users.*', 'categories.*', 'products.*'];
        
        // 使用 login 方法設置認證狀態
        login(mockUser, mockToken, mockPermissions);
        
        toast.success(`登入成功！歡迎回到 LomisX3 企業管理系統，${mockUser.display_name}`);
        
        void navigate(from, { replace: true });
      } catch (error) {
        toast.error(`登入失敗：${error instanceof Error ? error.message : "請檢查帳號和密碼是否正確"}`);
      } finally {
        setIsLoading(false);
      }
    }, 1000);
  };

  /**
   * 處理雙因子驗證提交
   */
  const handleTwoFactorSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!verificationCode || verificationCode.length !== 6) {
      toast.error("驗證碼格式錯誤，請輸入 6 位數驗證碼");
      return;
    }

    setIsLoading(true);
    
    // 使用 setTimeout 模擬異步驗證
    setTimeout(() => {
      try {
        // TODO: 實現雙因子驗證邏輯
        toast.success("驗證成功！雙因子驗證完成");
        void navigate(from, { replace: true });
      } catch {
        toast.error("驗證失敗：驗證碼錯誤，請重新輸入");
      } finally {
        setIsLoading(false);
      }
    }, 1000);
  };

  // 雙因子驗證頁面
  if (showTwoFactor) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-muted to-muted/50 p-4">
        <div className={cn("flex flex-col gap-6 w-full max-w-lg")}>
          <Card className="overflow-hidden shadow-2xl border-0">
            <CardContent className="p-0">
              <form className="p-8" onSubmit={handleTwoFactorSubmit}>
                <div className="flex flex-col gap-6">
                  <div className="flex flex-col items-center text-center">
                    <div className="mb-4 p-3 bg-primary/10 rounded-full">
                      <Smartphone className="h-8 w-8 text-primary" />
                    </div>
                    <h1 className="text-2xl font-bold">雙因子驗證</h1>
                    <p className="text-balance text-muted-foreground">
                      請輸入手機應用程式中的 6 位數驗證碼
                    </p>
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="verificationCode">驗證碼</Label>
                    <Input
                      id="verificationCode"
                      type="text"
                      placeholder="123456"
                      value={verificationCode}
                      onChange={(e) => setVerificationCode(e.target.value)}
                      maxLength={6}
                      className="text-center text-lg tracking-widest"
                      required
                    />
                  </div>
                  <Button type="submit" className="w-full" disabled={isLoading}>
                    {isLoading ? (
                      <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        驗證中...
                      </>
                    ) : (
                      <>
                        <Shield className="mr-2 h-4 w-4" />
                        驗證並登入
                      </>
                    )}
                  </Button>
                  <div className="text-center text-sm">
                    <button 
                      type="button"
                      onClick={() => setShowTwoFactor(false)}
                      className="text-primary hover:underline"
                    >
                      返回登入頁面
                    </button>
                  </div>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

  // 主要登入頁面
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-muted to-muted/50 p-4">
      <div className={cn("flex flex-col gap-6 w-full max-w-6xl")}>
        <Card className="overflow-hidden shadow-2xl border-0">
          <CardContent className="grid p-0 md:grid-cols-2">
            {/* 登入表單區域 */}
            <form className="p-8 md:p-10" onSubmit={handleLogin}>
              <div className="flex flex-col gap-6">
                <div className="flex flex-col items-center text-center">
                  <div className="mb-4 flex items-center space-x-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                      <Building2 className="h-8 w-8 text-primary" />
                    </div>
                    <div>
                      <h1 className="text-2xl font-bold">LomisX3</h1>
                      <p className="text-sm text-muted-foreground">企業級管理系統</p>
                    </div>
                  </div>
                  <h2 className="text-2xl font-bold">歡迎回來</h2>
                  <p className="text-balance text-muted-foreground">
                    登入您的 LomisX3 管理帳戶
                  </p>
                </div>
                
                <div className="grid gap-2">
                  <Label htmlFor="username">使用者名稱</Label>
                  <Input
                    id="username"
                    name="username"
                    type="text"
                    placeholder="請輸入使用者名稱"
                    value={formData.username}
                    onChange={handleInputChange}
                    required
                  />
                </div>
                
                <div className="grid gap-2">
                  <div className="flex items-center">
                    <Label htmlFor="password">密碼</Label>
                    <a
                      href="#"
                      className="ml-auto text-sm underline-offset-2 hover:underline text-primary"
                    >
                      忘記密碼？
                    </a>
                  </div>
                  <div className="relative">
                    <Input 
                      id="password" 
                      name="password"
                      type={showPassword ? "text" : "password"} 
                      placeholder="請輸入密碼"
                      value={formData.password}
                      onChange={handleInputChange}
                      required 
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                      {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                </div>

                <div className="flex items-center space-x-2">
                  <Checkbox 
                    id="remember" 
                    checked={rememberMe}
                    onCheckedChange={(checked) => setRememberMe(checked as boolean)}
                  />
                  <Label htmlFor="remember" className="text-sm">記住我</Label>
                </div>
                
                <Button type="submit" className="w-full" disabled={isLoading}>
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      登入中...
                    </>
                  ) : (
                    <>
                      <Key className="mr-2 h-4 w-4" />
                      登入
                    </>
                  )}
                </Button>
                
                <div className="relative text-center text-sm after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-border">
                  <span className="relative z-10 bg-background px-2 text-muted-foreground">
                    或選擇其他登入方式
                  </span>
                </div>
                
                <div className="grid grid-cols-2 gap-4">
                  <Button variant="outline" className="w-full" type="button">
                    <svg className="mr-2 h-4 w-4" viewBox="0 0 24 24">
                      <path
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        fill="#4285F4"
                      />
                      <path
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        fill="#34A853"
                      />
                      <path
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        fill="#FBBC05"
                      />
                      <path
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        fill="#EA4335"
                      />
                    </svg>
                    Google
                  </Button>
                  <Button variant="outline" className="w-full" type="button">
                    <svg className="mr-2 h-4 w-4" viewBox="0 0 24 24">
                      <path
                        d="M23.5 12.5c0-.8-.1-1.6-.2-2.4H12v4.5h6.5c-.3 1.4-1.1 2.6-2.3 3.4v2.8h3.7c2.2-2 3.5-5 3.5-8.3z"
                        fill="#4285f4"
                      />
                      <path
                        d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.7-2.8c-1.1.7-2.5 1.1-4.2 1.1-3.2 0-5.9-2.2-6.9-5.1H1.4v2.9C3.4 20.9 7.4 24 12 24z"
                        fill="#34a853"
                      />
                      <path
                        d="M5.1 14.3c-.2-.7-.4-1.4-.4-2.3s.1-1.6.4-2.3V6.8H1.4C.5 8.6 0 10.2 0 12s.5 3.4 1.4 5.2l3.7-2.9z"
                        fill="#fbbc04"
                      />
                      <path
                        d="M12 4.8c1.8 0 3.4.6 4.7 1.8l3.5-3.5C18 1.1 15.2 0 12 0 7.4 0 3.4 3.1 1.4 7.7l3.7 2.9C6.1 7.0 8.8 4.8 12 4.8z"
                        fill="#ea4335"
                      />
                    </svg>
                    Microsoft
                  </Button>
                </div>
                
                <div className="text-center text-sm">
                  還沒有帳戶？{" "}
                  <a href="#" className="underline underline-offset-4 text-primary">
                    註冊新帳戶
                  </a>
                </div>
              </div>
            </form>
            
            {/* 右側品牌區域 */}
            <div className="relative hidden bg-gradient-to-br from-primary via-primary/90 to-primary/80 md:block">
              <div className="absolute inset-0 bg-black/10"></div>
              <div className="relative h-full flex flex-col justify-center items-center p-10 text-primary-foreground">
                <div className="text-center space-y-6">
                  <div className="mb-8">
                    <Building2 className="h-16 w-16 mx-auto mb-4 text-primary-foreground/80" />
                    <h2 className="text-4xl font-bold mb-2">LomisX3</h2>
                    <p className="text-xl text-primary-foreground/80">企業級管理系統</p>
                  </div>
                  
                  <div className="space-y-4 text-primary-foreground/90">
                    <div className="flex items-center space-x-3">
                      <Shield className="h-5 w-5 text-primary-foreground/70" />
                      <span>企業級安全防護</span>
                    </div>
                    <div className="flex items-center space-x-3">
                      <Building2 className="h-5 w-5 text-primary-foreground/70" />
                      <span>多租戶架構支援</span>
                    </div>
                    <div className="flex items-center space-x-3">
                      <Key className="h-5 w-5 text-primary-foreground/70" />
                      <span>細粒度權限控制</span>
                    </div>
                  </div>
                  
                  <p className="text-sm text-primary-foreground/70 max-w-md">
                    強大、安全、可擴展的企業管理解決方案
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <div className="text-balance text-center text-xs text-muted-foreground [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-primary">
          點擊繼續即表示您同意我們的 <a href="#">服務條款</a> 和 <a href="#">隱私政策</a>
        </div>
      </div>
    </div>
  );
} 