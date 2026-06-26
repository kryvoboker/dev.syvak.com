<?php

declare(strict_types=1);

namespace App\Enums;

enum CartRequestKeyEnum: string
{
    case CartMode = 'cart_mode';
    case IsCallFromModal = 'is_call_from_modal';
}
