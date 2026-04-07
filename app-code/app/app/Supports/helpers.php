<?php

declare(strict_types=1);

use App\Data\AppSettingsData;
use App\Models\ApplicationSettings\Language;
use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use App\Services\Modules\ModuleRuntimeResolverService;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\Currency\ConvertPrice;
use App\Supports\Services\Images\ImageUrlBuilderService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

if (! function_exists('clear_telephone')) {
    function clear_telephone(?string $telephone, bool $is_delete_first_nums = false): string
    {
        if (! isset($telephone)) {
            return '';
        }

        if ($is_delete_first_nums) {
            return (string) (preg_replace(['/\D+/', '/^38/'], '', $telephone) ?: $telephone);
        }

        return (string) (preg_replace('/\D+/', '', $telephone) ?: $telephone);
    }
}

if (! function_exists('parse_telephone')) {
    function parse_telephone(string $telephone): string
    {
        $telephone = clear_telephone($telephone, true);

        $mask         = '+38 (___) ___-__-__';
        $phone_length = Str::length($telephone);

        for ($index_number = 0; $index_number < $phone_length; $index_number++) {
            $mask = Str::replaceMatches('/_/', $telephone[$index_number], $mask, 1);
        }

        return $mask;
    }
}

if (! function_exists('trim_strs_in_arr')) {
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

if (! function_exists('convert_img_and_get_url')) {
    /**
     * @param  string  $bg_color  HEX or transparent color
     */
    function convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): string
    {
        return app(ImageUrlBuilderService::class)->url($path, $width, $height, $is_square, $bg_color);
    }
}

if (! function_exists('multiple_convert_img_and_get_url')) {
    /**
     * @param  string  $bg_color  HEX or transparent color
     */
    function multiple_convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): array
    {
        return app(ImageUrlBuilderService::class)->multipleUrl($path, $width, $height, $is_square, $bg_color);
    }
}

if (! function_exists('get_app_settings')) {
    function get_app_settings(): ?AppSettingsData
    {
        return app(AppSettingsService::class)->getSettings();
    }
}

if (! function_exists('sanitaze_str')) {
    function sanitaze_str(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        $sanitized_value = decode_html_entities($string);
        $sanitized_value = strip_tags($sanitized_value);
        $sanitized_value = Str::trim($sanitized_value);

        return (string) Str::replaceMatches('/\s+/', ' ', $sanitized_value);
    }
}

if (! function_exists('breadcrumb')) {
    function breadcrumb(string $title, ?string $url = null): array
    {
        return [
            'title' => sanitaze_str($title),
            'url'   => sanitaze_url($url),
        ];
    }
}

if (! function_exists('try_detect_page_type')) {
    function try_detect_page_type(?Request $request = null): ?string
    {
        if ($request === null) {
            $request = request();
        }

        $route_name = $request->route()?->getName();

        if ($route_name === null) {
            return null;
        }

        if (is_string($route_name) && filled($route_name)) {
            return match (true) {
                Str::endsWith($route_name, '.home') => (string) config('page-settings.page_type.home'),
                Str::endsWith($route_name, '.product'),
                Str::endsWith($route_name, '.product.show') => (string) config('page-settings.page_type.product'),
                Str::endsWith($route_name, '.category'),
                Str::endsWith($route_name, '.category.show') => (string) config('page-settings.page_type.category'),
                Str::endsWith($route_name, '.search'),
                Str::endsWith($route_name, '.search-products.index') => (string) config('page-settings.page_type.search'),
                default                                              => null,
            };
        }

        $segments = collect(explode('/', Str::trim($request->path(), '/')))
            ->filter(fn (string $segment): bool => filled($segment))
            ->values();

        if ($segments->count() === 1) {
            return (string) config('page-settings.page_type.home');
        }

        return match ($segments->get(1)) {
            'product'  => (string) config('page-settings.page_type.product'),
            'category' => (string) config('page-settings.page_type.category'),
            'search'   => (string) config('page-settings.page_type.search'),
            default    => null,
        };
    }
}

if (! function_exists('localizedRoute')) {
    function localizedRoute(BackedEnum|string $route, array $parameters = [], bool $absolute = true): string
    {
        $locale_key = config('localization.locale_parameter');

        if (Str::startsWith($route, 'localized.') === false) {
            $route = 'localized.' . $route;
        }

        return route($route, array_merge([
            $locale_key => app()->getLocale(),
        ], $parameters), $absolute);
    }
}

