<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Support;

use App\Enums\Order\PaymentMethodEnum;

final class PaymentUponDeliveryConfig
{
    public const string PAYMENT_METHOD = PaymentMethodEnum::PaymentUponDelivery->value;

    public function getPaymentMethod(): string
    {
        return self::PAYMENT_METHOD;
    }

    public function getTranslationKey(): string
    {
        return 'paymentupondelivery::storefront/checkout.payment_methods.payment_upon_delivery';
    }
}
