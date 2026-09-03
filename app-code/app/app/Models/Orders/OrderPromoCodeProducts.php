<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Enums\Marketing\PromoCodeProductOverrideEnum;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Marketing\PromoCodeUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $order_id
 * @property int $promo_code_usage_id
 * @property int $order_product_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property bool $is_eligible
 * @property PromoCodeProductOverrideEnum|null $override
 * @property float|string $discount_amount
 */
class OrderPromoCodeProducts extends Model
{
    protected $fillable = [
        'order_id',
        'promo_code_usage_id',
        'order_product_id',
        'product_id',
        'product_variant_id',
        'is_eligible',
        'override',
        'discount_amount',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'promo_code_usage_id' => 'integer',
            'order_product_id' => 'integer',
            'product_id' => 'integer',
            'product_variant_id' => 'integer',
            'is_eligible' => 'boolean',
            'override' => PromoCodeProductOverrideEnum::class,
            'discount_amount' => 'decimal:4',
        ];
    }

    /** @phpstan-return BelongsTo<Orders, $this>
     * @psalm-return BelongsTo<Orders, self>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /** @phpstan-return BelongsTo<PromoCodeUsage, $this>
     * @psalm-return BelongsTo<PromoCodeUsage, self>
     */
    public function promoCodeUsage(): BelongsTo
    {
        return $this->belongsTo(PromoCodeUsage::class);
    }

    /** @phpstan-return BelongsTo<OrderProducts, $this>
     * @psalm-return BelongsTo<OrderProducts, self>
     */
    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProducts::class);
    }

    /** @phpstan-return BelongsTo<Product, $this>
     * @psalm-return BelongsTo<Product, self>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @phpstan-return BelongsTo<ProductVariant, $this>
     * @psalm-return BelongsTo<ProductVariant, self>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
