<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 批次狀態更新請求驗證
 * 處理批次更新商品分類狀態時的資料驗證
 */
class BatchStatusProductCategoryRequest extends FormRequest
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
        return [
            'ids' => [
                'required',
                'array',
                'min:1',
                'max:100', // 限制一次最多處理 100 個項目
            ],
            'ids.*' => [
                'integer',
                'exists:product_categories,id',
            ],
            'status' => [
                'required',
                'boolean',
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
            'ids.required' => '請選擇要更新的分類',
            'ids.array' => '分類 ID 必須是陣列格式',
            'ids.min' => '至少需要選擇一個分類',
            'ids.max' => '一次最多只能處理 100 個分類',

            'ids.*.integer' => '分類 ID 必須是整數',
            'ids.*.exists' => '選中的分類中包含不存在的項目',

            'status.required' => '請指定要更新的狀態',
            'status.boolean' => '狀態值必須是布林值（true 或 false）',
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
            'ids' => '分類 ID 列表',
            'ids.*' => '分類 ID',
            'status' => '狀態',
        ];
    }

    /**
     * 在驗證完成後執行的動作
     */
    public function after(): array
    {
        return [
            function ($validator) {
                // 檢查是否有重複的 ID
                $ids = $this->input('ids', []);
                if (count($ids) !== count(array_unique($ids))) {
                    $validator->errors()->add('ids', '分類 ID 列表中包含重複項目');
                }

                // 檢查 ID 是否都是正整數
                foreach ($ids as $id) {
                    if (! is_int($id) && ! ctype_digit((string) $id)) {
                        $validator->errors()->add('ids', '所有分類 ID 必須是有效的整數');

                        break;
                    }
                }
            },
        ];
    }

    /**
     * 準備驗證資料
     */
    protected function prepareForValidation(): void
    {
        // 確保 ids 是陣列格式
        if ($this->has('ids') && ! is_array($this->input('ids'))) {
            $this->merge([
                'ids' => [],
            ]);
        }

        // 移除無效的 ID（空值、非數字等）
        $ids = $this->input('ids', []);
        $cleanIds = array_filter($ids, function ($id) {
            return $id !== null && $id !== '' && (is_int($id) || ctype_digit((string) $id));
        });

        // 轉換為整數並去重
        $cleanIds = array_unique(array_map('intval', $cleanIds));

        $this->merge([
            'ids' => array_values($cleanIds), // 重新索引陣列
            'status' => $this->boolean('status'), // 轉換為布林值
        ]);
    }
}
