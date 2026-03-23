<?php

declare(strict_types=1);

namespace App\Services\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterDiscountOnlyPolicyEnum;
use App\Enums\CatalogFilter\CatalogFilterPriceSourceModeEnum;

class PriceSourceResolverService
{
    public function resolveEffectivePrice(
        ?float                              $base_price,
        ?float                              $discount_price,
        CatalogFilterPriceSourceModeEnum    $price_source_mode,
        CatalogFilterDiscountOnlyPolicyEnum $discount_only_policy,
    ): ?float {
        return match ($price_source_mode) {
            CatalogFilterPriceSourceModeEnum::BaseOnly     => $base_price,
            CatalogFilterPriceSourceModeEnum::Both         => $this->resolveBothModePrice($base_price, $discount_price),
            CatalogFilterPriceSourceModeEnum::DiscountOnly => $this->resolveDiscountOnlyModePrice(
                $base_price,
                $discount_price,
                $discount_only_policy,
            ),
        };
    }

    private function resolveBothModePrice(?float $base_price, ?float $discount_price): ?float
    {
        if ($discount_price !== null) {
            return $discount_price;
        }

        return $base_price;
    }

    private function resolveDiscountOnlyModePrice(
        ?float                              $base_price,
        ?float                              $discount_price,
        CatalogFilterDiscountOnlyPolicyEnum $discount_only_policy,
    ): ?float {
        if ($discount_price !== null) {
            return $discount_price;
        }

        if ($discount_only_policy === CatalogFilterDiscountOnlyPolicyEnum::FallbackToBase) {
            return $base_price;
        }

        return null;
    }
}
