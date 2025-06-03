<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 商品分類排序請求驗證
 * 處理拖曳排序時的資料驗證
 */
class SortProductCategoryRequest extends FormRequest
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
            'positions' => [
                'required',
                'array',
                'min:1',
            ],
            'positions.*.id' => [
                'required',
                'integer',
                'exists:product_categories,id',
            ],
            'positions.*.position' => [
                'required',
                'integer',
                'min:0',
            ],
            'positions.*.parent_id' => [
                'nullable',
                'integer',
                'exists:product_categories,id',
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
            'positions.required' => '排序資料為必填項目',
            'positions.array' => '排序資料必須是陣列格式',
            'positions.min' => '至少需要一個排序項目',

            'positions.*.id.required' => '分類 ID 為必填項目',
            'positions.*.id.integer' => '分類 ID 必須是整數',
            'positions.*.id.exists' => '指定的分類不存在',

            'positions.*.position.required' => '排序位置為必填項目',
            'positions.*.position.integer' => '排序位置必須是整數',
            'positions.*.position.min' => '排序位置不能小於 0',

            'positions.*.parent_id.integer' => '父分類 ID 必須是整數',
            'positions.*.parent_id.exists' => '指定的父分類不存在',
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
            'positions' => '排序資料',
            'positions.*.id' => '分類 ID',
            'positions.*.position' => '排序位置',
            'positions.*.parent_id' => '父分類 ID',
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
                $ids = collect($this->input('positions'))->pluck('id');
                if ($ids->count() !== $ids->unique()->count()) {
                    $validator->errors()->add('positions', '排序資料中包含重複的分類 ID');
                }

                // 檢查是否有重複的 position（同層級內）
                $positions = collect($this->input('positions'));
                $groupedByParent = $positions->groupBy('parent_id');

                foreach ($groupedByParent as $parentId => $group) {
                    $positionValues = $group->pluck('position');
                    if ($positionValues->count() !== $positionValues->unique()->count()) {
                        $validator->errors()->add('positions', '同層級分類的排序位置不能重複');
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
        // 確保 positions 是陣列格式
        if ($this->has('positions') && ! is_array($this->input('positions'))) {
            $this->merge([
                'positions' => [],
            ]);
        }

        // 處理 parent_id 為空字串的情況
        $positions = $this->input('positions', []);
        foreach ($positions as $index => $position) {
            if (isset($position['parent_id']) && $position['parent_id'] === '') {
                $positions[$index]['parent_id'] = null;
            }
        }

        $this->merge(['positions' => $positions]);
    }
}
