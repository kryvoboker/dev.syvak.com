<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Users\UserGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_variant_id
 * @property int|null $user_group_id
 * @property int|null $quantity
 * @property int $priority
 * @property float $price
 * @property \DateTimeInterface|null $date_start
 * @property \DateTimeInterface|null $date_end
 * @property \Illuminate\Support\Carbon|null $date_start
 * @property \Illuminate\Support\Carbon|null $date_end
 */
class ProductVariantDiscount extends Model
{
    protected $fillable = [
        'product_variant_id',
        'user_group_id',
        'quantity',
        'priority',
        'price',
        'date_start',
        'date_end',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'product_variant_id' => 'integer',
            'user_group_id' => 'integer',
            'quantity' => 'integer',
            'priority' => 'integer',
            'price' => 'float',
            'date_start' => 'datetime',
            'date_end' => 'datetime',
        ];
    }

    /**
     * @phpstan-return BelongsTo<ProductVariant, $this>
     * @psalm-return BelongsTo<ProductVariant, self>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @phpstan-return BelongsTo<UserGroup, $this>
     * @psalm-return BelongsTo<UserGroup, self>
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
