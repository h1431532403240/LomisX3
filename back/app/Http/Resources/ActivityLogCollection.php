<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * 活動日誌集合資源
 * 
 * 格式化活動日誌清單的 API 回應
 * 提供額外的統計資訊和分頁後設資料
 */
class ActivityLogCollection extends ResourceCollection
{
    /**
     * 將資源集合轉換為陣列
     *
     * @param Request $request HTTP 請求
     * @return array<string, mixed> 格式化的活動日誌集合資料
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => ActivityLogResource::collection($this->collection),
            'summary' => $this->when($this->collection->isNotEmpty(), [
                'events_count' => $this->collection->groupBy('event')->map->count(),
                'log_names_count' => $this->collection->groupBy('log_name')->map->count(),
                'causers_count' => $this->collection->whereNotNull('causer_id')->groupBy('causer_id')->count(),
                'date_range' => [
                    'earliest' => $this->collection->min('created_at')?->format('Y-m-d H:i:s'),
                    'latest' => $this->collection->max('created_at')?->format('Y-m-d H:i:s'),
                ],
                'has_batch_operations' => $this->collection->whereNotNull('batch_uuid')->isNotEmpty(),
            ]),
        ];
    }
} 