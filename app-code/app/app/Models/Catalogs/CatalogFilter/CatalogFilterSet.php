<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterContextTypeEnum;
use App\Enums\CatalogFilter\CatalogFilterDiscountOnlyPolicyEnum;
use App\Enums\CatalogFilter\CatalogFilterFacetStrategyEnum;
use App\Enums\CatalogFilter\CatalogFilterPriceSourceModeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property CatalogFilterPriceSourceModeEnum $price_source_mode
 * @property CatalogFilterDiscountOnlyPolicyEnum $discount_only_policy
 * @property array<int, string>|null $context_types
 */
class CatalogFilterSet extends Model
{
    protected $fillable = [
        'code',
        'context_type',
        'context_types',
        'is_enabled',
        'is_price_filter_enabled',
        'is_attribute_filtering_enabled',
        'price_source_mode',
        'facet_strategy',
        'discount_only_policy',
        'min_stock_quantity',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context_type' => CatalogFilterContextTypeEnum::class,
            'context_types' => 'array',
            'is_enabled' => 'boolean',
            'is_price_filter_enabled' => 'boolean',
            'is_attribute_filtering_enabled' => 'boolean',
            'price_source_mode' => CatalogFilterPriceSourceModeEnum::class,
            'facet_strategy' => CatalogFilterFacetStrategyEnum::class,
            'discount_only_policy' => CatalogFilterDiscountOnlyPolicyEnum::class,
            'min_stock_quantity' => 'integer',
            'settings' => 'array',
        ];
    }

    /**
     * @return HasMany<CatalogFilterGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(CatalogFilterGroup::class);
    }

    /**
     * @return HasMany<CatalogFilterProductIndex, $this>
     */
    public function indexRows(): HasMany
    {
        return $this->hasMany(CatalogFilterProductIndex::class);
    }

    /**
     * @return HasOne<CatalogFilterIndexMeta, $this>
     */
    public function indexMeta(): HasOne
    {
        return $this->hasOne(CatalogFilterIndexMeta::class);
    }
}
