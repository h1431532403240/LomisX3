<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Enums\{UserStatus, UserRole};

/**
 * 更新使用者 Request 驗證
 * 遵循 LomisX3 架構標準 + V6.2 軟刪除唯一約束
 * 
 * 驗證功能：
 * - 軟刪除唯一約束排除自己
 * - 可選密碼更新驗證
 * - 門市隔離權限檢查
 * - 角色變更權限驗證
 * - 部分欄位更新支援
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * 授權檢查
     * 確保使用者具有更新使用者的權限
     */
    public function authorize(): bool
    {
        $user = $this->route('user');
        
        if (!auth()->check()) {
            return false;
        }
        
        // 檢查基本權限
        if (!auth()->user()->can('users.update')) {
            return false;
        }
        
        // 門市隔離檢查：非管理員只能更新自己門市的使用者
        if (!auth()->user()->hasRole('admin') && $user->store_id !== auth()->user()->store_id) {
            return false;
        }
        
        // 防止刪除或降級管理員帳號
        if ($user->hasRole('admin') && !auth()->user()->hasRole('admin')) {
            return false;
        }
        
        return true;
    }

    /**
     * 驗證規則
     * 定義更新使用者的所有驗證規則
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;
        
        return [
            // 基本資訊驗證（可選更新）
            'username' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                // V6.2 軟刪除唯一約束：排除當前使用者
                Rule::unique('users', 'username')->whereNull('deleted_at')->ignore($userId)
            ],
            
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100'
            ],
            
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:100',
                // Email 唯一性驗證：排除當前使用者和軟刪除記錄
                Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($userId)
            ],
            
            // 密碼更新（可選）
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            
            'password_confirmation' => [
                'required_with:password',
                'string'
            ],
            
            // 門市隔離驗證
            'store_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:stores,id',
                function ($attribute, $value, $fail) use ($userId) {
                    // 檢查門市變更權限
                    if (!$this->canChangeStore($userId, $value)) {
                        $fail('您沒有權限變更使用者所屬門市');
                    }
                    
                    // 檢查門市是否啟用
                    $store = \App\Models\Store::find($value);
                    if (!$store || !$store->status) {
                        $fail('無法轉移到已停用的門市');
                    }
                }
            ],
            
            // 聯絡資訊
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9\-\+\(\)\s]+$/'
            ],
            
            // 使用者狀態
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(array_column(UserStatus::cases(), 'value')),
                function ($attribute, $value, $fail) use ($userId) {
                    // 防止停用管理員帳號
                    if (!$this->canChangeStatus($userId, $value)) {
                        $fail('您沒有權限變更此使用者的狀態');
                    }
                }
            ],
            
            // 角色分配驗證
            'roles' => [
                'sometimes',
                'array'
            ],
            
            'roles.*' => [
                'string',
                'exists:roles,name',
                function ($attribute, $value, $fail) use ($userId) {
                    // 檢查角色分配權限
                    if (!$this->canAssignRole($value, $userId)) {
                        $fail("您沒有權限分配 {$value} 角色");
                    }
                }
            ],
            
            // 權限分配驗證
            'permissions' => [
                'sometimes',
                'array'
            ],
            
            'permissions.*' => [
                'string',
                'exists:permissions,name'
            ],
            
            // 使用者偏好設定
            'preferences' => [
                'sometimes',
                'nullable',
                'array'
            ],
            
            // 2FA 設定
            'enable_2fa' => [
                'sometimes',
                'boolean'
            ],
            
            'disable_2fa' => [
                'sometimes',
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
            'username.min' => '使用者名稱至少需要 3 個字元',
            'username.max' => '使用者名稱不能超過 50 個字元',
            'username.alpha_dash' => '使用者名稱只能包含字母、數字、底線和連字號',
            'username.unique' => '此使用者名稱已被使用',
            
            'name.required' => '姓名為必填欄位',
            'name.max' => '姓名不能超過 100 個字元',
            
            'email.required' => 'Email 為必填欄位',
            'email.email' => 'Email 格式不正確',
            'email.unique' => '此 Email 已被使用',
            
            'password.confirmed' => '密碼確認不一致',
            'password_confirmation.required_with' => '更新密碼時必須提供密碼確認',
            
            'store_id.exists' => '指定的門市不存在',
            
            'phone.regex' => '電話號碼格式不正確',
            
            'status.in' => '使用者狀態無效',
            
            'roles.array' => '角色必須為陣列格式',
            'roles.*.exists' => '指定的角色不存在',
            
            'permissions.array' => '權限必須為陣列格式',
            'permissions.*.exists' => '指定的權限不存在',
        ];
    }

    /**
     * 資料預處理
     * 在驗證前對資料進行預處理
     */
    protected function prepareForValidation(): void
    {
        // 清理和格式化 username
        if ($this->has('username')) {
            $this->merge(['username' => strtolower(trim($this->username))]);
        }
        
        // 清理和格式化 email
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
        
        // 處理 2FA 設定互斥
        if ($this->boolean('disable_2fa')) {
            $this->merge(['enable_2fa' => false]);
        }
    }

    /**
     * 檢查是否有權限變更門市
     */
    private function canChangeStore(int $userId, int $newStoreId): bool
    {
        $currentUser = auth()->user();
        $targetUser = \App\Models\User::find($userId);
        
        // 系統管理員可以變更任何使用者的門市
        if ($currentUser->hasRole('admin')) {
            return true;
        }
        
        // 門市管理員不能轉移使用者到其他門市
        if ($targetUser->store_id !== $currentUser->store_id || $newStoreId !== $currentUser->store_id) {
            return false;
        }
        
        return true;
    }

    /**
     * 檢查是否有權限變更狀態
     */
    private function canChangeStatus(int $userId, string $newStatus): bool
    {
        $currentUser = auth()->user();
        $targetUser = \App\Models\User::find($userId);
        
        // 系統管理員可以變更任何使用者狀態
        if ($currentUser->hasRole('admin')) {
            return true;
        }
        
        // 防止停用管理員帳號
        if ($targetUser->hasRole('admin') && $newStatus !== 'active') {
            return false;
        }
        
        // 防止自己停用自己的帳號
        if ($targetUser->id === $currentUser->id && $newStatus !== 'active') {
            return false;
        }
        
        return true;
    }

    /**
     * 檢查是否有權限分配指定角色
     */
    private function canAssignRole(string $role, int $userId): bool
    {
        $currentUser = auth()->user();
        $targetUser = \App\Models\User::find($userId);
        
        // 系統管理員可以分配任何角色
        if ($currentUser->hasRole('admin')) {
            return true;
        }
        
        // 防止提升權限到管理員
        if ($role === 'admin') {
            return false;
        }
        
        // 門市管理員只能分配低於自己層級的角色
        if ($currentUser->hasRole('store_admin')) {
            $allowedRoles = ['manager', 'staff', 'guest'];
            return in_array($role, $allowedRoles);
        }
        
        return false;
    }

    /**
     * 取得經過驗證的使用者資料
     */
    public function getUserData(): array
    {
        return array_filter($this->only([
            'username',
            'name', 
            'email',
            'password',
            'store_id',
            'phone',
            'status',
            'preferences'
        ]), function ($value) {
            return $value !== null;
        });
    }

    /**
     * 取得角色和權限變更資料
     */
    public function getRolePermissionData(): array
    {
        return [
            'roles' => $this->input('roles'),
            'permissions' => $this->input('permissions'),
            'enable_2fa' => $this->has('enable_2fa') ? $this->boolean('enable_2fa') : null,
            'disable_2fa' => $this->boolean('disable_2fa', false)
        ];
    }
}
