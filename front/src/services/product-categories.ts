/**
 * @file 商品分類服務
 * @description 此服務封裝了與後端商品分類 API 的所有互動。
 */
import { openapi } from '@/lib/openapi-client';
import type { paths, components } from '@/types/api';

// =================================================================================
// TYPE DEFINITIONS
// =================================================================================

export type ProductCategory = components['schemas']['ProductCategory'];
export type PaginatedCategories = paths['/api/product-categories']['get']['responses']['200']['content']['application/json'];
export type CategoryTree = paths['/api/product-categories/tree']['get']['responses']['200']['content']['application/json']['data'];
export type CategoryListParams = paths['/api/product-categories']['get']['parameters']['query'];
export type CreateCategoryRequest = components['schemas']['CreateCategoryRequest'];
export type UpdateCategoryRequest = components['schemas']['UpdateCategoryRequest'];
export type SortCategoriesRequest = components['schemas']['SortCategoriesRequest'];
export type BatchStatusCategoriesRequest = components['schemas']['BatchStatusCategoriesRequest'];
export type CategoryResponse = paths['/api/product-categories/{id}']['get']['responses']['200']['content']['application/json'];
export type SuccessResponse = components['schemas']['SuccessResponse'];
export type BreadcrumbsResponse = paths['/api/product-categories/{id}/breadcrumbs']['get']['responses']['200']['content']['application/json'];
export type CategoryStatsResponse = paths['/api/product-categories/statistics']['get']['responses']['200']['content']['application/json'];

// =================================================================================
// API SERVICE FUNCTIONS
// =================================================================================

const productCategoryService = {
  /**
   * 獲取商品分類列表 (分頁)。
   */
  async getList(params: CategoryListParams): Promise<PaginatedCategories> {
    const { data, error } = await openapi.GET('/api/product-categories', { params: { query: params } });
    if (error) throw new Error('無法獲取商品分類列表');
    return data;
  },

  /**
   * 獲取完整的商品分類樹。
   */
  async getTree(onlyActive = false): Promise<NonNullable<CategoryTree>> {
    const { data, error } = await openapi.GET('/api/product-categories/tree', {
      params: { query: { status: onlyActive } },
    });
    if (error || !data?.data) throw new Error('無法獲取商品分類樹');
    return data.data;
  },

  /**
   * 獲取單一商品分類資訊。
   */
  async getById(id: number): Promise<ProductCategory> {
    const { data, error } = await openapi.GET('/api/product-categories/{id}', {
      params: { path: { id } },
    });
    if (error || !data?.data) throw new Error(`無法獲取 ID 為 ${id} 的商品分類`);
    return data.data;
  },
  
  /**
   * 建立新商品分類。
   */
  async create(categoryData: CreateCategoryRequest): Promise<CategoryResponse> {
    const { data, error } = await openapi.POST('/api/product-categories', { body: categoryData });
    if (error) throw new Error('建立商品分類失敗');
    return data;
  },

  /**
   * 更新商品分類。
   */
  async update(id: number, categoryData: UpdateCategoryRequest): Promise<CategoryResponse> {
    const { data, error } = await openapi.PUT('/api/product-categories/{id}', {
      params: { path: { id } },
      body: categoryData,
    });
    if (error) throw new Error('更新商品分類失敗');
    return data;
  },

  /**
   * 刪除商品分類。
   */
  async delete(id: number): Promise<void> {
    const { error } = await openapi.DELETE('/api/product-categories/{id}', {
      params: { path: { id } },
    });
    if (error) throw new Error('刪除商品分類失敗');
  },

  /**
   * 批次更新商品分類狀態。
   */
  async batchUpdateStatus(updateData: BatchStatusCategoriesRequest): Promise<SuccessResponse> {
    const { data, error } = await openapi.PATCH('/api/product-categories/batch/status', {
      body: updateData,
    });
    if (error) throw new Error('批次更新商品分類狀態失敗');
    return data;
  },

  /**
   * 批次刪除商品分類。
   */
  async batchDelete(ids: number[]): Promise<SuccessResponse> {
    const { data, error } = await openapi.POST('/api/product-categories/batch/delete', {
      body: { ids },
    });
    if (error) throw new Error('批次刪除商品分類失敗');
    return data;
  },

  /**
   * 更新商品分類排序。
   */
  async updateSort(sortData: SortCategoriesRequest): Promise<SuccessResponse> {
      const { data, error } = await openapi.PATCH('/api/product-categories/sort', {
          body: sortData
      });
      if (error) throw new Error('更新商品分類排序失敗');
      return data;
  },

  /**
   * 取得指定分類的麵包屑導航。
   */
  async getBreadcrumbs(id: number): Promise<BreadcrumbsResponse> {
    const { data, error } = await openapi.GET('/api/product-categories/{id}/breadcrumbs', {
      params: { path: { id } },
    });
    if (error) throw new Error('無法獲取麵包屑');
    return data;
  },

  /**
   * 獲取一個分類的所有後代（樹狀）。
   */
  async getDescendants(id: number): Promise<NonNullable<CategoryTree>> {
    const { data, error } = await openapi.GET('/api/product-categories/{id}/descendants', {
      params: { path: { id } },
    });
    if (error || !data?.data) throw new Error('無法獲取後代分類');
    return data.data;
  },

  /**
   * 獲取商品分類的統計數據。
   */
  async getStatistics(): Promise<CategoryStatsResponse> {
    const { data, error } = await openapi.GET('/api/product-categories/statistics', {});
    if (error) throw new Error('無法獲取分類統計數據');
    return data;
  },
};

export default productCategoryService; 