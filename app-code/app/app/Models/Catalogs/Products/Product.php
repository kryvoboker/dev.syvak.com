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

    use HasSlugsTrait;
    use SlugTrait;

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
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    protected function casts(): array
    {
        return [
            'default_variant_id' => 'integer',
            'default_category_id' => 'integer',
            'quantity' => 'integer',
            'minimum' => 'integer',
            'price' => 'float',
            'viewed' => 'integer',
            'date_available' => 'datetime',
            'date_added' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @phpstan-return HasMany<ProductNameHash, $this>
     * @psalm-return HasMany<ProductNameHash, self>
     */
    public function productNameHash(): HasMany
    {
        return $this->hasMany(ProductNameHash::class);
    }

    /**
     * @phpstan-return HasMany<ProductDescriptionHash, $this>
     * @psalm-return HasMany<ProductDescriptionHash, self>
     */
    public function productDescriptionHash(): HasMany
    {
        return $this->hasMany(ProductDescriptionHash::class);
    }

    /**
     * @phpstan-return HasMany<ProductAttributeTextHash, $this>
     * @psalm-return HasMany<ProductAttributeTextHash, self>
     */
    public function productAttributeTextHash(): HasMany
    {
        return $this->hasMany(ProductAttributeTextHash::class);
    }

    /**
     * @phpstan-return HasOne<ProductNameHash, $this>
     * @psalm-return HasOne<ProductNameHash, self>
     */
    public function latestProductNameHash(): HasOne
    {
        return $this->hasOne(ProductNameHash::class)->latestOfMany('updated_at');
    }

    /** @phpstan-return HasOne<ProductDescriptionHash, $this>
     * @psalm-return HasOne<ProductDescriptionHash, self>
     */
    public function lagestProductDescriptionHash(): HasOne
    {
        return $this->hasOne(ProductDescriptionHash::class)->latestOfMany('updated_at');
    }

    /**
     * @phpstan-return HasOne<ProductAttributeTextHash, $this>
     * @psalm-return HasOne<ProductAttributeTextHash, self>
     */
    public function latestProductAttributeTextHash(): HasOne
    {
        return $this->hasOne(ProductAttributeTextHash::class)->latestOfMany('updated_at');
    }

    /**
     * @phpstan-return HasMany<ProductDescription, $this>
     * @psalm-return HasMany<ProductDescription, self>
     */
    public function productDescription(): HasMany
    {
        return $this->hasMany(ProductDescription::class);
    }

    /**
     * Compatibility relation for legacy code paths.
     *
     * @phpstan-return HasManyThrough<ProductVariantDiscount, ProductVariant, $this>
     * @psalm-return HasManyThrough<ProductVariantDiscount, ProductVariant, self>
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
     * @phpstan-return HasManyThrough<ProductVariantImage, ProductVariant, $this>
     * @psalm-return HasManyThrough<ProductVariantImage, ProductVariant, self>
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
     * @phpstan-return HasManyThrough<ProductVariantAttributeValue, ProductVariant, $this>
     * @psalm-return HasManyThrough<ProductVariantAttributeValue, ProductVariant, self>
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
     * @phpstan-return BelongsTo<ProductVariant, $this>
     * @psalm-return BelongsTo<ProductVariant, self>
     */
    public function defaultVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'default_variant_id');
    }

    /**
     * @phpstan-return BelongsTo<Category, $this>
     * @psalm-return BelongsTo<Category, self>
     */
    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    /**
     * @phpstan-return HasMany<ProductVariant, $this>
     * @psalm-return HasMany<ProductVariant, self>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @phpstan-return HasMany<ProductSizeGuide, $this>
     * @psalm-return HasMany<ProductSizeGuide, self>
     */
    public function sizeGuides(): HasMany
    {
        return $this->hasMany(ProductSizeGuide::class);
    }

    /** @phpstan-return HasMany<ProductComposition, $this>
     * @psalm-return HasMany<ProductComposition, self>
     */
    public function compositions(): HasMany
    {
        return $this->hasMany(ProductComposition::class);
    }

    /** @phpstan-return HasMany<ProductCare, $this>
     * @psalm-return HasMany<ProductCare, self>
     */
    public function cares(): HasMany
    {
        return $this->hasMany(ProductCare::class);
    }

    /**
     * @phpstan-return HasMany<ProductVariant, $this>
     * @psalm-return HasMany<ProductVariant, self>
     */
    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->where('is_active', true);
    }

    /** @phpstan-return BelongsToMany<Category, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
     * @psalm-return BelongsToMany<Category, self, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'>
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

    public function getLastActualAndLastModifiedDiscountFromModel(self $product): ?ProductVariantDiscount
    {
        $app_settings = get_app_settings() ?? throw new \LogicException('Application settings are not initialized.');
        $variant = $product->defaultVariant;

        if (! $variant instanceof ProductVariant) {
            return null;
        }

        return $variant->getLastActualAndLastModifiedDiscountForUserGroup((int) $app_settings->user_group_id);
    }

    /** @return LengthAwarePaginator<int, self> */
    public function search(string $keyword, int $per_page): LengthAwarePaginator
    {
        $app_settings = get_app_settings() ?? throw new \LogicException('Application settings are not initialized.');
        $minimum_stock_quantity = $this->integerValue(config('app.products.minimum_stock_quantity', 1));

        try {
            $minimum_stock_quantity = app(PageSettingsBootstrapService::class)->getProductMinimumStockQuantity();
        } catch (Throwable) {
            // Keep config fallback when page settings are not available.
        }

        return self::query()
            ->with([
                'slugs' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($app_settings): void {
                    $query->where('language_id', $app_settings->language_id);
                },
                'productDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($app_settings): void {
                    $query->where('language_id', $app_settings->language_id);
                },
                'defaultVariant.discounts' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($app_settings): void {
                    $timezone = config('app.timezone');
                    $current_date_time = now(is_scalar($timezone) ? (string) $timezone : null);

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

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
