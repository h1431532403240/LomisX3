<?php

use App\Http\Controllers\Api\ProductCategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * API 路由文件
 * 這些路由會自動套用 'api' middleware 群組
 * 並且會包含 Sanctum 的 EnsureFrontendRequestsAreStateful middleware
 */

/**
 * 取得認證用戶資訊的路由
 * 需要 Sanctum 認證
 */
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * 測試路由
 * 檢查 API 是否正常運作
 */
Route::get('/test', function () {
    return response()->json([
        'message' => 'API 運作正常',
        'timestamp' => now(),
    ]);
});

/**
 * 商品分類 API 路由
 */
Route::prefix('product-categories')->name('product-categories.')->group(function () {

    // 基礎 CRUD 路由
    Route::apiResource('/', ProductCategoryController::class)->parameters([
        '' => 'product_category',
    ]);

    // 進階功能路由
    Route::get('/tree', [ProductCategoryController::class, 'tree'])
        ->name('tree');

    Route::get('/{product_category}/breadcrumbs', [ProductCategoryController::class, 'breadcrumbs'])
        ->name('breadcrumbs');

    Route::get('/{product_category}/descendants', [ProductCategoryController::class, 'descendants'])
        ->name('descendants');

    Route::patch('/sort', [ProductCategoryController::class, 'sort'])
        ->name('sort');

    Route::patch('/batch-status', [ProductCategoryController::class, 'batchStatus'])
        ->name('batch-status');

    Route::delete('/batch-delete', [ProductCategoryController::class, 'batchDelete'])
        ->name('batch-delete');

    Route::get('/statistics', [ProductCategoryController::class, 'statistics'])
        ->name('statistics');
});

/**
 * 範例：其他模組路由
 */

// 主題系統路由（已存在）
// Route::prefix('themes')->group(function () {
//     // 主題相關路由
// });

// 商品路由（未來開發）
// Route::prefix('products')->group(function () {
//     // 商品相關路由
// });

// 訂單路由（未來開發）
// Route::prefix('orders')->group(function () {
//     // 訂單相關路由
// });

// 使用者管理路由（未來開發）
// Route::prefix('users')->group(function () {
//     // 使用者相關路由
// });

/**
 * 未來可以在這裡添加更多 API 路由
 * 例如：
 * Route::apiResource('posts', PostController::class);
 * Route::group(['middleware' => 'auth:sanctum'], function () {
 *     // 受保護的路由
 * });
 */
