/**
 * LoginPage - 登入頁面 (符合 LomisX3 架構標準)
 * 
 * 使用者身份驗證頁面
 * - 真實 API 登入驗證
 * - React Hook Form + Zod 表單處理
 * - 雙因子驗證支援
 * - 企業級設計界面
 * - 完整錯誤處理
 * 
 * @author LomisX3 開發團隊
 * @version 4.0.0 (真實 API 整合版本)
 */

import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';

import { cn } from "@/lib/utils"
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { toast } from 'sonner';

// API Hooks - 真實 API 調用
import { useLogin } from '@/hooks/api/auth/useLogin';
import { useTwoFactorChallenge } from '@/hooks/api/auth/useTwoFactorChallenge';
import { useAuthStore } from '@/stores/authStore';

// 圖標
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
 * 登入表單驗證 Schema
 * 使用 Zod 進行型別安全的表單驗證
 * 對應後端 LoginRequest 格式（email 字段）
 */
const loginSchema = z.object({
  email: z
    .string()
    .min(1, '請輸入使用者名稱或信箱')
    .max(255, '輸入內容過長'),
  password: z
    .string()
    .min(1, '請輸入密碼')
    .min(6, '密碼至少需要 6 個字元'),
  remember: z
    .boolean()
    .default(false),
});

/**
 * 雙因子驗證表單 Schema
 */
const twoFactorSchema = z.object({
  code: z
    .string()
    .length(6, '驗證碼必須是 6 位數')
    .regex(/^\d{6}$/, '驗證碼只能包含數字'),
});

type LoginFormData = z.infer<typeof loginSchema>;
type TwoFactorFormData = z.infer<typeof twoFactorSchema>;

/**
 * 登入頁面組件
 */
