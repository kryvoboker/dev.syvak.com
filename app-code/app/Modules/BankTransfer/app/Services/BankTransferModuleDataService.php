<?php

declare(strict_types=1);

namespace Modules\BankTransfer\Services;

use App\Models\ApplicationSettings\Language;
use Modules\BankTransfer\Support\BankTransferConfig;

final class BankTransferModuleDataService
{
    public function __construct(
        private readonly BankTransferConfig $bank_transfer_config,
    ) {
    }

    /**
     * @return array{is_available: bool, payment_method: string, label_translation_key: string, payment_name: string, payment_information: string}
     */
    public function getCheckoutData(string $locale): array
    {
        $active_language_codes = (new Language())
            ->getActiveLanguages()
            ->pluck('code')
            ->map(fn (mixed $code): string => strtolower((string) $code))
            ->values()
            ->all();
        $is_available = is_enabled_singleton_module('BankTransfer')
            && $this->bank_transfer_config->isComplete($active_language_codes);

        return [
            'is_available' => $is_available,
            'payment_method' => $this->bank_transfer_config->getPaymentMethod(),
            'label_translation_key' => $this->bank_transfer_config->getTranslationKey(),
            'payment_name' => $is_available ? $this->bank_transfer_config->getPaymentName($locale) : '',
            'payment_information' => $is_available
                ? $this->bank_transfer_config->getPaymentInformation($locale)
                : '',
        ];
    }
}
