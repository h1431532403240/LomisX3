<?php

namespace App\Rules;

use App\Repositories\Contracts\ProductCategoryRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 最大深度限制驗證規則
 * 檢查分類的層級深度是否超過設定的最大值
 */
class MaxDepthRule implements ValidationRule
{
    /**
     * 商品分類 Repository
     */
    protected ProductCategoryRepositoryInterface $repository;

    /**
     * 建構函式
     */
    public function __construct(
        protected int $maxDepth = 3
    ) {
        $this->repository = app(ProductCategoryRepositoryInterface::class);
    }

    /**
     * 執行驗證
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 如果沒有父分類，深度為 0，允許通過
        if (empty($value)) {
            return;
        }

        // 查找父分類
        $parent = $this->repository->findById($value);

        // 如果父分類不存在，讓其他規則處理
        if (! $parent) {
            return;
        }

        // 計算新的深度
        $newDepth = $parent->depth + 1;

        // 檢查是否超過最大深度
        if ($newDepth > $this->maxDepth) {
            $fail("分類層級不能超過 {$this->maxDepth} 層，當前將為第 {$newDepth} 層");
        }
    }
}
