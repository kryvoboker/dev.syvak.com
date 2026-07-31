<?php

declare(strict_types=1);

namespace App\Enums\Marketing;

enum PromoCodeDiscountBaseModeEnum: string
{
    case IncludeDiscountedProductsAtRrp = 'include_discounted_products_at_rrp';
    case ExcludeDiscountedProducts = 'exclude_discounted_products';
}
