<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * 業務邏輯異常類別
 * 處理應用程式業務邏輯相關的錯誤
 * V6.2: 支援通用錯誤代碼，兼容多種錯誤枚舉
 */
class BusinessException extends RuntimeException
{
    /**
     * 建立業務異常實例
     *
     * @param string         $message    錯誤訊息
     * @param string         $errorCode  錯誤代碼
     * @param int           $status     HTTP 狀態碼
     * @param object|null   $codeEnum   原始錯誤枚舉
     * @param Throwable|null $previous   前一個異常
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 422,
        public readonly ?object $codeEnum = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous); // code=0, 錯誤碼使用字串
    }

    /**
     * 從錯誤代碼枚舉建立異常實例
     * 
     * @param object      $errorCode 錯誤代碼枚舉 (支援 UserErrorCode, ProductCategoryErrorCode 等)
     * @param string|null $message   自訂錯誤訊息 (可選，預設使用枚舉的預設訊息)
     * @return static
     */
    public static function fromErrorCode(object $errorCode, ?string $message = null): static
    {
        // 取得錯誤訊息 - 使用自訂訊息或枚舉預設訊息
        $errorMessage = $message ?? (
            method_exists($errorCode, 'message') ? $errorCode->message() : 
            (method_exists($errorCode, 'getMessage') ? $errorCode->getMessage() : $errorCode->value)
        );

        // 取得 HTTP 狀態碼
        $httpStatus = 422; // 預設狀態碼
        if (method_exists($errorCode, 'httpStatus')) {
            $httpStatus = $errorCode->httpStatus();
        } elseif (method_exists($errorCode, 'getHttpStatus')) {
            $httpStatus = $errorCode->getHttpStatus();
        }

        return new static(
            $errorMessage,
            $errorCode->value,
            $httpStatus,
            $errorCode
        );
    }

    /**
     * 取得錯誤代碼
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * 取得 HTTP 狀態碼
     */
    public function getHttpStatus(): int
    {
        return $this->status;
    }

    /**
     * 取得 HTTP 狀態碼（舊方法名稱，向後相容）
     */
    public function getHttpStatusCode(): int
    {
        return $this->status;
    }

    /**
     * 轉換為陣列格式
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [],
            'code' => $this->getErrorCode(),
        ];
    }

    /**
     * 轉換為 JSON 格式
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
