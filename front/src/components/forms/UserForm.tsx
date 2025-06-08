/**
 * UserForm - 使用者表單組件
 * 
 * 企業級使用者表單，支援新增和編輯功能
 * - React Hook Form + Zod 驗證
 * - 型別安全的 API 調用
 * - 完整的錯誤處理
 * - 密碼強度檢查
 * - 即時驗證
 * 
 * @author LomisX3 開發團隊
 * @version 2.0.0
 */

import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';

import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
  Eye,
  EyeOff,
  User,
  Mail,
  Phone,
  Lock,
  Shield,
} from 'lucide-react';

import { toast } from 'sonner';
import { useCreateUser, useUpdateUser, type User as UserType } from '@/hooks/api/users';

/**
 * 密碼強度檢查
 */
const passwordSchema = z
  .string()
  .min(8, '密碼至少需要 8 個字元')
  .regex(/[a-z]/, '密碼必須包含小寫字母')
  .regex(/[A-Z]/, '密碼必須包含大寫字母')
  .regex(/[0-9]/, '密碼必須包含數字')
  .regex(/[^a-zA-Z0-9]/, '密碼必須包含特殊字元');

/**
 * 表單驗證架構 - 新增模式
 */
const createUserSchema = z.object({
  name: z
    .string()
    .min(2, '姓名至少需要 2 個字元')
    .max(50, '姓名不能超過 50 個字元'),
  email: z
    .string()
    .email('請輸入有效的 Email 地址'),
  username: z
    .string()
    .min(3, '用戶名至少需要 3 個字元')
    .max(30, '用戶名不能超過 30 個字元')
    .regex(/^[a-zA-Z0-9_]+$/, '用戶名只能包含字母、數字和底線'),
  password: passwordSchema,
  password_confirmation: z.string(),
  phone: z
    .string()
    .optional()
    .refine((val) => !val || /^(\+886|0)?[0-9]{8,10}$/.test(val), {
      message: '請輸入有效的電話號碼',
    }),
  status: z.enum(['active', 'inactive']),
  store_id: z.number().min(1, '請選擇門市'),
  role_ids: z.array(z.number()).min(1, '請選擇至少一個角色'),
}).refine((data) => data.password === data.password_confirmation, {
  message: '密碼確認不一致',
  path: ['password_confirmation'],
});

/**
 * 表單驗證架構 - 編輯模式
 */
const editUserSchema = z.object({
  name: z
    .string()
    .min(2, '姓名至少需要 2 個字元')
    .max(50, '姓名不能超過 50 個字元')
    .optional(),
  email: z
    .string()
    .email('請輸入有效的 Email 地址')
    .optional(),
  username: z
    .string()
    .min(3, '用戶名至少需要 3 個字元')
    .max(30, '用戶名不能超過 30 個字元')
    .regex(/^[a-zA-Z0-9_]+$/, '用戶名只能包含字母、數字和底線')
    .optional(),
  password: z.string().optional().or(passwordSchema),
  password_confirmation: z.string().optional(),
  phone: z
    .string()
    .optional()
    .refine((val) => !val || /^(\+886|0)?[0-9]{8,10}$/.test(val), {
      message: '請輸入有效的電話號碼',
    }),
  status: z.enum(['active', 'inactive', 'suspended']).optional(),
  store_id: z.number().min(1, '請選擇門市').optional(),
  role_ids: z.array(z.number()).min(1, '請選擇至少一個角色').optional(),
}).refine((data) => {
  if (data.password && data.password_confirmation) {
    return data.password === data.password_confirmation;
  }
  return true;
}, {
  message: '密碼確認不一致',
  path: ['password_confirmation'],
});

type CreateUserFormData = z.infer<typeof createUserSchema>;
type EditUserFormData = z.infer<typeof editUserSchema>;

/**
 * 使用者表單屬性
 */
export interface UserFormProps {
  /** 編輯模式的使用者資料 */
  user?: UserType;
  /** 表單模式 */
  mode: 'create' | 'edit';
  /** 提交成功回調 */
  onSuccess?: (user: UserType) => void;
  /** 取消回調 */
  onCancel?: () => void;
  /** 是否顯示卡片容器 */
  showCard?: boolean;
}

/**
 * 密碼強度計算
 */
function calculatePasswordStrength(password: string): {
  score: number;
  label: string;
  color: string;
} {
  let score = 0;
  
  if (password.length >= 8) score += 20;
  if (password.length >= 12) score += 10;
  if (/[a-z]/.test(password)) score += 15;
  if (/[A-Z]/.test(password)) score += 15;
  if (/[0-9]/.test(password)) score += 15;
  if (/[^a-zA-Z0-9]/.test(password)) score += 15;
  if (password.length >= 16) score += 10;

  let label = '非常弱';
  let color = 'bg-red-500';

  if (score >= 90) {
    label = '非常強';
    color = 'bg-green-500';
  } else if (score >= 70) {
    label = '強';
    color = 'bg-blue-500';
  } else if (score >= 50) {
    label = '中等';
    color = 'bg-yellow-500';
  } else if (score >= 30) {
    label = '弱';
    color = 'bg-orange-500';
  }

  return { score, label, color };
}

