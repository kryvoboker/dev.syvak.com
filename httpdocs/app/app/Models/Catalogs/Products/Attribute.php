<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'sort_order',
        'is_active',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    /**
     * @return HasMany<AttributeDescription>
     */
    public function attributeDescription(): HasMany
    {
        return $this->hasMany(AttributeDescription::class);
    }

    /**
     * @return HasMany<ProductToAttribute>
     */
    public function productToAttribute(): HasMany
    {
        return $this->hasMany(ProductToAttribute::class);
    }
}
