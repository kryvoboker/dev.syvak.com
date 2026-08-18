<?php

declare(strict_types=1);

namespace App\Supports\Services\Currency;

use App\Models\ApplicationSettings\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use RuntimeException;

final class ConvertPrice
{
    private Currency $default_currency;

    /** @var Collection<string, Currency> */
    private Collection $currencies;

    private string $locale;

    public function format(float|int $price, ?string $currency_code = null, float|int $exchange_rate = 0, bool $is_formatting = true): string|float
    {
        $this->setValues();

        if (empty($currency_code)) {
            $currency_code = $this->default_currency->code;
        }

        if ($this->currencies->has($currency_code) === false) {
            $currency_model = app(Currency::class);
            $currency = $currency_model->getActiveCurrencyByCode($currency_code);

            if ($currency !== null) {
                $this->currencies->offsetSet($currency_code, $currency);
            } else {
                return '';
            }
        } else {
            $currency = $this->currencies->get($currency_code);
        }

        if (! $currency instanceof Currency) {
            return '';
        }

        $decimal_place = $currency->decimal_places;

        if (! $exchange_rate) {
            $exchange_rate = (float) $currency->exchange_rate;
        }

        $amount = $exchange_rate ? $price * $exchange_rate : $price;

        if (! $decimal_place) {
            $amount = ceil($amount);
        } else {
            $amount = round($amount, $decimal_place);
        }

        if (! $is_formatting) {
            return $amount;
        }

        return (string) Number::currency(
            $amount,
            $currency_code,
            $this->locale,
            $decimal_place,
        );
    }

    public function convert(float $price, string $code_from, string $code_to): float
    {
        $this->setValues();

        if ($this->default_currency->code == $code_from) {
            $code_from = $this->default_currency->exchange_rate;
        } elseif (($currency_rate = $this->currencies->get($code_from)?->exchange_rate) !== null) {
            $code_from = $currency_rate;
        } else {
            $currency_model = app(Currency::class);
            $currency = $currency_model->getActiveCurrencyByCode($code_from);

            if ($currency !== null) {
                $this->currencies->offsetSet($code_from, $currency);

                $code_from = $currency->exchange_rate;
            } else {
                $code_from = 1;
            }
        }

        if ($this->default_currency->code == $code_to) {
            $code_to = $this->default_currency->exchange_rate;
        } elseif (($currency_rate = $this->currencies->get($code_to)?->exchange_rate) !== null) {
            $code_to = $currency_rate;
        } else {
            $currency_model = app(Currency::class);
            $currency = $currency_model->getActiveCurrencyByCode($code_to);

            if ($currency !== null) {
                $this->currencies->offsetSet($code_to, $currency);

                $code_to = $currency->exchange_rate;
            } else {
                $code_to = 1;
            }
        }

        return $price * ($code_to / $code_from);
    }

    public function convertUsingExchangeRates(
        float $price,
        float $source_exchange_rate,
        float $target_exchange_rate,
        int $target_decimal_places = 2,
    ): float {
        if ($source_exchange_rate <= 0 || $target_exchange_rate <= 0) {
            throw new RuntimeException('Currency exchange rates must be greater than zero.');
        }

        $converted_price = $price * ($source_exchange_rate / $target_exchange_rate);

        return $target_decimal_places > 0
            ? round($converted_price, $target_decimal_places)
            : ceil($converted_price);
    }

    private function setValues(): void
    {
        if (! isset($this->locale)) {
            $this->locale = app()->getLocale();
        }

        if (! isset($this->default_currency)) {
            $currency_model = app(Currency::class);
            $currency = $currency_model->getActiveCurrencyByCode(
                config('app.currency.current_currency_code'),
            );

            if ($currency === null) {
                throw new RuntimeException('Default currency is not set!');
            }

            $this->default_currency = $currency;
        }

        if (! isset($this->currencies)) {
            $this->currencies = new Collection();
        }

        if ($this->currencies->has($this->default_currency->code) === false) {
            $this->currencies->offsetSet($this->default_currency->code, $this->default_currency);
        }
    }

    public function replaceCurrencySymbolToCode(string $price_string, ?string $currency_symbol = null, ?string $currency_code = null): string
    {
        if ($currency_symbol === null) {
            $currency_symbol = $this->default_currency->symbol_left ?: $this->default_currency->symbol_right;
        }

        if ($currency_code === null) {
            $currency_code = $this->default_currency->code;
        }

        return Str::replace((string) $currency_symbol, $currency_code, $price_string, caseSensitive: false);
    }

    public function setDefaultCurrency(Currency $default_currency): ConvertPrice
    {
        $this->default_currency = $default_currency;

        return $this;
    }
}
