<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services;

use App\Models\Catalogs\Categories\Category;
use App\Models\Settings\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\ProductsCarousel\Support\ProductsCarouselConfig;

/**
 * Normalizes and sanitizes ProductsCarousel settings before persisting module instance payloads.
 */
readonly class ModuleSettingsNormalizerService
{
    public function __construct(
        private ProductsCarouselConfig $products_carousel_config,
        private ProductsCarouselProductSearchService $products_carousel_product_search_service,
    ) {}

    /**
     * @param  array<string, mixed>  $settings
     *
     * @throws ValidationException
     *
     * @return array<string, mixed>
     */
    public function normalize(array $settings): array
    {
        $allowed_source_modes = collect($this->products_carousel_config->get('settings.allowed_source_modes', []))
            ->filter(fn (mixed $mode): bool => is_string($mode) && filled($mode))
            ->values()
            ->all();
        $allowed_sort_modes = collect($this->products_carousel_config->get('settings.allowed_sort_modes', []))
            ->filter(fn (mixed $mode): bool => is_string($mode) && filled($mode))
            ->values()
            ->all();
        $allowed_sort_options = collect($this->products_carousel_config->get('settings.allowed_sort_options', []))
            ->filter(fn (mixed $option): bool => is_string($option) && filled($option))
            ->values()
            ->all();
        $allowed_page_types = collect(config('page-type', []))->values()->all();
        $active_languages   = (new Language())->getActiveLanguages();
        $shared_settings    = Arr::get($settings, 'shared', []);

        $default_source_mode = (string) $this->products_carousel_config->get('settings.default_source_mode', 'category_based');
        $source_mode         = (string) Arr::get($settings, 'source_mode', $default_source_mode);

        Log::channel('daily')->info('Normalizing ProductsCarousel settings payload.', [
            'source_mode' => $source_mode,
        ]);

        $mode_validator = Validator::make(
            ['source_mode' => $source_mode],
            ['source_mode' => ['required', 'string', 'in:' . implode(',', $allowed_source_modes)]],
        );

        if ($mode_validator->fails()) {
            throw ValidationException::withMessages($mode_validator->errors()->messages());
        }

        $category_based_settings = Arr::get($settings, 'category_based', []);

        if (! is_array($category_based_settings)) {
            $category_based_settings = [];
        }

        $manual_only_settings = Arr::get($settings, 'manual_only', []);

        if (! is_array($manual_only_settings)) {
            $manual_only_settings = [];
        }

        $category_ids        = $this->normalizeIds(Arr::get($category_based_settings, 'category_ids', []));
        $active_category_ids = Category::query()
            ->where('is_active', true)
            ->whereIn('id', $category_ids)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $use_selected_products_only = (bool) Arr::get($category_based_settings, 'use_selected_products_only', false);

        $category_based_selected_product_ids = $this->products_carousel_product_search_service->filterActiveProductIdsByCategories(
            Arr::get($category_based_settings, 'selected_product_ids', []),
            $active_category_ids,
        );

        $manual_only_selected_product_ids = $this->products_carousel_product_search_service->filterActiveProductIds(
            Arr::get($manual_only_settings, 'selected_product_ids', []),
        );

        $normalized_shared_settings = $this->normalizeSharedSettings(
            $shared_settings,
            $allowed_page_types,
            $allowed_sort_modes,
            $allowed_sort_options,
            $active_languages,
        );

        Log::channel('daily')->info('ProductsCarousel settings normalized.', [
            'source_mode'                       => $source_mode,
            'category_ids_count'                => count($active_category_ids),
            'category_mode_product_ids_count'   => count($category_based_selected_product_ids),
            'manual_mode_product_ids_count'     => count($manual_only_selected_product_ids),
            'shared_page_types_count'           => count($normalized_shared_settings['page_types']),
            'use_selected_products_only'        => $use_selected_products_only,
            'min_quantity'                      => $normalized_shared_settings['min_quantity'],
            'products_limit'                    => $normalized_shared_settings['products_limit'],
            'product_image_width'               => $normalized_shared_settings['product_image_width'],
            'product_image_height'              => $normalized_shared_settings['product_image_height'],
            'sort_mode'                         => $normalized_shared_settings['sort_mode'],
            'custom_sort_options_count'         => count($normalized_shared_settings['custom_sort_options']),
            'custom_sort'                       => $normalized_shared_settings['custom_sort'],
            'shared_translations_locales_count' => count($normalized_shared_settings['translations']),
            'filled_module_titles_count'        => collect($normalized_shared_settings['translations'])
                ->filter(fn (array $translation): bool => filled($translation['module_name_for_user']))
                ->count(),
            'filled_module_descriptions_count' => collect($normalized_shared_settings['translations'])
                ->filter(fn (array $translation): bool => filled($translation['short_description_for_user']))
                ->count(),
        ]);

        return [
            'shared'         => $normalized_shared_settings,
            'source_mode'    => $source_mode,
            'category_based' => [
                'category_ids'               => $active_category_ids,
                'use_selected_products_only' => $use_selected_products_only,
                'selected_product_ids'       => $category_based_selected_product_ids,
            ],
            'manual_only' => [
                'selected_product_ids' => $manual_only_selected_product_ids,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|mixed  $shared_settings
     * @param  array<int, string>  $allowed_page_types
     * @param  array<int, string>  $allowed_sort_modes
     * @param  array<int, string>  $allowed_sort_options
     * @param  Collection<int, Language>  $active_languages
     * @return array{
     *      translations: array<string, array{
     *          module_name_for_user: string,
     *          short_description_for_user: string
     *      }>,
     *      page_types: array<int, string>,
     *      min_quantity: int,
     *      products_limit: int,
     *      product_image_width: int,
     *      product_image_height: int,
     *      sort_mode: string,
     *      custom_sort: array{price: string, name: string, date_added: string, quantity: string},
     *      custom_sort_options: array<int, string>
     *  }
     */
    private function normalizeSharedSettings(
        mixed $shared_settings,
        array $allowed_page_types,
        array $allowed_sort_modes,
        array $allowed_sort_options,
        Collection $active_languages,
    ): array {
        $shared_settings = is_array($shared_settings) ? $shared_settings : [];
        $sort_mode       = $this->normalizeSortMode(
            Arr::get($shared_settings, 'sort_mode', $this->products_carousel_config->get('settings.default_sort_mode', 'custom')),
            $allowed_sort_modes,
        );
        $translations = $this->normalizeSharedTranslations($shared_settings, $active_languages);

        $custom_sort = $this->normalizeCustomSortMap(
            Arr::get($shared_settings, 'custom_sort', []),
        );

        $custom_sort_options = $this->normalizeCustomSortOptions(
            Arr::get($shared_settings, 'custom_sort_options', []),
            $allowed_sort_options,
            $sort_mode,
            $custom_sort,
        );

        $custom_sort = $this->synchronizeCustomSortMapWithOptions(
            $custom_sort,
            $custom_sort_options,
        );

        return [
            'translations' => $translations,
            'page_types'   => $this->normalizePageTypes(
                Arr::get($shared_settings, 'page_types', $this->products_carousel_config->get('settings.default_page_types', [])),
                $allowed_page_types,
            ),
            'min_quantity' => $this->normalizePositiveInteger(
                Arr::get($shared_settings, 'min_quantity', $this->products_carousel_config->get('settings.default_min_quantity', 1)),
                'settings.shared.min_quantity',
            ),
            'products_limit' => $this->normalizePositiveInteger(
                Arr::get($shared_settings, 'products_limit', $this->products_carousel_config->get('settings.default_products_limit', 15)),
                'settings.shared.products_limit',
            ),
            'product_image_width' => $this->normalizePositiveInteger(
                Arr::get($shared_settings, 'product_image_width', $this->products_carousel_config->get('settings.default_image_width', 420)),
                'settings.shared.product_image_width',
            ),
            'product_image_height' => $this->normalizePositiveInteger(
                Arr::get($shared_settings, 'product_image_height', $this->products_carousel_config->get('settings.default_image_height', 420)),
                'settings.shared.product_image_height',
            ),
            'sort_mode'           => $sort_mode,
            'custom_sort'         => $custom_sort,
            'custom_sort_options' => $custom_sort_options,
        ];
    }

    /**
     * @param  array<string, mixed>  $shared_settings
     * @param  Collection<int, Language>  $active_languages
     * @return array<string, array{module_name_for_user: string, short_description_for_user: string}>
     */
    private function normalizeSharedTranslations(array $shared_settings, Collection $active_languages): array
    {
        $shared_translations = Arr::get($shared_settings, 'translations', []);

        if (! is_array($shared_translations)) {
            Log::channel('stack')->warning('ProductsCarousel shared translations payload has invalid shape. Using legacy fallback.', [
                'received_type' => gettype($shared_translations),
            ]);

            $shared_translations = [];
        }

        return $active_languages
            ->mapWithKeys(function (Language $language) use ($shared_translations, $shared_settings): array {
                $language_code        = (string) $language->code;
                $language_translation = Arr::get($shared_translations, $language_code, []);

                if (! is_array($language_translation)) {
                    $language_translation = [];
                }

                $module_name_for_user       = Str::squish((string) Arr::get($language_translation, 'module_name_for_user'));
                $short_description_for_user = Str::squish((string) Arr::get($language_translation, 'short_description_for_user'));

                if (blank($module_name_for_user)) {
                    $module_name_for_user = $this->resolveLegacySharedValue($shared_settings, 'module_name_for_user');
                }

                if (blank($short_description_for_user)) {
                    $short_description_for_user = $this->resolveLegacySharedValue($shared_settings, 'short_description_for_user');
                }

                return [
                    $language_code => [
                        'module_name_for_user'       => $module_name_for_user,
                        'short_description_for_user' => $short_description_for_user,
                    ],
                ];
            })
            ->all();
    }

    private function resolveLegacySharedValue(array $shared_settings, string $field): string
    {
        $legacy_scalar = Str::squish((string) Arr::get($shared_settings, $field));

        if (filled($legacy_scalar)) {
            return $legacy_scalar;
        }

        $translations_payload = Arr::get($shared_settings, 'translations', []);

        if (! is_array($translations_payload)) {
            return '';
        }

        return collect($translations_payload)
            ->filter(fn (mixed $translation): bool => is_array($translation))
            ->map(fn (array $translation): string => Str::squish((string) Arr::get($translation, $field)))
            ->first(fn (string $value): bool => filled($value), '');
    }

    /**
     * @param  array<int, string>  $allowed_sort_modes
     */
    private function normalizeSortMode(mixed $sort_mode, array $allowed_sort_modes): string
    {
        $sort_mode = Str::trim((string) $sort_mode);

        $sort_mode_validator = Validator::make(
            ['sort_mode' => $sort_mode],
            ['sort_mode' => ['required', 'string', 'in:' . implode(',', $allowed_sort_modes)]],
        );

        if ($sort_mode_validator->fails()) {
            throw ValidationException::withMessages($sort_mode_validator->errors()->messages());
        }

        return $sort_mode;
    }

    /**
     * @param  array<int, string>  $allowed_sort_options
     * @param  array{price: string, name: string, date_added: string, quantity: string}  $custom_sort
     * @return array<int, string>
     */
    private function normalizeCustomSortOptions(
        mixed $custom_sort_options,
        array $allowed_sort_options,
        string $sort_mode,
        array $custom_sort,
    ): array {
        if ($sort_mode !== 'custom') {
            return [];
        }

        $map_based_sort_options = $this->buildSortOptionsFromMap($custom_sort);

        if ($map_based_sort_options !== []) {
            return $map_based_sort_options;
        }

        $custom_sort_options = collect(is_array($custom_sort_options) ? $custom_sort_options : [$custom_sort_options])
            ->filter(fn (mixed $option): bool => is_string($option) && filled($option))
            ->map(fn (string $option): string => Str::trim($option))
            ->unique()
            ->values();

        $invalid_sort_options = $custom_sort_options
            ->diff($allowed_sort_options)
            ->values();

        if ($invalid_sort_options->isNotEmpty()) {
            Log::channel('stack')->warning('ProductsCarousel received invalid custom sort options payload.', [
                'sort_mode'            => $sort_mode,
                'invalid_sort_options' => $invalid_sort_options->all(),
            ]);

            throw ValidationException::withMessages([
                'settings.shared.custom_sort_options' => 'The selected sort options are invalid.',
            ]);
        }

        return $this->removeConflictingSortOptions(
            collect($allowed_sort_options)
                ->intersect($custom_sort_options)
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>|mixed  $custom_sort
     * @return array{price: string, name: string, date_added: string, quantity: string}
     */
    private function normalizeCustomSortMap(mixed $custom_sort): array
    {
        $custom_sort = is_array($custom_sort) ? $custom_sort : [];

        return [
            'price'      => $this->normalizeSortDirection(Arr::get($custom_sort, 'price', 'none')),
            'name'       => $this->normalizeSortDirection(Arr::get($custom_sort, 'name', 'none')),
            'date_added' => $this->normalizeSortDirection(Arr::get($custom_sort, 'date_added', 'none')),
            'quantity'   => $this->normalizeSortDirection(Arr::get($custom_sort, 'quantity', 'none')),
        ];
    }

    private function normalizeSortDirection(mixed $direction): string
    {
        $direction = Str::lower(Str::trim((string) $direction));

        if (in_array($direction, ['asc', 'desc'], true)) {
            return $direction;
        }

        return 'none';
    }

    /**
     * @param  array{price: string, name: string, date_added: string, quantity: string}  $custom_sort
     * @return array<int, string>
     */
    private function buildSortOptionsFromMap(array $custom_sort): array
    {
        return collect(['price', 'name', 'date_added', 'quantity'])
            ->map(function (string $field) use ($custom_sort): ?string {
                $direction = $custom_sort[$field] ?? 'none';

                if (! in_array($direction, ['asc', 'desc'], true)) {
                    return null;
                }

                return $field . '_' . $direction;
            })
            ->filter(fn (mixed $sort_option): bool => is_string($sort_option))
            ->values()
            ->all();
    }

    /**
     * @param  array{price: string, name: string, date_added: string, quantity: string}  $custom_sort
     * @param  array<int, string>  $custom_sort_options
     * @return array{price: string, name: string, date_added: string, quantity: string}
     */
    private function synchronizeCustomSortMapWithOptions(array $custom_sort, array $custom_sort_options): array
    {
        $map_has_values = collect($custom_sort)
            ->contains(fn (string $direction): bool => in_array($direction, ['asc', 'desc'], true));

        if ($map_has_values) {
            return $custom_sort;
        }

        $derived_map = [
            'price'      => 'none',
            'name'       => 'none',
            'date_added' => 'none',
            'quantity'   => 'none',
        ];

        foreach ($custom_sort_options as $sort_option) {
            $field     = Str::beforeLast($sort_option, '_');
            $direction = Str::afterLast($sort_option, '_');

            if (! array_key_exists($field, $derived_map)) {
                continue;
            }

            if (! in_array($direction, ['asc', 'desc'], true)) {
                continue;
            }

            $derived_map[$field] = $direction;
        }

        return $derived_map;
    }

    /**
     * @param  array<int, string>  $sort_options
     * @return array<int, string>
     */
    private function removeConflictingSortOptions(array $sort_options): array
    {
        return collect($sort_options)
            ->unique(function (string $sort_option): string {
                return Str::beforeLast($sort_option, '_');
            })
            ->values()
            ->all();
    }

    private function normalizePositiveInteger(mixed $value, string $field): int
    {
        $normalized_value = (int) $value;

        $validator = Validator::make(
            ['value' => $normalized_value],
            ['value' => ['required', 'integer', 'min:1']],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                $field => $validator->errors()->first('value'),
            ]);
        }

        return $normalized_value;
    }

    /**
     * @param  array<int, string>  $allowed_page_types
     * @return array<int, string>
     */
    private function normalizePageTypes(mixed $page_types, array $allowed_page_types): array
    {
        $page_types = collect(is_array($page_types) ? $page_types : [$page_types])
            ->filter(fn (mixed $page_type): bool => is_string($page_type) && filled($page_type))
            ->map(fn (string $page_type): string => Str::trim($page_type))
            ->unique()
            ->values();

        if ($page_types->isEmpty()) {
            throw ValidationException::withMessages([
                'settings.shared.page_types' => 'Select at least one page type.',
            ]);
        }

        $invalid_page_types = $page_types->diff($allowed_page_types)->values();

        if ($invalid_page_types->isNotEmpty()) {
            throw ValidationException::withMessages([
                'settings.shared.page_types' => 'The selected page types are invalid.',
            ]);
        }

        return $page_types->all();
    }

    /**
     * @param  array<int|string, mixed>|mixed  $ids
     * @return array<int>
     */
    private function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            $ids = [$ids];
        }

        return collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
