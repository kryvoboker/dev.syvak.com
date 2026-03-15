<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services;

use App\Models\Catalogs\Categories\Category;
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
        private ProductsCarouselConfig               $products_carousel_config,
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
        $allowed_page_types = collect(config('page-type', []))->values()->all();
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
        $normalized_shared_settings = $this->normalizeSharedSettings($shared_settings, $allowed_page_types);

        Log::channel('daily')->info('ProductsCarousel settings normalized.', [
            'source_mode'                     => $source_mode,
            'category_ids_count'              => count($active_category_ids),
            'category_mode_product_ids_count' => count($category_based_selected_product_ids),
            'manual_mode_product_ids_count'   => count($manual_only_selected_product_ids),
            'shared_page_types_count'         => count($normalized_shared_settings['page_types']),
            'use_selected_products_only'      => $use_selected_products_only,
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
     * @return array{module_name_for_user: string, short_description_for_user: string, page_types: array<int, string>}
     */
    private function normalizeSharedSettings(mixed $shared_settings, array $allowed_page_types): array
    {
        $shared_settings = is_array($shared_settings) ? $shared_settings : [];

        return [
            'module_name_for_user'       => Str::squish((string) Arr::get($shared_settings, 'module_name_for_user')),
            'short_description_for_user' => Str::squish((string) Arr::get($shared_settings, 'short_description_for_user')),
            'page_types'                 => $this->normalizePageTypes(
                Arr::get($shared_settings, 'page_types', $this->products_carousel_config->get('settings.default_page_types', [])),
                $allowed_page_types,
            ),
        ];
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
