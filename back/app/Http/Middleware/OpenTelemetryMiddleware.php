<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * OpenTelemetry HTTP 追蹤中介軟體
 * 
 * 為所有 HTTP 請求自動建立 span，記錄請求資訊和回應狀態
 * 支援錯誤追蹤和效能監控
 */
class OpenTelemetryMiddleware
{
    /**
     * 處理傳入的請求
     * 
     * @param Request $request HTTP 請求
     * @param Closure $next 下一個中介軟體
     * @return SymfonyResponse
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // 檢查 OpenTelemetry 是否啟用
        if (!config('services.opentelemetry.enabled', false)) {
            return $next($request);
        }

        $tracer = Globals::tracerProvider()->getTracer('laravel-http');
        $operationName = $this->generateOperationName($request);
        
        $span = $tracer->spanBuilder($operationName)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();
            
        $scope = $span->activate();
        
        try {
            // 設定 HTTP 請求屬性
            $this->setHttpRequestAttributes($span, $request);
            
            // 處理請求
            $response = $next($request);
            
            // 設定 HTTP 回應屬性
            $this->setHttpResponseAttributes($span, $response);
            
            // 設定 span 狀態
            $this->setSpanStatus($span, $response);
            
            return $response;
            
        } catch (\Throwable $exception) {
            // 記錄異常資訊
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
            
            // 設定錯誤屬性
            $span->setAttributes([
                'error' => true,
                'error.type' => get_class($exception),
                'error.message' => $exception->getMessage(),
                'error.stack' => $exception->getTraceAsString(),
            ]);
            
            throw $exception;
            
        } finally {
            $span->end();
            $scope->detach();
        }
    }

    /**
     * 生成操作名稱
     * 基於路由資訊創建有意義的 span 名稱
     * 
     * @param Request $request
     * @return string
     */
    private function generateOperationName(Request $request): string
    {
        $route = $request->route();
        
        if ($route && $route->getName()) {
            return "HTTP {$request->method()} {$route->getName()}";
        }
        
        if ($route && $route->getActionName()) {
            $action = $route->getActionName();
            // 移除 Controller 後綴和命名空間
            $action = str_replace(['Controller@', 'App\\Http\\Controllers\\'], '', $action);
            return "HTTP {$request->method()} {$action}";
        }
        
        $path = $request->path();
        // 替換路由參數為泛用格式
        $path = preg_replace('/\d+/', '{id}', $path);
        
        return "HTTP {$request->method()} /{$path}";
    }

    /**
     * 設定 HTTP 請求屬性
     * 
     * @param \OpenTelemetry\API\Trace\SpanInterface $span
     * @param Request $request
     */
    private function setHttpRequestAttributes($span, Request $request): void
    {
        $attributes = [
            'http.method' => $request->method(),
            'http.url' => $request->fullUrl(),
            'http.scheme' => $request->getScheme(),
            'http.host' => $request->getHost(),
            'http.target' => $request->getRequestUri(),
            'http.user_agent' => $request->userAgent() ?? '',
            'http.request_content_length' => $request->header('Content-Length', 0),
        ];
        
        // 添加路由資訊
        $route = $request->route();
        if ($route) {
            $attributes['http.route'] = $route->uri();
            if ($route->getName()) {
                $attributes['laravel.route.name'] = $route->getName();
            }
            if ($route->getActionName()) {
                $attributes['laravel.route.action'] = $route->getActionName();
            }
        }
        
        // 添加用戶資訊（如果已認證）
        $user = $request->user();
        if ($user) {
            $attributes['user.id'] = $user->id;
            if (method_exists($user, 'email')) {
                $attributes['user.email'] = $user->email;
            }
        }
        
        // 添加 IP 資訊
        $attributes['client.address'] = $request->ip();
        
        // 添加查詢參數數量（但不包含實際值以保護隱私）
        $queryParams = $request->query();
        if (!empty($queryParams)) {
            $attributes['http.query_params_count'] = count($queryParams);
        }
        
        $span->setAttributes($attributes);
    }

    /**
     * 設定 HTTP 回應屬性
     * 
     * @param \OpenTelemetry\API\Trace\SpanInterface $span
     * @param SymfonyResponse $response
     */
    private function setHttpResponseAttributes($span, SymfonyResponse $response): void
    {
        $attributes = [
            'http.status_code' => $response->getStatusCode(),
            'http.response_content_length' => $response->headers->get('Content-Length', 0),
        ];
        
        // 添加內容類型
        $contentType = $response->headers->get('Content-Type');
        if ($contentType) {
            $attributes['http.response_content_type'] = $contentType;
        }
        
        $span->setAttributes($attributes);
    }

    /**
     * 設定 span 狀態
     * 根據 HTTP 狀態碼設定對應的 span 狀態
     * 
     * @param \OpenTelemetry\API\Trace\SpanInterface $span
     * @param SymfonyResponse $response
     */
    private function setSpanStatus($span, SymfonyResponse $response): void
    {
        $statusCode = $response->getStatusCode();
        
        if ($statusCode >= 400) {
            if ($statusCode >= 500) {
                $span->setStatus(StatusCode::STATUS_ERROR, "HTTP {$statusCode}");
            } else {
                $span->setStatus(StatusCode::STATUS_ERROR, "HTTP {$statusCode} Client Error");
            }
        } else {
            $span->setStatus(StatusCode::STATUS_OK);
        }
    }
} 