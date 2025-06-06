/**
 * 商品分類 Hooks 單元測試
 * 測試查詢、變更和錯誤處理功能
 */
import { describe, it, expect, afterEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import MockAdapter from 'axios-mock-adapter';
import axios from 'axios';
import React from 'react';

import { useCategories, CATEGORY_KEYS } from '../use-product-categories';
import type { components } from '@/types/api';

type ProductCategory = components['schemas']['ProductCategory'];
type PaginatedResponse_ProductCategory_ = {
  data?: ProductCategory[];
  links?: {
      first?: string | null;
      last?: string | null;
      prev?: string | null;
      next?: string | null;
  };
  meta?: {
      current_page?: number;
      from?: number;
      last_page?: number;
      links?: {
          url?: string | null;
          label?: string;
          active?: boolean;
      }[];
      path?: string;
      per_page?: number;
      to?: number;
      total?: number;
  };
};

const mockPaginatedCategories: PaginatedResponse_ProductCategory_ = {
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

// 創建一個 axios 模擬器實例
const mock = new MockAdapter(axios);

describe('useCategories Hook', () => {

  afterEach(() => {
    mock.reset();
  });

  it('應該成功獲取分類列表', async () => {
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
    // 模擬 API 回應
    mock.onGet('/product-categories', { params }).reply(200, mockPaginatedCategories);
    
    const { result } = renderHook(() => useCategories(params), { wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toEqual(mockPaginatedCategories);
    expect(queryClient.getQueryData(CATEGORY_KEYS.list(params))).toBeDefined();
  });
}); 