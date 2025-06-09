<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * 使用者快取服務介面
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
interface UserCacheServiceInterface
{
    /**
     * 取得使用者並帶有降級機制
     */
    public function getUserWithFallback(int $userId): User;

    /**
     * 取得使用者權限快取
     */
    public function getUserPermissions(int $userId, int $storeId): array;

    /**
     * 取得門市統計資料
     */
    public function getStoreStatistics(int $storeId): array;

    /**
     * 清除使用者快取
     */
    public function clearUserCache(int $userId): void;

    /**
     * 清除門市使用者快取
     */
    public function clearStoreUsersCache(int $storeId): void;

    /**
     * 批次清除快取
     */
    public function clearBatch(array $userIds): void;

    /**
     * 預熱活躍使用者快取
     */
    public function warmupActiveUsersCache(): void;

    /**
     * 快取健康檢查
     */
    public function healthCheck(): array;
} 