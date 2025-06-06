/**
 * @file 使用者管理與認證服務
 * @description 封裝所有與使用者和認證相關的 API 互動。
 */
import apiClient from '@/lib/openapi-client';
import type { paths, components } from '@/types/api';

// =================================================================================
// TYPE DEFINITIONS
// =================================================================================

// User CRUD types
export type User = components['schemas']['User'];
export type PaginatedUsers = paths['/api/users']['get']['responses']['200']['content']['application/json'];
export type UserListParams = paths['/api/users']['get']['parameters']['query'];
export type CreateUserRequest = components['schemas']['CreateUserRequest'];
export type UpdateUserRequest = components['schemas']['UpdateUserRequest'];
export type UserResponse = paths['/api/users/{id}']['get']['responses']['200']['content']['application/json'];
export type BatchStatusUserRequest = components['schemas']['BatchStatusUserRequest'];

// Auth types
export type LoginRequest = components['schemas']['LoginRequest'];
export type LoginResponse = paths['/api/auth/login']['post']['responses']['200']['content']['application/json'];
export type AuthenticatedUserResponse = paths['/api/auth/me']['get']['responses']['200']['content']['application/json'];

// Generic types
export type SuccessResponse = components['schemas']['SuccessResponse'];

// =================================================================================
// API SERVICE
// =================================================================================

const userService = {
  /**
   * 登入
   */
  async login(credentials: LoginRequest): Promise<LoginResponse> {
    const { data, error } = await apiClient.POST('/api/auth/login', { body: credentials });
    if (error) throw new Error('登入失敗，請檢查您的帳號或密碼。');
    if (data?.token) {
      localStorage.setItem('auth_token', data.token);
    }
    return data;
  },

  /**
   * 登出
   */
  async logout(): Promise<void> {
    // 即使後端呼叫失敗，前端也應清除 token
    localStorage.removeItem('auth_token');
    const { error } = await apiClient.POST('/api/auth/logout', {});
    if (error) {
      // 在此處可以選擇性地記錄錯誤，但不必阻礙使用者登出流程
      console.error('Logout API call failed:', error);
    }
  },

  /**
   * 獲取當前登入的使用者資訊
   */
  async getCurrentUser(): Promise<User> {
    const { data, error } = await apiClient.GET('/api/auth/me', {});
    if (error || !data?.data) throw new Error('無法獲取當前使用者資訊。');
    return data.data;
  },

  /**
   * 獲取使用者列表 (分頁)
   */
  async getUsers(params: UserListParams): Promise<PaginatedUsers> {
    const { data, error } = await apiClient.GET('/api/users', { params: { query: params } });
    if (error) throw new Error('無法獲取使用者列表。');
    return data;
  },

  /**
   * 獲取單一使用者
   */
  async getUser(userId: number): Promise<UserResponse> {
    const { data, error } = await apiClient.GET('/api/users/{id}', { params: { path: { id: userId } } });
    if (error) throw new Error(`無法獲取 ID 為 ${userId} 的使用者。`);
    return data;
  },

  /**
   * 建立新使用者
   */
  async createUser(userData: CreateUserRequest): Promise<UserResponse> {
    const { data, error } = await apiClient.POST('/api/users', { body: userData });
    if (error) throw new Error('建立使用者失敗。');
    return data;
  },

  /**
   * 更新使用者
   */
  async updateUser(userId: number, updateData: UpdateUserRequest): Promise<UserResponse> {
    const { data, error } = await apiClient.PUT('/api/users/{id}', {
      params: { path: { id: userId } },
      body: updateData,
    });
    if (error) throw new Error('更新使用者失敗。');
    return data;
  },

  /**
   * 刪除使用者
   */
  async deleteUser(userId: number): Promise<void> {
    const { error } = await apiClient.DELETE('/api/users/{id}', { params: { path: { id: userId } } });
    if (error) throw new Error('刪除使用者失敗。');
  },

  /**
   * 批次更新使用者狀態
   */
  async batchUpdateUserStatus(updateData: BatchStatusUserRequest): Promise<SuccessResponse> {
    const { data, error } = await apiClient.PATCH('/api/users/batch/status', { body: updateData });
    if (error) throw new Error('批次更新使用者狀態失敗。');
    return data;
  },

  /**
   * 重設使用者密碼
   */
  async resetPassword(userId: number): Promise<SuccessResponse> {
    const { data, error } = await apiClient.POST('/api/users/{id}/reset-password', {
      params: { path: { id: userId } },
    });
    if (error) throw new Error('重設密碼失敗。');
    return data;
  },
};

export default userService; 