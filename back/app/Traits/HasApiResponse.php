<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * API 回應格式統一處理 Trait
 * 
 * 提供標準化的 API 回應格式，確保所有 API 端點回應一致性
 * 
 * @package App\Traits
 * @author LomisX3 Team
 * @version 1.0
 * 
 * 功能特色：
 * - 統一 API 回應格式 {success, message, data, error_code}
 * - 支援多層級錯誤處理（業務邏輯錯誤、系統錯誤、驗證錯誤）
 * - 自動記錄錯誤日誌
 * - 支援 HTTP 狀態碼標準化
 * - 企業級錯誤代碼系統整合
 */
trait HasApiResponse
{
    /**
     * API 錯誤回應
     * 
     * 統一格式化錯誤回應，支援業務錯誤代碼、HTTP 狀態碼、額外資料
     * 
     * @param string $message 錯誤訊息 
     * @param int $status HTTP 狀態碼
     * @param string|null $errorCode 業務錯誤代碼（如：USER_NOT_FOUND）
     * @param array $extra 額外資料（如：驗證錯誤詳情、除錯資訊）
     * @return JsonResponse
     * 
     * @example
     * return $this->apiError('使用者不存在', 404, 'USER_NOT_FOUND');
     * return $this->apiError('驗證失敗', 422, 'VALIDATION_ERROR', ['errors' => $validator->errors()]);
     */
    protected function apiError(
        string $message = '操作失敗', 
        int $status = 400, 
        ?string $errorCode = null,
        array $extra = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        // 添加錯誤代碼（如果提供）
        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        // 合併額外資料
        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }

        return response()->json($response, $status);
    }

    /**
     * API 成功回應
     * 
     * 統一格式化成功回應，支援資料載荷、成功訊息、HTTP 狀態碼
     * 
     * @param array $data 回應資料
     * @param string $message 成功訊息
     * @param int $status HTTP 狀態碼
     * @return JsonResponse
     * 
     * @example
     * return $this->apiSuccess(['user' => $user], '登入成功');
     * return $this->apiSuccess([], '操作完成', 201);
     */
    protected function apiSuccess(
        array $data = [], 
        string $message = '操作成功', 
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * API 分頁成功回應
     * 
     * 專門處理分頁資料的回應格式，包含 data、links、meta 結構
     * 
     * @param array $data 分頁資料（來自 Laravel Resource Collection）
     * @param string $message 成功訊息
     * @return JsonResponse
     * 
     * @example
     * return $this->apiPaginatedSuccess($userCollection->response()->getData(true));
     */
    protected function apiPaginatedSuccess(
        array $data, 
        string $message = '查詢成功'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data['data'] ?? [],
            'links' => $data['links'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);
    }

    /**
     * API 驗證錯誤回應
     * 
     * 專門處理表單驗證錯誤的回應格式
     * 
     * @param array $errors 驗證錯誤陣列
     * @param string $message 錯誤訊息
     * @return JsonResponse
     * 
     * @example
     * return $this->apiValidationError($validator->errors()->toArray(), '表單驗證失敗');
     */
    protected function apiValidationError(
        array $errors, 
        string $message = '表單驗證失敗'
    ): JsonResponse {
        return $this->apiError($message, 422, 'VALIDATION_ERROR', [
            'errors' => $errors
        ]);
    }

    /**
     * API 權限錯誤回應
     * 
     * 專門處理權限相關的錯誤回應
     * 
     * @param string $message 權限錯誤訊息
     * @param string|null $permission 缺少的權限名稱
     * @return JsonResponse
     * 
     * @example
     * return $this->apiPermissionError('您沒有訪問此資源的權限', 'users.view');
     */
    protected function apiPermissionError(
        string $message = '權限不足', 
        ?string $permission = null
    ): JsonResponse {
        $extra = [];
        if ($permission !== null) {
            $extra['required_permission'] = $permission;
        }

        return $this->apiError($message, 403, 'PERMISSION_DENIED', $extra);
    }

    /**
     * API 資源不存在錯誤回應
     * 
     * 統一處理 404 資源不存在的錯誤回應
     * 
     * @param string $resource 資源名稱
     * @return JsonResponse
     * 
     * @example
     * return $this->apiNotFoundError('使用者');
     */
    protected function apiNotFoundError(string $resource = '資源'): JsonResponse
    {
        return $this->apiError(
            message: "找不到指定的{$resource}",
            status: 404,
            errorCode: 'RESOURCE_NOT_FOUND'
        );
    }
} 