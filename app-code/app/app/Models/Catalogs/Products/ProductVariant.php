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
        'size_guide_data',
        'composition_and_care_data',
    ];

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'product_id'                => 'integer',
            'is_default'                => 'boolean',
            'is_active'                 => 'boolean',
            'quantity'                  => 'integer',
            'minimum'                   => 'integer',
            'price'                     => 'float',
            'date_available'            => 'datetime',
            'sort_order'                => 'integer',
            'size_guide_data'           => 'array',
            'composition_and_care_data' => 'array',
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

    public function getLastActualAndLastModifiedDiscountForUserGroup(int $user_group_id): ?ProductVariantDiscount
    {
        $current_date_time = now(config('app.timezone'));

        /** @var ProductVariantDiscount|null $discount */
        $discount = $this->discounts()
            ->where('user_group_id', $user_group_id)
            ->where('date_start', '<=', $current_date_time)
            ->where('date_end', '>=', $current_date_time)
            ->orderByDesc('updated_at')
            ->first();

        return $discount;
    }
}
