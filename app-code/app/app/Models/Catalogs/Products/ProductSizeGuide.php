<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\ApplicationSettings\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSizeGuide extends Model
{
    protected $fillable = [
        'product_id',
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

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'language_id' => 'integer',
            'image_width' => 'integer',
            'image_height' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Language, $this> */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
