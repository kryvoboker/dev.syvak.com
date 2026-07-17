<?php

declare(strict_types=1);

namespace App\Enums\Cart;

use App\Enums\EnumValuesTrait;

enum CartModeEnum:string
{
    use EnumValuesTrait;

    case Regular   = 'regular';
    case FastOrder = 'fast_order';
}
