<?php

declare(strict_types=1);

namespace App\Enums;

enum CartModeEnum:string
{
    use EnumValuesTrait;

    case Regular   = 'regular';
    case FastOrder = 'fast_order';
}
