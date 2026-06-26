<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDiscount extends Model
{
    protected $fillable = [
        'product_id',
        'user_group_id',
        'quantity',
        'priority',
        'price',
        'date_start',
        'date_end',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'user_group_id' => 'integer',
            'quantity' => 'integer',
            'priority' => 'integer',
            'price' => 'float',
            'date_start' => 'datetime',
            'date_end' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
