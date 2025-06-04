<?php

namespace App\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * 按執行者姓名排序
 * 
 * 實現活動日誌按照執行者（causer）姓名進行排序的功能
 */
class CauserNameSort implements Sort
{
    /**
     * 執行排序邏輯
     *
     * @param Builder $query 查詢建構器
     * @param bool $descending 是否降序排列
     * @param string $property 排序屬性名稱
     * @return Builder 排序後的查詢建構器
     */
    public function __invoke(Builder $query, bool $descending, string $property): Builder
    {
        $direction = $descending ? 'desc' : 'asc';

        return $query
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->orderBy('users.name', $direction)
            ->select('activity_log.*'); // 確保只選擇活動日誌的欄位
    }
} 