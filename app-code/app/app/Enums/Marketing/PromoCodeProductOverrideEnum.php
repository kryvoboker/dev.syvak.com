<?php

declare(strict_types=1);

namespace App\Enums\Marketing;

enum PromoCodeProductOverrideEnum: string
{
    case Force = 'force';
    case Deny = 'deny';
}
