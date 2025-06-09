<?php

declare(strict_types=1);

namespace App\Enums\Auth;

/**
 * 登入類型枚舉
 * 
 * 定義使用者可以使用的登入方式
 * 
 * @package App\Enums\Auth
 * @author LomisX3 Team
 * @version 6.2
 */
enum LoginType: string
{
    /**
     * 使用 Email 登入
     */
    case Email = 'email';
    
    /**
     * 使用使用者名稱登入
     */
    case Username = 'username';

    /**
     * 取得登入類型的顯示名稱
     * 
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Username => '使用者名稱',
        };
    }

    /**
     * 取得登入類型的描述
     * 
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::Email => '使用電子郵件地址登入',
            self::Username => '使用使用者名稱登入',
        };
    }

    /**
     * 檢查是否為 Email 登入
     * 
     * @return bool
     */
    public function isEmail(): bool
    {
        return $this === self::Email;
    }

    /**
     * 檢查是否為使用者名稱登入
     * 
     * @return bool
     */
    public function isUsername(): bool
    {
        return $this === self::Username;
    }

    /**
     * 取得所有登入類型
     * 
     * @return array<LoginType>
     */
    public static function all(): array
    {
        return [
            self::Email,
            self::Username,
        ];
    }

    /**
     * 根據輸入值自動檢測登入類型
     * 
     * @param string $input
     * @return self
     */
    public static function detect(string $input): self
    {
        return filter_var($input, FILTER_VALIDATE_EMAIL) 
            ? self::Email 
            : self::Username;
    }
} 