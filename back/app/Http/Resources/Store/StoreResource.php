<?php

declare(strict_types=1);

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\StoreStatus;

/**
 * 門市資源類別
 * 
 * 負責處理門市資料的 API 回應格式化
 */
class StoreResource extends JsonResource
{
    /**
     * 將資源轉換為陣列
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => [
                'value' => $this->status,
                'label' => $this->getStatusLabel(),
                'color' => $this->getStatusColor(),
                'is_active' => $this->status === 'active'
            ],
            'contact' => [
                'address' => $this->address,
                'phone' => $this->phone,
                'email' => $this->email,
            ],
            'settings' => $this->when(
                auth()->user() && auth()->user()->can('stores.view_settings'),
                $this->settings
            ),
            'audit' => [
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
                'created_by' => $this->whenLoaded('createdBy', function () {
                    return [
                        'id' => $this->createdBy->id,
                        'name' => $this->createdBy->name,
                    ];
                }),
            ],
        ];
    }

    /**
     * 取得狀態標籤
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'active' => '營運中',
            'inactive' => '暫停營運',
            'maintenance' => '維護中',
            'closed' => '已關閉',
            default => '未知'
        };
    }

    /**
     * 取得狀態顏色
     */
    private function getStatusColor(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'inactive' => 'warning',
            'maintenance' => 'info',
            'closed' => 'error',
            default => 'default'
        };
    }
} 