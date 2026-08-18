<?php

declare(strict_types=1);

namespace Modules\WayForPay\Support;

use Illuminate\Support\Str;

final class WayForPayConfig
{
    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $value = get_global_config($this->getSettingsGlobalConfigKey(), []);

        if (is_array($value)) {
            return $this->withDefaults($this->normalizeSettings($value));
        }

        $decoded_value = json_decode(is_scalar($value) ? (string) $value : '', true);

        return is_array($decoded_value) ? $this->withDefaults($this->normalizeSettings($decoded_value)) : $this->withDefaults([]);
    }

    public function get(string $key, string $default = ''): string
    {
        $value = $this->getSettings()[$key] ?? $default;

        return trim(is_scalar($value) ? (string) $value : $default);
    }

    public function getDefault(string $key, string $fallback = ''): string
    {
        return $this->getConfigString("wayforpay.settings.defaults.$key", $fallback);
    }

    public function getDefaultBoolean(string $key, bool $fallback = false): bool
    {
        return (bool) config("wayforpay.settings.defaults.$key", $fallback);
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        $value = $this->getSettings()[$key] ?? null;

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedLanguages(): array
    {
        return array_values(array_filter(
            (array) config('wayforpay.settings.options.languages', []),
            static fn (mixed $language): bool => is_string($language) && trim($language) !== '',
        ));
    }

    public function getPaymentMethod(): string
    {
        return $this->getConfigString('wayforpay.identifiers.payment_method', 'wayforpay');
    }

    public function getTranslationKey(): string
    {
        return $this->getConfigString('wayforpay.identifiers.translation_key', '');
    }

    public function getPaymentEndpoint(): string
    {
        return $this->getConfigString('wayforpay.endpoints.payment', '');
    }

    public function getWidgetScriptUrl(): string
    {
        return $this->getConfigString('wayforpay.endpoints.widget_script', '');
    }

    public function getCallbackRouteName(): string
    {
        return $this->getConfigString('wayforpay.callback.route_name', '');
    }

    public function getCallbackHandlerMethod(): string
    {
        return $this->getConfigString('wayforpay.callback.handler_method', '');
    }

    public function getReturnRouteName(): string
    {
        return $this->getConfigString('wayforpay.return.route_name', '');
    }

    public function getSettingsGlobalConfigKey(): string
    {
        return $this->getConfigString('wayforpay.storage.settings_global_config_key', '');
    }

    public function getPaymentNamesGlobalConfigKey(): string
    {
        return $this->getConfigString('wayforpay.storage.payment_names_global_config_key', '');
    }

    public function getRedirectMethod(): string
    {
        return $this->getConfigString('wayforpay.request.redirect_method', 'POST');
    }

    public function getClientCountry(): string
    {
        return $this->getConfigString('wayforpay.request.client_country', 'Ukraine');
    }

    /**
     * @return array<int, string>
     */
    public function getOptionList(string $key): array
    {
        return array_values(array_filter(
            (array) config("wayforpay.settings.options.$key", []),
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        ));
    }

    public function getPaymentName(string $locale): string
    {
        $value = $this->getPaymentNames()[Str::lower($locale)] ?? '';

        return trim(is_scalar($value) ? (string) $value : '');
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentNames(): array
    {
        $value = get_global_config($this->getPaymentNamesGlobalConfigKey(), []);

        if (is_array($value)) {
            return $this->normalizeLocalizedValues($value);
        }

        $decoded_value = json_decode(is_scalar($value) ? (string) $value : '', true);

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

        foreach (['merchant_account', 'secret_key', 'merchant_domain_name'] as $required_key) {
            if ($this->get($required_key) === '') {
                return false;
            }
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
     * @param array<int|string, mixed> $settings
     * @return array<string, string>
     */
    private function normalizeSettings(array $settings): array
    {
        return collect($settings)
            ->mapWithKeys(fn (mixed $value, mixed $key): array => [
                trim((string) $key) => is_bool($value) ? ($value ? '1' : '0') : (is_scalar($value) ? trim((string) $value) : ''),
            ])
            ->filter(fn (string $value, string $key): bool => $key !== '')
            ->all();
    }

    /**
     * @param array<string, string> $settings
     * @return array<string, mixed>
    */
    private function withDefaults(array $settings): array
    {
        $defaults = [
            'merchant_auth_type' => $this->getDefault('merchant_auth_type'),
            'merchant_transaction_type' => $this->getDefault('merchant_transaction_type'),
            'merchant_transaction_secure_type' => $this->getDefault('merchant_transaction_secure_type'),
            'api_version' => $this->getDefault('api_version'),
            'language' => $this->getDefault('language'),
            'checkout_widget_enabled' => $this->getDefaultBoolean('checkout_widget_enabled', true),
        ];

        $resolved_settings = array_merge($defaults, $settings);

        $resolved_settings['checkout_widget_enabled'] = filter_var(
            $resolved_settings['checkout_widget_enabled'],
            FILTER_VALIDATE_BOOLEAN,
        );

        return $resolved_settings;
    }

    /**
     * @param array<int|string, mixed> $localized_values
     * @return array<string, string>
     */
    private function normalizeLocalizedValues(array $localized_values): array
    {
        $normalized_values = [];

        foreach ($localized_values as $language_code => $value) {
            if (! is_string($language_code) || ! is_scalar($value)) {
                continue;
            }

            $normalized_language_code = Str::lower(trim($language_code));
            $normalized_value = trim((string) $value);

            if ($normalized_language_code !== '' && $normalized_value !== '') {
                $normalized_values[$normalized_language_code] = $normalized_value;
            }
        }

        return $normalized_values;
    }

    private function getConfigString(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }
}
