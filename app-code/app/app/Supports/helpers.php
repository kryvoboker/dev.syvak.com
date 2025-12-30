<?php

declare(strict_types=1);

use App\Data\AppSettingsData;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\Currency\ConvertPrice;
use App\Supports\Services\Images\ImageUrlBuilderService;
use Illuminate\Support\Str;

if (!function_exists('clear_telephone')) {
    /**
     * @param string|null $telephone
     * @param bool        $is_delete_first_nums
     *
     * @return string
     */
    function clear_telephone(?string $telephone, bool $is_delete_first_nums = false): string
    {
        if (!isset($telephone)) {
            return '';
        }

        if ($is_delete_first_nums) {
            return (string)(preg_replace(['/\D+/', '/^38/'], '', $telephone) ?: $telephone);
        }

        return (string)(preg_replace('/\D+/', '', $telephone) ?: $telephone);
    }
}

if (!function_exists('parse_telephone')) {
    /**
     * @param string $telephone
     *
     * @return string
     */
    function parse_telephone(string $telephone): string
    {
        $telephone = clear_telephone($telephone, true);

        $mask         = '+38 (___) ___-__-__';
        $phone_length = Str::length($telephone);

        for ($index_number = 0; $index_number < $phone_length; $index_number++) {
            $mask = preg_replace('/_/', $telephone[$index_number], $mask, 1);
        }

        return $mask;
    }
}

if (!function_exists('trim_strs_in_arr')) {
    /**
     * @param array $arr
     *
     * @return array
     */
    function trim_strs_in_arr(array $arr): array
    {
        return array_map(function ($item) {
            if (is_string($item)) {
                return Str::trim($item);
            }

            return $item;
        }, $arr);
    }
}

if (!function_exists('convert_img_and_get_url')) {
    /**
     * @param string|null $path
     * @param int         $width
     * @param int|null    $height
     * @param bool        $is_square
     * @param string      $bg_color HEX or transparent color
     *
     * @return string
     */
    function convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): string
    {
        return app(ImageUrlBuilderService::class)->url($path, $width, $height, $is_square, $bg_color);
    }
}

if (!function_exists('multiple_convert_img_and_get_url')) {
    /**
     * @param string|null $path
     * @param int         $width
     * @param int|null    $height
     * @param bool        $is_square
     * @param string      $bg_color HEX or transparent color
     *
     * @return array
     */
    function multiple_convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): array
    {
        return app(ImageUrlBuilderService::class)->multipleUrl($path, $width, $height, $is_square, $bg_color);
    }
}

if (!function_exists('get_app_settings')) {
    /**
     * @return AppSettingsData|null
     */
    function get_app_settings(): ?AppSettingsData
    {
        return app(AppSettingsService::class)->getSettings();
    }
}

if (!function_exists('breadcrumb')) {
    /**
     * @param string      $title
     * @param string|null $url
     *
     * @return array
     */
    function breadcrumb(string $title, ?string $url = null): array
    {
        return ['title' => $title, 'url' => $url];
    }
}

if (!function_exists('try_detect_page_type')) {
    /**
     * @return string|null
     */
    function try_detect_page_type(): ?string
    {
        $route_name = request()->route()?->getName();

        if ($route_name === null) {
            return null;
        }

        return match (true) {
            str_ends_with($route_name, '.home')     => config('page-type.home'),
            str_ends_with($route_name, '.product')  => config('page-type.product'),
            str_ends_with($route_name, '.category') => config('page-type.category'),
            default                                 => null,
        };
    }
}

if (!function_exists('localizedRoute')) {
    /**
     * @param BackedEnum|string $route
     * @param array             $parameters
     * @param bool              $absolute
     *
     * @return string
     */
    function localizedRoute(BackedEnum|string $route, array $parameters = [], bool $absolute = true): string
    {
        $locale_key = config('localization.locale_parameter');

        return route($route, array_merge([
            $locale_key => app()->getLocale(),
        ], $parameters), $absolute);
    }
}

if (!function_exists('decode_html_entities')) {
    /**
     * @param string|null $string
     *
     * @return string
     */
    function decode_html_entities(?string $string): string
    {
        return html_entity_decode((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('escape_special_html')) {
    /**
     * @param string|null $html_string
     *
     * @return string
     */
    function escape_special_html(?string $html_string): string
    {
        $prepared_html = Str::replaceMatches('/<script>.*?<\/script>/s', function ($match) {
            if (isset($match[0])) {
                return Str::replace(['<', '>'], ['&lt;', '&gt;'], $match[0], false);
            }

            return '';
        }, decode_html_entities($html_string));

        return Str::replace("'", '&apos;', $prepared_html, false);
    }
}

if (!function_exists('convert_price')) {
    /**
     * @param float  $price
     * @param string $code_from
     * @param string $code_to
     *
     * @return float
     */
    function convert_price(float $price, string $code_from, string $code_to): float
    {
        return app(ConvertPrice::class)->convert(
            price    : $price,
            code_from: $code_from,
            code_to  : $code_to
        );
    }
}

if (!function_exists('format_price')) {
    /**
     * @param float|int   $price
     * @param string|null $currency_code
     * @param float|int   $exchange_rate
     * @param bool        $is_formatting
     *
     * @return string|float
     */
    function format_price(float|int $price, ?string $currency_code = null, float|int $exchange_rate = 0, bool $is_formatting = true): string|float
    {
        return app(ConvertPrice::class)->format(
            price        : $price,
            currency_code: $currency_code,
            exchange_rate: $exchange_rate,
            is_formatting: $is_formatting
        );
    }
}

if (!function_exists('replace_currency_symbol_to_code')) {
    /**
     * @param string      $price_string
     * @param string|null $currency_symbol
     * @param string|null $currency_code
     *
     * @return string
     */
    function replace_currency_symbol_to_code(string $price_string, ?string $currency_symbol = null, ?string $currency_code = null): string
    {
        return app(ConvertPrice::class)->replaceCurrencySymbolToCode(
            price_string   : $price_string,
            currency_symbol: $currency_symbol,
            currency_code  : $currency_code
        );
    }
}
