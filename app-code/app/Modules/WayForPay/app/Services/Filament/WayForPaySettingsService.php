<?php

declare(strict_types=1);

namespace Modules\WayForPay\Services\Filament;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;

final class WayForPaySettingsService
{
    public function __construct(
        private readonly WayForPayConfig $wayforpay_config,
    ) {
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $payment_names
     * @return array{settings: array<string, mixed>, payment_names: array<string, string>}
     */
    public function save(array $settings, array $payment_names): array
    {
        $normalized_settings = $this->normalizeSettings($settings);
        $normalized_payment_names = $this->normalizeLocalizedValues($payment_names);

        try {
            set_global_config($this->wayforpay_config->getSettingsGlobalConfigKey(), $normalized_settings);
            set_global_config($this->wayforpay_config->getPaymentNamesGlobalConfigKey(), $normalized_payment_names);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[WayForPaySettingsService.save] settings save failed', [
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        return [
            'settings' => $normalized_settings,
            'payment_names' => $normalized_payment_names,
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function normalizeSettings(array $settings): array
    {
        return collect($settings)
            ->mapWithKeys(fn (mixed $value, mixed $key): array => [
                trim((string) $key) => trim((string) $key) === 'checkout_widget_enabled'
                    ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    : (is_bool($value) ? ($value ? '1' : '0') : trim((string) $value)),
            ])
            ->filter(fn (mixed $value, string $key): bool => $key !== '' && ($value !== '' || $key === 'checkout_widget_enabled'))
            ->all();
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
