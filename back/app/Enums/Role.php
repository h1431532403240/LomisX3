<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 使用者角色枚舉
 *
 * 定義系統中所有可用的使用者角色，包含：
 * - 超級管理員 (ADMIN)
 * - 一般管理員 (MANAGER)
 * - 員工 (STAFF)
 * - 訪客 (GUEST)
 */
enum Role: string
{
    /**
     * 超級管理員 - 擁有所有權限
     */
    case ADMIN = 'admin';

    /**
     * 一般管理員 - 擁有管理權限，但無法刪除敏感資料
     */
    case MANAGER = 'manager';

    /**
     * 員工 - 擁有基本查看和部分操作權限
     */
    case STAFF = 'staff';

    /**
     * 訪客 - 僅擁有基本查看權限
     */
    case GUEST = 'guest';

    /**
     * 取得角色的中文名稱
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => '超級管理員',
            self::MANAGER => '一般管理員',
            self::STAFF => '員工',
            self::GUEST => '訪客',
        };
    }

    /**
     * 取得角色的權限等級
     * 數字越大權限越高
     */
    public function getLevel(): int
    {
        return match ($this) {
            self::ADMIN => 100,
            self::MANAGER => 80,
            self::STAFF => 60,
            self::GUEST => 20,
        };
    }

    /**
     * 檢查是否為管理員等級
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * 檢查是否為管理員或經理等級
     */
    public function isManagerOrAbove(): bool
    {
        return in_array($this, [self::ADMIN, self::MANAGER]);
    }

    /**
     * 檢查是否為員工等級或以上
     */
    public function isStaffOrAbove(): bool
    {
        return in_array($this, [self::ADMIN, self::MANAGER, self::STAFF]);
    }

    /**
     * 取得所有管理員等級角色
     *
     * @return array<Role>
     */
    public static function getAdminRoles(): array
    {
        return [self::ADMIN, self::MANAGER];
    }

    /**
     * 取得所有有權限的角色
     *
     * @return array<Role>
     */
    public static function getAuthorizedRoles(): array
    {
        return [self::ADMIN, self::MANAGER, self::STAFF];
    }

    /**
     * 從字串建立角色實例
     *
     * @param string $role 角色字串
     */
    public static function fromString(string $role): ?Role
    {
        return self::tryFrom(strtolower($role));
    }

    /**
     * 檢查角色是否有效
     *
     * @param string $role 角色字串
     */
    public static function isValid(string $role): bool
    {
        return self::fromString($role) !== null;
    }
}
