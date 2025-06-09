<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * @version V1.0 - 標準 API 回應格式
 * @description 為所有 API 控制器提供統一的成功和失敗的回應方法。
 */
trait ApiResponseTrait
{
    /**
     * 返回成功的 API 回應。
     *
     * @param mixed|null $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function apiSuccess(mixed $data = null, string $message = '操作成功', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * 返回失敗的 API 回應。
     *
     * @param string $message
     * @param int $statusCode
     * @param string|null $errorCode
     * @param mixed|null $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function apiError(string $message, int $statusCode = 400, ?string $errorCode = null, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!is_null($errorCode)) {
            $response['error_code'] = $errorCode;
        }

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
} 