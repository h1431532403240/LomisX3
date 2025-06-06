<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\UserStatus;
use App\Models\User;

/**
 * 批次狀態更新 Request 驗證
 * 遵循 LomisX3 架構標準的企業級批次操作
 * 
 * 驗證功能：
 * - 批次使用者 ID 驗證
 * - 門市隔離權限檢查
 * - 管理員帳號保護
 * - 狀態變更權限控制
 * - 操作範圍限制
 */
class BatchStatusUserRequest extends FormRequest
{
    /**
     * 授權檢查
     * 確保使用者具有批次更新使用者狀態的權限
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        // 檢查基本權限
        if (!auth()->user()->can('users.batch-status')) {
            return false;
        }
        
        // 檢查是否有權限操作指定的使用者
        return $this->canOperateOnUsers();
    }

    /**
     * 驗證規則
     * 定義批次狀態更新的所有驗證規則
     */
    public function rules(): array
    {
        return [
            // 使用者 ID 陣列驗證
            'user_ids' => [
                'required',
                'array',
                'min:1',
                'max:100' // 限制單次批次操作數量
            ],
            
            'user_ids.*' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // 檢查使用者是否存在且未軟刪除
                    $user = User::find($value);
                    if (!$user || $user->deleted_at) {
                        $fail('使用者 ID ' . $value . ' 不存在或已被刪除');
                        return;
                    }
                    
                    // 門市隔離檢查
                    if (!$this->canOperateOnUser($user)) {
                        $fail('您沒有權限操作使用者 ID ' . $value);
                        return;
                    }
                    
                    // 管理員保護
                    if ($user->hasRole('admin') && !auth()->user()->hasRole('admin')) {
                        $fail('您沒有權限操作管理員帳號 (ID: ' . $value . ')');
                        return;
                    }
                }
            ],
            
            // 目標狀態驗證
            'status' => [
                'required',
                'string',
                Rule::in(array_column(UserStatus::cases(), 'value')),
                function ($attribute, $value, $fail) {
                    // 檢查狀態變更權限
                    if (!$this->canChangeToStatus($value)) {
                        $fail('您沒有權限將使用者狀態變更為：' . $value);
                    }
                }
            ],
            
            // 操作原因（可選）
            'reason' => [
                'nullable',
                'string',
                'max:500'
            ],
            
            // 是否發送通知
            'send_notification' => [
                'nullable',
                'boolean'
            ],
            
            // 強制執行（跳過某些檢查）
            'force' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    /**
     * 自訂錯誤訊息
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => '必須選擇至少一個使用者',
            'user_ids.array' => '使用者 ID 必須為陣列格式',
            'user_ids.min' => '必須選擇至少一個使用者',
            'user_ids.max' => '單次批次操作最多只能選擇 100 個使用者',
            
            'user_ids.*.required' => '使用者 ID 不能為空',
            'user_ids.*.integer' => '使用者 ID 必須為整數',
            'user_ids.*.exists' => '使用者 ID 不存在',
            
            'status.required' => '目標狀態為必填欄位',
            'status.in' => '目標狀態無效',
            
            'reason.max' => '操作原因不能超過 500 個字元',
        ];
    }

    /**
     * 資料預處理
     * 在驗證前對資料進行預處理
     */
    protected function prepareForValidation(): void
    {
        // 去除重複的使用者 ID
        if ($this->has('user_ids') && is_array($this->user_ids)) {
            $this->merge([
                'user_ids' => array_values(array_unique($this->user_ids))
            ]);
        }
        
        // 設定預設值
        $this->merge([
            'send_notification' => $this->boolean('send_notification', false),
            'force' => $this->boolean('force', false)
        ]);
    }

    /**
     * 檢查是否有權限操作所有指定的使用者
     */
    private function canOperateOnUsers(): bool
    {
        if (!$this->has('user_ids') || !is_array($this->user_ids)) {
            return false;
        }
        
        $users = User::whereIn('id', $this->user_ids)->get();
        
        foreach ($users as $user) {
            if (!$this->canOperateOnUser($user)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 檢查是否有權限操作單一使用者
     */
    private function canOperateOnUser(User $user): bool
    {
        $currentUser = auth()->user();
        
        // 系統管理員可以操作任何使用者
        if ($currentUser->hasRole('admin')) {
            return true;
        }
        
        // 門市隔離：只能操作同門市的使用者
        if ($user->store_id !== $currentUser->store_id) {
            return false;
        }
        
        // 防止操作管理員帳號
        if ($user->hasRole('admin')) {
            return false;
        }
        
        // 防止操作自己的帳號（避免意外鎖定）
        if ($user->id === $currentUser->id) {
            return false;
        }
        
        return true;
    }

    /**
     * 檢查是否有權限變更到指定狀態
     */
    private function canChangeToStatus(string $status): bool
    {
        $currentUser = auth()->user();
        
        // 系統管理員可以變更到任何狀態
        if ($currentUser->hasRole('admin')) {
            return true;
        }
        
        // 門市管理員只能啟用/停用，不能鎖定使用者
        if ($currentUser->hasRole('store_admin')) {
            return in_array($status, ['active', 'inactive']);
        }
        
        // 其他角色沒有批次變更狀態的權限
        return false;
    }

    /**
     * 取得經過驗證的批次操作資料
     */
    public function getBatchData(): array
    {
        return [
            'user_ids' => $this->input('user_ids'),
            'status' => $this->input('status'),
            'reason' => $this->input('reason'),
            'send_notification' => $this->boolean('send_notification'),
            'force' => $this->boolean('force'),
            'operator_id' => auth()->id(),
            'operator_store_id' => auth()->user()->store_id
        ];
    }

    /**
     * 取得受影響的使用者集合
     */
    public function getAffectedUsers()
    {
        return User::whereIn('id', $this->input('user_ids', []))
                   ->with(['roles', 'store'])
                   ->get();
    }

    /**
     * 檢查是否為強制執行模式
     */
    public function isForceMode(): bool
    {
        return $this->boolean('force') && auth()->user()->hasRole('admin');
    }
}
