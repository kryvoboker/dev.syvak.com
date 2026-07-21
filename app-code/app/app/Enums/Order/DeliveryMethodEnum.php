<?php

declare(strict_types=1);

namespace App\Enums\Order;

use App\Enums\EnumValuesTrait;

enum DeliveryMethodEnum: string
{
    use EnumValuesTrait;

    case NovaPoshta = 'nova_poshta';
    case NovaPoshtaPoshtomat = 'nova_poshta_poshtomat';
    case NovaPoshtaCourier = 'nova_poshta_courier';
    case UkrPoshta = 'ukr_poshta';
    case PickupStore = 'pickup_store';
}
