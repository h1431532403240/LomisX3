<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Enums\UserStatus;

/**
 * 使用者集合資源回應格式
 * 遵循 LomisX3 架構標準的統一集合回應格式
 * 
 * 功能特色：
 * - 統計資訊彙總
 * - 分頁元資料
 * - 篩選狀態摘要
 * - 效能優化資料
 * - 門市級別統計
 */
class UserCollection extends ResourceCollection
{
    /**
     * 指定使用的資源類別
     */
    public $collects = UserResource::class;

    /**
     * 轉換資源集合為陣列格式
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 檢查是否為分頁資料
        if ($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            // 取得分頁器
            $paginator = $this->resource;
            
            // 手動建構分頁回應
            return [
                'data' => $this->collection,
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
                'meta' => array_merge([
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'path' => $paginator->path(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ], [
                    // 基本統計
                    'statistics' => $this->getStatistics($request),
                    
                    // 狀態分布
                    'status_distribution' => $this->getStatusDistribution(),
                    
                    // 角色分布
                    'role_distribution' => $this->when(
                        auth()->user()->can('users.view_statistics'),
                        fn() => $this->getRoleDistribution()
                    ),
                    
                    // 門市分布
                    'store_distribution' => $this->when(
                        auth()->user()->hasRole('admin'),
                        fn() => $this->getStoreDistribution()
                    ),
                    
                    // 時間統計
                    'time_statistics' => $this->getTimeStatistics(),
                    
                    // 安全統計
                    'security_statistics' => $this->when(
                        auth()->user()->can('users.view_security_stats'),
                        fn() => $this->getSecurityStatistics()
                    ),
                    
                    // 查詢效能資訊
                    'performance' => $this->when(
                        config('app.debug') && auth()->user()->hasRole('admin'),
                        fn() => $this->getPerformanceStats()
                    )
                ])
            ];
        }
        
        // 非分頁資料，返回自訂格式
        return [
            'data' => $this->collection,
            'meta' => [
                // 基本統計
                'statistics' => $this->getStatistics($request),
                
                // 狀態分布
                'status_distribution' => $this->getStatusDistribution(),
                
                // 角色分布
                'role_distribution' => $this->when(
                    auth()->user()->can('users.view_statistics'),
                    fn() => $this->getRoleDistribution()
                ),
                
                // 門市分布
                'store_distribution' => $this->when(
                    auth()->user()->hasRole('admin'),
                    fn() => $this->getStoreDistribution()
                ),
                
                // 時間統計
                'time_statistics' => $this->getTimeStatistics(),
                
                // 安全統計
                'security_statistics' => $this->when(
                    auth()->user()->can('users.view_security_stats'),
                    fn() => $this->getSecurityStatistics()
                ),
                
                // 查詢效能資訊
                'performance' => $this->when(
                    config('app.debug') && auth()->user()->hasRole('admin'),
                    fn() => $this->getPerformanceStats()
                )
            ]
        ];
    }

    /**
     * 取得基本統計資訊
     */
    private function getStatistics(Request $request): array
    {
        // 確保 collection 是 Collection 物件
        $collection = collect($this->collection);
        
        return [
            'total_count' => $collection->count(),
            'active_count' => $collection->where('status', UserStatus::ACTIVE->value)->count(),
            'inactive_count' => $collection->where('status', UserStatus::INACTIVE->value)->count(),
            'locked_count' => $collection->where('status', UserStatus::LOCKED->value)->count(),
            'pending_count' => $collection->where('status', UserStatus::PENDING->value)->count(),
            'verified_email_count' => $collection->whereNotNull('email_verified_at')->count(),
            'two_factor_enabled_count' => $collection->whereNotNull('two_factor_confirmed_at')->count(),
            'has_avatar_count' => $collection->filter(fn($user) => $user->hasMedia('avatar'))->count(),
        ];
    }

    /**
     * 取得狀態分布
     */
    private function getStatusDistribution(): array
    {
        // 確保 collection 是 Collection 物件
        $collection = collect($this->collection);
        $total = $collection->count();
        
        if ($total === 0) {
            return [];
        }
        
        return collect(UserStatus::cases())->map(function ($status) use ($collection, $total) {
            $count = $collection->where('status', $status->value)->count();
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            
            return [
                'status' => $status->value,
                'label' => $this->getStatusLabel($status->value),
                'count' => $count,
                'percentage' => $percentage,
                'color' => $this->getStatusColor($status->value)
            ];
        })->filter(fn($item) => $item['count'] > 0)->values()->toArray();
    }

