<?php

declare(strict_types=1);

use App\Data\AppSettingsData;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use App\Services\Modules\ModuleRuntimeResolverService;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\Currency\ConvertPrice;
use App\Supports\Services\GlobalConfigService;
use App\Supports\Services\Images\ImageUrlBuilderService;
use App\Supports\Services\RequestLookupContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

if (!function_exists('clear_telephone')) {
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
    function parse_telephone(string $telephone): string
    {
        $telephone = clear_telephone($telephone, true);

        $mask = '+38 (___) ___-__-__';
        $phone_length = Str::length($telephone);

        for ($index_number = 0; $index_number < $phone_length; $index_number++) {
            $mask = Str::replaceMatches('/_/', $telephone[$index_number], $mask, 1);
        }

        return $mask;
    }
}

if (!function_exists('trim_strs_in_arr')) {
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
     * @return array{
     *      thumb_1x: string,
     *      thumb_2x: string,
     *      thumb_3x: string,
     *      thumb_4x?: string
     *  }
     *
     * @note Use this function with 'x-catalog::common.img' blade component
     */
    function multiple_convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): array
    {
        return app(ImageUrlBuilderService::class)->multipleUrl($path, $width, $height, $is_square, $bg_color);
    }
}

if (!function_exists('get_app_settings')) {
    function get_app_settings(): ?AppSettingsData
    {
        return app(AppSettingsService::class)->getSettings();
    }
}

if (!function_exists('set_app_setting')) {
    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    function set_app_setting(string $key, mixed $value): void
    {
        app(AppSettingsService::class)->setSetting($key, $value);
    }
}

if (!function_exists('get_global_config')) {
    function get_global_config(string $key, mixed $default = null): mixed
    {
        return app(GlobalConfigService::class)->getGlobalConfig($key, $default);
    }
}

if (!function_exists('set_global_config')) {
    /**
     * @param array<string, mixed>|string $key
     */
    function set_global_config(array|string $key, mixed $value = null, bool $is_active = true): mixed
    {
        $global_config_service = app(GlobalConfigService::class);

        if (is_array($key)) {
            return $global_config_service->upsertGlobalConfig($key, null, $is_active);
        }

        return $global_config_service->upsertGlobalConfig($key, $value, $is_active);
    }
}

if (!function_exists('get_global_configs')) {
    function get_global_configs(): Collection
    {
        return app(GlobalConfigService::class)->getGlobalConfigs();
    }
}

if (!function_exists('delete_global_config')) {
    /**
     * @param array<string, mixed>|string $key
     */
    function delete_global_config(array|string $key): int
    {
        return app(GlobalConfigService::class)->deleteGlobalConfig($key);
    }
}

if (!function_exists('disable_global_config')) {
    /**
     * @param array<string, mixed>|string $key
     */
    function disable_global_config(array|string $key): int
    {
        return app(GlobalConfigService::class)->disableGlobalConfig($key);
    }
}

if (!function_exists('sanitaze_str')) {
    function sanitaze_str(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        $sanitized_value = Str::trim(strip_tags(decode_html_entities($string)));

        return (string)Str::replaceMatches('/\s+/', ' ', $sanitized_value);
    }
}

if (!function_exists('breadcrumb')) {
    function breadcrumb(string $title, ?string $url = null): array
    {
        return [
            'title' => sanitaze_str($title),
            'url' => $url,
        ];
    }
}

