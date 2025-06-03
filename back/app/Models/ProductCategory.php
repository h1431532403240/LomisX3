<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 商品分類模型
 * 支援階層式分類結構、軟刪除、SEO 等企業級功能
 *
 * @property int                 $id
 * @property string              $name
 * @property string              $slug
 * @property int|null            $parent_id
 * @property int                 $position
 * @property bool                $status
 * @property int                 $depth
 * @property string|null         $description
 * @property string|null         $meta_title
 * @property string|null         $meta_description
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ProductCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 資料表名稱
     */
    protected $table = 'product_categories';

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'path',
        'position',
        'status',
        'depth',
        'description',
        'meta_title',
        'meta_description',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'parent_id' => 'integer',
        'position' => 'integer',
        'status' => 'boolean',
        'depth' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'path' => 'string',
    ];

    /**
     * 上層分類關聯
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * 子分類關聯
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')
            ->orderBy('position');
    }

    /**
     * 所有祖先分類（遞迴）
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * 所有子孫分類（遞迴）
     */
    public function descendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    // ============ Scopes ============

    /**
     * 僅啟用的分類
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * 根分類（沒有父分類）
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 指定深度以內的分類
     */
    public function scopeWithDepth($query, int $maxDepth)
    {
        return $query->where('depth', '<=', $maxDepth);
    }

    /**
     * 搜尋功能
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * 按層級和位置排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name');
    }

    // ============ Accessors ============

    /**
     * 是否有子分類
     */
    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }

    /**
     * 完整路徑（麵包屑）
     */
    public function getFullPathAttribute(): string
    {
        $ancestors = $this->ancestors();
        $path = $ancestors->pluck('name')->toArray();
        $path[] = $this->name;

        return implode(' > ', $path);
    }

    // ============ 領域邏輯方法 ============

    /**
     * 是否為某分類的子孫
     */
    public function isDescendantOf(ProductCategory $category): bool
    {
        return $this->ancestors()->contains('id', $category->id);
    }

    /**
     * 是否為某分類的祖先
     */
    public function isAncestorOf(ProductCategory $category): bool
    {
        return $this->descendants()->contains('id', $category->id);
    }

    /**
     * 計算層級深度
     */
    public function calculateDepth(): int
    {
        if (! $this->parent_id) {
            return 0;
        }

        $parent = static::find($this->parent_id);

        return $parent ? $parent->depth + 1 : 0;
    }

    /**
     * 取得根祖先節點 ID（迭代避免遞迴 N+1）
     * 
     * 此方法使用迭代而非遞迴來找到根祖先，避免N+1查詢問題
     * 只查詢必要的欄位（id, parent_id）以提升效能
     * 
     * @return int 根祖先的ID，如果當前節點就是根節點則返回自己的ID
     */
    public function getRootAncestorId(): int
    {
        $current = $this;
        
        while ($current->parent_id) {
            // 只抓 id/parent_id，避免 full model select
            $current = $current->parent()->withoutGlobalScopes()->first(['id', 'parent_id']);
            if (!$current) {
                break; // 資料缺失 fallback
            }
        }
        
        return (int) ($current->id ?? $this->id);
    }

    // ============ Materialized Path 方法 ============

    /**
     * 產生當前分類的 Materialized Path
     * 
     * 基於父分類的路徑生成當前分類的完整路徑
     * 格式：/1/3/5/ 表示從根分類到當前分類的路徑
     * 
     * @return string 完整的 Materialized Path
     */
    public function generatePath(): string
    {
        // 如果是根分類，直接返回 /id/
        if (!$this->parent_id) {
            return "/{$this->id}/";
        }

        // 取得父分類的路徑
        $parent = $this->parent()->withoutGlobalScopes()->first(['id', 'path']);
        
        if (!$parent || !$parent->path) {
            // 如果父分類沒有路徑，回退到迭代方式計算
            return $this->generatePathFromAncestors();
        }

        // 在父分類路徑後加上自己的ID
        return $parent->path . $this->id . '/';
    }

    /**
     * 從祖先鏈生成路徑（備用方案）
     * 
     * 當父分類路徑缺失時使用此方法
     * 
     * @return string
     */
    private function generatePathFromAncestors(): string
    {
        $ancestors = [];
        $current = $this;

        // 向上追蹤所有祖先
        while ($current) {
            array_unshift($ancestors, $current->id);
            
            if (!$current->parent_id) {
                break;
            }
            
            $current = $current->parent()->withoutGlobalScopes()->first(['id', 'parent_id']);
        }

        return '/' . implode('/', $ancestors) . '/';
    }

    /**
     * 更新自己和所有子孫的路徑
     * 
     * 當分類層級結構變更時調用此方法
     * 使用批量查詢和更新提升效能
     */
    public function updatePathsRecursively(): void
    {
        // 更新自己的路徑
        $newPath = $this->generatePath();
        $oldPath = $this->path;
        
        // 如果路徑沒變，不需要處理
        if ($newPath === $oldPath) {
            return;
        }

        $this->update(['path' => $newPath]);

        // 如果沒有舊路徑，說明是新建分類，不需要更新子孫
        if (!$oldPath) {
            return;
        }

        // 批量更新所有子孫的路徑
        $this->updateDescendantsPaths($oldPath, $newPath);
    }

    /**
     * 批量更新子孫分類的路徑
     * 
     * @param string $oldPath 舊路徑
     * @param string $newPath 新路徑
     */
    private function updateDescendantsPaths(string $oldPath, string $newPath): void
    {
        // 找出所有子孫分類（路徑以舊路徑開頭的）
        static::withTrashed()
            ->where('path', 'like', $oldPath . '%')
            ->where('id', '!=', $this->id)
            ->chunkById(1000, function ($descendants) use ($oldPath, $newPath) {
                foreach ($descendants as $descendant) {
                    // 替換路徑前綴
                    $updatedPath = str_replace($oldPath, $newPath, $descendant->path);
                    $descendant->update(['path' => $updatedPath]);
                }
            });
    }

    /**
     * 基於路徑查詢祖先分類
     * 
     * 利用 Materialized Path 快速查詢祖先，無需遞迴
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAncestorsByPath()
    {
        if (!$this->path || $this->path === "/{$this->id}/") {
            return collect(); // 根分類沒有祖先
        }

        // 從路徑中提取祖先 ID
        $ancestorIds = $this->extractAncestorIdsFromPath();
        
        if (empty($ancestorIds)) {
            return collect();
        }

        // 按路徑順序查詢祖先
        return static::whereIn('id', $ancestorIds)
            ->ordered()
            ->get()
            ->sortBy(function ($ancestor) use ($ancestorIds) {
                return array_search($ancestor->id, $ancestorIds);
            });
    }

    /**
     * 基於路徑查詢直接子孫分類
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDescendantsByPath()
    {
        if (!$this->path) {
            return collect();
        }

        return static::where('path', 'like', $this->path . '%')
            ->where('id', '!=', $this->id)
            ->ordered()
            ->get();
    }

    /**
     * 從路徑中提取祖先 ID 陣列
     * 
     * @return array
     */
    private function extractAncestorIdsFromPath(): array
    {
        if (!$this->path) {
            return [];
        }

        // 移除首尾斜線並分割
        $pathParts = explode('/', trim($this->path, '/'));
        
        // 移除最後一個（自己的ID）
        array_pop($pathParts);
        
        // 轉換為整數並過濾無效值
        return array_filter(array_map('intval', $pathParts));
    }

    /**
     * 取得根祖先 ID（基於路徑的快速實現）
     * 
     * 替代原有的 getRootAncestorId 方法，使用路徑快速查找
     * 
     * @return int
     */
    public function getRootAncestorIdByPath(): int
    {
        if (!$this->path) {
            return $this->id; // 沒有路徑時回退到原方法
        }

        // 從路徑中提取第一個 ID（根分類）
        if (preg_match('/^\/(\d+)\//', $this->path, $matches)) {
            return (int) $matches[1];
        }

        return $this->id;
    }

    /**
     * 查詢範圍：基於路徑前綴查詢子樹
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pathPrefix 路徑前綴
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPathPrefix($query, string $pathPrefix)
    {
        return $query->where('path', 'like', $pathPrefix . '%');
    }

    /**
     * 查詢範圍：根據分類 ID 查詢其子樹
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $categoryId 分類 ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubtreeOf($query, int $categoryId)
    {
        $pathPrefix = "/{$categoryId}/";
        return $query->where('path', 'like', $pathPrefix . '%');
    }
}
