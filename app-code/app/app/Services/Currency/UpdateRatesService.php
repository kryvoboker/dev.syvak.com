<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Models\ApplicationSettings\Currency;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateRatesService
{
    public function handle(): ?string
    {
        $currency = new Currency();

        $default_active_currency = $currency->getDefaultActiveCurrency();

        if (! $default_active_currency) {
            return (string) __('admin/settings/currencies.error_absent_default_currency');
        }

        $json_url = config('app.currency.json_url');

        try {
            $response = Http::timeout(10)->get($this->stringValue($json_url));

            if ($response->failed()) {
                Log::channel('stack')->error(__('admin/settings/currencies.error_failed_to_update_rates'), [
                    'status' => $response->status(),
                ]);

                return (string) __('admin/settings/currencies.error_failed_to_update_rates');
            }

            $json = $response->json();

            if (! is_array($json) || $json === []) {
                return null;
            }
        } catch (Exception $e) {
            Log::channel('stack')->error(__('admin/settings/currencies.error_failed_to_update_rates'), [
                'message' => $e->getMessage(),
            ]);

            return (string) __('admin/settings/currencies.error_failed_to_update_rates');
        }

        $currencies = $currency->getAllCurrencies();
        $currencies_data = [
            $default_active_currency->code => $default_active_currency->exchange_rate,
        ];

        foreach ($json as $rate_data) {
            if (! is_array($rate_data)) {
                continue;
            }

            $code = $rate_data['cc'] ?? null;
            $rate = $rate_data['rate'] ?? null;

            if (is_string($code) && is_numeric($rate)) {
                $currencies_data[$code] = (float) $rate;
            }
        }

        unset($currency);

        $currencies->each(function (Currency $currency) use ($currencies_data) {
            if (! isset($currencies_data[$currency->code])) {
                return;
            }

            $rate = $currencies_data[$currency->code];

            $currency->exchange_rate = (float) $rate;
            $currency->save();
        });

        return null;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
