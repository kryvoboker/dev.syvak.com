<?php

declare(strict_types=1);

namespace App\Enums\CatalogFilter;

enum CatalogFilterPriceSourceModeEnum: string
{
    case BaseOnly     = 'base_only';
    case DiscountOnly = 'discount_only';
    case Both         = 'both';
}
