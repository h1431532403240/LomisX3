/**
 * @file 🎛️ 商品分類 Hook
 * @description 提供完整的分類管理功能，採用 TanStack Query 進行資料管理。
 */
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import productCategoryService from '@/services/product-categories';
import type {
  ProductCategory,
  PaginatedCategories,
  CategoryTree,
  CategoryListParams,
  CreateCategoryRequest,
  UpdateCategoryRequest,
  SortCategoriesRequest,
  BatchStatusCategoriesRequest,
  CategoryResponse,
  SuccessResponse,
  CategoryStatsResponse
} from '@/services/product-categories';

// =================================================================================
// QUERY KEYS
// =================================================================================

export const CATEGORY_KEYS = {
  all: ['categories'] as const,
  lists: () => [...CATEGORY_KEYS.all, 'list'] as const,
  list: (params: CategoryListParams) => [...CATEGORY_KEYS.lists(), params] as const,
  trees: () => [...CATEGORY_KEYS.all, 'tree'] as const,
  tree: (activeOnly: boolean) => [...CATEGORY_KEYS.trees(), { activeOnly }] as const,
  details: () => [...CATEGORY_KEYS.all, 'detail'] as const,
  detail: (id: number) => [...CATEGORY_KEYS.details(), id] as const,
  stats: () => [...CATEGORY_KEYS.all, 'stats'] as const,
} as const;


// =================================================================================
// QUERY HOOKS
// =================================================================================

/**
 * 查詢分類列表 (分頁)
 */
export function useCategories(params: Omit<CategoryListParams, 'parent_id'> & { parent_id?: number | 'root' } = {}) {
  const queryParams: CategoryListParams = { 
    ...params,
    parent_id: params.parent_id === 'root' ? undefined : params.parent_id
  };

  return useQuery<PaginatedCategories, Error>({
    queryKey: CATEGORY_KEYS.list(queryParams),
    queryFn: () => productCategoryService.getList(queryParams),
    staleTime: 5 * 60 * 1000, // 5分鐘
  });
}

/**
 * 查詢分類樹狀結構
 */
export function useCategoryTree(activeOnly = true) {
  return useQuery<CategoryTree, Error>({
    queryKey: CATEGORY_KEYS.tree(activeOnly),
    queryFn: () => productCategoryService.getTree(activeOnly),
    staleTime: 10 * 60 * 1000, // 10分鐘
  });
}

/**
 * 查詢單個分類
 */
export function useCategory(id?: number) {
  return useQuery<ProductCategory, Error>({
    queryKey: CATEGORY_KEYS.detail(id!),
    queryFn: () => productCategoryService.getById(id!),
    enabled: !!id,
  });
}

/**
 * 查詢分類統計
 */
export function useCategoryStatistics() {
    return useQuery<CategoryStatsResponse, Error>({
        queryKey: CATEGORY_KEYS.stats(),
        queryFn: productCategoryService.getStatistics,
    });
}


// =================================================================================
// MUTATION HOOKS
// =================================================================================

/**
 * ➕ 建立分類
 */
export function useCreateCategory() {
  const queryClient = useQueryClient();
  return useMutation<CategoryResponse, Error, CreateCategoryRequest>({
    mutationFn: productCategoryService.create,
    onSuccess: (data) => {
      toast.success(`分類 "${data.data?.name ?? ''}" 已成功建立`);
      queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.all });
    },
    onError: (error) => {
      toast.error('建立分類失敗', { description: error.message });
    },
  });
}

/**
 * ✏️ 更新分類
 */
export function useUpdateCategory() {
  const queryClient = useQueryClient();
  return useMutation<CategoryResponse, Error, { id: number; data: UpdateCategoryRequest }>({
    mutationFn: ({ id, data }) => productCategoryService.update(id, data),
    onSuccess: (data) => {
      toast.success(`分類 "${data.data?.name ?? ''}" 已成功更新`);
      queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.lists() });
      queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.trees() });
      if (data.data?.id) {
        queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.detail(data.data.id) });
      }
    },
    onError: (error) => {
      toast.error('更新分類失敗', { description: error.message });
    },
  });
}

/**
 * ❌ 刪除分類
 */
export function useDeleteCategory() {
  const queryClient = useQueryClient();
  return useMutation<void, Error, number>({
    mutationFn: productCategoryService.delete,
    onSuccess: () => {
      toast.success('分類已成功刪除');
      queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.all });
    },
    onError: (error) => {
      toast.error('刪除分類失敗', { description: error.message });
    },
  });
}

/**
 * 🔄 批次更新狀態
 */
export function useBatchUpdateStatus() {
  const queryClient = useQueryClient();
  return useMutation<SuccessResponse, Error, BatchStatusCategoriesRequest>({
    mutationFn: productCategoryService.batchUpdateStatus,
    onSuccess: () => {
      toast.success('分類狀態已批次更新');
      queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.all });
    },
    onError: (error) => {
      toast.error('批次更新狀態失敗', { description: error.message });
    },
  });
}

/**
 * 🗑️ 批次刪除
 */
export function useBatchDelete() {
  const queryClient = useQueryClient();
  return useMutation<SuccessResponse, Error, number[]>({
    mutationFn: productCategoryService.batchDelete,
    onSuccess: () => {
      toast.success('分類已批次刪除');
      queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.all });
    },
    onError: (error) => {
      toast.error('批次刪除失敗', { description: error.message });
    },
  });
}

/**
 * ↕️ 更新排序
 */
export function useSortCategories() {
  const queryClient = useQueryClient();
  return useMutation<SuccessResponse, Error, SortCategoriesRequest>({
    mutationFn: productCategoryService.updateSort,
    onSuccess: () => {
      toast.success('分類排序已更新');
      queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.lists() });
      queryClient.invalidateQueries({ queryKey: CATEGORY_KEYS.trees() });
    },
    onError: (error) => {
      toast.error('更新排序失敗', { description: error.message });
    },
  });
} 