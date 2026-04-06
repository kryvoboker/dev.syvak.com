<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantImage extends Model
{
    protected $fillable = [
        'product_variant_id',
        'image',
        'is_primary',
        'sort_order',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'product_variant_id' => 'integer',
            'is_primary'         => 'boolean',
            'sort_order'         => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
