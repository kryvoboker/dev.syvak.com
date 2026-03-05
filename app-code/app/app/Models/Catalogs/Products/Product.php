<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Catalogs\Categories\Category;
use App\Models\Trait\HasSlugsTrait;
use App\Models\Trait\SlugTrait;
use Database\Factories\Catalogs\Products\ProductFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasSlugsTrait, SlugTrait;

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
     * @return HasMany<ProductNameHash, $this>
     */
    public function productNameHash(): HasMany
    {
        return $this->hasMany(ProductNameHash::class);
    }

    /**
     * @return HasMany<ProductDescriptionHash, $this>
     */
    public function productDescriptionHash(): HasMany
    {
        return $this->hasMany(ProductDescriptionHash::class);
    }

    /**
     * @return HasMany<ProductAttributeTextHash, $this>
     */
    public function productAttributeTextHash(): HasMany
    {
        return $this->hasMany(ProductAttributeTextHash::class);
    }

    /**
     * @return HasOne<ProductNameHash, $this>
     */
    public function latestProductNameHash(): HasOne
    {
        return $this->hasOne(ProductNameHash::class)->latestOfMany('updated_at');
    }

    public function lagestProductDescriptionHash(): HasOne
    {
        return $this->hasOne(ProductDescriptionHash::class)->latestOfMany('updated_at');
    }

    /**
     * @return HasOne<ProductAttributeTextHash, $this>
     */
    public function latestProductAttributeTextHash(): HasOne
    {
        return $this->hasOne(ProductAttributeTextHash::class)->latestOfMany('updated_at');
    }

    /**
     * @return HasMany<ProductDescription, $this>
     */
    public function productDescription(): HasMany
    {
        return $this->hasMany(ProductDescription::class);
    }

    /**
     * @return HasMany<ProductDiscount, $this>
     */
    public function productDiscount(): HasMany
    {
        return $this->hasMany(ProductDiscount::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function productImage(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * @return HasMany<ProductToAttribute, $this>
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
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'category_product',
            'product_id',
            'category_id',
        )->withTimestamps();
    }

    public function getProductByModel(string $model): ?Product
    {
        return self::query()
            ->where('model', $model)
            ->first();
    }

    public function getLastActualAndLastModifiedDiscountFromModel(self $product): ?ProductDiscount
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

    public function search(string $keyword, int $per_page): LengthAwarePaginator
    {
        $app_settings = get_app_settings();

        return self::query()
            ->with([
                'slugs' => function ($query) use ($app_settings) {
                    $query->where('language_id', $app_settings->language_id);
                },
                'productDescription' => function ($query) use ($app_settings) {
                    $query->where('language_id', $app_settings->language_id);
                },
                'productDiscount' => function ($query) use ($app_settings) {
                    $current_date_time = now(config('app.timezone'));

                    $query
                        ->where('user_group_id', $app_settings->user_group_id)
                        ->where('date_start', '<=', $current_date_time)
                        ->where('date_end', '>=', $current_date_time)
                        ->orderBy('priority');
                },
            ])
            ->where('quantity', '>', (int) config('app.products.minimum_stock_quantity'))
            ->where(function (Builder $query) use ($keyword) {
                $query->whereHas('productDescription', function ($query_2) use ($keyword) {
                    $query_2->whereLike('name', "%$keyword%");
                })
                    ->orWhereLike('sku', "%$keyword%");
            })
            ->orderByDesc('date_added')
            ->paginate($per_page)
            ->withQueryString();
    }
}
