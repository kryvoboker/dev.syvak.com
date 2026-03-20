<?php

declare(strict_types=1);

namespace App\Enums\CatalogFilter;

enum CatalogFilterDiscountOnlyPolicyEnum: string
{
    case ExcludeWithoutDiscount = 'exclude_without_discount';
    case FallbackToBase         = 'fallback_to_base';
}
