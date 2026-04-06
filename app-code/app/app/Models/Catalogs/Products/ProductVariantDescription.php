<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantDescription extends Model
{
    protected $fillable = [
        'product_variant_id',
        'language_id',
        'name',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'product_variant_id' => 'integer',
            'language_id'        => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
