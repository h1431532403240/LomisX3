<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * 全域異常處理器
 * 統一處理應用程式中的所有異常
 */
class Handler extends ExceptionHandler
{
    /**
     * 不需要報告的異常類型列表
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        BusinessException::class,
    ];

    /**
     * 不需要閃存到 Session 的驗證錯誤輸入列表
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * 註冊異常處理回調
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * 將異常渲染為 HTTP 回應
     *
     * @param Request $request
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response|JsonResponse
    {
        // 處理業務邏輯異常
        if ($e instanceof BusinessException) {
            return response()->json($e->toArray(), $e->getHttpStatus());
        }

        // 處理驗證異常
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => '驗證失敗',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_FAILED',
            ], 422);
        }

        // 處理 HTTP 異常
        if ($e instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: '請求失敗',
                'errors' => [],
                'code' => 'HTTP_ERROR',
            ], $e->getStatusCode());
        }

        // 如果是 API 請求，返回 JSON 格式錯誤
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => app()->hasDebugModeEnabled()
                    ? $e->getMessage()
                    : '系統錯誤，請稍後再試',
                'errors' => [],
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }

        return parent::render($request, $e);
    }

   /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception): \Illuminate\Http\JsonResponse
    {
        // V4.0 架構修正：強制所有未認證的請求返回標準的 JSON 401 回應。
        // 這徹底禁用了 Laravel 預設的、嘗試重定向到 'login' 命名路由的行為，
        // 使我們的純 API 後端行為完全可預測，並與前端 SPA 的認證流程兼容。
        return response()->json([
            'success'    => false,
            'message'    => $exception->getMessage() ?: 'Unauthenticated.',
            'error_code' => 'UNAUTHENTICATED',
        ], 401);
    }
}
