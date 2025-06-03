<?php

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
 * 未來可以在這裡添加更多 API 路由
 * 例如：
 * Route::apiResource('posts', PostController::class);
 * Route::group(['middleware' => 'auth:sanctum'], function () {
 *     // 受保護的路由
 * });
 */ 