if (!function_exists('try_detect_page_type')) {
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
                Str::endsWith($route_name, '.home') => (string)config('page-settings.page_type.home'),
                Str::endsWith($route_name, '.product'),
                Str::endsWith($route_name, '.product.variant.show'),
                Str::endsWith($route_name, '.product.show') => (string)config('page-settings.page_type.product'),
                Str::endsWith($route_name, '.category'),
                Str::endsWith($route_name, '.category.show') => (string)config('page-settings.page_type.category'),
                Str::endsWith($route_name, '.search'),
                Str::endsWith($route_name, '.search-products.index') => (string)config('page-settings.page_type.search'),
                Str::endsWith($route_name, '.cart'),
                Str::endsWith($route_name, '.cart.store'),
                Str::endsWith($route_name, '.cart.update'),
                Str::endsWith($route_name, '.cart.delete'),
                Str::endsWith($route_name, '.cart.index') => (string)config('page-settings.page_type.cart'),
                Str::endsWith($route_name, '.order'),
                Str::endsWith($route_name, '.order-confirm'),
                Str::endsWith($route_name, '.order-confirm.store'),
                Str::endsWith($route_name, '.order-confirm.validate') => (string)config('page-settings.page_type.order'),
                Str::endsWith($route_name, '.thank-you'),
                Str::endsWith($route_name, '.thank-you.index') => (string)config('page-settings.page_type.thankyou'),
                Str::endsWith($route_name, '.failure-order'),
                Str::endsWith($route_name, '.failure-order.index') => (string)config('page-settings.page_type.failure'),
                default => null,
            };
        }

        $segments = collect(explode('/', Str::trim($request->path(), '/')))
            ->filter(fn (string $segment): bool => filled($segment))
            ->values();

        if ($segments->count() === 1) {
            return (string)config('page-settings.page_type.home');
        }

        return match ($segments->get(1)) {
            'product' => (string)config('page-settings.page_type.product'),
            'category' => (string)config('page-settings.page_type.category'),
            'search' => (string)config('page-settings.page_type.search'),
            'cart' => (string)config('page-settings.page_type.cart'),
            'checkout' => (string)config('page-settings.page_type.checkout'),
            'order' => (string)config('page-settings.page_type.order'),
            'thank-you' => (string)config('page-settings.page_type.thankyou'),
            'failure' => (string)config('page-settings.page_type.failure'),
            default => null,
        };
    }
}