export default function LoginPage() {
  // 狀態管理
  const [showPassword, setShowPassword] = useState(false);
  const [showTwoFactor, setShowTwoFactor] = useState(false);

  // Hooks
  const navigate = useNavigate();
  const location = useLocation();
  // Toast 通知已改用 Sonner，直接使用 toast() 函數
  
  // API Hooks - 真實登入調用
  const loginMutation = useLogin();
  const twoFactorMutation = useTwoFactorChallenge();
  
  // Auth Store
  const authStore = useAuthStore();

  // 取得重導向路徑（登入前使用者想要存取的頁面）
  const from = ((location.state as { from?: string })?.from) ?? '/dashboard';

  /**
   * 登入表單配置
   */
  const loginForm = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: '',
      password: '',
      remember: false,
    },
  });

  /**
   * 雙因子驗證表單配置
   */
  const twoFactorForm = useForm<TwoFactorFormData>({
    resolver: zodResolver(twoFactorSchema),
    defaultValues: {
      code: '',
    },
  });

  /**
   * 處理登入表單提交
   * 使用真實的 API 調用替代模擬資料
   */
  const handleLogin = async (data: LoginFormData) => {
    console.log('🔥 [LoginPage] handleLogin 被調用:', data);
    
    try {
      console.log('🔥 [LoginPage] 開始調用 loginMutation.mutateAsync');
      const result = await loginMutation.mutateAsync(data);
      console.log('🔥 [LoginPage] loginMutation 回傳結果:', result);
      
      // 檢查是否需要雙因子驗證
      if (result?.data?.requires_2fa) {
        console.log('🔥 [LoginPage] 需要雙因子驗證');
        setShowTwoFactor(true);
        
        toast.info("請輸入您的驗證碼以完成登入");
        return;
      }

      // ✅ 登入成功！useLogin Hook 的 onSuccess 已自動處理以下操作：
      // - 更新 AuthStore (setUser, setToken, setPermissions, setRoles)
      // - 儲存 token 到 localStorage
      // - 自動導航到目標頁面
      // LoginPage 無需重複處理，只需處理特殊情況
      
      console.log('🔥 [LoginPage] 登入成功，useLogin Hook 已自動處理所有後續邏輯');
    } catch (error) {
      console.error('🔥 [LoginPage] handleLogin catch 錯誤:', {
        error,
        message: error instanceof Error ? error.message : String(error),
        stack: error instanceof Error ? error.stack : undefined
      });
      
      // 錯誤訊息已由 useLogin Hook 的 onError 處理，此處無需重複顯示 Toast
      // 只記錄錯誤以供調試使用
    }
  };

  /**
   * 處理雙因子驗證提交
   */
  const handleTwoFactorSubmit = async (data: TwoFactorFormData) => {
    try {
      const result = await twoFactorMutation.mutateAsync(data);

      // 驗證成功，處理登入狀態
      if (result?.token && result?.user) {
        authStore.setUser(result.user);
        authStore.setToken(result.token);

        toast({
          title: "驗證成功",
          description: "雙因子驗證完成，登入成功",
        });

        navigate(from, { replace: true });
      }
    } catch (error) {
      // 錯誤處理已在 Hook 中處理
      console.error('2FA 驗證失敗:', error);
    }
  };

  // 雙因子驗證頁面
  if (showTwoFactor) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-background via-muted to-muted/50 p-4">
        <div className={cn("flex flex-col gap-6 w-full max-w-lg")}>
          <Card className="overflow-hidden shadow-2xl border-0">
            <CardContent className="p-0">
              <Form {...twoFactorForm}>
                <form onSubmit={twoFactorForm.handleSubmit(handleTwoFactorSubmit)} className="p-8">
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

                    <FormField
                      control={twoFactorForm.control}
                      name="code"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>驗證碼</FormLabel>
                          <FormControl>
                            <Input
                              {...field}
                              type="text"
                              placeholder="123456"
                              maxLength={6}
                              className="text-center text-lg tracking-widest"
                              autoComplete="one-time-code"
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <Button 
                      type="submit" 
                      className="w-full" 
                      disabled={twoFactorMutation.isPending}
                    >
                      {twoFactorMutation.isPending ? (
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
              </Form>
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
            <div className="p-8 md:p-10">
              <Form {...loginForm}>
                <form onSubmit={loginForm.handleSubmit(handleLogin)} className="flex flex-col gap-6">
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
                  
                  <FormField
                    control={loginForm.control}
                    name="email"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>使用者名稱或信箱</FormLabel>
                        <FormControl>
                          <Input
                            {...field}
                            type="text"
                            placeholder="請輸入使用者名稱或信箱"
                            autoComplete="username"
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  
                  <FormField
                    control={loginForm.control}
                    name="password"
                    render={({ field }) => (
                      <FormItem>
                        <div className="flex items-center">
                          <FormLabel>密碼</FormLabel>
                          <a
                            href="#"
                            className="ml-auto text-sm underline-offset-2 hover:underline text-primary"
                          >
                            忘記密碼？
                          </a>
                        </div>
                        <FormControl>
                          <div className="relative">
                            <Input 
                              {...field}
                              type={showPassword ? "text" : "password"} 
                              placeholder="請輸入密碼"
                              autoComplete="current-password"
                            />
                            <button
                              type="button"
                              onClick={() => setShowPassword(!showPassword)}
                              className="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                              {showPassword ? (
                                <EyeOff className="h-4 w-4" />
                              ) : (
                                <Eye className="h-4 w-4" />
                              )}
                            </button>
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={loginForm.control}
                    name="remember"
                    render={({ field }) => (
                      <FormItem className="flex flex-row items-start space-x-3 space-y-0">
                        <FormControl>
                          <Checkbox
                            checked={field.value}
                            onCheckedChange={field.onChange}
                          />
                        </FormControl>
                        <div className="space-y-1 leading-none">
                          <FormLabel className="text-sm font-normal">
                            記住我的登入狀態
                          </FormLabel>
                        </div>
                      </FormItem>
                    )}
                  />

                  <Button 
                    type="submit" 
                    className="w-full" 
                    disabled={loginMutation.isPending}
                  >
                    {loginMutation.isPending ? (
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

                  <div className="text-center text-sm text-muted-foreground">
                    <p>
                      還沒有帳戶？{' '}
                      <a href="#" className="text-primary hover:underline">
                        聯繫管理員
                      </a>
                    </p>
                  </div>
                </form>
              </Form>
            </div>

            {/* 品牌展示區域 */}
            <div className="relative hidden bg-primary md:block">
              <div className="absolute inset-0 bg-gradient-to-br from-primary to-primary/80 p-10 flex flex-col justify-center text-primary-foreground">
                <div className="space-y-6">
                  <div className="space-y-2">
                    <h1 className="text-3xl font-bold tracking-tight">
                      LomisX3 企業管理系統
                    </h1>
                    <p className="text-lg opacity-90">
                      現代化的企業級解決方案
                    </p>
                  </div>
                  
                  <div className="space-y-4">
                    <div className="flex items-center space-x-3">
                      <Shield className="h-5 w-5 opacity-80" />
                      <span>企業級安全防護</span>
                    </div>
                    <div className="flex items-center space-x-3">
                      <Building2 className="h-5 w-5 opacity-80" />
                      <span>多門市管理支援</span>
                    </div>
                    <div className="flex items-center space-x-3">
                      <Key className="h-5 w-5 opacity-80" />
                      <span>細粒度權限控制</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
} 