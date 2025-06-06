<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * 審計欄位 Trait
 * 
 * 自動追蹤資源的建立者和更新者
 * 提供標準的審計日誌功能
 * 
 * 要求模型必須包含以下欄位：
 * - created_by (nullable foreign key to users.id)
 * - updated_by (nullable foreign key to users.id)
 */
trait HasAuditFields
{
    /**
     * 建立者關聯
     * 
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 更新者關聯
     * 
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 取得建立者名稱
     * 
     * @return string|null
     */
    public function getCreatorNameAttribute(): ?string
    {
        return $this->creator?->name;
    }

    /**
     * 取得更新者名稱
     * 
     * @return string|null
     */
    public function getUpdaterNameAttribute(): ?string
    {
        return $this->updater?->name;
    }

    /**
     * 檢查是否由當前使用者建立
     * 
     * @return bool
     */
    public function isCreatedByCurrentUser(): bool
    {
        return auth()->check() && $this->created_by === auth()->id();
    }

    /**
     * 檢查是否由當前使用者更新
     * 
     * @return bool
     */
    public function isUpdatedByCurrentUser(): bool
    {
        return auth()->check() && $this->updated_by === auth()->id();
    }

    /**
     * 檢查當前使用者是否為此資源的操作者
     * 
     * @return bool
     */
    public function isOwnedByCurrentUser(): bool
    {
        return $this->isCreatedByCurrentUser() || $this->isUpdatedByCurrentUser();
    }

    /**
     * 模型啟動時自動設定審計欄位
     */
    protected static function bootHasAuditFields(): void
    {
        // 建立時自動設定建立者
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        // 更新時自動設定更新者
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // 恢復軟刪除時更新更新者
        static::restoring(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * 在模型初始化時將審計欄位添加到 fillable
     */
    public function initializeHasAuditFields(): void
    {
        $this->fillable = array_merge($this->fillable, [
            'created_by',
            'updated_by'
        ]);
    }

    /**
     * 設定建立者
     * 
     * @param int|null $userId 使用者 ID，null 時使用當前使用者
     * @return self
     */
    public function setCreatedBy(int $userId = null): self
    {
        if ($userId === null && auth()->check()) {
            $userId = auth()->id();
        }

        $this->created_by = $userId;
        return $this;
    }

    /**
     * 設定更新者
     * 
     * @param int|null $userId 使用者 ID，null 時使用當前使用者
     * @return self
     */
    public function setUpdatedBy(int $userId = null): self
    {
        if ($userId === null && auth()->check()) {
            $userId = auth()->id();
        }

        $this->updated_by = $userId;
        return $this;
    }

    /**
     * 靜默設定審計欄位（不觸發事件）
     * 
     * @param int|null $createdBy 建立者 ID
     * @param int|null $updatedBy 更新者 ID
     * @return self
     */
    public function setAuditFieldsQuietly(?int $createdBy = null, ?int $updatedBy = null): self
    {
        if ($createdBy !== null) {
            $this->created_by = $createdBy;
        }

        if ($updatedBy !== null) {
            $this->updated_by = $updatedBy;
        }

        return $this;
    }
} 