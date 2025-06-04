<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * 擴展的活動日誌模型
 * 
 * 增加自定義的範圍查詢和業務邏輯方法
 */
class Activity extends BaseActivity
{
    /**
     * 查詢指定日期之後創建的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date 日期字串
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedAfter($query, string $date)
    {
        return $query->where('created_at', '>=', $date);
    }

    /**
     * 查詢指定日期之前創建的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date 日期字串
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedBefore($query, string $date)
    {
        return $query->where('created_at', '<=', $date);
    }

    /**
     * 查詢特定用戶執行的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId 用戶 ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCauser($query, int $userId)
    {
        return $query->where('causer_id', $userId)
                    ->where('causer_type', 'App\Models\User');
    }

    /**
     * 查詢商品分類相關的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductCategoryActivities($query)
    {
        return $query->where('subject_type', 'App\Models\ProductCategory');
    }

    /**
     * 查詢批次操作的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBatchOperations($query)
    {
        return $query->whereNotNull('batch_uuid');
    }

    /**
     * 查詢最近的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days 天數
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
} 