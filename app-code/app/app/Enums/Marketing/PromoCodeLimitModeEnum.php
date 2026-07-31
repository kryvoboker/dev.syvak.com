<?php

declare(strict_types=1);

namespace App\Enums\Marketing;

enum PromoCodeLimitModeEnum: string
{
    case PerEntity = 'per_entity';
    case Shared = 'shared';
}
