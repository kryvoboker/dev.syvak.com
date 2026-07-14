<?php

declare(strict_types=1);

namespace Modules\WayForPay\Services;

use App\Models\ApplicationSettings\Language;
use Modules\WayForPay\Support\WayForPayConfig;

final class WayForPayModuleDataService
{
    public function __construct(
        private readonly WayForPayConfig $wayforpay_config,
    ) {
    }

    /**
     * @return array{is_available: bool, payment_method: string, label_translation_key: string, payment_name: string}
     */
    public function getCheckoutData(string $locale): array
    {
        $active_language_codes = (new Language())
            ->getActiveLanguages()
            ->pluck('code')
            ->map(fn (mixed $code): string => strtolower((string) $code))
            ->values()
            ->all();
        $is_available = is_enabled_singleton_module('WayForPay')
            && $this->wayforpay_config->isComplete($active_language_codes);

        return [
            'is_available' => $is_available,
            'payment_method' => $this->wayforpay_config->getPaymentMethod(),
            'label_translation_key' => $this->wayforpay_config->getTranslationKey(),
            'payment_name' => $is_available ? $this->wayforpay_config->getPaymentName($locale) : '',
        ];
    }
}
