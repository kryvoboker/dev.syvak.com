<?php

declare(strict_types=1);

namespace App\Enums\Marketing;

enum PromoCodeErrorTypeEnum: string
{
    case Expired = 'expired';
    case MinimumOrder = 'minimum_order';
    case UsageLimit = 'usage_limit';
}
