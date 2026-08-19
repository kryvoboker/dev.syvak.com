<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use App\Enums\Marketing\PromoCodeDiscountBaseModeEnum;
use App\Enums\Marketing\PromoCodeDiscountTypeEnum;
use App\Enums\Marketing\PromoCodeLimitModeEnum;
use App\Enums\Marketing\PromoCodeTypeEnum;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Products\Product;
use App\Models\Orders\Orders;
use App\Models\Users\User;
use App\Models\Users\UserGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property PromoCodeTypeEnum $promo_type
 * @property PromoCodeDiscountTypeEnum $discount_type
 * @property PromoCodeDiscountBaseModeEnum $discount_base_mode
 * @property PromoCodeLimitModeEnum $user_limit_mode
 * @property PromoCodeLimitModeEnum $group_limit_mode
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property string $code
 * @property int|null $user_usage_limit
 * @property int|null $group_usage_limit
 * @property int|null $global_usage_limit
 * @property int|null $all_users_usage_limit
 * @property int|null $all_groups_usage_limit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PromoCodeDiscount> $discounts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $categories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserGroup> $userGroups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PromoCodeErrorTranslation> $errorTranslations
 * @property-read \Illuminate\Support\Collection<int, PromoCodeErrorTranslation> $errorTranslations
 */
class PromoCode extends Model
{
    protected $fillable = [
        'name',
        'code',
        'normalized_code',
        'promo_type',
        'discount_type',
        'discount_base_mode',
        'global_usage_limit',
        'all_users_usage_limit',
        'user_limit_mode',
        'user_usage_limit',
        'all_groups_usage_limit',
        'group_limit_mode',
        'group_usage_limit',
        'minimum_order_amount',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'promo_type' => PromoCodeTypeEnum::class,
            'discount_type' => PromoCodeDiscountTypeEnum::class,
            'discount_base_mode' => PromoCodeDiscountBaseModeEnum::class,
            'user_limit_mode' => PromoCodeLimitModeEnum::class,
            'group_limit_mode' => PromoCodeLimitModeEnum::class,
            'global_usage_limit' => 'integer',
            'all_users_usage_limit' => 'integer',
            'user_usage_limit' => 'integer',
            'all_groups_usage_limit' => 'integer',
            'group_usage_limit' => 'integer',
            'minimum_order_amount' => 'decimal:4',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @phpstan-return HasMany<PromoCodeDiscount, $this>
     * @psalm-return HasMany<PromoCodeDiscount, self>
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(PromoCodeDiscount::class);
    }

    /** @phpstan-return HasMany<PromoCodeErrorTranslation, $this>
     * @psalm-return HasMany<PromoCodeErrorTranslation, self>
     */
    public function errorTranslations(): HasMany
    {
        return $this->hasMany(PromoCodeErrorTranslation::class);
    }

    /** @phpstan-return HasMany<PromoCodeUsage, $this>
     * @psalm-return HasMany<PromoCodeUsage, self>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    /** @phpstan-return BelongsToMany<User, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     * @psalm-return BelongsToMany<User, self, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'promo_code_user');
    }

    /** @phpstan-return BelongsToMany<UserGroup, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     * @psalm-return BelongsToMany<UserGroup, self, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'promo_code_user_group');
    }

    /** @phpstan-return BelongsToMany<Product, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     * @psalm-return BelongsToMany<Product, self, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promo_code_product');
    }

    /** @phpstan-return BelongsToMany<Category, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     * @psalm-return BelongsToMany<Category, self, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'promo_code_category');
    }

    /** @phpstan-return BelongsToMany<Orders, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     * @psalm-return BelongsToMany<Orders, self, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Orders::class, 'promo_code_usages')
            ->withPivot(['user_id', 'user_group_id', 'used_at']);
    }

    public static function normalizeCode(string $code): string
    {
        return Str::lower(Str::squish($code));
    }

    public function isWithinActivePeriod(?\DateTimeInterface $now = null): bool
    {
        $now = $now ?? now();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at <= $now)
            && ($this->ends_at === null || $this->ends_at >= $now);
    }
}