if (! function_exists('decode_html_entities')) {
    function decode_html_entities(?string $string): string
    {
        return html_entity_decode((string) $string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('escape_special_html')) {
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

if (! function_exists('convert_price')) {
    function convert_price(float $price, string $code_from, string $code_to): float
    {
        return app(ConvertPrice::class)->convert(
            price    : $price,
            code_from: $code_from,
            code_to  : $code_to,
        );
    }
}

if (! function_exists('format_price')) {
    function format_price(float|int $price, ?string $currency_code = null, float|int $exchange_rate = 0, bool $is_formatting = true): string|float
    {
        return app(ConvertPrice::class)->format(
            price        : $price,
            currency_code: $currency_code,
            exchange_rate: $exchange_rate,
            is_formatting: $is_formatting,
        );
    }
}

if (! function_exists('replace_currency_symbol_to_code')) {
    function replace_currency_symbol_to_code(string $price_string, ?string $currency_symbol = null, ?string $currency_code = null): string
    {
        return app(ConvertPrice::class)->replaceCurrencySymbolToCode(
            price_string   : $price_string,
            currency_symbol: $currency_symbol,
            currency_code  : $currency_code,
        );
    }
}

if (! function_exists('str_more_or_equal_length')) {
    function str_more_or_equal_length(?string $string, ?int $length): bool
    {
        return $string !== null && $length !== null && Str::length(Str::trim($string)) >= $length;
    }
}

if (! function_exists('num_more_or_equal_num')) {
    function num_more_or_equal_num(mixed $num, ?int $num_for_comparison): bool
    {
        return is_numeric($num) && $num_for_comparison !== null && $num >= $num_for_comparison;
    }
}

if (! function_exists('get_now_date')) {
    function get_now_date(?string $time_zone = null): Carbon|CarbonInterface
    {
        return now($time_zone ?: config('app.timezone'));
    }
}

if (! function_exists('resolve_modules_for_context')) {
    /**
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    function resolve_modules_for_context(?string $placement = null, ?string $context_key = null): Collection
    {
        return app(ModuleRuntimeResolverService::class)->resolve($placement, $context_key);
    }
}

if (! function_exists('sanitaze_url')) {
    function sanitaze_url(?string $url): string
    {
        if ($url === null) {
            return '';
        }

        $sanitized_url = filter_var($url, FILTER_SANITIZE_URL);

        // Ensure the URL has a valid scheme (http or https)
        if (Str::startsWith($sanitized_url, ['http://', 'https://']) === false) {
            $sanitized_url = (request()->isSecure() ? 'https://' : 'http://') . $sanitized_url;
        }

        return $sanitized_url;
    }
}

if (! function_exists('normalize_upload_path_template')) {
    function normalize_upload_path_template(?string $path): string
    {
        $normalized_path = (string) $path;

        return (string) preg_replace('/\/\d{4}\/\d{2}$/', '/{year}/{month}', $normalized_path);
    }
}

if (! function_exists('resolve_upload_path_placeholders')) {
    function resolve_upload_path_placeholders(?string $path): string
    {
        $normalized_path = (string) $path;
        $now_date        = get_now_date();

        return Str::replace(
            ['{year}', '{month}'],
            [$now_date->format('Y'), $now_date->format('m')],
            $normalized_path,
        );
    }
}

if (! function_exists('resolve_language_by_locale')) {
    function resolve_language_by_locale(string $locale): ?Language
    {
        $language = new Language();

        return $language->getLanguageByCode($locale) ?: $language->getDefaultLanguage();
    }
}

if (! function_exists('get_page_settings')) {
    function get_page_settings(PageSetting $page_setting): array
    {
        return is_array($page_setting->settings) ? $page_setting->settings : [];
    }
}

if (! function_exists('get_sorting_items')) {
    /**
     * @return Collection<int, array<string, mixed>>
     */
    function get_sorting_items(PageSetting $page_setting): Collection
    {
        return collect($page_setting->getSortingItemsFromSettings())
            ->filter(fn (array $sorting_item): bool => (bool) Arr::get($sorting_item, 'is_enabled', true))
            ->sortBy('sort_order')
            ->values();
    }
}

if (! function_exists('resolve_sort_code')) {
    function resolve_sort_code(PageSetting $page_setting, string $sort_value): string
    {
        if ($sort_value === '') {
            return 'default';
        }

        $sorting_items          = get_sorting_items($page_setting);
        $sorting_values_to_code = [];

        foreach ($sorting_items as $sorting_item) {
            $item_get   = is_array(Arr::get($sorting_item, 'get')) ? Arr::get($sorting_item, 'get') : [];
            $item_value = (string) Arr::get($item_get, 'value', '');

            if (filled($item_value)) {
                $sorting_values_to_code[$item_value] = (string) Arr::get($sorting_item, 'code', '');
            }
        }

        return $sorting_values_to_code[$sort_value] ?? 'default';
    }
}

if (! function_exists('normalize_locale')) {
    function normalize_locale(?string $locale): string
    {
        $languages = new Language()->getActiveLanguages();

        if ($locale === null || $languages->containsStrict(fn (Language $language) => $language->code === $locale) === false) {
            $locale = app()->getLocale();
        }

        return $locale;
    }
}

if (! function_exists('get_slug_variants')) {
    function get_slug_variants(?string $sluggable_type, ?string $slug_value = null): array
    {
        if ($sluggable_type === null) {
            return [];
        }

        $language_ids = new Language()
            ->getActiveLanguages()
            ->pluck('id', 'code')
            ->all();

        $slug_instance = new Slug();

        $slug = $slug_instance
            ->where('slug', $slug_value)
            ->first();

        if (blank($slug?->sluggable_id)) {
            return [];
        }

        $slugs_query = $slug_instance
            ->where('sluggable_type', $sluggable_type)
            ->where('sluggable_id', $slug->sluggable_id)
            ->whereIn('language_id', $language_ids);

        $slugs_query->whereNot('slug', $slug_value);

        /** @var Collection<int, Slug> $slugs */
        $slugs = $slugs_query->get();

        $language_ids = array_flip($language_ids);

        return $slugs
            ->mapWithKeys(function (Slug $slug) use ($language_ids) {
                $language_code = Arr::get($language_ids, $slug->language_id);

                if ($language_code === null) {
                    return [];
                }

                return [$language_code => $slug->slug];
            })
            ->all();
    }
}

if (! function_exists('get_allowed_locales')) {
    function get_allowed_locales(): array
    {
        $languages = new Language()->getActiveLanguages();

        return $languages->pluck('code')->toArray() ?: array_values(array_filter((array) config('app.locales', [config('app.locale', 'en')])));
    }
}
