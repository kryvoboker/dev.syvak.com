<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDescription extends Model
{
    protected $fillable = [
        'product_id',
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
            'product_id'  => 'integer',
            'language_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
