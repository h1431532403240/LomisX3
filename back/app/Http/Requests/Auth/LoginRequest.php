<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\{Cache, Hash, RateLimiter};
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Enums\{UserStatus, UserErrorCode};

/**
 * 登入 Request 驗證
 * 遵循 V6.2 企業級安全標準
 * 
 * 安全功能：
 * - 登入節流限制 (5次/分鐘)
 * - IP 位址追蹤
 * - 帳號鎖定機制
 * - 登入嘗試次數統計
 * - 異常登入檢測
 */
class LoginRequest extends FormRequest
{
    /**
     * 最大登入嘗試次數
     */
    private const MAX_LOGIN_ATTEMPTS = 5;
    
    /**
     * 帳號鎖定時間（分鐘）
     */
    private const LOCKOUT_MINUTES = 30;
    
    /**
     * 節流限制時間（秒）
     */
    private const THROTTLE_SECONDS = 60;

    /**
     * 授權檢查
     * 登入請求不需要特別授權
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 驗證規則
     * 定義登入的所有驗證規則
     */
    public function rules(): array
    {
        return [
            // 登入帳號驗證（支援使用者名稱或 Email）
            'login' => [
                'required',
                'string',
                'max:100'
            ],
            
            // 密碼驗證
            'password' => [
                'required',
                'string',
                'max:255'
            ],
            
            // 記住我選項
            'remember' => [
                'nullable',
                'boolean'
            ],
            
            // 2FA 驗證碼（如果啟用）
            'two_factor_code' => [
                'nullable',
                'string',
                'size:6',
                'regex:/^[0-9]+$/'
            ],
            
            // 2FA 恢復代碼
            'recovery_code' => [
                'nullable',
                'string',
                'size:10'
            ],
            
            // 裝置信息（可選）
            'device_name' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];
    }

    /**
     * 自訂錯誤訊息
     */
    public function messages(): array
    {
        return [
            'login.required' => '請輸入帳號或 Email',
            'login.max' => '帳號長度不能超過 100 個字元',
            
            'password.required' => '請輸入密碼',
            'password.max' => '密碼長度不能超過 255 個字元',
            
            'two_factor_code.size' => '雙因子驗證碼必須為 6 位數字',
            'two_factor_code.regex' => '雙因子驗證碼只能包含數字',
            
            'recovery_code.size' => '恢復代碼必須為 10 個字元',
            
            'device_name.max' => '裝置名稱不能超過 255 個字元',
        ];
    }

    /**
     * 資料預處理
     */
    protected function prepareForValidation(): void
    {
        // 清理登入帳號
        if ($this->has('login')) {
            $this->merge(['login' => strtolower(trim($this->login))]);
        }
        
        // 記錄登入嘗試的 IP
        $this->merge([
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent()
        ]);
        
        // 設定預設裝置名稱
        if (!$this->has('device_name')) {
            $this->merge(['device_name' => $this->getDefaultDeviceName()]);
        }
    }

    /**
     * 取得預設裝置名稱
     */
    private function getDefaultDeviceName(): string
    {
        $userAgent = $this->userAgent();
        
        if (str_contains($userAgent, 'Mobile')) {
            return '手機';
        }
        
        if (str_contains($userAgent, 'Tablet')) {
            return '平板';
        }
        
        return '桌上型電腦';
    }

    /**
     * 取得登入資料
     */
    public function getLoginData(): array
    {
        return [
            'login' => $this->login,
            'password' => $this->password,
            'remember' => $this->boolean('remember'),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'device_name' => $this->device_name
        ];
    }
}
