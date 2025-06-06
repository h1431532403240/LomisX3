<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserStatus;

/**
 * 使用者列表查詢 Request 驗證
 * 遵循 LomisX3 架構標準的統一驗證規範
 * 
 * 支援功能：
 * - 分頁參數驗證
 * - 搜尋關鍵字驗證
 * - 狀態篩選驗證
 * - 排序參數驗證
 * - 日期範圍篩選
 */
class IndexUserRequest extends FormRequest
{
    /**
     * 授權檢查
     * 確保使用者具有查看使用者列表的權限
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('users.view');
    }

    /**
     * 驗證規則
     * 定義所有查詢參數的驗證規則
     */
    public function rules(): array
    {
        return [
            // 分頁參數
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'cursor' => ['string'], // 支援 cursor 分頁
            
            // 搜尋參數
            'search' => ['nullable', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:255'],
            
            // 篩選參數
            'status' => ['nullable', 'string', 'in:active,inactive,locked,pending'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'has_2fa' => ['nullable', 'boolean'],
            'email_verified' => ['nullable', 'boolean'],
            
            // 日期範圍篩選
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'last_login_from' => ['nullable', 'date'],
            'last_login_to' => ['nullable', 'date', 'after_or_equal:last_login_from'],
            
            // 排序參數
            'sort' => ['nullable', 'string', 'in:id,name,username,email,status,last_login_at,created_at,updated_at'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            
            // 包含關聯
            'include' => ['nullable', 'string'],
            'with_count' => ['nullable', 'boolean'],
        ];
    }

    /**
     * 自訂錯誤訊息
     */
    public function messages(): array
    {
        return [
            'per_page.max' => '每頁最多只能顯示 100 筆資料',
            'status.in' => '狀態必須為：active, inactive, locked, pending 其中之一',
            'store_id.exists' => '指定的門市不存在',
            'role.exists' => '指定的角色不存在',
            'created_to.after_or_equal' => '結束日期必須大於或等於開始日期',
            'last_login_to.after_or_equal' => '登入結束日期必須大於或等於開始日期',
            'sort.in' => '排序欄位無效',
            'order.in' => '排序方向必須為 asc 或 desc',
        ];
    }

    /**
     * 資料預處理
     * 在驗證前對資料進行預處理
     */
    protected function prepareForValidation(): void
    {
        // 設定預設分頁參數
        if (!$this->has('per_page')) {
            $this->merge(['per_page' => 20]);
        }
        
        // 設定預設排序
        if (!$this->has('sort')) {
            $this->merge([
                'sort' => 'created_at',
                'order' => 'desc'
            ]);
        }
        
        // 門市隔離：非管理員只能查看自己門市的使用者
        if (auth()->check() && !auth()->user()->hasRole('admin')) {
            $this->merge(['store_id' => auth()->user()->store_id]);
        }
    }

    /**
     * 取得篩選參數
     * 返回經過驗證的篩選參數陣列
     */
    public function getFilters(): array
    {
        return $this->only([
            'search',
            'keyword', 
            'status',
            'store_id',
            'role',
            'has_2fa',
            'email_verified',
            'created_from',
            'created_to',
            'last_login_from',
            'last_login_to'
        ]);
    }

    /**
     * 取得分頁參數
     */
    public function getPaginationParams(): array
    {
        return [
            'page' => $this->integer('page', 1),
            'per_page' => $this->integer('per_page', 20),
            'cursor' => $this->string('cursor')
        ];
    }

    /**
     * 取得排序參數
     */
    public function getSortParams(): array
    {
        return [
            'sort' => $this->string('sort', 'created_at'),
            'order' => $this->string('order', 'desc')
        ];
    }
}
