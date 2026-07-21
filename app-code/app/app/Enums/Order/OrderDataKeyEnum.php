<?php

declare(strict_types=1);

namespace App\Enums\Order;

enum OrderDataKeyEnum: string
{
    case DeliveryMethod = 'delivery_method';
    case PaymentMethod = 'payment_method';
    case DeliveryAddress = 'delivery_address';
    case DeliveryPoint = 'delivery_point';
    case City = 'city';
    case Comment = 'comment';
    case PromoCode = 'promo_code';
    case NoCall = 'no_call';
}
