/**
 * MSW API 模擬處理器
 * 提供商品分類相關的假資料和行為模擬
 */
import { http, HttpResponse } from 'msw';

// 🆕 API 基礎 URL
const API_BASE_URL = 'http://localhost:8000';

// 🆕 測試用的簡化分類型別
interface TestProductCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  parent_id: number | null;
  sort_order: number;
  depth: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  children?: TestProductCategory[];
}

// 🆕 模擬分類資料
const mockCategories: TestProductCategory[] = [
  {
    id: 1,
    name: '電子產品',
    slug: 'electronics',
    description: '各類電子設備和配件',
    parent_id: null,
    sort_order: 1,
    depth: 0,
    is_active: true,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  },
  {
    id: 2,
    name: '智慧型手機',
    slug: 'smartphones',
    description: '各品牌智慧型手機',
    parent_id: 1,
    sort_order: 1,
    depth: 1,
    is_active: true,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  },
  {
    id: 3,
    name: '筆記型電腦',
    slug: 'laptops',
    description: '各類筆記型電腦',
    parent_id: 1,
    sort_order: 2,
    depth: 1,
    is_active: true,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  },
  {
    id: 4,
    name: '服飾',
    slug: 'clothing',
    description: '各類服裝和配件',
    parent_id: null,
    sort_order: 2,
    depth: 0,
    is_active: true,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  },
  {
    id: 5,
    name: '家電',
    slug: 'appliances',
    description: '家用電器設備',
    parent_id: null,
    sort_order: 3,
    depth: 0,
    is_active: false,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  },
];

// 🆕 API 處理器
export const categoryHandlers = [
  // 取得分類清單
  http.get(`${API_BASE_URL}/api/product-categories`, ({ request }) => {
    const url = new URL(request.url);
    const search = url.searchParams.get('search');
    const status = url.searchParams.get('status');
    const page = parseInt(url.searchParams.get('page') ?? '1');
    const perPage = parseInt(url.searchParams.get('per_page') ?? '10');

    let filteredCategories = [...mockCategories];

    // 搜尋篩選
    if (search) {
      filteredCategories = filteredCategories.filter(cat =>
        cat.name.toLowerCase().includes(search.toLowerCase()) ??
        (cat.description?.toLowerCase().includes(search.toLowerCase()))
      );
    }

    // 狀態篩選
    if (status !== null) {
      const isActive = status === 'true';
      filteredCategories = filteredCategories.filter(cat => cat.is_active === isActive);
    }

    // 分頁
    const startIndex = (page - 1) * perPage;
    const endIndex = startIndex + perPage;
    const paginatedCategories = filteredCategories.slice(startIndex, endIndex);

    return HttpResponse.json({
      data: paginatedCategories,
      meta: {
        current_page: page,
        per_page: perPage,
        total: filteredCategories.length,
        last_page: Math.ceil(filteredCategories.length / perPage),
      },
    });
  }),

  // 取得分類樹狀結構
  http.get(`${API_BASE_URL}/api/product-categories/tree`, ({ request }) => {
    const url = new URL(request.url);
    const onlyActive = url.searchParams.get('only_active') === 'true';

    let categories = [...mockCategories];
    if (onlyActive) {
      categories = categories.filter(cat => cat.is_active);
    }

    // 建立樹狀結構
    const rootCategories = categories.filter(cat => cat.parent_id === null);
    const tree = rootCategories.map(root => ({
      ...root,
      children: categories.filter(cat => cat.parent_id === root.id),
    }));

    return HttpResponse.json({
      data: tree,
    });
  }),

  // 建立分類
  http.post(`${API_BASE_URL}/api/product-categories`, async ({ request }) => {
    const body = await request.json() as any;
    
    // 模擬驗證錯誤
    if (!body.name) {
      return HttpResponse.json(
        {
          message: '驗證失敗',
          errors: {
            name: ['分類名稱為必填項目'],
          },
        },
        { status: 422 }
      );
    }

    const newCategory: TestProductCategory = {
      id: Math.max(...mockCategories.map(c => c.id)) + 1,
      name: body.name,
      slug: body.slug || body.name.toLowerCase().replace(/\s+/g, '-'),
      description: body.description || '',
      parent_id: body.parent_id || null,
      sort_order: body.sort_order || 1,
      depth: body.parent_id ? 1 : 0,
      is_active: body.is_active ?? true,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    mockCategories.push(newCategory);

    return HttpResponse.json({
      data: newCategory,
    }, { status: 201 });
  }),
];

// 🆕 導出所有處理器
export const handlers = categoryHandlers; 