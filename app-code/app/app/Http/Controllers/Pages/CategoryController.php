<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Actions\FilterProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\CatalogFilterAjaxIndexRequest;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Categories\CategoryPath;
use App\Models\PageSettings\PageSetting;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Supports\Services\Products\ProductsLimitService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CategoryController extends Controller
{
    /**
     * @throws Throwable
     */
    public function show(
        CatalogFilterAjaxIndexRequest $request,
        FilterProductsAction $filter_products_action,
        string $locale,
        ?string $slug,
    ): View|Factory {
        $locale = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);
        $language_id = $language instanceof Language ? (int) $language->id : null;
        $header_data = app(HeaderService::class)([
            'sluggable_type' => Category::class,
            'slug' => $slug,
        ]);
        $page_type = try_detect_page_type($request);
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();
        $page_settings_arr = get_page_settings($page_setting);
        $category_context = $this->resolveCategoryContext($slug, $locale);
        $products_per_page_limit = ProductsLimitService::getProductsCategoryLimit($page_settings_arr);
        $requested_sort_value = $this->normalizeSortValue((string) Arr::get($request->validated(), 'sort', ''));
        $fallback_active_sort = resolve_sort_code($page_setting, $requested_sort_value);

        try {
            $response_data = $filter_products_action->handle(
                [
                'validated_data' => $request->validated(),
                'category_slug' => $slug,
                'is_get_filters_data' => true,
                'page_path' => localized_route('localized.catalog.category.show', ['slug' => $slug], absolute: false),
                ],
                locale: $locale,
            );
        } catch (Throwable $e) {
            report($e);

            $response_data = [];
        }

        $response_data = $this->normalizeCategoryActionResponseData(
            response_data       : $response_data,
            requested_sort_value: $requested_sort_value,
            fallback_sort_code  : $fallback_active_sort,
        );

        /** @var LengthAwarePaginator|null $paginator */
        $paginator = Arr::get($response_data, 'paginator');
        $current_page = $paginator?->currentPage();
        $is_has_more_pages = (bool) $paginator?->hasMorePages();
        $is_ajax_products_loading_enabled = (bool) Arr::get($page_settings_arr, 'pagination.ajax_products_loading_enabled') === true
            && $products_per_page_limit < (int) $paginator?->total();

        $data = [
            'header_data' => $header_data,
            'footer_data' => app(FooterService::class)([
                // Footer uses category links too; pass already loaded categories from header.
                'categories' => $header_data['categories'],
            ]),
            'page_type' => $page_type,
            'sort_options' => $this->buildSortOptions($page_setting, $language_id),
            'active_sort_code' => (string) Arr::get($response_data, 'active_sort_code', $fallback_active_sort),
            'selected_sort_value' => (string) Arr::get($response_data, 'selected_sort_value', $requested_sort_value),
            'is_has_more_pages' => $is_has_more_pages,
            'is_ajax_products_loading_enabled' => $is_ajax_products_loading_enabled,
            'next_page' => $current_page !== null ? ($current_page + 1) : null,
            'products_per_page_limit' => $products_per_page_limit,
            'clear_filters_url' => localized_route('localized.catalog.category.show', ['slug' => $slug]),
            'catalog_filter_ajax_url' => localized_route('localized.catalog.catalog-filter-ajax.index', ['slug' => $slug]),
            'load_more_products_ajax_url' => localized_route('localized.catalog.load-more-products-ajax.index', ['slug' => $slug]),
            'category_slug' => $slug,
            'category_title' => $category_context['title'],
            'breadcrumbs' => $category_context['breadcrumbs'],
            ...$response_data,
        ];

        return view('catalog.pages.category', $data);
    }

    /**
     * We pass sort options in a stable DTO-like shape for Blade and JS.
     * `value` is query-string value, `code` is internal code for active-state checks.
     *
     * @return array<int, array{code: string, value: string, label: string, url: string}>
     */
    private function buildSortOptions(PageSetting $page_setting, ?int $language_id = null): array
    {
        $sorting_items = get_sorting_items($page_setting);
        $request_url = app()->bound('request') ? request()->url() : '';
        $request_query = app()->bound('request') ? (array) request()->query() : [];
        $sorting_keys = $this->resolveSortingGetKeys($sorting_items);

        return $sorting_items
            ->map(function (array $sorting_item) use ($request_url, $request_query, $sorting_keys, $language_id): array {
                $item_get = is_array(Arr::get($sorting_item, 'get')) ? Arr::get($sorting_item, 'get') : [];
                $sort_key = trim((string) Arr::get($item_get, 'key', config('page-settings.sort_get_keys.sort', 'sort')));
                $sort_key = $sort_key !== '' ? $sort_key : (string) config('page-settings.sort_get_keys.sort', 'sort');
                $sort_value = (string) Arr::get($item_get, 'value', (string) Arr::get($sorting_item, 'code', ''));

                return [
                    'code' => (string) Arr::get($sorting_item, 'code', ''),
                    'value' => $sort_value,
                    'label' => $this->resolveSortLabel(
                        sort_code   : (string) Arr::get($sorting_item, 'code', ''),
                        sorting_item: $sorting_item,
                        language_id : $language_id,
                    ),
                    'url' => $this->buildSortOptionUrl(
                        request_url  : $request_url,
                        request_query: $request_query,
                        sort_key     : $sort_key,
                        sort_value   : $sort_value,
                        sorting_keys : $sorting_keys,
                    ),
                ];
            })
            ->filter(fn (array $option): bool => filled($option['value']) && filled($option['label']) && filled($option['url']))
            ->values()
            ->all();
    }

    /**
     * Prefer sorting labels from DB settings and use lang files only as fallback.
     *
     * @param  array<string, mixed>  $sorting_item
     */
    private function resolveSortLabel(string $sort_code, array $sorting_item = [], ?int $language_id = null): string
    {
        $config_payload = is_array(Arr::get($sorting_item, 'config')) ? Arr::get($sorting_item, 'config') : [];
        $labels_map = is_array(Arr::get($config_payload, 'labels')) ? Arr::get($config_payload, 'labels') : [];

        if ($language_id !== null) {
            $label_from_db = trim((string) Arr::get($labels_map, (string) $language_id, ''));

            if (filled($label_from_db)) {
                return $label_from_db;
            }
        }

        $first_available_label = collect($labels_map)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->first(fn (string $label): bool => filled($label));

        if (is_string($first_available_label) && filled($first_available_label)) {
            return $first_available_label;
        }

        $translation_key = 'catalog/default.sort.' . $sort_code;
        $label = __($translation_key);

        if ($label === $translation_key) {
            return Str::headline((string) Str::replace(['_', '-'], ' ', $sort_code));
        }

        return (string) $label;
    }

    /**
     * Keep category page data shape stable even when filtering action fails.
     *
     * @param  array<string, mixed>  $response_data
     * @return array<string, mixed>
     */
    private function normalizeCategoryActionResponseData(
        array $response_data,
        string $requested_sort_value,
        string $fallback_sort_code,
    ): array {
        return array_replace_recursive([
            'products' => [],
            'paginator' => null,
            'applied_filters' => [
                'sort' => $requested_sort_value,
                'price_from' => null,
                'price_to' => null,
                'attributes' => [],
            ],
            'active_sort_code' => $fallback_sort_code,
            'selected_sort_value' => $requested_sort_value,
            'is_filter_enabled' => false,
            'filters_data' => [],
            'is_show_clear_filters_link' => false,
        ], $response_data);
    }

    private function normalizeSortValue(string $sort_value): string
    {
        return Str::lower(trim($sort_value));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $sorting_items
     * @return array<int, string>
     */
    private function resolveSortingGetKeys(Collection $sorting_items): array
    {
        $default_sort_key = (string) config('page-settings.sort_get_keys.sort', 'sort');

        return $sorting_items
            ->map(function (array $sorting_item) use ($default_sort_key): string {
                $item_get = is_array(Arr::get($sorting_item, 'get')) ? Arr::get($sorting_item, 'get') : [];
                $sort_key = trim((string) Arr::get($item_get, 'key', $default_sort_key));

                return $sort_key !== '' ? $sort_key : $default_sort_key;
            })
            ->filter(fn (string $sort_key): bool => filled($sort_key))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $sorting_keys
     */
    private function buildSortOptionUrl(
        string $request_url,
        array $request_query,
        string $sort_key,
        string $sort_value,
        array $sorting_keys,
    ): string {
        if (blank($request_url) || blank($sort_key) || blank($sort_value)) {
            return '';
        }

        $next_query = $request_query;

        foreach ($sorting_keys as $sorting_key) {
            if (filled($sorting_key)) {
                $this->forgetByGetKey($next_query, $sorting_key);
            }
        }

        $this->setByGetKey($next_query, $sort_key, $sort_value);

        $query_string = Arr::query($next_query);

        return filled($query_string) ? $request_url . '?' . $query_string : $request_url;
    }

    private function forgetByGetKey(array &$query_parameters, string $get_key): void
    {
        if (Str::contains($get_key, '[') && Str::endsWith($get_key, ']')) {
            $normalized_key = str_replace(['[', ']'], ['.', ''], $get_key);
            Arr::forget($query_parameters, $normalized_key);

            $root_key = Str::before($normalized_key, '.');

            if (Arr::get($query_parameters, $root_key) === []) {
                Arr::forget($query_parameters, $root_key);
            }

            return;
        }

        Arr::forget($query_parameters, $get_key);
    }

    private function setByGetKey(array &$query_parameters, string $get_key, string $value): void
    {
        if (Str::contains($get_key, '[') && Str::endsWith($get_key, ']')) {
            $normalized_key = str_replace(['[', ']'], ['.', ''], $get_key);
            Arr::set($query_parameters, $normalized_key, $value);

            return;
        }

        Arr::set($query_parameters, $get_key, $value);
    }

    /**
     * @return array{title: string, breadcrumbs: array<int, array{title: string, url: ?string}>}
     */
    private function resolveCategoryContext(string $slug, string $locale): array
    {
        $fallback_title = (string) __('catalog/default.texts.category_title_fallback');

        $breadcrumbs = [
            breadcrumb(
                title: (string) __('catalog/default.links.home'),
                url  : localized_route('localized.catalog.home'),
            ),
        ];

        $language = resolve_language_by_locale($locale);

        if (! $language instanceof Language) {
            return [
                'title' => $fallback_title,
                'breadcrumbs' => $breadcrumbs,
            ];
        }

        $category = Category::findBySlug($slug, (int) $language->id);

        if (! $category instanceof Category) {
            return [
                'title' => $fallback_title,
                'breadcrumbs' => $breadcrumbs,
            ];
        }

        $path_ids = (new CategoryPath())
            ->getPathIdsByCategoryId((int) $category->id)
            ->pluck('path_id')
            ->map(fn (mixed $path_id): int => (int) $path_id)
            ->values()
            ->all();

        if ($path_ids === []) {
            $path_ids = [(int) $category->id];
        }

        $path_categories = Category::query()
            ->with([
                'categoryDescription' => function ($query) use ($language): void {
                    $query->where('language_id', (int) $language->id);
                },
                'slugs' => function ($query) use ($language): void {
                    $query->where('language_id', (int) $language->id);
                },
            ])
            ->whereIn('id', $path_ids)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $resolved_breadcrumbs = $breadcrumbs;
        $resolved_title = $fallback_title;

        $last_path_index = array_key_last($path_ids);

        foreach ($path_ids as $index => $path_id) {
            /** @var Category|null $path_category */
            $path_category = $path_categories->get($path_id);

            if (! $path_category instanceof Category) {
                continue;
            }

            $path_title = $this->resolveCategoryTitle($path_category, (int) $language->id, $fallback_title);
            $resolved_title = $path_title;

            $is_last = $last_path_index === $index;

            $slug = (string) optional($path_category->slugs->first())->slug;
            $url = null;

            if (! $is_last && filled($slug)) {
                $url = localized_route('localized.catalog.category.show', ['slug' => $slug]);
            }

            $resolved_breadcrumbs[] = breadcrumb(
                title: $path_title,
                url  : $url,
            );
        }

        return [
            'title' => $resolved_title,
            'breadcrumbs' => $resolved_breadcrumbs,
        ];
    }

    private function resolveCategoryTitle(Category $category, int $language_id, string $fallback_title): string
    {
        $description = $category->categoryDescription
            ->firstWhere('language_id', $language_id)
            ?? $category->categoryDescription->first();

        if ($description === null) {
            return $fallback_title;
        }

        $title = (string) ($description->name ?? '');

        if (blank($title)) {
            return $fallback_title;
        }

        return $title;
    }
}
