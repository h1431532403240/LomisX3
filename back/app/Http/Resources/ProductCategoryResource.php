<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 商品分類資源
 * 格式化商品分類的 API 輸出資料
 */
class ProductCategoryResource extends JsonResource
{
    /**
     * 將資源轉換為陣列
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'position' => $this->position,
            'status' => $this->status,
            'depth' => $this->depth,
            'description' => $this->description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            // 計算屬性
            'has_children' => $this->has_children,
            'full_path' => $this->full_path,

            // 時間戳記
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),

            // 關聯資料（按需載入）
            'parent' => $this->whenLoaded('parent', function () {
                return new ProductCategoryResource($this->parent);
            }),

            'children' => $this->whenLoaded('children', function () {
                return ProductCategoryResource::collection($this->children);
            }),

            // 額外資訊（僅在需要時提供）
            'ancestors' => $this->when(
                $request->boolean('with_ancestors'),
                function () {
                    return ProductCategoryResource::collection($this->ancestors());
                }
            ),

            'descendants' => $this->when(
                $request->boolean('with_descendants'),
                function () {
                    return ProductCategoryResource::collection($this->descendants());
                }
            ),

            // 統計資訊（僅在管理後台需要時提供）
            'children_count' => $this->when(
                $request->boolean('with_counts'),
                function () {
                    return $this->children()->count();
                }
            ),

            'descendants_count' => $this->when(
                $request->boolean('with_counts'),
                function () {
                    return $this->descendants()->count();
                }
            ),
        ];
    }

    /**
     * 取得應與資源一起返回的額外資料
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * 自訂資源包裝器
     */
    public static function wrap($value): ?string
    {
        return 'category';
    }
}
