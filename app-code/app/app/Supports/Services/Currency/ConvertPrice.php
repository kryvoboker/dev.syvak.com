<?php

declare(strict_types=1);

namespace App\Supports\Services\Currency;

use App\Models\Settings\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use RuntimeException;

final class ConvertPrice
{
    private Currency $default_currency;
    /**
     * @var null|Collection<string, Currency> $currencies
     */
    private ?Collection $currencies = null;
    private string      $locale;

    /**
     * @param float|int   $price
     * @param string|null $currency_code
     * @param float|int   $exchange_rate
     * @param bool        $is_formatting
     *
     * @return string|float
     */
    public function format(float|int $price, ?string $currency_code = null, float|int $exchange_rate = 0, bool $is_formatting = true): string|float
    {
        $this->setValues();

        if (empty($currency_code)) {
            $currency_code = $this->default_currency->code;
        }

        if ($this->currencies->has($currency_code) === false) {
            $currency = new Currency()->getActiveCurreyncyByCode($currency_code);

            if ($currency !== null) {
                $this->currencies->offsetSet($currency_code, $currency);
            } else {
                return '';
            }
        } else {
            $currency = $this->currencies->get($currency_code);
        }

        $decimal_place = $currency->decimal_places;

        if (!$exchange_rate) {
            $exchange_rate = (float)$currency->exchange_rate;
        }

        $amount = $exchange_rate ? $price * $exchange_rate : $price;

        if (!$decimal_place) {
            $amount = ceil($amount);
        } else {
            $amount = round($amount, $decimal_place);
        }

        if (!$is_formatting) {
            return $amount;
        }

        return Number::currency(
            $amount,
            $currency_code,
            $this->locale,
            $decimal_place
        );
    }

    /**
     * @param float  $price
     * @param string $code_from
     * @param string $code_to
     *
     * @return float
     */
    public function convert(float $price, string $code_from, string $code_to): float
    {
        $this->setValues();

        if ($this->default_currency->code == $code_from) {
            $code_from = $this->default_currency->exchange_rate;
        } else if ($this->currencies->has($code_from)) {
            $code_from = $this->currencies->get($code_from)->exchange_rate;
        } else {
            $currency = new Currency()->getActiveCurreyncyByCode($code_from);

            if ($currency !== null) {
                $this->currencies->offsetSet($code_from, $currency);

                $code_from = $currency->exchange_rate;
            } else {
                $code_from = 1;
            }
        }

        if ($this->default_currency->code == $code_to) {
            $code_to = $this->default_currency->exchange_rate;
        } else if ($this->currencies->has($code_to)) {
            $code_to = $this->currencies->get($code_to)->exchange_rate;
        } else {
            $currency = new Currency()->getActiveCurreyncyByCode($code_to);

            if ($currency !== null) {
                $this->currencies->offsetSet($code_to, $currency);

                $code_to = $currency->exchange_rate;
            } else {
                $code_to = 1;
            }
        }

        return $price * ($code_to / $code_from);
    }

    /**
     * @return void
     */
    private function setValues(): void
    {
        if (!isset($this->locale)) {
            $this->locale = app()->getLocale();
        }

        if (!isset($this->default_currency)) {
            $currency = new Currency()->getActiveCurreyncyByCode(
                config('app.currency.default_currency_code')
            );

            if ($currency === null) {
                throw new RuntimeException('Default currency is not set!');
            }

            $this->default_currency = $currency;
        }

        if ($this->currencies === null) {
            $this->currencies = new Collection();
        }

        if ($this->currencies->has($this->default_currency->code) === false) {
            $this->currencies->offsetSet($this->default_currency->code, $this->default_currency);
        }
    }

    /**
     * @param string      $price_string
     * @param string|null $currency_symbol
     * @param string|null $currency_code
     *
     * @return string
     */
    public function replaceCurrencySymbolToCode(string $price_string, ?string $currency_symbol = null, ?string $currency_code = null): string
    {
        if ($currency_symbol === null) {
            $currency_symbol = $this->default_currency->symbol_left ?: $this->default_currency->symbol_right;
        }

        if ($currency_code === null) {
            $currency_code = $this->default_currency->code;
        }

        return Str::replace($currency_symbol, $currency_code, $price_string, caseSensitive: false);
    }

    /**
     * @param Currency $default_currency
     *
     * @return ConvertPrice
     */
    public function setDefaultCurrency(Currency $default_currency): ConvertPrice
    {
        $this->default_currency = $default_currency;

        return $this;
    }
}
