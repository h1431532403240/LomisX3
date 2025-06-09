<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Enums\{UserStatus, UserRole};

/**
 * 建立使用者 Request 驗證
 * 遵循 LomisX3 架構標準 + V6.2 軟刪除唯一約束
 * 
 * 驗證功能：
 * - 軟刪除唯一約束驗證
 * - 密碼強度驗證
 * - 門市隔離驗證
 * - 角色權限驗證
 * - 2FA 設定驗證
 */
class StoreUserRequest extends FormRequest
{
    /**
     * 授權檢查
     * 確保使用者具有建立使用者的權限
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('users.create');
    }

    /**
     * 驗證規則
     * 定義建立使用者的所有驗證規則
     */
    public function rules(): array
    {
        return [
            // 基本資訊驗證
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                // 使用者名稱唯一性驗證（包含軟刪除）
                Rule::unique('users', 'username')->whereNull('deleted_at')
            ],
            
            'name' => [
                'required',
                'string',
                'max:100'
            ],
            
            'email' => [
                'required',
                'email',
                'max:100',
                // Email 唯一性驗證（包含軟刪除）
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            
            // 密碼強度驗證 (遵循企業級安全標準)
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    // V6.2: 測試環境暫時禁用洩露檢查
                    ->when(!app()->environment('testing'), fn($rule) => $rule->uncompromised())
            ],
            
            'password_confirmation' => [
                'required',
                'string'
            ],
            
            // 門市隔離驗證
            'store_id' => [
                'required',
                'integer',
                'exists:stores,id',
                function ($attribute, $value, $fail) {
                    // 非管理員只能在自己的門市建立使用者
                    if (!auth()->user()->hasRole('admin') && $value != auth()->user()->store_id) {
                        $fail('您只能在自己的門市建立使用者');
                    }
                    
                    // 檢查門市是否啟用
                    $store = \App\Models\Store::find($value);
                    if (!$store || !$store->status) {
                        $fail('無法在已停用的門市建立使用者');
                    }
                }
            ],
            
            // 聯絡資訊
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9\-\+\(\)\s]+$/'
            ],
            
            // 使用者狀態
            'status' => [
                'nullable',
                'string',
                Rule::in(array_column(UserStatus::cases(), 'value'))
            ],
            
            // 角色分配驗證
            'roles' => [
                'nullable',
                'array'
            ],
            
            'roles.*' => [
                'string',
                'exists:roles,name',
                function ($attribute, $value, $fail) {
                    // 檢查角色分配權限
                    if (!$this->canAssignRole($value)) {
                        $fail("您沒有權限分配 {$value} 角色");
                    }
                }
            ],
            
            // 權限分配驗證
            'permissions' => [
                'nullable',
                'array'
            ],
            
            'permissions.*' => [
                'string',
                'exists:permissions,name'
            ],
            
            // 使用者偏好設定
            'preferences' => [
                'nullable',
                'array'
            ],
            
            // 2FA 設定
            'enable_2fa' => [
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
            'username.required' => '使用者名稱為必填欄位',
            'username.min' => '使用者名稱至少需要 3 個字元',
            'username.max' => '使用者名稱不能超過 50 個字元',
            'username.alpha_dash' => '使用者名稱只能包含字母、數字、底線和連字號',
            'username.unique' => '此使用者名稱已被使用',
            
            'name.required' => '姓名為必填欄位',
            'name.max' => '姓名不能超過 100 個字元',
            
            'email.required' => 'Email 為必填欄位',
            'email.email' => 'Email 格式不正確',
            'email.unique' => '此 Email 已被使用',
            
            'password.required' => '密碼為必填欄位',
            'password.confirmed' => '密碼確認不一致',
            
            'store_id.required' => '門市為必填欄位',
            'store_id.exists' => '指定的門市不存在',
            
            'phone.regex' => '電話號碼格式不正確',
            
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
        // 自動設定預設狀態
        if (!$this->has('status')) {
            $this->merge(['status' => UserStatus::PENDING->value]);
        }
        
        // 非管理員自動設定為自己的門市
        if (!auth()->user()->hasRole('admin') && !$this->has('store_id')) {
            $this->merge(['store_id' => auth()->user()->store_id]);
        }
        
        // 清理和格式化 username
        if ($this->has('username')) {
            $this->merge(['username' => strtolower(trim($this->username))]);
        }
        
        // 清理和格式化 email
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
    }

    /**
     * 檢查是否有權限分配指定角色
     * 
     * @param string $role 角色名稱
     * @return bool
     */
    private function canAssignRole(string $role): bool
    {
        $user = auth()->user();
        
        // 系統管理員可以分配任何角色
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // 門市管理員只能分配低於自己層級的角色
        if ($user->hasRole('store_admin')) {
            $allowedRoles = ['manager', 'staff', 'guest'];
            return in_array($role, $allowedRoles);
        }
        
        // 其他使用者不能分配角色
        return false;
    }

    /**
     * 取得經過驗證的使用者資料
     * 
     * @return array
     */
    public function getUserData(): array
    {
        return $this->only([
            'username',
            'name', 
            'email',
            'password',
            'store_id',
            'phone',
            'status',
            'preferences'
        ]);
    }

    /**
     * 取得角色和權限資料
     * 
     * @return array
     */
    public function getRolePermissionData(): array
    {
        return [
            'roles' => $this->input('roles', []),
            'permissions' => $this->input('permissions', []),
            'enable_2fa' => $this->boolean('enable_2fa', false)
        ];
    }
}
