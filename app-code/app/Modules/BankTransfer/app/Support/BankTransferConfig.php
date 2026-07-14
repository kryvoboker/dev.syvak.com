<?php

declare(strict_types=1);

namespace Modules\BankTransfer\Support;

use Illuminate\Support\Str;

final class BankTransferConfig
{
    public const string PAYMENT_NAMES_GLOBAL_CONFIG_KEY = 'bank_transfer.payment_names';

    public const string PAYMENT_INFORMATION_GLOBAL_CONFIG_KEY = 'bank_transfer.payment_information';

    public const string PAYMENT_METHOD = 'bank_transfer';

    public function getPaymentMethod(): string
    {
        return self::PAYMENT_METHOD;
    }

    public function getTranslationKey(): string
    {
        return 'banktransfer::storefront/checkout.payment_methods.bank_transfer';
    }

    public function getPaymentName(string $locale): string
    {
        $payment_names = $this->getPaymentNames();

        return trim((string) ($payment_names[Str::lower($locale)] ?? ''));
    }

    /**
     * @return array<string, string>
     */
    public function getPaymentNames(): array
    {
        $value = get_global_config(self::PAYMENT_NAMES_GLOBAL_CONFIG_KEY, []);

        if (is_array($value)) {
            return $this->normalizeLocalizedValues($value);
        }

        $decoded_value = json_decode((string) $value, true);

        return is_array($decoded_value) ? $this->normalizeLocalizedValues($decoded_value) : [];
    }

    public function getPaymentInformation(string $locale): string
    {
        $information = $this->getPaymentInformationByLocale();

        return trim((string) ($information[Str::lower($locale)] ?? ''));
    }

    /**
     * @return array<string, string>
     */
    public function getPaymentInformationByLocale(): array
    {
        $value = get_global_config(self::PAYMENT_INFORMATION_GLOBAL_CONFIG_KEY, []);

        if (is_array($value)) {
            return $this->normalizeLocalizedValues($value);
        }

        $decoded_value = json_decode((string) $value, true);

        return is_array($decoded_value) ? $this->normalizeLocalizedValues($decoded_value) : [];
    }

    /**
     * @param array<int, string> $active_language_codes
     */
    public function isComplete(array $active_language_codes): bool
    {
        if ($active_language_codes === []) {
            return false;
        }

        $payment_names = $this->getPaymentNames();

        foreach ($active_language_codes as $language_code) {
            if (blank($payment_names[Str::lower($language_code)] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $localized_values
     * @return array<string, string>
     */
    private function normalizeLocalizedValues(array $localized_values): array
    {
        return collect($localized_values)
            ->mapWithKeys(fn (mixed $value, mixed $language_code): array => [
                Str::lower(trim((string) $language_code)) => trim((string) $value),
            ])
            ->filter(fn (string $value, string $language_code): bool => $language_code !== '' && $value !== '')
            ->all();
    }
}
