<?php

declare(strict_types=1);

namespace App\Enums;

enum PositionInPageEnum: string
{
    case Top    = 'top';
    case Center = 'center';
    case Bottom = 'bottom';
}
