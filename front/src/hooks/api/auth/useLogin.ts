/**
 * 認證相關 Hooks
 * 包含登入、登出、當前使用者資訊等功能
 */
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import { useToast } from '@/hooks/use-toast';
import type { components } from '@/types/api';

// API 類型定義
type LoginRequest = components['schemas']['LoginRequest'];
type TwoFactorChallengeRequest = components['schemas']['TwoFactorChallengeRequest'];

/**
 * 登入 Hook
 * @returns useMutation 結果
 */
export function useLogin() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: async (credentials: LoginRequest) => {
      const result = await safeApiCall(() =>
        openapi.POST('/api/auth/login', {
          body: credentials
        })
      );

      if (result.error) {
        throw new Error(result.error.message || '登入失敗');
      }

      return result.data;
    },
    onSuccess: (data) => {
      // 如果有 token，儲存到 localStorage
      if (data?.token) {
        localStorage.setItem('auth_token', data.token);
        
        // 清除查詢快取，重新取得使用者資料
        queryClient.invalidateQueries({ queryKey: ['auth'] });
        queryClient.invalidateQueries({ queryKey: ['users'] });
        
        toast({
          title: "登入成功",
          description: `歡迎回來，${data.user?.name}！`,
        });
      }
    },
    onError: (error: Error) => {
      toast({
        title: "登入失敗",
        description: error.message,
        variant: "destructive",
      });
    },
  });
}

/**
 * 登出 Hook
 * @returns useMutation 結果
 */
export function useLogout() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: async () => {
      const result = await safeApiCall(() =>
        openapi.POST('/api/auth/logout')
      );

      if (result.error) {
        throw new Error(result.error.message || '登出失敗');
      }

      return result.data;
    },
    onSuccess: () => {
      // 清除 token
      localStorage.removeItem('auth_token');
      
      // 清除所有查詢快取
      queryClient.clear();
      
      toast({
        title: "登出成功",
        description: "您已安全登出系統",
      });
    },
    onError: (error: Error) => {
      // 即使登出失敗，也要清除本地 token
      localStorage.removeItem('auth_token');
      queryClient.clear();
      
      toast({
        title: "登出失敗",
        description: error.message,
        variant: "destructive",
      });
    },
  });
}

/**
 * 取得當前使用者資訊 Hook
 * @returns useQuery 結果
 */
export function useGetCurrentUser() {
  return useQuery({
    queryKey: ['auth', 'current-user'],
    queryFn: async () => {
      const result = await safeApiCall(() =>
        openapi.GET('/api/auth/me')
      );

      if (result.error) {
        throw new Error(result.error.message || '取得使用者資訊失敗');
      }

      return result.data;
    },
    // 只在有 token 時執行
    enabled: !!localStorage.getItem('auth_token'),
    // 1分鐘快取時間
    staleTime: 1 * 60 * 1000,
    // 重試機制
    retry: (failureCount, error) => {
      // 401 不重試（未認證）
      if (error.message.includes('401')) {
        localStorage.removeItem('auth_token');
        return false;
      }
      return failureCount < 2;
    },
  });
}

/**
 * 2FA 驗證 Hook
 * @returns useMutation 結果
 */
export function useTwoFactorChallenge() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: async (challengeData: TwoFactorChallengeRequest) => {
      const result = await safeApiCall(() =>
        openapi.POST('/api/auth/2fa/challenge', {
          body: challengeData
        })
      );

      if (result.error) {
        throw new Error(result.error.message || '2FA 驗證失敗');
      }

      return result.data;
    },
    onSuccess: () => {
      // 驗證成功後重新取得使用者資料
      queryClient.invalidateQueries({ queryKey: ['auth'] });
      
      toast({
        title: "驗證成功",
        description: "雙因子驗證通過",
      });
    },
    onError: (error: Error) => {
      toast({
        title: "驗證失敗",
        description: error.message,
        variant: "destructive",
      });
    },
  });
} 