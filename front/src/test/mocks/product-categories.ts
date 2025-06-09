import type { components } from '@/types/api';

type PaginatedCategoriesResponse = components['schemas']['PaginatedCategoriesResponse'];

export const mockPaginatedCategories: PaginatedCategoriesResponse = {
  data: [
    {
      id: 1,
      name: '電子產品',
      slug: 'electronics',
      description: '所有電子產品',
      parent_id: null,
      status: true,
      position: 1,
      depth: 0,
      children_count: 1,
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
      children: [
        {
          id: 2,
          name: '手機',
          slug: 'smartphones',
          description: '最新款智能手機',
          parent_id: 1,
          status: true,
          position: 1,
          depth: 1,
          children_count: 0,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-01T00:00:00Z',
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
  },
}; 