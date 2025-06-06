<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Laravel 套件
use Laravel\Sanctum\HasApiTokens;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Notifications\Notifiable;

// Spatie 套件 Traits
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

// 自訂 Traits
use App\Traits\HasStoreIsolation;
use App\Traits\HasAuditFields;

// Enums
use App\Enums\{UserStatus, UserRole};

/**
 * 使用者模型 V6.2
 * 
 * 企業級 SaaS 使用者管理系統，支援：
 * - 多門市隔離機制
 * - 角色權限管理 (Spatie Permission)
 * - 雙因子驗證 (2FA)
 * - 媒體頭像管理
 * - 活動日誌追蹤
 * - 智能快取機制
 * 
 * @property int $id
 * @property string $username 使用者名稱 (唯一)
 * @property string $name 姓名
 * @property string $email 電子郵件 (唯一)
 * @property string $password 密碼
 * @property int $store_id 門市 ID
 * @property string|null $phone 電話號碼
 * @property UserStatus $status 帳號狀態
 * @property string|null $two_factor_secret 2FA 金鑰
 * @property string|null $two_factor_recovery_codes 2FA 復原碼
 * @property Carbon|null $two_factor_confirmed_at 2FA 確認時間
 * @property Carbon|null $email_verified_at 信箱驗證時間
 * @property Carbon|null $last_login_at 最後登入時間
 * @property string|null $last_login_ip 最後登入 IP
 * @property int $login_attempts 登入嘗試次數
 * @property Carbon|null $locked_until 鎖定到期時間
 * @property array|null $preferences 使用者偏好設定
 * @property int|null $created_by 建立者 ID
 * @property int|null $updated_by 更新者 ID
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * 
 * @property-read Store $store 所屬門市
 * @property-read string|null $avatar_url 頭像 URL
 * @property-read bool $is_locked 是否被鎖定
 * @property-read bool $has_2fa 是否啟用 2FA
 * @property-read string $status_label 狀態標籤
 */
class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, SoftDeletes;
    use HasApiTokens, TwoFactorAuthenticatable;
    use HasRoles, LogsActivity, InteractsWithMedia;
    use HasStoreIsolation, HasAuditFields;

    /**
     * 可批量賦值的欄位
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name', 
        'email',
        'password',
        'store_id',
        'phone',
        'status',
        'preferences',
        'created_by',
        'updated_by'
    ];

    /**
     * 隱藏的欄位
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * 欄位類型轉換
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime', 
            'locked_until' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'preferences' => 'array',
            'login_attempts' => 'integer',
        ];
    }

    /**
     * 模型啟動方法
     */
    protected static function boot(): void
    {
        parent::boot();
        
        // 註冊 Observer
        static::observe(\App\Observers\UserObserver::class);
    }

    /**
     * 門市關聯
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 個人存取權杖
     *
     * @return HasMany
     */
    public function personalAccessTokens(): HasMany
    {
        return $this->hasMany(\Laravel\Sanctum\PersonalAccessToken::class, 'tokenable_id')
                    ->where('tokenable_type', static::class);
    }

    /**
     * 門市存取權限檢查
     * 
     * 支援管理員跨門市存取
     *
     * @param int $targetStoreId 目標門市 ID
     * @return bool
     */
    public function canAccessStore(int $targetStoreId): bool
    {
        // 系統管理員可存取所有門市
        if ($this->hasRole('admin')) {
            return true;
        }

        return Cache::remember(
            "user_accessible_stores_{$this->id}",
            300,
            fn() => $this->getAccessibleStoreIds()->contains($targetStoreId)
        );
    }

    /**
     * 取得可存取的門市 ID 集合
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAccessibleStoreIds(): \Illuminate\Support\Collection
    {
        // 系統管理員可存取所有門市
        if ($this->hasRole('admin')) {
            return \App\Models\Store::pluck('id');
        }

        // 一般使用者只能存取所屬門市
        return collect([$this->store_id]);
    }

    /**
     * Media Library 設定
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
            
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'image/*']);
    }

    /**
     * 頭像 URL 屬性
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb');
    }

    /**
     * 是否被鎖定屬性
     *
     * @return bool
     */
    public function getIsLockedAttribute(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * 是否啟用 2FA 屬性
     *
     * @return bool
     */
    public function getHas2faAttribute(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    /**
     * 檢查是否需要雙因子驗證
     * 用於登入流程中判斷是否需要 2FA 挑戰
     *
     * @return bool
     */
    public function requiresTwoFactorAuthentication(): bool
    {
        // 檢查是否已啟用 2FA
        return !is_null($this->two_factor_confirmed_at) && !is_null($this->two_factor_secret);
    }

    /**
     * 狀態標籤屬性
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * 檢查密碼是否正確
     *
     * @param string $password 密碼
     * @return bool
     */
    public function checkPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    /**
     * 重設登入嘗試次數
     *
     * @return void
     */
    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * 增加登入嘗試次數
     *
     * @param int $maxAttempts 最大嘗試次數
     * @param int $lockMinutes 鎖定分鐘數
     * @return void
     */
    public function incrementLoginAttempts(int $maxAttempts = 5, int $lockMinutes = 30): void
    {
        $attempts = $this->login_attempts + 1;
        $lockUntil = $attempts >= $maxAttempts ? now()->addMinutes($lockMinutes) : null;

        $this->update([
            'login_attempts' => $attempts,
            'locked_until' => $lockUntil,
        ]);
    }

    /**
     * 記錄成功登入
     *
     * @param string|null $ip IP 位址
     * @return void
     */
    public function recordLogin(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?: request()->ip(),
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Activity Log 設定
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['username', 'name', 'email', 'status', 'store_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * 條件性門市隔離查詢範圍
     * 
     * 支援管理員跨門市查詢
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $storeId 指定門市 ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStoreIsolated($query, ?int $storeId = null): \Illuminate\Database\Eloquent\Builder
    {
        if ($storeId || (auth()->check() && auth()->user()->store_id)) {
            $targetStoreId = $storeId ?? auth()->user()->store_id;
            
            // 系統管理員可查詢所有門市
            if (auth()->check() && auth()->user()->hasRole('admin')) {
                return $query;
            }
            
            return $query->where('store_id', $targetStoreId);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * 活躍使用者查詢範圍
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    /**
     * 最近登入查詢範圍
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days 天數
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyLoggedIn($query, int $days = 7): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }
}
