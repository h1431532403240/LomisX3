<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\HasAuditFields;
use App\Enums\UserStatus;

/**
 * 門市模型
 * 
 * 支援階層式門市架構，用於多租戶資料隔離
 * 
 * @property int $id
 * @property string $name 門市名稱
 * @property string $code 門市代碼
 * @property int|null $parent_id 父門市 ID
 * @property string $status 狀態
 * @property array|null $settings 設定
 * @property string|null $address 地址
 * @property string|null $phone 電話
 * @property string|null $email 電子郵件
 * @property int|null $created_by 建立者 ID
 * @property int|null $updated_by 更新者 ID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read Store|null $parent 父門市
 * @property-read \Illuminate\Database\Eloquent\Collection|Store[] $children 子門市
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $users 使用者
 */
class Store extends Model implements HasMedia
{
    use HasFactory, SoftDeletes;
    use LogsActivity, InteractsWithMedia;
    use HasAuditFields;

    /**
     * 可批量賦值的欄位
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'parent_id',
        'status',
        'settings',
        'address',
        'phone',
        'email',
        'created_by',
        'updated_by'
    ];

    /**
     * 欄位類型轉換
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'status' => UserStatus::class, // 使用相同狀態枚舉
        ];
    }

    /**
     * 父門市關聯
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'parent_id');
    }

    /**
     * 子門市關聯
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Store::class, 'parent_id');
    }

    /**
     * 使用者關聯
     *
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 活躍使用者關聯
     *
     * @return HasMany
     */
    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('status', UserStatus::ACTIVE);
    }

    /**
     * Media Library 設定
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);
    }

    /**
     * 取得門市 Logo URL
     *
     * @return string|null
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('logo');
    }

    /**
     * 活躍門市查詢範圍
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    /**
     * 根門市查詢範圍
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRoot($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Activity Log 設定
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['name', 'code', 'status', 'parent_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
} 