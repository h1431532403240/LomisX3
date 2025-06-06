<?php

use App\Http\Controllers\Api\{ProductCategoryController, AuthController, UserController};
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
 * 認證 & 2FA API 路由
 * 遵循 LomisX3 架構標準的使用者管理模組 V6.2
 * 🎯 專為 SPA 設計，支援 Sanctum CSRF 流程
 */
Route::prefix('auth')->name('auth.')->group(function () {
    // 公開認證路由 (不需要認證)
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login')
        ->middleware(['throttle:5,1']); // 登入限流：每分鐘最多 5 次
        
    Route::post('/2fa/challenge', [AuthController::class, 'twoFactorChallenge'])->name('2fa.challenge');
    
    // 登出路由（不需要強制認證，支援冪等性操作）
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // 需要認證的路由
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        
        // 2FA 管理
        Route::post('/2fa/enable', [AuthController::class, 'enable2FA'])->name('2fa.enable');
        Route::post('/2fa/confirm', [AuthController::class, 'confirm2FA'])->name('2fa.confirm');
        Route::post('/2fa/disable', [AuthController::class, 'disable2FA'])->name('2fa.disable');
    });
});

/**
 * 使用者管理 API 路由
 * 支援門市隔離、權限控制、批次操作
 */
Route::prefix('users')->name('users.')->middleware(['auth:sanctum'])->group(function () {
    // 統計資訊 (必須在 {user} 路由之前)
    Route::get('/statistics', [UserController::class, 'statistics'])->name('statistics');
    
    // 批次操作路由
    Route::patch('/batch-status', [UserController::class, 'batchStatus'])->name('batch-status');
    
    // 基礎 CRUD 路由
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}', [UserController::class, 'show'])->name('show');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    
    // 密碼管理
    Route::patch('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    
    // 活動日誌
    Route::get('/{user}/activities', [UserController::class, 'activities'])->name('activities');
    
    // 頭像管理
    Route::post('/{user}/avatar', [UserController::class, 'uploadAvatar'])->name('upload-avatar');
    Route::delete('/{user}/avatar', [UserController::class, 'deleteAvatar'])->name('delete-avatar');
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

/**
 * 未來可以在這裡添加更多 API 路由
 * 例如：
 * Route::apiResource('posts', PostController::class);
 * Route::group(['middleware' => 'auth:sanctum'], function () {
 *     // 受保護的路由
 * });
 */ 