<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Catalogs\Categories\Category;
use App\Models\Trait\HasSlugsTrait;
use App\Models\Trait\SlugTrait;
use Database\Factories\Catalogs\Products\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use SlugTrait, HasSlugsTrait;

    protected $fillable = [
        'model',
        'sku',
        'ean',
        'quantity',
        'minimum',
        'image',
        'price',
        'viewed',
        'is_active',
        'date_available',
        'date_added',
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
     * @return HasMany<ProductNameHash>
     */
    public function productNameHash(): HasMany
    {
        return $this->hasMany(ProductNameHash::class);
    }

    /**
     * @return HasMany<ProductDescriptionHash>
     */
    public function productDescriptionHash(): HasMany
    {
        return $this->hasMany(ProductDescriptionHash::class);
    }

    /**
     * @return HasMany<ProductAttributeTextHash>
     */
    public function productAttributeTextHash(): HasMany
    {
        return $this->hasMany(ProductAttributeTextHash::class);
    }

    /**
     * @return HasOne<ProductNameHash>
     */
    public function latestProductNameHash(): HasOne
    {
        return $this->hasOne(ProductNameHash::class)->latestOfMany('updated_at');
    }

    /**
     * @return HasOne
     */
    public function lagestProductDescriptionHash(): HasOne
    {
        return $this->hasOne(ProductDescriptionHash::class)->latestOfMany('updated_at');
    }

    /**
     * @return HasOne<ProductAttributeTextHash>
     */
    public function latestProductAttributeTextHash(): HasOne
    {
        return $this->hasOne(ProductAttributeTextHash::class)->latestOfMany('updated_at');
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
     * // Get all products in the category
     * $category = Category::find(1);
     * $products = $category->products;
     *
     * // Get all categories for a product
     * $product = Product::find(1);
     * $categories = $product->categories;
     *
     * // With eager loading
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

    /**
     * @param string $model
     *
     * @return Product|null
     */
    public function getProductByModel(string $model): ?Product
    {
        return self::query()
            ->where('model', $model)
            ->first();
    }

    /**
     * @param Product $product
     *
     * @return ProductDiscount|null
     */
    public function getLastActualAndLastModifiedDiscountFromModel(self $product): null|ProductDiscount
    {
        $current_date_time = now(config('app.timezone'));

        /** @var ProductDiscount $discount */
        $discount = $product
            ->productDiscount()
            ->where('date_start', '<=', $current_date_time)
            ->where('date_end', '>=', $current_date_time)
            ->orderByDesc('updated_at')
            ->first();

        return $discount;
    }
}
