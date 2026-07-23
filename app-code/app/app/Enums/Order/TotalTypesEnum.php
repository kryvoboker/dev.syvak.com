<?php

declare(strict_types=1);

namespace App\Enums\Order;

use App\Enums\EnumValuesTrait;

enum TotalTypesEnum: string
{
    use EnumValuesTrait;

    case Total     = 'total';
    case Subtotal  = 'sub_total';
    case Shipping  = 'shipping';
    case Discount  = 'discount';
    case PromoCode = 'promo_code';
}
