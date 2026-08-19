<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_variant_id
 * @property int $language_id
 * @property string|null $short_title
 * @property string|null $short_description
 * @property array<int, mixed>|null $table_rows
 * @property string|null $image
 * @property int|null $image_width
 * @property int|null $image_height
 * @property string|null $full_description_title
 * @property string|null $full_description
 */
class ProductVariantSizeGuide extends Model
{
    protected $fillable = [
        'product_variant_id',
        'language_id',
        'short_title',
        'short_description',
        'table_rows',
        'image',
        'image_width',
        'image_height',
        'full_description_title',
        'full_description',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'product_variant_id' => 'integer',
            'language_id' => 'integer',
            'image_width' => 'integer',
            'image_height' => 'integer',
        ];
    }

    /**
     * @phpstan-return BelongsTo<ProductVariant, $this>
     * @psalm-return BelongsTo<ProductVariant, self>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @phpstan-return BelongsTo<Language, $this>
     * @psalm-return BelongsTo<Language, self>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
