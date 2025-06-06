/**
 * 使用者相關型別定義
 * 
 * 與後端 API Resource 保持完全一致
 * 確保型別安全和資料完整性
 */

/**
 * 使用者角色枚舉
 */
export type UserRole = 'admin' | 'store_admin' | 'manager' | 'staff' | 'guest';

/**
 * 使用者狀態枚舉
 */
export type UserStatus = 'active' | 'inactive' | 'suspended' | 'pending';

/**
 * 性別枚舉
 */
export type Gender = 'male' | 'female' | 'other' | 'prefer_not_to_say';

/**
 * 門市資訊介面
 */
export interface Store {
  id: number;
  name: string;
  code: string;
  address?: string;
  phone?: string;
  email?: string;
  status: boolean;
  created_at: string;
  updated_at: string;
}

/**
 * 使用者主要介面
 * 
 * ⚠️ 重要：這個介面必須與後端 UserResource 的輸出格式完全一致
 * 包含所有可能的空值情況，確保運行時安全
 */
export interface User {
  // 基礎欄位
  id: number;
  email: string;
  username: string;
  
  // 名稱欄位 - 注意這些欄位可能為空，需要安全處理
  first_name: string | null;
  last_name: string | null;
  full_name: string; // 後端計算得出的完整名稱
  display_name: string; // 顯示名稱，優先級：full_name > username > email
  
  // 角色和權限
  role: UserRole;
  permissions: string[];
  
  // 狀態資訊
  status: UserStatus;
  email_verified_at: string | null;
  two_factor_enabled: boolean;
  last_login_at: string | null;
  
  // 門市關聯
  store_id: number;
  store: Store;
  
  // 個人資訊
  phone: string | null;
  avatar_url: string | null;
  timezone: string | null;
  locale: string;
  
  // 系統欄位
  created_at: string;
  updated_at: string;
  created_by: number | null;
  updated_by: number | null;
}

/**
 * 使用者搜尋參數介面
 */
export interface UserSearchParams {
  // 分頁參數
  page?: number;
  per_page?: number;
  
  // 搜尋參數
  search?: string;
  
  // 篩選參數
  role?: UserRole;
  status?: UserStatus;
  store_id?: number;
  email_verified?: boolean;
  two_factor_enabled?: boolean;
  
  // 排序參數
  sort?: 'id' | 'email' | 'username' | 'full_name' | 'role' | 'status' | 'last_login_at' | 'created_at';
  direction?: 'asc' | 'desc';
  
  // 關聯載入
  with?: string[];
}

/**
 * 使用者列表回應介面
 */
export interface PaginatedUsers {
  data: User[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
  };
}

/**
 * 使用者建立/更新資料介面
 */
export interface UserFormData {
  // 基礎資訊
  email: string;
  username: string;
  first_name?: string;
  last_name?: string;
  
  // 角色和權限
  role: UserRole;
  permissions?: string[];
  
  // 狀態
  status: UserStatus;
  
  // 門市
  store_id: number;
  
  // 個人資訊
  phone?: string;
  timezone?: string;
  locale?: string;
  
  // 密碼（僅建立時需要，更新時可選）
  password?: string;
  password_confirmation?: string;
}

/**
 * 登入回應介面
 */
export interface AuthResponse {
  user: User;
  token: string;
  expires_at: string;
}

/**
 * 2FA 設定回應介面
 */
export interface TwoFactorSetupResponse {
  secret: string;
  qr_code_url: string;
  recovery_codes: string[];
}

/**
 * 使用者統計介面
 */
export interface UserStatistics {
  total_users: number;
  active_users: number;
  inactive_users: number;
  suspended_users: number;
  pending_users: number;
  two_factor_enabled_count: number;
  by_role: Record<UserRole, number>;
  by_store: Array<{
    store_id: number;
    store_name: string;
    user_count: number;
  }>;
  recent_logins: number; // 最近 24 小時登入數
}

/**
 * 安全的頭像顯示工具函數
 * 
 * V2.7 生產加固版 - 防止運行時崩潰
 * 解決原始代碼風險：{user.first_name.charAt(0)} 可能導致 TypeError
 * 
 * 多層級回退邏輯，確保永不崩潰：
 * 1. full_name (後端計算欄位)
 * 2. first_name + last_name 組合
 * 3. username 
 * 4. email
 * 5. 兜底值 "??"
 * 
 * @param user - 使用者物件
 * @returns 2字元大寫頭像回退文字
 */
export function getAvatarFallback(user: User): string {
  // 第一優先：使用完整名稱（後端計算得出）
  if (user.full_name?.trim()) {
    return user.full_name.trim().substring(0, 2).toUpperCase();
  }
  
  // 第二優先：組合 first_name 和 last_name
  if (user.first_name || user.last_name) {
    const first = user.first_name?.charAt(0)?.toUpperCase() || '';
    const last = user.last_name?.charAt(0)?.toUpperCase() || '';
    const combined = (first + last).substring(0, 2);
    if (combined) return combined;
  }
  
  // 第三優先：使用 username
  if (user.username?.trim()) {
    return user.username.trim().substring(0, 2).toUpperCase();
  }
  
  // 第四優先：使用 email
  if (user.email?.trim()) {
    return user.email.trim().substring(0, 2).toUpperCase();
  }
  
  // 兜底值：確保永不為空
  return '??';
}

