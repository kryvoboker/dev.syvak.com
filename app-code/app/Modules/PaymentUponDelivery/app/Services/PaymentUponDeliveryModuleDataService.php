<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Services;

use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;

final class PaymentUponDeliveryModuleDataService
{
    public function __construct(
        private readonly PaymentUponDeliveryConfig $payment_config,
    ) {
    }

    /**
     * @return array{is_available: bool, payment_method: string, label_translation_key: string}
     */
    public function getCheckoutData(): array
    {
        $payment_method = $this->payment_config->getPaymentMethod();
        $translation_key = $this->payment_config->getTranslationKey();

        return [
            'is_available' => is_enabled_singleton_module('PaymentUponDelivery'),
            'payment_method' => $payment_method,
            'label_translation_key' => $translation_key,
        ];
    }
}