/**
 * 使用者表單組件
 */
export function UserForm({
  user,
  mode,
  onSuccess,
  onCancel,
  showCard = true,
}: UserFormProps) {
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);

  // API Mutations
  const createUserMutation = useCreateUser();
  const updateUserMutation = useUpdateUser();

  // 表單配置
  const schema = mode === 'create' ? createUserSchema : editUserSchema;
  const form = useForm<CreateUserFormData | EditUserFormData>({
    resolver: zodResolver(schema),
    defaultValues: mode === 'edit' && user ? {
      name: user.name || '',
      email: user.email || '',
      username: user.username || '',
      phone: user.phone || '',
      status: user.status || 'active',
      store_id: user.store?.id || undefined,
      role_ids: user.roles?.map((role: any) => role.id || 0).filter((id: number) => id > 0) || [],
    } : {
      name: '',
      email: '',
      username: '',
      password: '',
      password_confirmation: '',
      phone: '',
      status: 'active' as const,
      store_id: undefined,
      role_ids: [],
    },
  });

  // 監聽密碼變化
  const password = form.watch('password');
  const passwordStrength = password ? calculatePasswordStrength(password) : null;

  // 處理表單提交
  const handleSubmit = async (data: CreateUserFormData | EditUserFormData) => {
    try {
      if (mode === 'create') {
        const createData = data as CreateUserFormData;
        const payload: CreateUserRequest = {
          name: createData.name,
          username: createData.username,
          email: createData.email,
          password: createData.password,
          password_confirmation: createData.password_confirmation,
          phone: createData.phone,
          store_id: createData.store_id,
          role_ids: createData.role_ids,
          status: createData.status,
        };
        
        const response = await createUserMutation.mutateAsync(payload);
        const newUser = response.data;
        
        if (newUser) {
          // 顯示建立成功訊息 (Sonner API)
          toast.success(`使用者「${newUser.name || newUser.username}」已成功建立`);
          onSuccess?.(newUser);
        }
      } else {
        const editData = data as EditUserFormData;
        const payload: UpdateUserRequest = {};
        
        if (editData.name) payload.name = editData.name;
        if (editData.email) payload.email = editData.email;
        if (editData.username) payload.username = editData.username;
        if (editData.password) {
          payload.password = editData.password;
          payload.password_confirmation = editData.password_confirmation;
        }
        if (editData.phone !== undefined) payload.phone = editData.phone;
        if (editData.status) payload.status = editData.status;
        if (editData.store_id) payload.store_id = editData.store_id;
        if (editData.role_ids) payload.role_ids = editData.role_ids;

        const response = await updateUserMutation.mutateAsync({
          id: user?.id || 0,
          data: payload,
        });
        const updatedUser = response.data;
        
        if (updatedUser) {
          // 顯示更新成功訊息 (Sonner API)
          toast.success(`使用者「${updatedUser.name || updatedUser.username}」已成功更新`);
          onSuccess?.(updatedUser);
        }
      }
    } catch (error) {
      // 顯示錯誤訊息 (Sonner API)
      toast.error(mode === 'create' ? '建立失敗：請檢查輸入資料並重試' : '更新失敗：請檢查輸入資料並重試');
    }
  };

  const isLoading = createUserMutation.isPending || updateUserMutation.isPending;

  const formContent = (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-6">
        {/* 基本資訊區塊 */}
        <div className="space-y-4">
          <div className="flex items-center space-x-2">
            <User className="h-5 w-5 text-muted-foreground" />
            <h3 className="text-lg font-medium">基本資訊</h3>
          </div>
          
          <div className="grid gap-4 md:grid-cols-2">
            {/* 姓名 */}
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>姓名 *</FormLabel>
                  <FormControl>
                    <Input placeholder="請輸入姓名" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* 用戶名 */}
            <FormField
              control={form.control}
              name="username"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>用戶名 *</FormLabel>
                  <FormControl>
                    <Input placeholder="請輸入用戶名" {...field} />
                  </FormControl>
                  <FormDescription>
                    只能包含字母、數字和底線，3-30 個字元
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Email */}
            <FormField
              control={form.control}
              name="email"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>電子郵件 *</FormLabel>
                  <FormControl>
                    <div className="relative">
                      <Mail className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                      <Input className="pl-10" placeholder="請輸入電子郵件" {...field} />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* 電話 */}
            <FormField
              control={form.control}
              name="phone"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>電話</FormLabel>
                  <FormControl>
                    <div className="relative">
                      <Phone className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                      <Input className="pl-10" placeholder="請輸入電話號碼" {...field} />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>
        </div>

        {/* 密碼區塊 */}
        {(mode === 'create' || mode === 'edit') && (
          <div className="space-y-4">
            <div className="flex items-center space-x-2">
              <Lock className="h-5 w-5 text-muted-foreground" />
              <h3 className="text-lg font-medium">
                {mode === 'create' ? '密碼設定' : '密碼變更'}
              </h3>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              {/* 密碼 */}
              <FormField
                control={form.control}
                name="password"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>
                      {mode === 'create' ? '密碼 *' : '新密碼'}
                    </FormLabel>
                    <FormControl>
                      <div className="relative">
                        <Lock className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                          className="pl-10 pr-10"
                          type={showPassword ? 'text' : 'password'}
                          placeholder={mode === 'create' ? '請輸入密碼' : '留空表示不修改'}
                          {...field}
                        />
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                          onClick={() => setShowPassword(!showPassword)}
                        >
                          {showPassword ? (
                            <EyeOff className="h-4 w-4" />
                          ) : (
                            <Eye className="h-4 w-4" />
                          )}
                        </Button>
                      </div>
                    </FormControl>
                    {passwordStrength && (
                      <div className="space-y-2">
                        <div className="flex justify-between text-sm">
                          <span>密碼強度</span>
                          <span className="font-medium">{passwordStrength.label}</span>
                        </div>
                        <Progress value={passwordStrength.score} className="h-2" />
                      </div>
                    )}
                    <FormDescription>
                      至少 8 個字元，包含大小寫字母、數字和特殊字元
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {/* 確認密碼 */}
              <FormField
                control={form.control}
                name="password_confirmation"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>
                      {mode === 'create' ? '確認密碼 *' : '確認新密碼'}
                    </FormLabel>
                    <FormControl>
                      <div className="relative">
                        <Lock className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                          className="pl-10 pr-10"
                          type={showPasswordConfirm ? 'text' : 'password'}
                          placeholder="請再次輸入密碼"
                          {...field}
                        />
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                          onClick={() => setShowPasswordConfirm(!showPasswordConfirm)}
                        >
                          {showPasswordConfirm ? (
                            <EyeOff className="h-4 w-4" />
                          ) : (
                            <Eye className="h-4 w-4" />
                          )}
                        </Button>
                      </div>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
          </div>
        )}

        {/* 系統設定區塊 */}
        <div className="space-y-4">
          <div className="flex items-center space-x-2">
            <Shield className="h-5 w-5 text-muted-foreground" />
            <h3 className="text-lg font-medium">系統設定</h3>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            {/* 狀態 */}
            <FormField
              control={form.control}
              name="status"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>狀態</FormLabel>
                  <Select onValueChange={field.onChange} defaultValue={field.value}>
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="請選擇狀態" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      <SelectItem value="active">啟用</SelectItem>
                      <SelectItem value="inactive">停用</SelectItem>
                      {mode === 'edit' && (
                        <SelectItem value="suspended">暫停</SelectItem>
                      )}
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* 門市 */}
            <FormField
              control={form.control}
              name="store_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>門市 *</FormLabel>
                  <Select 
                    onValueChange={(value) => field.onChange(Number(value))} 
                    defaultValue={field.value?.toString()}
                  >
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="請選擇門市" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      <SelectItem value="1">總店</SelectItem>
                      <SelectItem value="2">分店 A</SelectItem>
                      <SelectItem value="3">分店 B</SelectItem>
                    </SelectContent>
                  </Select>
                  <FormDescription>
                    使用者所屬的門市
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>

          {/* 角色選擇 */}
          <div className="space-y-2">
            <FormLabel>角色 *</FormLabel>
            <div className="text-center py-4 text-muted-foreground">
              <Shield className="h-8 w-8 mx-auto mb-2 opacity-50" />
              <p>角色管理功能開發中...</p>
            </div>
          </div>
        </div>

        {/* 按鈕區域 */}
        <div className="flex justify-end space-x-4 pt-6 border-t">
          {onCancel && (
            <Button type="button" variant="outline" onClick={onCancel}>
              取消
            </Button>
          )}
          <Button type="submit" disabled={isLoading}>
            {isLoading ? (
              <>
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-current mr-2" />
                {mode === 'create' ? '建立中...' : '更新中...'}
              </>
            ) : (
              mode === 'create' ? '建立使用者' : '更新使用者'
            )}
          </Button>
        </div>
      </form>
    </Form>
  );

  return showCard ? (
    <Card>
      <CardHeader>
        <CardTitle>
          {mode === 'create' ? '新增使用者' : '編輯使用者'}
        </CardTitle>
        <CardDescription>
          {mode === 'create' 
            ? '填寫以下資訊來建立新的使用者帳戶'
            : '修改使用者的基本資訊和系統設定'
          }
        </CardDescription>
      </CardHeader>
      <CardContent>
        {formContent}
      </CardContent>
    </Card>
  ) : (
    formContent
  );
}

export default UserForm; 