<?php

declare(strict_types=1);

namespace App\Models\Marketing;

use App\Enums\Marketing\PromoCodeDiscountTypeEnum;
use App\Enums\Marketing\PromoCodeTypeEnum;
use App\Models\Orders\OrderPromoCodeProducts;
use App\Models\Orders\Orders;
use App\Models\Users\User;
use App\Models\Users\UserGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property PromoCodeDiscountTypeEnum $discount_type
 * @property PromoCodeTypeEnum $promo_type
 * @property int $promo_code_id
 */
class PromoCodeUsage extends Model
{
    protected $fillable = [
        'promo_code_id',
        'order_id',
        'discount_type',
        'promo_type',
        'user_id',
        'user_group_id',
        'consumer_key',
        'used_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'discount_type' => PromoCodeDiscountTypeEnum::class,
            'promo_type' => PromoCodeTypeEnum::class,
            'used_at' => 'datetime',
        ];
    }

    /** @phpstan-return BelongsTo<PromoCode, $this>
     * @psalm-return BelongsTo<PromoCode, self>
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** @phpstan-return BelongsTo<Orders, $this>
     * @psalm-return BelongsTo<Orders, self>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /** @phpstan-return BelongsTo<User, $this>
     * @psalm-return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @phpstan-return BelongsTo<UserGroup, $this>
     * @psalm-return BelongsTo<UserGroup, self>
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    /** @phpstan-return HasMany<OrderPromoCodeProducts, $this>
     * @psalm-return HasMany<OrderPromoCodeProducts, self>
     */
    public function products(): HasMany
    {
        return $this->hasMany(OrderPromoCodeProducts::class, 'promo_code_usage_id');
    }
}
