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
     * 處理未認證的請求
     * 
     * 重要：防止 API 請求被重導向到登入頁面，避免 CORS 錯誤
     * 符合 SPA 設計原則，API 請求應該返回 JSON 錯誤而非重定向
     * 
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse|Response
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse|Response
    {
        // 檢查請求是否期望 JSON 回應 (通常 API 請求都是)
        if ($request->expectsJson() || $request->is('api/*')) {
            // 返回一個符合我們架構手冊規範的 401 JSON 錯誤
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'error_code' => 'UNAUTHENTICATED',
                'errors' => null
            ], 401);
        }

        // 對於非 API 的網頁請求，保留原始的重定向行為
        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
