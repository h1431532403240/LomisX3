<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProductCategory;

/**
 * ProductCategory Factory
 * 
 * 為測試提供假資料生成
 * 符合 Phase 2.3 P0.2 測試需求
 */
class ProductCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProductCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . $this->faker->unique()->randomNumber(5),
            'description' => $this->faker->optional()->paragraph(),
            'parent_id' => null,
            'depth' => 0,
            'position' => $this->faker->numberBetween(1, 100),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 建立子分類狀態
     */
    public function withParent(int $parentId, int $depth = 1): static
    {
        return $this->state(function (array $attributes) use ($parentId, $depth) {
            return [
                'parent_id' => $parentId,
                'depth' => $depth,
            ];
        });
    }

    /**
     * 建立停用狀態
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => false,
            ];
        });
    }

    /**
     * 建立指定名稱的分類
     */
    public function withName(string $name): static
    {
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
            ];
        });
    }

    /**
     * 建立指定 slug 的分類
     */
    public function withSlug(string $slug): static
    {
        return $this->state(function (array $attributes) use ($slug) {
            return [
                'slug' => $slug,
            ];
        });
    }
}
