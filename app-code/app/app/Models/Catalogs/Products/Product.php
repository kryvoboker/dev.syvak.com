<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Catalogs\Categories\Category;
use App\Models\Trait\HasSlugsTrait;
use App\Models\Trait\SlugTrait;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Database\Factories\Catalogs\Products\ProductFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Throwable;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasSlugsTrait, SlugTrait;

    protected $fillable = [
        'default_variant_id',
        'default_category_id',
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
        'size_guide_data',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'default_variant_id'  => 'integer',
            'default_category_id' => 'integer',
            'quantity'            => 'integer',
            'minimum'             => 'integer',
            'price'               => 'float',
            'viewed'              => 'integer',
            'date_available'      => 'datetime',
            'date_added'          => 'datetime',
            'is_active'           => 'boolean',
            'size_guide_data'     => 'array',
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
     * Compatibility relation for legacy code paths.
     *
     * @return HasManyThrough<ProductVariantDiscount, ProductVariant, $this>
     */
    public function productDiscount(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductVariantDiscount::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
            'id',
            'id',
        );
    }

    /**
     * Compatibility relation for legacy code paths.
     *
     * @return HasManyThrough<ProductVariantImage, ProductVariant, $this>
     */
    public function productImage(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductVariantImage::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
            'id',
            'id',
        );
    }

    /**
     * Compatibility relation for legacy code paths.
     *
     * @return HasManyThrough<ProductVariantAttributeValue, ProductVariant, $this>
     */
    public function productToAttribute(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductVariantAttributeValue::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
            'id',
            'id',
        );
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function defaultVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'default_variant_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->where('is_active', true);
    }

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

    public function getLastActualAndLastModifiedDiscountFromModel(self $product): ?ProductVariantDiscount
    {
        $app_settings = get_app_settings();
        $variant      = $product->defaultVariant;

        if (! $variant instanceof ProductVariant) {
            return null;
        }

        return $variant->getLastActualAndLastModifiedDiscountForUserGroup((int) $app_settings->user_group_id);
    }

    public function search(string $keyword, int $per_page): LengthAwarePaginator
    {
        $app_settings           = get_app_settings();
        $minimum_stock_quantity = (int) config('app.products.minimum_stock_quantity', 1);

        try {
            $minimum_stock_quantity = app(PageSettingsBootstrapService::class)->getProductMinimumStockQuantity();
        } catch (Throwable) {
            // Keep config fallback when page settings are not available.
        }

        return self::query()
            ->with([
                'slugs' => function ($query) use ($app_settings): void {
                    $query->where('language_id', $app_settings->language_id);
                },
                'productDescription' => function ($query) use ($app_settings): void {
                    $query->where('language_id', $app_settings->language_id);
                },
                'defaultVariant.discounts' => function ($query) use ($app_settings): void {
                    $current_date_time = now(config('app.timezone'));

                    $query
                        ->where('user_group_id', $app_settings->user_group_id)
                        ->where('date_start', '<=', $current_date_time)
                        ->where('date_end', '>=', $current_date_time)
                        ->orderBy('priority');
                },
            ])
            ->where('is_active', true)
            ->whereHas('defaultVariant', function (Builder $query) use ($minimum_stock_quantity): void {
                $query
                    ->where('is_active', true)
                    ->where('quantity', '>=', max(0, $minimum_stock_quantity));
            })
            ->where(function (Builder $query) use ($keyword): void {
                $query->whereHas('productDescription', function (Builder $query_2) use ($keyword): void {
                    $query_2->whereLike('name', "%$keyword%");
                })
                    ->orWhereLike('sku', "%$keyword%");
            })
            ->orderByDesc('date_added')
            ->paginate($per_page)
            ->withQueryString();
    }
}
