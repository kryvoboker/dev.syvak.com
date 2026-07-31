<?php

declare(strict_types=1);

namespace App\Enums\Marketing;

enum PromoCodeDiscountTypeEnum: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
