<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Support;

final class PaymentUponDeliveryConfig
{
    public const string PAYMENT_METHOD = 'payment_upon_delivery';

    public function getPaymentMethod(): string
    {
        return self::PAYMENT_METHOD;
    }

    public function getTranslationKey(): string
    {
        return 'paymentupondelivery::storefront/checkout.payment_methods.payment_upon_delivery';
    }
}
