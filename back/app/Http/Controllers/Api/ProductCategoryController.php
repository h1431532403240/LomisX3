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
 * 提供完整的 RESTful API 和進階功能
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
