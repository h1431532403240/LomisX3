<?php

use App\Http\Controllers\Api\{ProductCategoryController, AuthController, UserController, SystemController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * API 路由文件 (Pure Bearer Token 模式)
 * 這些路由會自動套用 'api' middleware 群組
 * 採用純 Bearer Token 認證，不使用 Session 或 Cookie
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
 * 🎯 純 Bearer Token 模式，所有認證都使用 Authorization 標頭
 */
Route::prefix('auth')->name('auth.')->group(function () {
    // 公開認證路由 (不需要認證)
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login')
        ->middleware(['throttle:5,1']); // 登入限流：每分鐘最多 5 次
        
    Route::post('/2fa/challenge', [AuthController::class, 'twoFactorChallenge'])->name('2fa.challenge');
    
    // 登出路由（不需要強制認證，支援冪等性操作）
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // ✅ /me 端點重新移回認證區域，需要 Bearer Token
    // 注意：此端點必須使用 auth:sanctum 中間件來正確處理 Bearer Token
    
    // 需要認證的路由
    Route::middleware('auth:sanctum')->group(function () {
        // 用戶資訊端點（重要：必須在 auth:sanctum 保護下才能正確處理 Bearer Token）
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

// ✅✅✅ V5.5 最終修復：為 'user' 參數定義一個明確的、帶有權限檢查的綁定 ✅✅✅
Route::bind('user', function ($value) {
    \Illuminate\Support\Facades\Log::info('🔍 [Route::bind] 開始解析 user 參數', [
        'requested_user_id' => $value,
        'auth_check' => auth()->check(),
        'auth_user_id' => auth()->check() ? auth()->id() : null,
        'has_cross_store_permission' => auth()->check() ? auth()->user()->can('system.operate_across_stores') : false
    ]);
    
    // 如果當前用戶已認證，並且擁有跨越門市操作的權限...
    if (auth()->check() && auth()->user()->can('system.operate_across_stores')) {
        \Illuminate\Support\Facades\Log::info('✅ [Route::bind] 使用跨門市權限查詢', ['user_id' => $value]);
        // ...我們就繞過所有的全域範圍(Global Scopes)來查找目標用戶。
        $user = \App\Models\User::withTrashed()->withoutGlobalScopes()->find($value);
        
        if ($user) {
            \Illuminate\Support\Facades\Log::info('✅ [Route::bind] 成功找到用戶 (跨門市)', [
                'found_user_id' => $user->id,
                'found_username' => $user->username,
                'found_store_id' => $user->store_id,
                'found_deleted_at' => $user->deleted_at
            ]);
            return $user;
        } else {
            \Illuminate\Support\Facades\Log::warning('❌ [Route::bind] 找不到用戶 (跨門市)', ['user_id' => $value]);
            abort(404, '使用者不存在');
        }
    }
    
    // 對於沒有特殊權限的使用者，遵循正常的模型查找規則（會自動應用門市隔離範圍）。
    \Illuminate\Support\Facades\Log::info('🔒 [Route::bind] 使用門市隔離查詢', ['user_id' => $value]);
    $user = \App\Models\User::withTrashed()->find($value);
    
    if ($user) {
        \Illuminate\Support\Facades\Log::info('✅ [Route::bind] 成功找到用戶 (門市隔離)', [
            'found_user_id' => $user->id,
            'found_username' => $user->username,
            'found_store_id' => $user->store_id,
            'found_deleted_at' => $user->deleted_at
        ]);
        return $user;
    } else {
        \Illuminate\Support\Facades\Log::warning('❌ [Route::bind] 找不到用戶 (門市隔離)', ['user_id' => $value]);
        abort(404, '使用者不存在');
    }
});

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
    
    // 使用 Route::delete(...) 的標準寫法，但為其添加一個自定義的綁定邏輯
    Route::delete('/{user}', [UserController::class, 'destroy'])
        ->name('destroy') // 保持命名不變
        ->where('user', '[0-9]+') // 添加數字約束，更安全
        ->missing(function () {
            // 如果找不到用戶，返回標準的 404 JSON 回應
            return response()->json(['success' => false, 'message' => '使用者不存在。'], 404);
        });
    
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
 * 系統配置 API 路由
 * 提供枚舉值和本地化文本，實現配置驅動UI
 */
Route::prefix('system')->name('system.')->group(function () {
    // 系統配置端點 (公開存取，前端應用啟動時使用)
    Route::get('/configs', [SystemController::class, 'getConfigs'])->name('configs');
    
    // 特定枚舉配置端點
    Route::get('/enums/{type}', [SystemController::class, 'getEnumConfig'])->name('enum-config');
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