    /**
     * 取得狀態標籤
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            UserStatus::ACTIVE->value => '啟用',
            UserStatus::INACTIVE->value => '停用',
            UserStatus::LOCKED->value => '鎖定',
            UserStatus::PENDING->value => '待啟用',
            default => '未知'
        };
    }

    /**
     * 取得狀態顏色
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            UserStatus::ACTIVE->value => 'success',
            UserStatus::INACTIVE->value => 'warning',
            UserStatus::LOCKED->value => 'error',
            UserStatus::PENDING->value => 'info',
            default => 'default'
        };
    }

    /**
     * 取得時間統計
     */
    private function getTimeStatistics(): array
    {
        $collection = collect($this->collection);
        $now = now();
        
        return [
            'created_today' => $collection->where('created_at', '>=', $now->startOfDay())->count(),
            'created_this_week' => $collection->where('created_at', '>=', $now->startOfWeek())->count(),
            'created_this_month' => $collection->where('created_at', '>=', $now->startOfMonth())->count(),
            'logged_in_today' => $collection->where('last_login_at', '>=', $now->startOfDay())->count(),
            'logged_in_this_week' => $collection->where('last_login_at', '>=', $now->startOfWeek())->count(),
            'never_logged_in' => $collection->whereNull('last_login_at')->count(),
        ];
    }

    /**
     * 取得角色分布
     */
    private function getRoleDistribution(): array
    {
        $roles = collect($this->collection)
            ->flatMap(fn($user) => $user->roles ?? [])
            ->groupBy('name')
            ->map(function ($roleUsers, $roleName) {
                return [
                    'role' => $roleName,
                    'label' => $this->getRoleLabel($roleName),
                    'count' => $roleUsers->count(),
                    'level' => $this->getRoleLevel($roleName),
                    'color' => $this->getRoleColor($roleName)
                ];
            })
            ->sortByDesc('level')
            ->values();
            
        return $roles->toArray();
    }

    /**
     * 取得門市分布
     */
    private function getStoreDistribution(): array
    {
        return collect($this->collection)
            ->groupBy('store_id')
            ->map(function ($storeUsers, $storeId) {
                $store = $storeUsers->first()->store ?? null;
                
                return [
                    'store_id' => $storeId,
                    'store_name' => $store?->name ?? '未知門市',
                    'user_count' => $storeUsers->count(),
                    'active_count' => $storeUsers->where('status', UserStatus::ACTIVE->value)->count(),
                    'inactive_count' => $storeUsers->where('status', UserStatus::INACTIVE->value)->count()
                ];
            })
            ->sortByDesc('user_count')
            ->values()
            ->toArray();
    }

    /**
     * 取得安全統計
     */
    private function getSecurityStatistics(): array
    {
        $collection = collect($this->collection);
        
        return [
            'accounts_with_failed_attempts' => $collection->where('login_attempts', '>', 0)->count(),
            'locked_accounts' => $collection->filter(function ($user) {
                return $user->locked_until && $user->locked_until->isFuture();
            })->count(),
            'accounts_without_2fa' => $collection->whereNull('two_factor_confirmed_at')->count(),
            'unverified_emails' => $collection->whereNull('email_verified_at')->count(),
        ];
    }

    /**
     * 取得效能統計（僅開發環境）
     */
    private function getPerformanceStats(): array
    {
        return [
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'collection_size' => collect($this->collection)->count(),
        ];
    }

    /**
     * 取得角色標籤
     */
    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'admin' => '系統管理員',
            'store_admin' => '門市管理員',
            'manager' => '經理',
            'staff' => '員工',
            'guest' => '訪客',
            default => $role
        };
    }

    /**
     * 取得角色層級
     */
    private function getRoleLevel(string $role): int
    {
        return match ($role) {
            'admin' => 100,
            'store_admin' => 80,
            'manager' => 60,
            'staff' => 40,
            'guest' => 20,
            default => 0
        };
    }

    /**
     * 取得角色顏色
     */
    private function getRoleColor(string $role): string
    {
        return match ($role) {
            'admin' => 'red',
            'store_admin' => 'purple',
            'manager' => 'blue',
            'staff' => 'green',
            'guest' => 'gray',
            default => 'default'
        };
    }

    /**
     * 格式化位元組
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 附加到回應的額外資料
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => '使用者列表取得成功',
            'timestamp' => now()->toISOString(),
        ];
    }
}
