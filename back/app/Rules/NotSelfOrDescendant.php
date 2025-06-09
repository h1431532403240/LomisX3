<?php

namespace App\Rules;

use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 防止自我或子孫引用驗證規則
 * 防止分類設置自己或自己的子分類為父分類，避免循環引用
 */
class NotSelfOrDescendant implements ValidationRule
{
    /**
     * 商品分類 Repository
     */
    protected ProductCategoryRepositoryInterface $repository;

    /**
     * 建構函式
     */
    public function __construct(
        protected ?int $categoryId
    ) {
        $this->repository = app(ProductCategoryRepositoryInterface::class);
    }

    /**
     * 執行驗證
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 如果沒有父分類，允許通過
        if (empty($value)) {
            return;
        }

        // 如果沒有當前分類 ID，跳過檢查（新增時）
        if (! $this->categoryId) {
            return;
        }

        // 檢查是否設置自己為父分類
        if ($value == $this->categoryId) {
            $fail('不能將分類設置為自己的父分類');

            return;
        }

        // 使用 Repository 檢查循環引用
        if (! $this->repository->checkCircularReference($this->categoryId, $value)) {
            $fail('不能將分類移動至自己的子分類，這會造成循環引用');
        }
    }
}
