/**
 * 當 OpenAPI 文檔無法使用時的備用型別定義
 * 確保前端開發不會因為後端文檔問題而中斷
 */

// 🆕 商品分類基礎型別
export interface ProductCategory {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  position: number;
  status: boolean;
  depth: number;
  description?: string;
  meta_title?: string;
  meta_description?: string;
  path: string;
  has_children: boolean;
  full_path: string;
  children_count: number;
  created_at: string;
  updated_at: string;
  children?: ProductCategory[];
}

// 🆕 API 回應基礎結構
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
  };
}

// 🆕 分類查詢參數
export interface CategoryListParams {
  search?: string;
  status?: boolean;
  parent_id?: number | 'root';
  per_page?: number;
  page?: number;
  sort?: 'name' | 'position' | 'created_at';
  direction?: 'asc' | 'desc';
}

// 🆕 創建分類請求
export interface CreateCategoryRequest {
  name: string;
  slug?: string;
  parent_id?: number | null;
  status?: boolean;
  position?: number;
  description?: string;
  meta_title?: string;
  meta_description?: string;
}

// 🆕 更新分類請求
export interface UpdateCategoryRequest {
  name?: string;
  slug?: string;
  parent_id?: number | null;
  status?: boolean;
  position?: number;
  description?: string;
  meta_title?: string;
  meta_description?: string;
}

// 🆕 排序請求
export interface SortOrderRequest {
  items: {
    id: number;
    position: number;
    parent_id?: number | null;
  }[];
}

// 🆕 批次狀態更新請求
export interface BatchStatusRequest {
  ids: number[];
  status: boolean;
}

// 🆕 批次刪除請求
export interface BatchDeleteRequest {
  ids: number[];
}

// 🆕 基礎的 API 路徑型別
export interface paths {
  '/api/product-categories': {
    get: {
      parameters: {
        query?: CategoryListParams;
      };
      responses: {
        200: {
          content: {
            'application/json': PaginatedResponse<ProductCategory>;
          };
        };
        400: {
          content: {
            'application/json': {
              success: false;
              message: string;
              errors?: Record<string, string[]>;
            };
          };
        };
      };
    };
    post: {
      requestBody: {
        content: {
          'application/json': CreateCategoryRequest;
        };
      };
      responses: {
        201: {
          content: {
            'application/json': ApiResponse<ProductCategory>;
          };
        };
        422: {
          content: {
            'application/json': {
              success: false;
              message: string;
              errors: Record<string, string[]>;
            };
          };
        };
      };
    };
  };
  '/api/product-categories/tree': {
    get: {
      parameters: {
        query?: {
          active_only?: boolean;
        };
      };
      responses: {
        200: {
          content: {
            'application/json': ApiResponse<ProductCategory[]>;
          };
        };
      };
    };
  };
  '/api/product-categories/{id}': {
    get: {
      parameters: {
        path: {
          id: number;
        };
      };
      responses: {
        200: {
          content: {
            'application/json': ApiResponse<ProductCategory>;
          };
        };
        404: {
          content: {
            'application/json': {
              success: false;
              message: string;
            };
          };
        };
      };
    };
    put: {
      parameters: {
        path: {
          id: number;
        };
      };
      requestBody: {
        content: {
          'application/json': UpdateCategoryRequest;
        };
      };
      responses: {
        200: {
          content: {
            'application/json': ApiResponse<ProductCategory>;
          };
        };
        404: {
          content: {
            'application/json': {
              success: false;
              message: string;
            };
          };
        };
        422: {
          content: {
            'application/json': {
              success: false;
              message: string;
              errors: Record<string, string[]>;
            };
          };
        };
      };
    };
    delete: {
      parameters: {
        path: {
          id: number;
        };
      };
      responses: {
        200: {
          content: {
            'application/json': {
              success: true;
              message: string;
            };
          };
        };
        404: {
          content: {
            'application/json': {
              success: false;
              message: string;
            };
          };
        };
        409: {
          content: {
            'application/json': {
              success: false;
              message: string;
            };
          };
        };
      };
    };
  };
  '/api/product-categories/sort-order': {
    put: {
      requestBody: {
        content: {
          'application/json': SortOrderRequest;
        };
      };
      responses: {
        200: {
          content: {
            'application/json': {
              success: true;
              message: string;
            };
          };
        };
        422: {
          content: {
            'application/json': {
              success: false;
              message: string;
              errors: Record<string, string[]>;
            };
          };
        };
      };
    };
  };
  '/api/product-categories/batch-status': {
    put: {
      requestBody: {
        content: {
          'application/json': BatchStatusRequest;
        };
      };
      responses: {
        200: {
          content: {
            'application/json': {
              success: true;
              message: string;
              updated_count: number;
            };
          };
        };
        422: {
          content: {
            'application/json': {
              success: false;
              message: string;
              errors: Record<string, string[]>;
            };
          };
        };
      };
    };
  };
  '/api/product-categories/batch-delete': {
    delete: {
      requestBody: {
        content: {
          'application/json': BatchDeleteRequest;
        };
      };
      responses: {
        200: {
          content: {
            'application/json': {
              success: true;
              message: string;
              deleted_count: number;
            };
          };
        };
        422: {
          content: {
            'application/json': {
              success: false;
              message: string;
              errors: Record<string, string[]>;
            };
          };
        };
      };
    };
  };
}

// 🆕 路徑參數型別
export type PathParams = {
  [K in keyof paths]: paths[K] extends { parameters: { path: infer P } } ? P : never;
};

// 🆕 查詢參數型別
export type QueryParams = {
  [K in keyof paths]: paths[K] extends { parameters: { query: infer Q } } ? Q : never;
};

// 🆕 請求體型別
export type RequestBody = {
  [K in keyof paths]: paths[K] extends { requestBody: { content: { 'application/json': infer B } } } ? B : never;
};

// 🆕 響應型別
export type ResponseData = {
  [K in keyof paths]: paths[K] extends { responses: { 200: { content: { 'application/json': infer R } } } } ? R : never;
};

// 🆕 錯誤響應型別
export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
}

// 🆕 components 型別 (用於相容性)
export interface components {
  schemas: {
    ProductCategory: ProductCategory;
    ApiResponse: ApiResponse<ProductCategory>;
    PaginatedResponse: PaginatedResponse<ProductCategory[]>;
    CategoryListParams: CategoryListParams;
    CreateCategoryRequest: CreateCategoryRequest;
    UpdateCategoryRequest: UpdateCategoryRequest;
    SortOrderRequest: SortOrderRequest;
    BatchStatusRequest: BatchStatusRequest;
    BatchDeleteRequest: BatchDeleteRequest;
    ApiError: ApiError;
  };
} 