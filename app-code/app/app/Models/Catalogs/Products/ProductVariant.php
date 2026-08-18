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
     * @return array<string, \Stringable|string>
     */
    #[\Override]
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
     * @phpstan-return BelongsTo<Product, $this>
     * @psalm-return BelongsTo<Product, self>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @phpstan-return HasMany<ProductVariantDescription, $this>
     * @psalm-return HasMany<ProductVariantDescription, self>
     */
    public function descriptions(): HasMany
    {
        return $this->hasMany(ProductVariantDescription::class);
    }

    /**
     * @phpstan-return HasMany<ProductVariantImage, $this>
     * @psalm-return HasMany<ProductVariantImage, self>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductVariantImage::class);
    }

    /**
     * @phpstan-return HasMany<ProductVariantDiscount, $this>
     * @psalm-return HasMany<ProductVariantDiscount, self>
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(ProductVariantDiscount::class);
    }

    /**
     * @phpstan-return HasMany<ProductVariantAttributeValue, $this>
     * @psalm-return HasMany<ProductVariantAttributeValue, self>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }

    /**
     * @phpstan-return HasMany<ProductVariantSizeGuide, $this>
     * @psalm-return HasMany<ProductVariantSizeGuide, self>
     */
    public function sizeGuides(): HasMany
    {
        return $this->hasMany(ProductVariantSizeGuide::class);
    }

    /**
     * @phpstan-return HasMany<ProductVariantComposition, $this>
     * @psalm-return HasMany<ProductVariantComposition, self>
     */
    public function compositions(): HasMany
    {
        return $this->hasMany(ProductVariantComposition::class);
    }

    /**
     * @phpstan-return HasMany<ProductVariantCare, $this>
     * @psalm-return HasMany<ProductVariantCare, self>
     */
    public function cares(): HasMany
    {
        return $this->hasMany(ProductVariantCare::class);
    }

    #[\Override]
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
