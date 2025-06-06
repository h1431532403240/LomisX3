<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 使用者角色枚舉
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
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
            self::ADMIN => '擁有系統最高權限，可管理所有門市和使用者',
            self::STORE_ADMIN => '可管理指定門市的使用者和資料',
            self::MANAGER => '可查看和管理部分門市功能',
            self::STAFF => '一般員工，基本查看權限',
            self::GUEST => '訪客權限，僅限查看自己的資料',
        };
    }

    /**
     * 取得角色權限列表
     */
    public function getPermissions(): array
    {
        return match($this) {
            self::ADMIN => [
                'users.*',
                'roles.*',
                'permissions.*',
                'stores.*',
                'reports.*',
                'media.view.private',
                'system.admin',
                'product-categories.*',
                'activities.view.all',
            ],
            self::STORE_ADMIN => [
                'users.view',
                'users.create', 
                'users.update',
                'roles.view',
                'stores.view', 
                'stores.update',
                'reports.view.store',
                'product-categories.view',
                'product-categories.create',
                'product-categories.update',
                'activities.view.store',
            ],
            self::MANAGER => [
                'users.view',
                'stores.view',
                'reports.view.store',
                'product-categories.view',
                'activities.view.store',
            ],
            self::STAFF => [
                'users.view.own',
                'stores.view',
                'product-categories.view',
                'activities.view.own',
            ],
            self::GUEST => [
                'users.view.own',
            ],
        };
    }

    /**
     * 取得角色級別（數字越大權限越高）
     */
    public function level(): int
    {
        return match($this) {
            self::GUEST => 1,
            self::STAFF => 2,
            self::MANAGER => 3,
            self::STORE_ADMIN => 4,
            self::ADMIN => 5,
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
     * 檢查是否為管理員角色
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::STORE_ADMIN]);
    }

    /**
     * 檢查是否可以跨門市操作
     */
    public function canAccessMultipleStores(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * 取得角色顏色（用於前端顯示）
     */
    public function color(): string
    {
        return match($this) {
            self::ADMIN => 'danger',
            self::STORE_ADMIN => 'warning',
            self::MANAGER => 'info',
            self::STAFF => 'success',
            self::GUEST => 'secondary',
        };
    }

    /**
     * 取得角色圖示
     */
    public function icon(): string
    {
        return match($this) {
            self::ADMIN => 'crown',
            self::STORE_ADMIN => 'shield-check',
            self::MANAGER => 'briefcase',
            self::STAFF => 'user',
            self::GUEST => 'eye',
        };
    }

    /**
     * 取得可分配的下級角色
     */
    public function getAssignableRoles(): array
    {
        return collect(self::cases())
            ->filter(fn($role) => $this->canManage($role))
            ->values()
            ->toArray();
    }

    /**
     * 取得所有角色選項（用於表單）
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($role) => [
            $role->value => $role->label()
        ])->toArray();
    }

    /**
     * 根據權限級別取得適當的預設角色
     */
    public static function getDefaultRole(): self
    {
        return self::STAFF;
    }

    /**
     * 檢查角色是否需要特殊審核
     */
    public function requiresApproval(): bool
    {
        return in_array($this, [self::ADMIN, self::STORE_ADMIN]);
    }
} 