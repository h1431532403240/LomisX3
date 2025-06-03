<?php

namespace App\Enums;

/**
 * 商品分類錯誤代碼枚舉
 * 定義所有商品分類相關的業務錯誤代碼
 */
enum ProductCategoryErrorCode: string
{
    /**
     * 循環引用檢測到
     */
    case CIRCULAR_REFERENCE_DETECTED = 'CIRCULAR_REFERENCE_DETECTED';

    /**
     * 超過最大深度
     */
    case MAX_DEPTH_EXCEEDED = 'MAX_DEPTH_EXCEEDED';

    /**
     * 分類下還有子分類
     */
    case CATEGORY_HAS_CHILDREN = 'CATEGORY_HAS_CHILDREN';

    /**
     * Slug 重複
     */
    case DUPLICATE_SLUG = 'DUPLICATE_SLUG';

    /**
     * 分類不存在
     */
    case CATEGORY_NOT_FOUND = 'CATEGORY_NOT_FOUND';

    /**
     * 批次操作失敗
     */
    case BATCH_OPERATION_FAILED = 'BATCH_OPERATION_FAILED';

    /**
     * 分類下有商品無法刪除
     */
    case CATEGORY_HAS_PRODUCTS = 'CATEGORY_HAS_PRODUCTS';

    /**
     * 父分類不存在
     */
    case PARENT_NOT_FOUND = 'PARENT_NOT_FOUND';

    /**
     * 父分類已停用
     */
    case PARENT_INACTIVE = 'PARENT_INACTIVE';

    /**
     * 排序位置無效
     */
    case INVALID_POSITION = 'INVALID_POSITION';

    /**
     * 分類名稱重複
     */
    case DUPLICATE_NAME = 'DUPLICATE_NAME';

    /**
     * 取得錯誤訊息
     */
    public function getMessage(): string
    {
        return match ($this) {
            self::CIRCULAR_REFERENCE_DETECTED => '不能將分類移動至自己的子分類，這會造成循環引用',
            self::MAX_DEPTH_EXCEEDED => '分類層級不能超過最大限制',
            self::CATEGORY_HAS_CHILDREN => '該分類下還有子分類，無法刪除',
            self::DUPLICATE_SLUG => 'URL 別名已存在',
            self::CATEGORY_NOT_FOUND => '分類不存在',
            self::BATCH_OPERATION_FAILED => '批次操作失敗',
            self::CATEGORY_HAS_PRODUCTS => '該分類下還有商品，無法刪除',
            self::PARENT_NOT_FOUND => '父分類不存在',
            self::PARENT_INACTIVE => '父分類已停用，無法在其下建立子分類',
            self::INVALID_POSITION => '排序位置無效',
            self::DUPLICATE_NAME => '分類名稱已存在',
        };
    }

    /**
     * 取得 HTTP 狀態碼
     */
    public function getHttpStatus(): int
    {
        return match ($this) {
            self::CATEGORY_NOT_FOUND,
            self::PARENT_NOT_FOUND => 404,
            default => 422,
        };
    }
}
