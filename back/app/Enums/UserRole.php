<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 使用者角色枚舉
 * 
 * @author LomisX3 開發團隊
 * @version V1.0
 */
enum UserRole: string
{
    case ADMIN = 'admin';
    case STORE_ADMIN = 'store_admin';
    case MANAGER = 'manager';
    case STAFF = 'staff';
    case GUEST = 'guest';
    
    /**
     * 取得角色標籤
     */
    public function label(): string
    {
        return match($this) {
            self::ADMIN => '系統管理員',
            self::STORE_ADMIN => '門市管理員',
            self::MANAGER => '經理',
            self::STAFF => '員工',
            self::GUEST => '訪客',
        };
    }

    /**
     * 取得角色描述
     */
    public function description(): string
    {
        return match($this) {
            self::ADMIN => '具有系統最高權限，可管理所有門市和使用者',
            self::STORE_ADMIN => '管理特定門市的所有業務和使用者',
            self::MANAGER => '管理門市日常營運和部分員工',
            self::STAFF => '執行日常業務操作',
            self::GUEST => '僅具備基本查看權限',
        };
    }

    /**
     * 取得角色層級
     */
    public function level(): int
    {
        return match($this) {
            self::ADMIN => 100,
            self::STORE_ADMIN => 80,
            self::MANAGER => 60,
            self::STAFF => 40,
            self::GUEST => 20,
        };
    }

    /**
     * 取得角色顏色
     */
    public function color(): string
    {
        return match($this) {
            self::ADMIN => 'danger',
            self::STORE_ADMIN => 'purple',
            self::MANAGER => 'primary',
            self::STAFF => 'success',
            self::GUEST => 'secondary',
        };
    }

    /**
     * 取得預設權限
     */
    public function defaultPermissions(): array
    {
        return match($this) {
            self::ADMIN => ['*'],
            self::STORE_ADMIN => [
                'users.*', 'orders.*', 'products.*', 'categories.*', 
                'reports.view', 'settings.store'
            ],
            self::MANAGER => [
                'users.view', 'orders.*', 'products.view', 'reports.view'
            ],
            self::STAFF => [
                'orders.view', 'orders.create', 'products.view'
            ],
            self::GUEST => [
                'dashboard.view'
            ],
        };
    }

    /**
     * 檢查是否可以管理指定角色
     */
    public function canManage(UserRole $targetRole): bool
    {
        return $this->level() > $targetRole->level();
    }

    /**
     * 取得所有角色選項
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($role) => [
            $role->value => $role->label()
        ])->toArray();
    }
} 