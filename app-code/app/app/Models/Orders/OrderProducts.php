<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $product_variant_id
 * @property bool $is_default_variant
 * @property string $name
 * @property string|null $model
 * @property string|null $sku
 * @property string|null $ean
 * @property int $quantity
 * @property float|string $discount
 * @property float|string $unit_price
 * @property float|string $line_total
 * @property-read Product|null $product
 * @property-read ProductVariant|null $productVariant
 */
class OrderProducts extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'is_default_variant',
        'name',
        'model',
        'sku',
        'ean',
        'quantity',
        'discount',
        'unit_price',
        'line_total',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'product_id' => 'integer',
            'product_variant_id' => 'integer',
            'is_default_variant' => 'boolean',
            'quantity' => 'integer',
            'discount' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    /**
     * @phpstan-return BelongsTo<Orders, $this>
     * @psalm-return BelongsTo<Orders, self>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class);
    }

    /**
     * @phpstan-return BelongsTo<Product, $this>
     * @psalm-return BelongsTo<Product, self>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @phpstan-return BelongsTo<ProductVariant, $this>
     * @psalm-return BelongsTo<ProductVariant, self>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @phpstan-return HasOne<OrderPromoCodeProducts, $this>
     * @psalm-return HasOne<OrderPromoCodeProducts, self>
     */
    public function promoCodeProduct(): HasOne
    {
        return $this->hasOne(OrderPromoCodeProducts::class, 'order_product_id');
    }
}
