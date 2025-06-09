<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchStatusProductCategoryRequest;
use App\Http\Requests\SortProductCategoryRequest;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use App\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * 商品分類 API 控制器
 * 
 * 提供完整的商品分類管理功能，包含樹狀結構、層級關係、快取優化等
 * 
 * @group 商品分類管理
 */
class ProductCategoryController extends Controller
{
    /**
     * 建構函式
     */
    public function __construct(
        protected ProductCategoryRepositoryInterface $categoryRepository,
        protected ProductCategoryService $categoryService
    ) {
        // 自動授權資源操作
        $this->authorizeResource(ProductCategory::class, 'product_category');
    }

    /**
     * 取得分類清單
     * 
     * 支援分頁、篩選、搜尋等功能。可通過查詢參數控制返回結果。
     * 
     * @group 商品分類管理
     * 
     * @queryParam search string 搜尋關鍵字（支援名稱、描述） Example: 電子產品
     * @queryParam status boolean 分類狀態篩選 Example: true
     * @queryParam parent_id integer 父分類ID篩選 Example: 1
     * @queryParam depth integer 分類深度篩選 Example: 2
     * @queryParam with_children boolean 是否包含子分類 Example: true
     * @queryParam max_depth integer 最大深度限制 Example: 3
     * @queryParam with_trashed boolean 是否包含已刪除項目 Example: false
     * @queryParam per_page integer 每頁項目數（1-100） Example: 20
     * @queryParam page integer 頁碼 Example: 1
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "電子產品",
     *       "slug": "electronics",
     *       "parent_id": null,
     *       "position": 1,
     *       "status": true,
     *       "depth": 0,
     *       "description": "各類電子產品分類",
     *       "meta_title": "電子產品 | LomisX3",
     *       "meta_description": "電子產品相關商品分類",
     *       "path": "/1/",
     *       "has_children": true,
     *       "full_path": "電子產品",
     *       "children_count": 5,
     *       "created_at": "2025-01-07T10:00:00.000000Z",
     *       "updated_at": "2025-01-07T10:00:00.000000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/product-categories?page=1",
     *     "last": "http://localhost/api/product-categories?page=10",
     *     "prev": null,
     *     "next": "http://localhost/api/product-categories?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 10,
     *     "per_page": 20,
     *     "to": 20,
     *     "total": 200
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "驗證失敗",
     *   "errors": {
     *     "per_page": ["每頁項目數不能超過100"]
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search', 'status', 'parent_id', 'depth',
            'with_children', 'max_depth', 'with_trashed',
        ]);

        $perPage = $request->integer('per_page', 20);

        $categories = $this->categoryRepository->paginate($perPage, $filters);

        return ProductCategoryResource::collection($categories);
    }

    /**
     * 儲存新分類
     * 
     * 創建新的商品分類。系統將自動計算層級深度、生成唯一slug，並觸發快取更新。
     * 
     * @group 商品分類管理
     * 
     * @bodyParam name string required 分類名稱（2-100字元） Example: 智慧型手機
     * @bodyParam slug string 自訂URL別名（可選，系統會自動生成） Example: smartphones
     * @bodyParam parent_id integer 父分類ID（可選） Example: 1
     * @bodyParam position integer 排序位置（可選，預設自動計算） Example: 10
     * @bodyParam status boolean 啟用狀態（預設true） Example: true
     * @bodyParam description string 分類描述（可選） Example: 各種品牌的智慧型手機
     * @bodyParam meta_title string SEO標題（可選，預設使用分類名稱） Example: 智慧型手機 | LomisX3
     * @bodyParam meta_description string SEO描述（可選） Example: 提供各大品牌智慧型手機，包含最新機型與優惠價格
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "分類建立成功",
     *   "data": {
     *     "id": 15,
     *     "name": "智慧型手機",
     *     "slug": "smartphones",
     *     "parent_id": 1,
     *     "position": 10,
     *     "status": true,
     *     "depth": 1,
     *     "description": "各種品牌的智慧型手機",
     *     "meta_title": "智慧型手機 | LomisX3",
     *     "meta_description": "提供各大品牌智慧型手機，包含最新機型與優惠價格",
     *     "path": "/1/15/",
     *     "has_children": false,
     *     "full_path": "電子產品 > 智慧型手機",
     *     "children_count": 0,
     *     "created_at": "2025-01-07T10:30:00.000000Z",
     *     "updated_at": "2025-01-07T10:30:00.000000Z"
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "分類建立失敗：名稱已存在",
     *   "code": "CATEGORY_CREATE_FAILED",
     *   "errors": {
     *     "name": ["分類名稱已存在"]
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "分類建立失敗：超過最大層級限制",
     *   "code": "MAX_DEPTH_EXCEEDED"
     * }
     */
    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $category = $this->categoryService->createCategory($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '分類建立成功',
                'data' => new ProductCategoryResource($category),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '分類建立失敗：' . $e->getMessage(),
                'code' => 'CATEGORY_CREATE_FAILED',
            ], 422);
        }
    }

    /**
     * 顯示指定分類
     * 
     * 取得單一商品分類的詳細資訊，包含父分類和子分類資料。
     * 
     * @group 商品分類管理
     * 
     * @urlParam productCategory integer required 分類ID Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "電子產品",
     *     "slug": "electronics",
     *     "parent_id": null,
     *     "position": 1,
     *     "status": true,
     *     "depth": 0,
     *     "description": "各類電子產品分類",
     *     "meta_title": "電子產品 | LomisX3",
     *     "meta_description": "電子產品相關商品分類",
     *     "path": "/1/",
     *     "has_children": true,
     *     "full_path": "電子產品",
     *     "children_count": 5,
     *     "parent": null,
     *     "children": [
     *       {
     *         "id": 2,
     *         "name": "智慧型手機",
     *         "slug": "smartphones",
     *         "parent_id": 1,
     *         "status": true,
     *         "depth": 1
     *       }
     *     ],
     *     "created_at": "2025-01-07T10:00:00.000000Z",
     *     "updated_at": "2025-01-07T10:00:00.000000Z"
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "分類不存在"
     * }
     */
    public function show(ProductCategory $productCategory): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProductCategoryResource($productCategory->load(['parent', 'children'])),
        ]);
    }

    /**
     * 更新指定分類
     * 
     * 更新商品分類資訊。變更父分類時會自動重新計算層級結構和快取。
     * 
     * @group 商品分類管理
     * 
     * @urlParam productCategory integer required 分類ID Example: 1
     * 
     * @bodyParam name string 分類名稱（2-100字元） Example: 更新的分類名稱
     * @bodyParam slug string 自訂URL別名 Example: updated-slug
     * @bodyParam parent_id integer 父分類ID（設為null表示設為根分類） Example: 2
     * @bodyParam position integer 排序位置 Example: 15
     * @bodyParam status boolean 啟用狀態 Example: false
     * @bodyParam description string 分類描述 Example: 更新的分類描述
     * @bodyParam meta_title string SEO標題 Example: 更新的SEO標題
     * @bodyParam meta_description string SEO描述 Example: 更新的SEO描述
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "分類更新成功",
     *   "data": {
     *     "id": 1,
     *     "name": "更新的分類名稱",
     *     "slug": "updated-slug",
     *     "parent_id": 2,
     *     "position": 15,
     *     "status": false,
     *     "depth": 1,
     *     "description": "更新的分類描述",
     *     "meta_title": "更新的SEO標題",
     *     "meta_description": "更新的SEO描述",
     *     "path": "/2/1/",
     *     "has_children": true,
     *     "full_path": "父分類 > 更新的分類名稱",
     *     "children_count": 3,
     *     "created_at": "2025-01-07T10:00:00.000000Z",
     *     "updated_at": "2025-01-07T11:00:00.000000Z"
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "分類更新失敗：會造成循環引用",
     *   "code": "CATEGORY_UPDATE_FAILED"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "分類不存在"
     * }
     */
    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updatedCategory = $this->categoryService->updateCategory(
                $productCategory->id,
                $request->validated()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '分類更新成功',
                'data' => new ProductCategoryResource($updatedCategory),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '分類更新失敗：' . $e->getMessage(),
                'code' => 'CATEGORY_UPDATE_FAILED',
            ], 422);
        }
    }

    /**
     * 刪除指定分類
     * 
     * 軟刪除商品分類。只能刪除沒有子分類的分類，刪除後會觸發快取更新。
     * 
     * @group 商品分類管理
     * 
     * @urlParam productCategory integer required 分類ID Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "分類刪除成功"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "分類刪除失敗：該分類包含子分類",
     *   "code": "CATEGORY_DELETE_FAILED"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "分類不存在"
     * }
     */
    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        try {
            DB::beginTransaction();

            $result = $this->categoryService->deleteCategory($productCategory->id);

            if (! $result) {
                throw new \Exception('分類刪除失敗');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '分類刪除成功',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '分類刪除失敗：' . $e->getMessage(),
                'code' => 'CATEGORY_DELETE_FAILED',
            ], 422);
        }
    }

    /**
     * 取得樹狀結構
     * 
     * 獲取完整的分類樹狀結構，支援快取優化，適用於選單展示和層級瀏覽。
     * 
     * @group 商品分類管理
     * 
     * @queryParam only_active boolean 僅顯示啟用的分類（預設true） Example: true
     * @queryParam max_depth integer 最大顯示深度 Example: 3
     * @queryParam root_id integer 指定根分類ID（顯示特定子樹） Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "電子產品",
     *       "slug": "electronics",
     *       "parent_id": null,
     *       "position": 1,
     *       "status": true,
     *       "depth": 0,
     *       "has_children": true,
     *       "children_count": 3,
     *       "children": [
     *         {
     *           "id": 2,
     *           "name": "智慧型手機",
     *           "slug": "smartphones",
     *           "parent_id": 1,
     *           "position": 1,
     *           "status": true,
     *           "depth": 1,
     *           "has_children": false,
     *           "children_count": 0,
     *           "children": []
     *         }
     *       ]
     *     }
     *   ],
     *   "meta": {
     *     "total_categories": 25,
     *     "max_depth": 3,
     *     "cache_hit": true
     *   }
     * }
     */
    public function tree(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);

        $tree = $this->categoryRepository->getTree($onlyActive);

        return response()->json([
            'success' => true,
            'data' => ProductCategoryResource::collection($tree),
        ]);
    }

    /**
     * 取得麵包屑
     */
    public function breadcrumbs(ProductCategory $productCategory): JsonResponse
    {
        $ancestors = $this->categoryRepository->getAncestors($productCategory->id);

        return response()->json([
            'success' => true,
            'data' => [
                'ancestors' => ProductCategoryResource::collection($ancestors),
                'current' => new ProductCategoryResource($productCategory),
                'breadcrumb_path' => $productCategory->full_path,
            ],
        ]);
    }

    /**
     * 取得子孫分類
     */
    public function descendants(ProductCategory $productCategory): JsonResponse
    {
        $descendants = $this->categoryRepository->getDescendants($productCategory->id);

        return response()->json([
            'success' => true,
            'data' => ProductCategoryResource::collection($descendants),
            'meta' => [
                'total_descendants' => $descendants->count(),
            ],
        ]);
    }

    /**
     * 拖曳排序
     */
    public function sort(SortProductCategoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $result = $this->categoryService->updatePositions($request->validated('positions'));

            if (! $result) {
                throw new \Exception('排序更新失敗');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '分類排序更新成功',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '排序更新失敗：' . $e->getMessage(),
                'code' => 'CATEGORY_SORT_FAILED',
            ], 422);
        }
    }

    /**
     * 批次更新狀態
     */
    public function batchStatus(BatchStatusProductCategoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $affectedRows = $this->categoryService->batchUpdateStatus(
                $validated['ids'],
                $validated['status']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "成功更新 {$affectedRows} 個分類的狀態",
                'data' => [
                    'affected_rows' => $affectedRows,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '批次狀態更新失敗：' . $e->getMessage(),
                'code' => 'BATCH_STATUS_UPDATE_FAILED',
            ], 422);
        }
    }

    /**
     * 批次刪除
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:product_categories,id'],
        ]);

        try {
            DB::beginTransaction();

            $affectedRows = $this->categoryService->batchDelete($request->input('ids'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "成功刪除 {$affectedRows} 個分類",
                'data' => [
                    'affected_rows' => $affectedRows,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '批次刪除失敗：' . $e->getMessage(),
                'code' => 'BATCH_DELETE_FAILED',
            ], 422);
        }
    }

    /**
     * 取得分類統計
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->categoryRepository->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}
