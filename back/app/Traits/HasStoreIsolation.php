<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store;

/**
 * 門市隔離 Trait
 * 
 * 提供統一的門市隔離機制，確保多租戶資料安全
 * 使用於所有需要門市隔離的模型
 * 
 * @method static Builder storeIsolated(int|null $storeId = null)
 * @method static Builder currentStore()
 * @method static Builder accessibleStores(array $storeIds)
 */
trait HasStoreIsolation
{
    /**
     * 門市隔離查詢範圍
     * 
     * 根據指定門市 ID 或當前使用者門市進行資料隔離
     * 支援管理員跨門市存取權限
     * 
     * @param Builder $query
     * @param int|null $storeId 指定門市 ID，null 時使用當前使用者門市
     * @return Builder
     */
    public function scopeStoreIsolated(Builder $query, int $storeId = null): Builder
    {
        // 如果指定門市 ID，直接使用
        if ($storeId) {
            return $query->where('store_id', $storeId);
        }

        // 如果使用者已認證，使用使用者的門市
        if (auth()->check() && auth()->user()->store_id) {
            $targetStoreId = auth()->user()->store_id;
            
            // 檢查使用者是否有跨門市權限
            if (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin')) {
                // 系統管理員可存取所有門市，不套用隔離
                return $query;
            }
            
            return $query->where('store_id', $targetStoreId);
        }

        // 未認證或無門市資訊時，返回空結果集
        return $query->whereRaw('1 = 0');
    }

    /**
     * 當前門市查詢範圍
     * 
     * 只查詢當前使用者所屬門市的資料
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeCurrentStore(Builder $query): Builder
    {
        if (auth()->check() && auth()->user()->store_id) {
            return $query->where('store_id', auth()->user()->store_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * 可存取門市查詢範圍
     * 
     * 查詢指定門市 ID 陣列內的資料
     * 
     * @param Builder $query
     * @param array $storeIds 可存取的門市 ID 陣列
     * @return Builder
     */
    public function scopeAccessibleStores(Builder $query, array $storeIds): Builder
    {
        return $query->whereIn('store_id', $storeIds);
    }

    /**
     * 門市關聯
     * 
     * 定義與門市的歸屬關係
     * 
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 檢查是否屬於指定門市
     * 
     * @param int $storeId 門市 ID
     * @return bool
     */
    public function belongsToStore(int $storeId): bool
    {
        return $this->store_id === $storeId;
    }

    /**
     * 檢查當前使用者是否可存取此資源
     * 
     * 基於門市歸屬和使用者權限進行檢查
     * 
     * @return bool
     */
    public function isAccessibleByCurrentUser(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();
        
        // 系統管理員可存取所有門市資料
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // 檢查門市歸屬
        return $this->belongsToStore($user->store_id);
    }

    /**
     * 設定門市 ID
     * 
     * 在建立資源時自動設定門市歸屬
     * 
     * @param int|null $storeId 門市 ID，null 時使用當前使用者門市
     * @return self
     */
    public function setStoreId(int $storeId = null): self
    {
        if ($storeId === null && auth()->check()) {
            $storeId = auth()->user()->store_id;
        }

        $this->store_id = $storeId;
        return $this;
    }

    /**
     * 模型啟動時自動套用門市隔離
     * 
     * 在模型初始化時自動設定門市 ID
     */
    protected static function bootHasStoreIsolation(): void
    {
        // 在建立資源時自動設定門市 ID
        static::creating(function ($model) {
            if (empty($model->store_id) && auth()->check() && auth()->user()->store_id) {
                $model->store_id = auth()->user()->store_id;
            }
        });
    }
} 