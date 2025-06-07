<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Enums\{UserStatus, UserRole};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 系統配置 API 控制器
 * 
 * 提供前端所需的所有枚舉值配置和本地化文本
 * 符合 LomisX3 V4.0 配置驅動UI架構標準
 * 
 * @author LomisX3 開發團隊
 * @version V1.0
 */
class SystemController extends BaseController
{
    /**
     * 取得系統配置
     * 
     * 包含所有枚舉值的本地化文本，實現配置驅動UI
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getConfigs(Request $request): JsonResponse
    {
        try {
            $configs = [
                // 使用者狀態配置
                'user_statuses' => $this->getUserStatusConfigs(),
                
                // 使用者角色配置  
                'user_roles' => $this->getUserRoleConfigs(),
                
                // 系統元資訊
                'meta' => [
                    'version' => config('app.version', '1.0.0'),
                    'generated_at' => now()->toISOString(),
                    'locale' => app()->getLocale(),
                ]
            ];

            return $this->successResponse($configs, '系統配置取得成功');
            
        } catch (\Exception $e) {
            return $this->errorResponse('系統配置取得失敗', 500, 'SYSTEM_CONFIG_ERROR');
        }
    }

    /**
     * 取得使用者狀態配置
     * 
     * @return array
     */
    private function getUserStatusConfigs(): array
    {
        return collect(UserStatus::cases())->map(function ($status) {
            return [
                'value' => $status->value,
                'label' => $status->label(),
                'description' => $status->description(),
                'color' => $status->color(),
                'can_login' => $status->canLogin(),
                'requires_admin_action' => $status->requiresAdminAction(),
            ];
        })->keyBy('value')->toArray();
    }

    /**
     * 取得使用者角色配置
     * 
     * @return array
     */
    private function getUserRoleConfigs(): array
    {
        return collect(UserRole::cases())->map(function ($role) {
            return [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
                'level' => $role->level(),
                'color' => $role->color(),
                'permissions' => $role->defaultPermissions(),
            ];
        })->keyBy('value')->toArray();
    }

    /**
     * 取得特定枚舉配置
     * 
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    public function getEnumConfig(Request $request, string $type): JsonResponse
    {
        try {
            $config = match($type) {
                'user-statuses' => $this->getUserStatusConfigs(),
                'user-roles' => $this->getUserRoleConfigs(),
                default => throw new \InvalidArgumentException("不支援的枚舉類型: {$type}")
            };

            return $this->successResponse($config, "枚舉配置 {$type} 取得成功");
            
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400, 'INVALID_ENUM_TYPE');
        } catch (\Exception $e) {
            return $this->errorResponse('枚舉配置取得失敗', 500, 'ENUM_CONFIG_ERROR');
        }
    }
} 