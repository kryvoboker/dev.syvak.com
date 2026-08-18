<?php

declare(strict_types=1);

namespace Modules\BankTransfer\Services\Filament;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\BankTransfer\Support\BankTransferConfig;
use Throwable;

final class BankTransferSettingsService
{
    /**
     * @param array<int|string, mixed> $payment_names
     * @param array<int|string, mixed> $payment_information
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
     * @param array<int|string, mixed> $localized_values
     * @return array<string, string>
     */
    private function normalizeLocalizedValues(array $localized_values): array
    {
        $normalized_values = [];

        foreach ($localized_values as $language_code => $value) {
            if ((! is_string($language_code) && ! is_int($language_code)) || ! is_scalar($value)) {
                continue;
            }

            $normalized_language_code = Str::lower(Str::trim((string) $language_code));
            $normalized_value = Str::trim((string) $value);

            if ($normalized_language_code !== '' && $normalized_value !== '') {
                $normalized_values[$normalized_language_code] = $normalized_value;
            }
        }

        return $normalized_values;
    }
}
