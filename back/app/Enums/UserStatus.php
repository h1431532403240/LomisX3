<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 使用者狀態枚舉
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case LOCKED = 'locked';
    case PENDING = 'pending';
    
    /**
     * 取得狀態標籤
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => '啟用',
            self::INACTIVE => '停用',
            self::LOCKED => '鎖定',
            self::PENDING => '待審核',
        };
    }

    /**
     * 取得狀態描述
     */
    public function description(): string
    {
        return match($this) {
            self::ACTIVE => '帳號正常啟用，可正常使用系統',
            self::INACTIVE => '帳號已停用，無法登入系統',
            self::LOCKED => '帳號已鎖定，需要管理員解鎖',
            self::PENDING => '帳號待審核，尚未通過驗證',
        };
    }

    /**
     * 取得狀態顏色（用於前端顯示）
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'secondary',
            self::LOCKED => 'danger',
            self::PENDING => 'warning',
        };
    }

    /**
     * 判斷是否為可登入狀態
     */
    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * 判斷是否需要管理員處理
     */
    public function requiresAdminAction(): bool
    {
        return in_array($this, [self::LOCKED, self::PENDING]);
    }

    /**
     * 取得所有狀態選項（用於表單）
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($status) => [
            $status->value => $status->label()
        ])->toArray();
    }

    /**
     * 取得狀態轉換規則
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::ACTIVE, self::INACTIVE],
            self::ACTIVE => [self::INACTIVE, self::LOCKED],
            self::INACTIVE => [self::ACTIVE],
            self::LOCKED => [self::ACTIVE, self::INACTIVE],
        };
    }

    /**
     * 檢查是否可以轉換到指定狀態
     */
    public function canTransitionTo(UserStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }

    /**
     * 取得所有狀態的陣列
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
} 