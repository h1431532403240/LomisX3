<?php

namespace App\Http\Requests;

use App\Rules\MaxDepthRule;
use App\Rules\NotSelfOrDescendant;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新商品分類請求驗證
 * 處理更新商品分類時的資料驗證，包含循環引用檢查
 */
class UpdateProductCategoryRequest extends FormRequest
{
    /**
     * 確定使用者是否有權限發出此請求
     */
    public function authorize(): bool
    {
        // TODO: 實作權限控制
        return true;
    }

    /**
     * 取得應用於請求的驗證規則
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('product_category')->id ?? $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                "unique:product_categories,name,{$categoryId},id,deleted_at,NULL",
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                "unique:product_categories,slug,{$categoryId},id,deleted_at,NULL",
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:product_categories,id',
                new NotSelfOrDescendant($categoryId),
                new MaxDepthRule(3),
            ],
            'position' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'status' => [
                'nullable',
                'boolean',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'meta_title' => [
                'nullable',
                'string',
                'max:100',
            ],
            'meta_description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '分類名稱為必填項目',
            'name.string' => '分類名稱必須是字串',
            'name.max' => '分類名稱不能超過 100 個字元',
            'name.unique' => '分類名稱已存在',

            'slug.string' => 'URL 別名必須是字串',
            'slug.max' => 'URL 別名不能超過 100 個字元',
            'slug.regex' => 'URL 別名格式不正確，只能包含小寫字母、數字和連字符',
            'slug.unique' => 'URL 別名已存在',

            'parent_id.integer' => '父分類 ID 必須是整數',
            'parent_id.exists' => '指定的父分類不存在',

            'position.integer' => '排序位置必須是整數',
            'position.min' => '排序位置不能小於 0',

            'status.boolean' => '狀態必須是布林值',

            'description.string' => '分類描述必須是字串',
            'description.max' => '分類描述不能超過 1000 個字元',

            'meta_title.string' => 'SEO 標題必須是字串',
            'meta_title.max' => 'SEO 標題不能超過 100 個字元',

            'meta_description.string' => 'SEO 描述必須是字串',
            'meta_description.max' => 'SEO 描述不能超過 255 個字元',
        ];
    }

    /**
     * 取得驗證屬性的自訂名稱
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '分類名稱',
            'slug' => 'URL 別名',
            'parent_id' => '父分類',
            'position' => '排序位置',
            'status' => '啟用狀態',
            'description' => '分類描述',
            'meta_title' => 'SEO 標題',
            'meta_description' => 'SEO 描述',
        ];
    }

    /**
     * 在驗證完成後執行的動作
     */
    public function after(): array
    {
        return [
            function ($validator) {
                // 可在此處添加額外的業務邏輯驗證
                // 例如：檢查是否有商品使用此分類等
            },
        ];
    }

    /**
     * 準備驗證資料，處理可選欄位
     */
    protected function prepareForValidation(): void
    {
        // 移除空的可選欄位，避免不必要的驗證
        $data = $this->all();

        // 如果 parent_id 為空字串，轉為 null
        if (isset($data['parent_id']) && $data['parent_id'] === '') {
            $data['parent_id'] = null;
        }

        // 如果 slug 為空字串，轉為 null（讓系統自動生成）
        if (isset($data['slug']) && $data['slug'] === '') {
            $data['slug'] = null;
        }

        $this->replace($data);
    }
}
