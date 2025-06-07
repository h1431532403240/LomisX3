import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { openapi, safeApiCall } from '@/lib/openapi-client';
import type { paths } from '@/types/api';

/**
 * 系統配置類型定義
 * 符合 LomisX3 V4.0 API 合約標準
 */
export interface UserStatusConfig {
  value: string;
  label: string;
  description: string;
  color: string;
  can_login: boolean;
  requires_admin_action: boolean;
}

export interface UserRoleConfig {
  value: string;
  label: string;
  description: string;
  level: number;
  color: string;
  permissions: string[];
}

export interface SystemConfigMeta {
  version: string;
  generated_at: string;
  locale: string;
}

export interface SystemConfigs {
  user_statuses: Record<string, UserStatusConfig>;
  user_roles: Record<string, UserRoleConfig>;
  meta: SystemConfigMeta;
}

/**
 * 系統配置 Store 介面
 */
interface SystemConfigStore {
  // 配置資料
  configs: SystemConfigs | null;
  isLoading: boolean;
  lastUpdated: string | null;
  error: string | null;

  // 操作方法
  loadConfigs: () => Promise<void>;
  clearConfigs: () => void;
  isConfigsExpired: () => boolean;

  // 便捷方法
  getUserStatusConfig: (status: string) => UserStatusConfig | null;
  getUserRoleConfig: (role: string) => UserRoleConfig | null;
  formatUserStatus: (status: string) => { label: string; color: string };
  formatUserRole: (role: string) => { label: string; color: string };
}

/**
 * 系統配置 Zustand Store
 * 
 * 職責：
 * - 管理所有枚舉值配置和本地化文本
 * - 提供型別安全的配置存取方法
 * - 自動快取和過期管理
 * - 實現配置驅動UI架構
 * 
 * 符合 LomisX3 V4.0 標準：
 * - API 合約驗證
 * - 配置驅動UI
 * - 完全受控組件支援
 * 
 * @author LomisX3 開發團隊
 * @version V1.0
 */
