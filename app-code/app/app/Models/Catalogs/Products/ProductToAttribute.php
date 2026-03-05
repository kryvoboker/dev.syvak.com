<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Catalogs\Attributes\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductToAttribute extends Model
{
    protected $fillable = [
        'product_id',
        'attribute_id',
        'language_id',
        'text',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'product_id'   => 'integer',
            'attribute_id' => 'integer',
            'language_id'  => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
