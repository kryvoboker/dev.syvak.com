<?php

declare(strict_types=1);

namespace App\Models\Catalogs\Products;

use App\Models\Trait\HasSlugsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasSlugsTrait;

    protected $fillable = [
        'product_id',
        'is_default',
        'is_active',
        'quantity',
        'minimum',
        'price',
        'image',
        'date_available',
        'sort_order',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'quantity' => 'integer',
            'minimum' => 'integer',
            'price' => 'float',
            'date_available' => 'datetime',
            'sort_order' => 'integer',
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
     * @return HasMany<ProductVariantDescription, $this>
     */
    public function descriptions(): HasMany
    {
        return $this->hasMany(ProductVariantDescription::class);
    }

    /**
     * @return HasMany<ProductVariantImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductVariantImage::class);
    }

    /**
     * @return HasMany<ProductVariantDiscount, $this>
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(ProductVariantDiscount::class);
    }

    /**
     * @return HasMany<ProductVariantAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }

    /**
     * @return HasMany<ProductVariantSizeGuide, $this>
     */
    public function sizeGuides(): HasMany
    {
        return $this->hasMany(ProductVariantSizeGuide::class);
    }

    /**
     * @return HasMany<ProductVariantComposition, $this>
     */
    public function compositions(): HasMany
    {
        return $this->hasMany(ProductVariantComposition::class);
    }

    /**
     * @return HasMany<ProductVariantCare, $this>
     */
    public function cares(): HasMany
    {
        return $this->hasMany(ProductVariantCare::class);
    }

    protected static function booted(): void
    {
        static::saved(function (ProductVariant $variant): void {
            if (! $variant->is_default) {
                return;
            }

            static::query()
                ->where('product_id', (int) $variant->product_id)
                ->where('id', '!=', (int) $variant->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $variant->product()->update(['default_variant_id' => (int) $variant->id]);
        });
    }

    public function getLastActualAndLastModifiedDiscountForUserGroup(?int $user_group_id): ?ProductVariantDiscount
    {
        $timezone = config('app.timezone');
        $current_date_time = now(is_scalar($timezone) ? (string) $timezone : null);

        /** @var ProductVariantDiscount|null $discount */
        $discount = $this->discounts()
            ->where('date_start', '<=', $current_date_time)
            ->where('date_end', '>=', $current_date_time)
            ->orderBy('priority')
            ->orderByDesc('updated_at')
            ->when(
                $user_group_id === null,
                fn ($query) => $query->whereNull('user_group_id'),
                fn ($query) => $query->where('user_group_id', $user_group_id),
            )
            ->first();

        return $discount;
    }
}
