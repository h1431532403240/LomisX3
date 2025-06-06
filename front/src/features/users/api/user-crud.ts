/**
 * @file User CRUD (Create, Read, Update, Delete) operations and hooks.
 * This file provides a set of hooks for performing CRUD operations on users,
 * powered by TanStack Query. It handles API calls, caching, and optimistic updates.
 *
 * @module features/users/api/user-crud
 */
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { openapi } from '@/lib/openapi-client';
import type { paths, components } from '@/types/api';
import { toast } from 'sonner';
import { userQueryKeys } from './use-users';

// =================================================================================
// TYPE DEFINITIONS
// =================================================================================

/**
 * @description Represents a single User object, derived from the API schema.
 * Using NonNullable to ensure the data array is not undefined, then getting the type of one of its elements.
 */
export type User = NonNullable<paths['/api/users']['get']['responses']['200']['content']['application/json']['data']>[number];

/**
 * @description The full, paginated response for the user list.
 */
export type PaginatedUsers = paths['/api/users']['get']['responses']['200']['content']['application/json'];

/**
 * @description Type for the query parameters used when fetching a list of users.
 */
export type UserQueryParams = paths['/api/users']['get']['parameters']['query'];

/**
 * @description Type for creating a new user.
 */
export type CreateUserRequest = components['schemas']['CreateUserRequest'];
/**
 * @description Type for updating an existing user.
 */
export type UpdateUserRequest = components['schemas']['UpdateUserRequest'];
/**
 * @description Type for batch updating user statuses.
 */
export type BatchStatusUpdateRequest = components['schemas']['BatchStatusUserRequest'];
/**
 * @description The API response for a single user.
 */
type UserResponse = paths['/api/users/{id}']['get']['responses']['200']['content']['application/json'];


// =================================================================================
// API FUNCTIONS
// =================================================================================

/**
 * @description Fetches a paginated list of users.
 * @param params - Query parameters for filtering, sorting, and pagination.
 * @returns {Promise<PaginatedUsers>} A promise that resolves to the paginated user list.
 */
const getUsers = async (params: UserQueryParams): Promise<PaginatedUsers> => {
  const { data, error } = await openapi.GET('/api/users', { params: { query: params } });
  if (error) {
    throw new Error('Failed to fetch users.');
  }
  return data;
};

/**
 * @description Fetches a single user by their ID.
 * @param userId - The ID of the user to fetch.
 * @returns {Promise<User>} A promise that resolves to the user object.
 */
const getUser = async (userId: number): Promise<User> => {
  const { data, error } = await openapi.GET('/api/users/{id}', {
    params: { path: { id: userId } },
  });
  if (error || !data?.data) {
    throw new Error(`Failed to fetch user with ID ${userId}.`);
  }
  return data.data as User;
};


// =================================================================================
// HOOKS
// =================================================================================

/**
 * @description Hook to fetch a paginated list of users.
 * @param params - Query parameters for filtering, sorting, and pagination.
 * @returns {QueryResult<PaginatedUsers, Error>} The result of the query.
 */
export const useUsers = (params: UserQueryParams) => {
  return useQuery({
    queryKey: userQueryKeys.list(params),
    queryFn: () => getUsers(params),
  });
};

/**
 * @description Hook to fetch a single user.
 * @param userId - The ID of the user.
 * @returns {QueryResult<User, Error>} The result of the query.
 */
export const useUser = (userId: number) => {
  return useQuery({
    queryKey: userQueryKeys.detail(userId),
    queryFn: () => getUser(userId),
    enabled: !!userId,
  });
};

/**
 * @description Hook for creating a new user.
 * Provides `mutate` function and tracks mutation state.
 * @returns {UseMutationResult<UserResponse, Error, CreateUserRequest>} The result of the mutation.
 */
