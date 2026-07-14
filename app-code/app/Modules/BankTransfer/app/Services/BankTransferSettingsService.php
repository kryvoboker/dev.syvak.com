<?php

declare(strict_types=1);

namespace Modules\BankTransfer\Services;

use Illuminate\Support\Facades\Log;
use Modules\BankTransfer\Support\BankTransferConfig;
use Throwable;

final class BankTransferSettingsService
{
    /**
     * @param array<string, string> $payment_names
     * @param array<string, string> $payment_information
     * @return array{payment_names: array<string, string>, payment_information: array<string, string>}
     */
    public function save(array $payment_names, array $payment_information): array
    {
        $normalized_payment_names = $this->normalizeLocalizedValues($payment_names);
        $normalized_payment_information = $this->normalizeLocalizedValues($payment_information);

        try {
            set_global_config(BankTransferConfig::PAYMENT_NAMES_GLOBAL_CONFIG_KEY, $normalized_payment_names);
            set_global_config(BankTransferConfig::PAYMENT_INFORMATION_GLOBAL_CONFIG_KEY, $normalized_payment_information);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[BankTransferSettingsService.save] settings save failed', [
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        return [
            'payment_names' => $normalized_payment_names,
            'payment_information' => $normalized_payment_information,
        ];
    }

    /**
     * @param array<string, string> $localized_values
     * @return array<string, string>
     */
    private function normalizeLocalizedValues(array $localized_values): array
    {
        return collect($localized_values)
            ->mapWithKeys(fn (mixed $value, mixed $language_code): array => [
                strtolower(trim((string) $language_code)) => trim((string) $value),
            ])
            ->filter(fn (string $value, string $language_code): bool => $language_code !== '' && $value !== '')
            ->all();
    }
}
