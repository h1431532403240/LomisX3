<?php

namespace Tests\Unit;

use App\Enums\ProductCategoryErrorCode;
use App\Exceptions\BusinessException;
use PHPUnit\Framework\TestCase;

/**
 * 業務異常測試
 * 測試 BusinessException 的功能正確性
 */
class BusinessExceptionTest extends TestCase
{
    /**
     * 測試使用錯誤代碼建立異常
     */
    public function test_can_create_exception_from_error_code(): void
    {
        $exception = BusinessException::fromErrorCode(
            ProductCategoryErrorCode::MAX_DEPTH_EXCEEDED
        );

        $this->assertInstanceOf(BusinessException::class, $exception);
        $this->assertEquals(ProductCategoryErrorCode::MAX_DEPTH_EXCEEDED, $exception->codeEnum);
        $this->assertEquals('MAX_DEPTH_EXCEEDED', $exception->getErrorCode());
        $this->assertEquals(422, $exception->getHttpStatus());
    }

    /**
     * 測試使用自訂訊息建立異常
     */
    public function test_can_create_exception_with_custom_message(): void
    {
        $customMessage = '自訂錯誤訊息';
        $exception = BusinessException::fromErrorCode(
            ProductCategoryErrorCode::CATEGORY_NOT_FOUND,
            $customMessage
        );

        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(404, $exception->getHttpStatus());
    }

    /**
     * 測試轉換為陣列格式
     */
    public function test_to_array_returns_correct_structure(): void
    {
        $exception = BusinessException::fromErrorCode(
            ProductCategoryErrorCode::DUPLICATE_SLUG,
            'Slug 已存在'
        );

        $array = $exception->toArray();

        $this->assertIsArray($array);
        $this->assertFalse($array['success']);
        $this->assertEquals('Slug 已存在', $array['message']);
        $this->assertEquals([], $array['errors']);
        $this->assertEquals('DUPLICATE_SLUG', $array['code']);
    }

    /**
     * 測試轉換為 JSON 格式
     */
    public function test_to_json_returns_valid_json(): void
    {
        $exception = BusinessException::fromErrorCode(
            ProductCategoryErrorCode::CIRCULAR_REFERENCE_DETECTED
        );

        $json = $exception->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertArrayHasKey('message', $decoded);
        $this->assertArrayHasKey('errors', $decoded);
        $this->assertArrayHasKey('code', $decoded);
    }

    /**
     * 測試不同錯誤代碼的 HTTP 狀態碼
     */
    public function test_different_error_codes_return_correct_status(): void
    {
        // 404 錯誤
        $notFoundException = BusinessException::fromErrorCode(
            ProductCategoryErrorCode::CATEGORY_NOT_FOUND
        );
        $this->assertEquals(404, $notFoundException->getHttpStatus());

        // 422 錯誤
        $validationException = BusinessException::fromErrorCode(
            ProductCategoryErrorCode::MAX_DEPTH_EXCEEDED
        );
        $this->assertEquals(422, $validationException->getHttpStatus());
    }
}
