<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategoryStoreRequest;
use App\Http\Requests\ProductCategoryUpdateRequest;
use App\Http\Resources\ProductCategoryCollection;
use App\Http\Resources\ProductCategoryResource;
use App\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 商品分類控制器
 * 
 * 提供商品分類的完整 CRUD API，支援：
 * - RESTful API 設計
 * - 游標分頁和標準分頁
 * - 資源轉換和回應格式統一
 * - Sanctum Token 權限檢查
 * 
 * @group 商品分類管理
 */
class ProductCategoryController extends Controller
{
    /**
     * 建構函式
     * 
     * @param ProductCategoryService $categoryService 商品分類服務
     */
    public function __construct(
        private readonly ProductCategoryService $categoryService
    ) {
        // 為需要認證的動作添加 Sanctum 中介軟體
        $this->middleware('auth:sanctum')->except(['index', 'show', 'tree']);
        
        // 設定 tokenCan 權限檢查
        $this->middleware('can:categories.read')->only(['index', 'show', 'tree']);
        $this->middleware('can:categories.create')->only(['store']);
        $this->middleware('can:categories.update')->only(['update']);
        $this->middleware('can:categories.delete')->only(['destroy']);
    }

    /**
     * 分頁取得商品分類列表
     * 
     * 支援標準分頁和游標分頁，可依狀態、父分類、深度進行篩選
     * 
     * @queryParam per_page integer 每頁筆數 (1-100) Example: 15
     * @queryParam cursor boolean 是否使用游標分頁 Example: false
     * @queryParam search string 搜尋關鍵字 (分類名稱) Example: 電子產品
     * @queryParam status boolean 分類狀態篩選 Example: true
     * @queryParam parent_id integer 父分類ID篩選 Example: 1
     * @queryParam depth integer 分類深度篩選 Example: 2
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "電子產品",
     *       "slug": "electronics",
     *       "status": true,
     *       "position": 1,
     *       "depth": 0,
     *       "parent_id": null,
     *       "description": "各類電子產品分類",
     *       "meta_title": "電子產品 - 商品分類",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "pagination": {
     *     "type": "standard",
     *     "current_page": 1,
     *     "last_page": 10,
     *     "per_page": 15,
     *     "total": 150,
     *     "from": 1,
     *     "to": 15,
     *     "path": "http://localhost/api/product-categories",
     *     "next_page_url": "http://localhost/api/product-categories?page=2",
     *     "prev_page_url": null
     *   }
     * }
     * 
     * @param Request $request HTTP 請求
     * @return ProductCategoryCollection 分類集合資源
     */
    public function index(Request $request): ProductCategoryCollection
    {
        // 檢查讀取權限
        if ($request->user() && !$request->user()->tokenCan('categories.read')) {
            return response()->json(['message' => '無權限讀取分類資料'], Response::HTTP_FORBIDDEN);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $useCursor = $request->boolean('cursor', false);

        $categories = $this->categoryService->getPaginatedList(
            $perPage,
            $request->only(['search', 'status', 'parent_id', 'depth']),
            $useCursor
        );

        return new ProductCategoryCollection($categories);
    }

    /**
     * 建立新的商品分類
     * 
     * @param ProductCategoryStoreRequest $request 建立請求
     * @return JsonResponse JSON 回應
     */
    public function store(ProductCategoryStoreRequest $request): JsonResponse
    {
        // 檢查建立權限
        if (!$request->user()->tokenCan('categories.create')) {
            return response()->json(['message' => '無權限建立分類'], Response::HTTP_FORBIDDEN);
        }

        try {
            $category = $this->categoryService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => '分類建立成功',
                'data' => new ProductCategoryResource($category),
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '分類建立失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 顯示指定的商品分類
     * 
     * @param int $id 分類 ID
     * @return JsonResponse JSON 回應
     */
    public function show(Request $request, int $id): JsonResponse
    {
        // 檢查讀取權限
        if ($request->user() && !$request->user()->tokenCan('categories.read')) {
            return response()->json(['message' => '無權限讀取分類資料'], Response::HTTP_FORBIDDEN);
        }

        try {
            $category = $this->categoryService->findById($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => '分類不存在',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'data' => new ProductCategoryResource($category),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '取得分類失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 更新指定的商品分類
     * 
     * @param ProductCategoryUpdateRequest $request 更新請求
     * @param int                          $id      分類 ID
     * @return JsonResponse JSON 回應
     */
    public function update(ProductCategoryUpdateRequest $request, int $id): JsonResponse
    {
        // 檢查更新權限
        if (!$request->user()->tokenCan('categories.update')) {
            return response()->json(['message' => '無權限更新分類'], Response::HTTP_FORBIDDEN);
        }

        try {
            $category = $this->categoryService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => '分類更新成功',
                'data' => new ProductCategoryResource($category),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '分類更新失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 刪除指定的商品分類
     * 
     * @param Request $request HTTP 請求
     * @param int     $id      分類 ID
     * @return JsonResponse JSON 回應
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // 檢查刪除權限
        if (!$request->user()->tokenCan('categories.delete')) {
            return response()->json(['message' => '無權限刪除分類'], Response::HTTP_FORBIDDEN);
        }

        try {
            $success = $this->categoryService->delete($id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => '分類刪除失敗，可能不存在或有子分類',
                ], Response::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'success' => true,
                'message' => '分類刪除成功',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '分類刪除失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 取得分類樹狀結構
     * 
     * @param Request $request HTTP 請求
     * @return JsonResponse JSON 回應
     */
    public function tree(Request $request): JsonResponse
    {
        // 檢查讀取權限
        if ($request->user() && !$request->user()->tokenCan('categories.read')) {
            return response()->json(['message' => '無權限讀取分類樹狀結構'], Response::HTTP_FORBIDDEN);
        }

        try {
            $onlyActive = $request->boolean('active', true);
            $tree = $this->categoryService->getTree($onlyActive);

            return response()->json([
                'success' => true,
                'data' => $tree,
                'meta' => [
                    'only_active' => $onlyActive,
                    'total_nodes' => $this->countTreeNodes($tree),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '取得分類樹狀結構失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 取得分類統計資訊
     * 
     * @param Request $request HTTP 請求
     * @return JsonResponse JSON 回應
     */
    public function statistics(Request $request): JsonResponse
    {
        // 檢查讀取權限
        if ($request->user() && !$request->user()->tokenCan('categories.read')) {
            return response()->json(['message' => '無權限讀取統計資訊'], Response::HTTP_FORBIDDEN);
        }

        try {
            $stats = $this->categoryService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '取得統計資訊失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 取得麵包屑路徑
     * 
     * @param Request $request    HTTP 請求
     * @param int     $categoryId 分類 ID
     * @return JsonResponse JSON 回應
     */
    public function breadcrumbs(Request $request, int $categoryId): JsonResponse
    {
        // 檢查讀取權限
        if ($request->user() && !$request->user()->tokenCan('categories.read')) {
            return response()->json(['message' => '無權限讀取麵包屑'], Response::HTTP_FORBIDDEN);
        }

        try {
            $breadcrumbs = $this->categoryService->getBreadcrumbs($categoryId);

            return response()->json([
                'success' => true,
                'data' => $breadcrumbs,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '取得麵包屑失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 批次更新分類狀態
     * 
     * @param Request $request HTTP 請求
     * @return JsonResponse JSON 回應
     */
    public function batchUpdateStatus(Request $request): JsonResponse
    {
        // 檢查更新權限
        if (!$request->user()->tokenCan('categories.update')) {
            return response()->json(['message' => '無權限批次更新分類'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:product_categories,id',
            'status' => 'required|boolean',
        ]);

        try {
            $affected = $this->categoryService->batchUpdateStatus(
                $request->input('ids'),
                $request->boolean('status')
            );

            return response()->json([
                'success' => true,
                'message' => "成功更新 {$affected} 個分類的狀態",
                'affected_count' => $affected,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批次更新失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 更新分類排序
     * 
     * @param Request $request HTTP 請求
     * @return JsonResponse JSON 回應
     */
    public function updateSortOrder(Request $request): JsonResponse
    {
        // 檢查更新權限
        if (!$request->user()->tokenCan('categories.update')) {
            return response()->json(['message' => '無權限更新排序'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:product_categories,id',
            'items.*.position' => 'required|integer|min:0',
        ]);

        try {
            $success = $this->categoryService->updateSortOrder($request->input('items'));

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => '排序更新成功',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => '排序更新失敗',
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '排序更新失敗：' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 計算樹狀結構節點數量
     * 
     * @param array $tree 樹狀結構
     * @return int 節點總數
     */
    private function countTreeNodes(array $tree): int
    {
        $count = 0;

        foreach ($tree as $node) {
            $count++; // 當前節點

            if (isset($node['children']) && is_array($node['children'])) {
                $count += $this->countTreeNodes($node['children']); // 遞迴計算子節點
            }
        }

        return $count;
    }
} 