/**
 * 安全的顯示名稱工具函數
 * 
 * V2.7 增強版 - 提供一致的顯示邏輯
 * 與後端 display_name 計算邏輯保持一致
 * 
 * @param user - 使用者物件
 * @returns 使用者顯示名稱
 */
export function getDisplayName(user: User): string {
  // 後端已計算的 display_name 為最高優先級
  if (user.display_name?.trim()) {
    return user.display_name.trim();
  }
  
  // 回退邏輯與後端保持一致
  if (user.full_name?.trim()) {
    return user.full_name.trim();
  }
  
  if (user.username?.trim()) {
    return user.username.trim();
  }
  
  if (user.email?.trim()) {
    return user.email.trim();
  }
  
  return '未知使用者';
}

/**
 * 格式化使用者角色顯示
 * 
 * @param role - 使用者角色
 * @returns 本地化的角色名稱
 */
export function formatUserRole(role: UserRole): string {
  const roleMap: Record<UserRole, string> = {
    admin: '系統管理員',
    store_admin: '門市管理員',
    manager: '經理',
    staff: '員工',
    guest: '訪客',
  };
  return roleMap[role] || role;
}

/**
 * 格式化使用者狀態顯示
 * 
 * @param status - 使用者狀態
 * @returns 包含標籤、顏色等資訊的狀態物件
 */
export function formatUserStatus(status: UserStatus) {
  const statusConfig = {
    active: { 
      label: '啟用', 
      variant: 'default' as const,
      color: 'green',
      bgColor: 'bg-green-100',
      textColor: 'text-green-800'
    },
    inactive: { 
      label: '停用', 
      variant: 'secondary' as const,
      color: 'gray',
      bgColor: 'bg-gray-100',
      textColor: 'text-gray-800'
    },
    suspended: { 
      label: '暫停', 
      variant: 'destructive' as const,
      color: 'red',
      bgColor: 'bg-red-100',
      textColor: 'text-red-800'
    },
    pending: { 
      label: '待審核', 
      variant: 'outline' as const,
      color: 'yellow',
      bgColor: 'bg-yellow-100',
      textColor: 'text-yellow-800'
    },
  };
  
  return statusConfig[status] || { 
    label: status, 
    variant: 'outline' as const,
    color: 'gray',
    bgColor: 'bg-gray-100',
    textColor: 'text-gray-800'
  };
}

/**
 * 使用者建立請求
 */
export interface CreateUserRequest {
  username: string;
  email: string;
  password: string;
  password_confirmation: string;
  first_name: string;
  last_name: string;
  gender?: Gender;
  phone?: string;
  birth_date?: string;
  role: UserRole;
  status: UserStatus;
  store_id: number;
}

/**
 * 使用者更新請求
 */
export interface UpdateUserRequest {
  username?: string;
  email?: string;
  first_name?: string;
  last_name?: string;
  gender?: Gender;
  phone?: string;
  birth_date?: string;
  role?: UserRole;
  status?: UserStatus;
  store_id?: number;
}

/**
 * 密碼變更請求
 */
export interface ChangePasswordRequest {
  current_password: string;
  password: string;
  password_confirmation: string;
}

/**
 * 2FA 確認請求
 */
export interface TwoFactorConfirmRequest {
  code: string;
  secret?: string;
}

/**
 * 2FA 驗證請求
 */
export interface TwoFactorVerifyRequest {
  code: string;
}

/**
 * 登入請求
 */
export interface LoginRequest {
  email: string;
  password: string;
  two_factor_code?: string;
  remember?: boolean;
}

/**
 * 批次操作請求
 */
export interface BatchOperationRequest {
  user_ids: number[];
  action: 'activate' | 'deactivate' | 'suspend' | 'delete';
  reason?: string;
}

/**
 * 使用者活動記錄
 */
export interface UserActivity {
  id: number;
  user_id: number;
  action: string;
  description: string;
  ip_address?: string;
  user_agent?: string;
  properties?: Record<string, any>;
  created_at: string;
  
  // 關聯資料
  user?: User;
}

/**
 * 權限資訊
 */
export interface Permission {
  name: string;
  display_name: string;
  description?: string;
  guard_name: string;
  created_at: string;
  updated_at: string;
}

/**
 * 角色資訊
 */
export interface Role {
  id: number;
  name: string;
  display_name: string;
  description?: string;
  guard_name: string;
  permissions: Permission[];
  users_count?: number;
  created_at: string;
  updated_at: string;
} 