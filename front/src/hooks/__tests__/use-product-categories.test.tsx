/**
 * 商品分類 Hooks 單元測試
 * 測試查詢、變更和錯誤處理功能
 */
import { describe, it, expect, afterEach, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';

import { useCategories, CATEGORY_KEYS } from '../use-product-categories';
import type { components } from '@/types/api';

// 模擬 API 客戶端
vi.mock('@/lib/openapi-client', () => ({
  openapi: {
    GET: vi.fn(),
  },
  safeApiCall: vi.fn((fn) => fn()),
}));

type ProductCategory = components['schemas']['ProductCategory'];

// 引入 openapi 以便在測試中使用 Mock
import { openapi } from '@/lib/openapi-client';

describe('useCategories Hook', () => {

  afterEach(() => {
    vi.clearAllMocks();
  });

  it('應該成功獲取分類列表', async () => {
    // 準備假數據
    const mockPaginatedCategories = { 
      data: [
        {
          id: 1,
          name: '電子產品',
          slug: 'electronics',
          description: '所有電子產品',
          parent_id: null,
          status: true,
          children: [
            {
              id: 2,
              name: '手機',
              slug: 'smartphones',
              description: '最新款智能手機',
              parent_id: 1,
              status: true,
              children: [],
            },
          ],
        },
      ],
      links: {
        first: 'http://localhost/api/product-categories?page=1',
        last: 'http://localhost/api/product-categories?page=1',
        prev: null,
        next: null,
      },
      meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        path: 'http://localhost/api/product-categories',
        per_page: 10,
        to: 1,
        total: 1,
        links: [],
      },
    };

    // 設置 Mock
    const mockedOpenApiGet = vi.mocked(openapi.GET);
    mockedOpenApiGet.mockResolvedValue({
      data: mockPaginatedCategories,
      error: null,
      response: new Response(JSON.stringify(mockPaginatedCategories), { status: 200 }),
    });

    const queryClient = new QueryClient({
      defaultOptions: {
        queries: {
          retry: false, // 測試時禁用重試
        },
      },
    });

    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );

    const params = { page: 1, per_page: 10 };
    
    const { result } = renderHook(() => useCategories(params), { wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toEqual(mockPaginatedCategories);
    expect(queryClient.getQueryData(CATEGORY_KEYS.list(params))).toBeDefined();
  });
}); 