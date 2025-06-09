<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 活動日誌資源
 * 
 * 格式化活動日誌的 API 回應數據
 * 包含詳細的活動資訊、執行者和主體資訊
 */
class ActivityLogResource extends JsonResource
{
    /**
     * 將資源轉換為陣列
     *
     * @param Request $request HTTP 請求
     * @return array<string, mixed> 格式化的活動日誌資料
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'event' => $this->event,
            'batch_uuid' => $this->batch_uuid,
            
            // 執行者資訊 (誰執行了這個動作)
            'causer' => $this->when($this->causer, function () {
                return [
                    'id' => $this->causer->id,
                    'type' => $this->causer_type,
                    'name' => $this->causer->name ?? '未知用戶',
                    'email' => $this->causer->email ?? null,
                ];
            }),
            
            // 主體資訊 (動作的目標對象)
            'subject' => $this->when($this->subject, function () {
                $subject = [
                    'id' => $this->subject->id,
                    'type' => $this->subject_type,
                ];
                
                // 根據主體類型添加相應的資訊
                if ($this->subject_type === 'App\Models\ProductCategory') {
                    $subject['name'] = $this->subject->name ?? null;
                    $subject['slug'] = $this->subject->slug ?? null;
                    $subject['status'] = $this->subject->status ?? null;
                    $subject['depth'] = $this->subject->depth ?? null;
                    $subject['parent_id'] = $this->subject->parent_id ?? null;
                }
                
                return $subject;
            }),
            
            // 屬性變更資訊
            'properties' => $this->when($this->properties, function () {
                $properties = $this->properties->toArray();
                
                // 格式化變更資訊，提供更好的可讀性
                if (isset($properties['attributes']) || isset($properties['old'])) {
                    $changes = [];
                    
                    if (isset($properties['attributes'])) {
                        foreach ($properties['attributes'] as $key => $value) {
                            $changes[$key] = [
                                'new' => $value,
                                'old' => $properties['old'][$key] ?? null,
                                'changed' => !isset($properties['old'][$key]) || $properties['old'][$key] !== $value,
                            ];
                        }
                    }
                    
                    $properties['formatted_changes'] = $changes;
                }
                
                return $properties;
            }),
            
            // 時間資訊
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'created_at_diff' => $this->created_at?->diffForHumans(),
            
            // 額外的上下文資訊
            'context' => [
                'has_changes' => !empty($this->properties['attributes']) || !empty($this->properties['old']),
                'is_batch' => !empty($this->batch_uuid),
                'subject_exists' => $this->subject !== null,
                'causer_exists' => $this->causer !== null,
            ],
        ];
    }
} 