export const useSystemConfigStore = create<SystemConfigStore>()(
  persist(
    (set, get) => ({
      // 初始狀態
      configs: null,
      isLoading: false,
      lastUpdated: null,
      error: null,

      /**
       * 載入系統配置
       * 從 API 取得最新的枚舉值和本地化文本
       */
             loadConfigs: async () => {
         const { isLoading, isConfigsExpired } = get();
         
         // 避免重複載入
         if (isLoading) {
           console.log('🔄 SystemConfigStore: 配置載入中，跳過重複請求');
           return;
         }

         // 檢查快取是否仍有效
         if (!isConfigsExpired()) {
           console.log('✅ SystemConfigStore: 使用快取配置');
           return;
         }

         set({ isLoading: true, error: null });

         try {
           console.log('🌐 SystemConfigStore: 開始載入系統配置...');

           // 使用 safeApiCall 包裝 API 調用
           const response = await safeApiCall(async () => {
             const result = await openapi.GET('/api/system/configs', {});
             return result;
           });

           if (response.error) {
             throw new Error(`API 錯誤: ${response.error || '系統配置載入失敗'}`);
           }

           if (!response.data?.success || !response.data?.data) {
             throw new Error('API 回應格式錯誤：缺少必要的 data 欄位');
           }

           // API 合約驗證
           const configs = response.data.data as SystemConfigs;
           if (!configs.user_statuses || !configs.user_roles || !configs.meta) {
             throw new Error('API 合約違反：系統配置格式不正確');
           }

           console.log('✅ SystemConfigStore: 系統配置載入成功', {
             userStatuses: Object.keys(configs.user_statuses).length,
             userRoles: Object.keys(configs.user_roles).length,
             version: configs.meta.version
           });

           set({
             configs,
             lastUpdated: new Date().toISOString(),
             isLoading: false,
             error: null
           });

         } catch (error) {
           const errorMessage = error instanceof Error ? error.message : '未知錯誤';
           console.error('❌ SystemConfigStore: 配置載入失敗', error);
           
           set({
             configs: null,
             isLoading: false,
             error: errorMessage
           });
           
           throw error; // 重新拋出錯誤供上層處理
         }
       },

      /**
       * 清除配置快取
       */
      clearConfigs: () => {
        console.log('🗑️ SystemConfigStore: 清除配置快取');
        set({
          configs: null,
          lastUpdated: null,
          error: null
        });
      },

      /**
       * 檢查配置是否過期
       * 快取時間：1小時
       */
      isConfigsExpired: () => {
        const { lastUpdated, configs } = get();
        
        if (!configs || !lastUpdated) {
          return true;
        }

        const now = new Date();
        const lastUpdate = new Date(lastUpdated);
        const diffHours = (now.getTime() - lastUpdate.getTime()) / (1000 * 60 * 60);
        
        return diffHours > 1; // 1小時後過期
      },

      /**
       * 取得使用者狀態配置
       * 
       * @param status 狀態值 (如 'active')
       * @returns 狀態配置或 null
       */
      getUserStatusConfig: (status: string) => {
        const { configs } = get();
        if (!configs?.user_statuses) {
          console.warn('⚠️ SystemConfigStore: 系統配置尚未載入');
          return null;
        }
        
        return configs.user_statuses[status] || null;
      },

      /**
       * 取得使用者角色配置
       * 
       * @param role 角色值 (如 'admin')
       * @returns 角色配置或 null
       */
      getUserRoleConfig: (role: string) => {
        const { configs } = get();
        if (!configs?.user_roles) {
          console.warn('⚠️ SystemConfigStore: 系統配置尚未載入');
          return null;
        }
        
        return configs.user_roles[role] || null;
      },

      /**
       * 格式化使用者狀態
       * 
       * ✅ V1.0 重要更新：強制字串型別，符合 API 合約
       * 
       * @param status 狀態值，必須為字串
       * @returns 格式化的狀態資訊
       */
      formatUserStatus: (status: string) => {
        // ✅ API 合約強制驗證：只接受字串
        if (typeof status !== 'string') {
          console.error('❌ SystemConfigStore: status 必須為字串，收到:', typeof status, status);
          throw new Error(`API 合約違反：status 必須為字串，收到 ${typeof status}`);
        }

        const config = get().getUserStatusConfig(status);
        
        if (config) {
          return {
            label: config.label,
            color: config.color
          };
        }

        // 回退機制
        console.warn(`⚠️ SystemConfigStore: 未知的使用者狀態: ${status}`);
        return {
          label: '未知狀態',
          color: 'default'
        };
      },

      /**
       * 格式化使用者角色
       * 
       * ✅ V1.0 重要更新：強制字串陣列型別，符合 API 合約
       * 
       * @param roles 角色陣列，必須為字串陣列
       * @returns 格式化的角色資訊
       */
      formatUserRole: (roles: string | string[]) => {
        // 標準化為陣列
        const roleArray = Array.isArray(roles) ? roles : [roles];
        
        // ✅ API 合約強制驗證：只接受字串陣列
        const invalidRoles = roleArray.filter(role => typeof role !== 'string');
        if (invalidRoles.length > 0) {
          console.error('❌ SystemConfigStore: roles 必須為字串陣列，發現非字串:', invalidRoles);
          throw new Error(`API 合約違反：roles 必須為字串陣列`);
        }

        const { getUserRoleConfig } = get();
        
        // 取得第一個有效角色的配置（通常使用者只有一個主要角色）
        const primaryRole = roleArray[0];
        const config = getUserRoleConfig(primaryRole);
        
        if (config) {
          return {
            label: config.label,
            color: config.color
          };
        }

        // 回退機制
        console.warn(`⚠️ SystemConfigStore: 未知的使用者角色: ${primaryRole}`);
        return {
          label: '未知角色',
          color: 'default'
        };
      }
    }),
    {
      name: 'lomis-system-config',
      version: 1,
      
      // 只持久化配置資料和時間戳，不持久化狀態
      partialize: (state) => ({
        configs: state.configs,
        lastUpdated: state.lastUpdated
      })
    }
  )
); 