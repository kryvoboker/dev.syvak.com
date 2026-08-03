<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantCare extends Model
{
    protected $fillable = [
        'product_variant_id',
        'language_id',
        'title',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'product_variant_id' => 'integer',
            'language_id' => 'integer',
            'items' => 'array',
        ];
    }

    public function items(): Attribute
    {
        return new Attribute(
            set: fn(mixed $items) => is_array($items) && $items !== [] ? json_encode($items, JSON_UNESCAPED_UNICODE) : null,
        );
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
