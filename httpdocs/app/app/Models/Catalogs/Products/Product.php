<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Catalogs\Categories\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'model',
        'sku',
        'ean',
        'quantity',
        'minimum',
        'image',
        'price',
        'viewed',
        'date_available',
        'date_added',
        'is_active',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'quantity'       => 'integer',
            'minimum'        => 'integer',
            'price'          => 'float',
            'viewed'         => 'integer',
            'date_available' => 'datetime',
            'date_added'     => 'datetime',
            'is_active'      => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProductDescription>
     */
    public function productDescription(): HasMany
    {
        return $this->hasMany(ProductDescription::class);
    }

    /**
     * @return HasMany<ProductDiscount>
     */
    public function productDiscount(): HasMany
    {
        return $this->hasMany(ProductDiscount::class);
    }

    /**
     * @return HasMany<ProductImage>
     */
    public function productImage(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * @return HasMany<ProductToAttribute>
     */
    public function productToAttribute(): HasMany
    {
        return $this->hasMany(ProductToAttribute::class);
    }

    /**
     * Get categories associated with the product
     *
     * ```
     * // Получить все товары категории
     * $category = Category::find(1);
     * $products = $category->products;
     *
     * // Получить все категории товара
     * $product = Product::find(1);
     * $categories = $product->categories;
     *
     * // С eager loading
     * $category = Category::with('products')->find(1);
     * $product = Product::with('categories')->find(1);
     * ```
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'category_product',
            'product_id',
            'category_id'
        )->withTimestamps();
    }
}
