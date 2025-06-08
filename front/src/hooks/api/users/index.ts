/**
 * Users API Hooks 模組統一匯出
 * 
 * ✅ LomisX3 V4.0 企業級標準 - 統一戰爭成果
 * 
 * 提供完整的 Users 相關 API Hooks 和型別定義
 * 基於 ProductCategory v2.3 黃金標準實現
 */

// ═══════════════════════════════════════
// API Hooks 匯出
// ═══════════════════════════════════════

export { useGetUsers } from './useGetUsers';
export { useGetUser } from './useGetUser';
export { useCreateUser } from './useCreateUser';
export { useUpdateUser } from './useUpdateUser';
export { useDeleteUser } from './useDeleteUser';
export { useBatchUpdateUserStatus, useResetUserPassword } from './useBatchUpdateUsers';

// ═══════════════════════════════════════
// 型別定義匯出
// ═══════════════════════════════════════

export type {
  // 核心實體型別
  User,
  UserRole,
  UserStatus,
  Store,
  
  // 查詢參數型別
  UserQueryParams,
  
  // 請求資料型別
  CreateUserData,
  UpdateUserData,
  
  // 回應資料型別
  UserListResponse,
  UserDetailResponse,
  UserCreateResponse,
  UserUpdateResponse,
  UserDeleteResponse,
  
  // 分頁相關型別
  PaginationMeta,
  PaginationLinks,
  
  // API 錯誤型別
  ApiError,
  ValidationError,
  BusinessError
} from './types';

// ═══════════════════════════════════════
// 預設匯出（主要查詢 Hook）
// ═══════════════════════════════════════

export { useGetUsers as default } from './useGetUsers'; 