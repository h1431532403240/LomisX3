<?php

namespace App\Exceptions;

use App\Enums\ProductCategoryErrorCode;
use RuntimeException;
use Throwable;

/**
 * 業務邏輯異常類別
 * 處理應用程式業務邏輯相關的錯誤
 */
class BusinessException extends RuntimeException
{
    /**
     * 建立業務異常實例
     *
     * @param string                   $message  錯誤訊息
     * @param ProductCategoryErrorCode $codeEnum 錯誤代碼枚舉
     * @param int                      $status   HTTP 狀態碼
     * @param Throwable|null           $previous 前一個異常
     */
    public function __construct(
        string $message,
        public readonly ProductCategoryErrorCode $codeEnum,
        public readonly int $status = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous); // code=0, 錯誤碼使用 Enum
    }

    /**
     * 使用錯誤代碼建立異常
     *
     * @param ProductCategoryErrorCode $codeEnum      錯誤代碼
     * @param string|null              $customMessage 自訂錯誤訊息
     */
    public static function fromErrorCode(
        ProductCategoryErrorCode $codeEnum,
        ?string $customMessage = null
    ): static {
        $message = $customMessage ?? $codeEnum->getMessage();
        $status = $codeEnum->getHttpStatus();

        return new static($message, $codeEnum, $status);
    }

    /**
     * 取得錯誤代碼
     */
    public function getErrorCode(): string
    {
        return $this->codeEnum->value;
    }

    /**
     * 取得 HTTP 狀態碼
     */
    public function getHttpStatus(): int
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