if (!function_exists('localized_route')) {
    function localized_route(BackedEnum|string $route, array $parameters = [], bool $absolute = true): string
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

if (!function_exists('prepare_product_attrs')) {
    /**
     * @param array<int|string, mixed> $attribute_filters
     *
     * @return array<int, array<int, int>>
     */
    function prepare_product_attrs(array $attribute_filters): array
    {
        return collect($attribute_filters)
            ->mapWithKeys(function (mixed $raw_value, int|string $raw_key): array {
                $attribute_id = null;

                if (is_int($raw_key) || ctype_digit($raw_key)) {
                    $attribute_id = (int)$raw_key;
                } else {
                    $matched_key = Str::match('/^attribute_(\d+)$/', $raw_key);

                    if (filled($matched_key)) {
                        $attribute_id = (int)$matched_key;
                    }
                }

                if (!is_int($attribute_id) || $attribute_id <= 0) {
                    return [];
                }

                $prepared_values = collect(is_array($raw_value) ? $raw_value : explode(',', (string)$raw_value))
                    ->map(function (mixed $value): int {
                        if (is_int($value) || ctype_digit((string)$value)) {
                            return (int)$value;
                        }

                        $matched_value_id = Str::match('/^attribute_value_(\d+)$/i', Str::trim((string)$value));

                        if (filled($matched_value_id)) {
                            return (int)$matched_value_id;
                        }

                        return 0;
                    })
                    ->filter(fn (int $value_id): bool => $value_id > 0)
                    ->unique()
                    ->values()
                    ->all();

                if ($prepared_values === []) {
                    return [];
                }

                return [$attribute_id => $prepared_values];
            })
            ->all();
    }
}

if (!function_exists('localized_product_variant_route')) {
    /**
     * @param array<int|string, int|string|array<int, int|string>> $attribute_filters
     */
    function localized_product_variant_route(
        string $product_slug,
        int $product_id,
        array $attribute_filters = [],
        bool $absolute = true,
    ): string {
        $normalized_filters = prepare_product_attrs($attribute_filters);

        $language = resolve_language_by_locale(app()->getLocale());
        $language_id = (int)($language->id ?? 0);
        $variant_slug = '';

        if ($language_id > 0) {
            $variant_query = ProductVariant::query()
                ->where('product_id', $product_id);

            if ($normalized_filters !== []) {
                foreach ($normalized_filters as $attribute_id => $attribute_value_ids) {
                    $variant_query->whereHas('attributeValues', function (Builder $query) use ($attribute_id, $attribute_value_ids): void {
                        $query->where('attribute_id', $attribute_id)
                            ->whereIn('id', $attribute_value_ids);
                    });
                }
            }

            $variant = $variant_query
                ->with([
                    'slugs' => function ($query) use ($language_id): void {
                        $query->where('language_id', $language_id);
                    },
                ])
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            $variant_slug = Str::trim((string)$variant?->slugs->first()?->slug);
        }

        if (filled($variant_slug)) {
            return localized_route('localized.catalog.product.variant.show', [
                'slug' => $product_slug,
                'variant_slug' => $variant_slug,
            ], $absolute);
        }

        $query_parameters = collect($normalized_filters)
            ->mapWithKeys(fn (array $value_ids, int $attribute_id): array => [
                'attribute_' . $attribute_id => collect($value_ids)
                    ->map(fn (int $value_id): string => (string)$value_id)
                    ->implode(','),
            ])
            ->all();

        return localized_route('localized.catalog.product.show', array_merge([
            'slug' => $product_slug,
        ], $query_parameters), $absolute);
    }
}

if (!function_exists('decode_html_entities')) {
    function decode_html_entities(?string $string): string
    {
        return html_entity_decode((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('escape_special_html')) {
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
    function convert_price(float $price, string $code_from, string $code_to): float
    {
        return app(ConvertPrice::class)->convert(
            price    : $price,
            code_from: $code_from,
            code_to  : $code_to,
        );
    }
}

if (!function_exists('format_price')) {
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

if (!function_exists('replace_currency_symbol_to_code')) {
    function replace_currency_symbol_to_code(string $price_string, ?string $currency_symbol = null, ?string $currency_code = null): string
    {
        return app(ConvertPrice::class)->replaceCurrencySymbolToCode(
            price_string   : $price_string,
            currency_symbol: $currency_symbol,
            currency_code  : $currency_code,
        );
    }
}

if (!function_exists('str_more_or_equal_length')) {
    function str_more_or_equal_length(?string $string, ?int $length): bool
    {
        return $string !== null && $length !== null && Str::length(Str::trim($string)) >= $length;
    }
}

if (!function_exists('num_more_or_equal_num')) {
    function num_more_or_equal_num(mixed $num, ?int $num_for_comparison): bool
    {
        return is_numeric($num) && $num_for_comparison !== null && $num >= $num_for_comparison;
    }
}

if (!function_exists('get_now_date')) {
    function get_now_date(?string $time_zone = null): Carbon|CarbonInterface
    {
        return now($time_zone ?: config('app.timezone'));
    }
}

if (!function_exists('resolve_modules_for_context')) {
    /**
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    function resolve_modules_for_context(?string $placement = null, ?string $context_key = null): Collection
    {
        return app(ModuleRuntimeResolverService::class)->resolve($placement, $context_key);
    }
}

if (!function_exists('is_enabled_singleton_module')) {
    /**
     * @param string $module_name
     *
     * @return bool
     */
    function is_enabled_singleton_module(string $module_name): bool
    {
        $module_name = Str::trim($module_name);

        if ($module_name === '') {
            return false;
        }

        return app(RequestLookupContext::class)->isEnabledSingletonModule($module_name);
    }
}

if (!function_exists('sanitaze_url')) {
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

if (!function_exists('normalize_upload_path_template')) {
    function normalize_upload_path_template(?string $path): string
    {
        $normalized_path = (string)$path;

        return (string)preg_replace('/\/\d{4}\/\d{2}$/', '/{year}/{month}', $normalized_path);
    }
}

if (!function_exists('resolve_upload_path_placeholders')) {
    function resolve_upload_path_placeholders(?string $path): string
    {
        $normalized_path = (string)$path;
        $now_date = get_now_date();

        return Str::replace(
            ['{year}', '{month}'],
            [$now_date->format('Y'), $now_date->format('m')],
            $normalized_path,
        );
    }
}

if (!function_exists('resolve_language_by_locale')) {
    function resolve_language_by_locale(string $locale, bool $is_get_new_instance = false): ?Language
    {
        if ($is_get_new_instance === true) {
            $language = new Language();

            return $language->getLanguageByCode($locale) ?: $language->getDefaultLanguage();
        }

        $lookup_context = app(RequestLookupContext::class);

        return $lookup_context->getLanguageByCode($locale) ?: $lookup_context->getDefaultLanguage();
    }
}

if (!function_exists('get_page_settings')) {
    function get_page_settings(PageSetting $page_setting): array
    {
        return is_array($page_setting->settings) ? $page_setting->settings : [];
    }
}

if (!function_exists('get_sorting_items')) {
    /**
     * @return Collection<int, array<string, mixed>>
     */
    function get_sorting_items(PageSetting $page_setting): Collection
    {
        return collect($page_setting->getSortingItemsFromSettings())
            ->filter(fn (array $sorting_item): bool => (bool)Arr::get($sorting_item, 'is_enabled', true))
            ->sortBy('sort_order')
            ->values();
    }
}

if (!function_exists('resolve_sort_code')) {
    function resolve_sort_code(PageSetting $page_setting, string $sort_value): string
    {
        if ($sort_value === '') {
            return 'default';
        }

        $sorting_items = get_sorting_items($page_setting);
        $sorting_values_to_code = [];

        foreach ($sorting_items as $sorting_item) {
            $item_get = is_array(Arr::get($sorting_item, 'get')) ? Arr::get($sorting_item, 'get') : [];
            $item_value = (string)Arr::get($item_get, 'value', '');

            if (filled($item_value)) {
                $sorting_values_to_code[$item_value] = (string)Arr::get($sorting_item, 'code', '');
            }
        }

        return $sorting_values_to_code[$sort_value] ?? 'default';
    }
}

if (!function_exists('normalize_locale')) {
    function normalize_locale(?string $locale, bool $is_get_new_instance = false): string
    {
        if ($locale === null) {
            return app()->getLocale();
        }

        $languages = $is_get_new_instance === true
            ? (new Language())->getActiveLanguages()
            : app(RequestLookupContext::class)->getActiveLanguages();

        if (
            $languages->contains(function (mixed $language) use ($locale): bool {
                return $language instanceof Language && $language->code === $locale;
            }) === false
        ) {
            $locale = app()->getLocale();
        }

        return $locale;
    }
}

if (!function_exists('get_slug_variants')) {
    /**
     * @param array<int|string, int|string|array<int, int|string>> $attribute_filters
     *
     * @return array<string, string|array<string, string>>
     */
    function get_slug_variants(
        ?string $sluggable_type,
        ?string $slug_value = null,
        ?string $variant_slug_value = null,
        array $attribute_filters = [],
    ): array {
        if ($sluggable_type === null || blank($slug_value)) {
            return [];
        }

        /** @var array<string, int> $language_ids_by_code */
        $language_ids_by_code = app(RequestLookupContext::class)
            ->getActiveLanguages()
            ->pluck('id', 'code')
            ->all();

        if ($language_ids_by_code === []) {
            return [];
        }

        $slug_instance = new Slug();

        if ($sluggable_type === ProductVariant::class) {
            return resolve_product_variant_slug_variants(
                slug_value          : (string)$slug_value,
                variant_slug_value  : $variant_slug_value,
                language_ids_by_code: $language_ids_by_code,
                attribute_filters   : $attribute_filters,
                slug_instance       : $slug_instance,
            );
        }

        $slug = $slug_instance->newQuery()
            ->where('slug', (string)$slug_value)
            ->where('sluggable_type', $sluggable_type)
            ->first();

        if (blank($slug?->sluggable_id)) {
            return [];
        }

        $slugs_query = $slug_instance->newQuery()
            ->where('sluggable_type', $sluggable_type)
            ->where('sluggable_id', $slug->sluggable_id)
            ->whereIn('language_id', array_values($language_ids_by_code));

        /** @var Collection<int, Slug> $slugs */
        $slugs = $slugs_query->get();

        $language_codes_by_id = array_flip($language_ids_by_code);

        return $slugs
            ->mapWithKeys(function (Slug $slug) use ($language_codes_by_id): array {
                $language_code = Arr::get($language_codes_by_id, $slug->language_id);

                if ($language_code === null) {
                    return [];
                }

                return [$language_code => $slug->slug];
            })
            ->all();
    }
}

if (!function_exists('resolve_product_variant_slug_variants')) {
    /**
     * @param array<string, int>                                   $language_ids_by_code
     * @param array<int|string, int|string|array<int, int|string>> $attribute_filters
     *
     * @return array<string, array{slug: string, variant_slug: string}>
     */
    function resolve_product_variant_slug_variants(
        string $slug_value,
        ?string $variant_slug_value,
        array $language_ids_by_code,
        array $attribute_filters,
        Slug $slug_instance,
    ): array {
        $product_slug = $slug_instance->newQuery()
            ->where('slug', $slug_value)
            ->where('sluggable_type', Product::class)
            ->first();

        if (!$product_slug instanceof Slug || (int)$product_slug->sluggable_id < 1) {
            return [];
        }

        $product_id = (int)$product_slug->sluggable_id;
        $variant_id = resolve_product_variant_id_for_slug_variants(
            product_id        : $product_id,
            variant_slug_value: $variant_slug_value,
            attribute_filters : $attribute_filters,
            slug_instance     : $slug_instance,
        );

        if ($variant_id < 1) {
            return [];
        }

        /** @var Collection<int, Slug> $related_slugs */
        $related_slugs = $slug_instance->newQuery()
            ->whereIn('sluggable_type', [Product::class, ProductVariant::class])
            ->where(function (Builder $query) use ($product_id, $variant_id): void {
                $query->where(function (Builder $inner_query) use ($product_id): void {
                    $inner_query
                        ->where('sluggable_type', Product::class)
                        ->where('sluggable_id', $product_id);
                })->orWhere(function (Builder $inner_query) use ($variant_id): void {
                    $inner_query
                        ->where('sluggable_type', ProductVariant::class)
                        ->where('sluggable_id', $variant_id);
                });
            })
            ->whereIn('language_id', array_values($language_ids_by_code))
            ->get();

        $result = [];

        foreach ($language_ids_by_code as $language_code => $language_id) {
            $localized_product_slug = (string)optional(
                $related_slugs
                    ->first(fn (Slug $slug): bool => (int)$slug->language_id === $language_id
                        && (string)$slug->sluggable_type === Product::class),
            )->slug;
            $localized_variant_slug = (string)optional(
                $related_slugs
                    ->first(fn (Slug $slug): bool => (int)$slug->language_id === $language_id
                        && (string)$slug->sluggable_type === ProductVariant::class),
            )->slug;

            if (blank($localized_product_slug) || blank($localized_variant_slug)) {
                continue;
            }

            $result[$language_code] = [
                'slug' => $localized_product_slug,
                'variant_slug' => $localized_variant_slug,
            ];
        }

        return $result;
    }
}

if (!function_exists('resolve_product_variant_id_for_slug_variants')) {
    /**
     * @param array<int|string, int|string|array<int, int|string>> $attribute_filters
     */
    function resolve_product_variant_id_for_slug_variants(
        int $product_id,
        ?string $variant_slug_value,
        array $attribute_filters,
        Slug $slug_instance,
    ): int {
        if (filled((string)$variant_slug_value)) {
            $variant_slug = $slug_instance->newQuery()
                ->where('slug', (string)$variant_slug_value)
                ->where('sluggable_type', ProductVariant::class)
                ->first();

            if ($variant_slug instanceof Slug && (int)$variant_slug->sluggable_id > 0) {
                /** @var ProductVariant|null $variant */
                $variant = ProductVariant::query()
                    ->where('id', (int)$variant_slug->sluggable_id)
                    ->where('product_id', $product_id)
                    ->first();

                if ($variant instanceof ProductVariant) {
                    return (int)$variant->id;
                }
            }
        }

        $normalized_filters = prepare_product_attrs($attribute_filters);
        $variant_query = ProductVariant::query()
            ->where('product_id', $product_id);

        if ($normalized_filters !== []) {
            foreach ($normalized_filters as $attribute_id => $attribute_value_ids) {
                $variant_query->whereHas('attributeValues', function (Builder $query) use ($attribute_id, $attribute_value_ids): void {
                    $query
                        ->where('attribute_id', $attribute_id)
                        ->whereIn('id', $attribute_value_ids);
                });
            }
        }

        /** @var ProductVariant|null $variant */
        $variant = $variant_query
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        return (int)($variant->id ?? 0);
    }
}

if (!function_exists('get_allowed_locales')) {
    function get_allowed_locales(bool $is_get_new_instance = false): array
    {
        try {
            $allowed_locales = ($is_get_new_instance === true
                ? (new Language())->getActiveLanguages()
                : app(RequestLookupContext::class)->getActiveLanguages())
                ->pluck('code')
                ->toArray();
        } catch (Throwable) {
            $allowed_locales = [];
        }

        if (is_array($allowed_locales) && $allowed_locales !== []) {
            return $allowed_locales;
        }

        $configured_locales = config('app.allowed_locales', []);

        if (is_array($configured_locales)) {
            return $configured_locales;
        }

        return string_to_array(is_string($configured_locales) ? $configured_locales : null);
    }
}