export const useCreateUser = () => {
  const queryClient = useQueryClient();
  return useMutation<UserResponse, Error, CreateUserRequest>({
    mutationFn: async (userData) => {
      const { data, error } = await openapi.POST('/api/users', {
        body: userData,
      });
      if (error) {
        // Here you could parse the error and provide more specific feedback
        throw new Error('Failed to create user.');
      }
      return data;
    },
    onSuccess: () => {
      toast.success('使用者已成功建立');
      // Invalidate user lists to refetch fresh data
      queryClient.invalidateQueries({ queryKey: userQueryKeys.lists() });
    },
    onError: (error) => {
      toast.error('建立使用者失敗', {
        description: error.message,
      });
    },
  });
};

/**
 * @description Hook for updating an existing user.
 * @returns {UseMutationResult<UserResponse, Error, { id: number; data: UpdateUserRequest }>} The result of the mutation.
 */
export const useUpdateUser = () => {
  const queryClient = useQueryClient();
  return useMutation<UserResponse, Error, { id: number; data: UpdateUserRequest }>({
    mutationFn: async ({ id, data: updateData }) => {
      const { data, error } = await openapi.PUT('/api/users/{id}', {
        params: { path: { id } },
        body: updateData,
      });
      if (error) {
        throw new Error('Failed to update user.');
      }
      return data;
    },
    onSuccess: (data) => {
      const updatedUser = data.data;
      toast.success(`使用者 ${updatedUser?.name ?? ''} 已成功更新`);
      // Invalidate both list and detail queries
      queryClient.invalidateQueries({ queryKey: userQueryKeys.lists() });
      if (updatedUser?.id) {
        queryClient.invalidateQueries({ queryKey: userQueryKeys.detail(updatedUser.id) });
      }
    },
    onError: (error) => {
      toast.error('更新使用者失敗', {
        description: error.message,
      });
    },
  });
};

/**
 * @description Hook for deleting a user.
 * @returns {UseMutationResult<void, Error, number>} The result of the mutation.
 */
export const useDeleteUser = () => {
  const queryClient = useQueryClient();
  return useMutation<void, Error, number>({
    mutationFn: async (userId) => {
      const { error } = await openapi.DELETE('/api/users/{id}', {
        params: { path: { id: userId } },
      });
      if (error) {
        throw new Error('Failed to delete user.');
      }
    },
    onSuccess: () => {
      toast.success('使用者已成功刪除');
      queryClient.invalidateQueries({ queryKey: userQueryKeys.lists() });
    },
    onError: (error) => {
      toast.error('刪除使用者失敗', {
        description: error.message,
      });
    },
  });
};

/**
 * @description Hook for batch updating user statuses.
 * @returns {UseMutationResult<components['schemas']['SuccessResponse'], Error, BatchStatusUpdateRequest>} The result of the mutation.
 */
export const useBatchUpdateUserStatus = () => {
  const queryClient = useQueryClient();
  return useMutation<components['schemas']['SuccessResponse'], Error, BatchStatusUpdateRequest>({
    mutationFn: async (updateData) => {
      const { data, error } = await openapi.PATCH('/api/users/batch/status', {
        body: updateData,
      });
      if (error) {
        throw new Error('Failed to batch update user statuses.');
      }
      return data;
    },
    onSuccess: () => {
      toast.success('使用者狀態已批次更新');
      queryClient.invalidateQueries({ queryKey: userQueryKeys.lists() });
    },
    onError: (error) => {
      toast.error('批次更新失敗', {
        description: error.message,
      });
    },
  });
};

/**
 * @description Hook for resetting a user's password.
 * @returns {UseMutationResult<components['schemas']['SuccessResponse'], Error, number>} The result of the mutation.
 */
export const useResetPassword = () => {
  return useMutation<components['schemas']['SuccessResponse'], Error, number>({
    mutationFn: async (userId: number) => {
        const { data, error } = await openapi.POST('/api/users/{id}/reset-password', {
            params: { path: { id: userId } },
        });
        if (error) {
            throw new Error('Failed to reset password.');
        }
        return data;
    },
    onSuccess: () => {
      toast.success('密碼重設郵件已成功寄出');
    },
    onError: (error) => {
      toast.error('密碼重設失敗', {
        description: error.message,
      });
    },
  });
}; 