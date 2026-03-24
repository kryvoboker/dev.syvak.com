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
use App\Models\PageSettings\PageSettingItem;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Supports\Services\Products\ProductsLimitService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
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
        FilterProductsAction          $filter_products_action,
        string                        $locale,
        string                        $slug,
    ): View|Factory {
        $locale                  = normalize_locale($locale);
        $header_data             = app(HeaderService::class)();
        $page_type               = try_detect_page_type($request);
        $page_setting            = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();
        $page_setting_settings   = get_page_settings($page_setting);
        $category_context        = $this->resolveCategoryContext($slug, $locale);
        $products_per_page_limit = ProductsLimitService::getProductsCategoryLimit($page_setting_settings);

        $response_data = $filter_products_action->handle(
            validated_data: $request->validated(),
            category_slug : $slug,
            locale        : $locale,
        );

        $is_ajax_products_loading_enabled = Arr::get(
                $page_setting_settings,
                'pagination.ajax_products_loading_enabled',
                (bool)config('app.page_settings.category.ajax_products_loading_enabled', true),
            ) === true
            && $products_per_page_limit < (int)Arr::get($response_data, 'pagination.total', 0);

        $category_page_settings = [
            'sort_options'                     => $this->buildSortOptions($page_setting),
            'is_ajax_products_loading_enabled' => $is_ajax_products_loading_enabled,
            'products_per_page_limit'          => $products_per_page_limit,
        ];

        \Illuminate\Support\Facades\View::share([
            'sluggable_type' => Category::class,
            'slug'           => $slug,
        ]);

        $data = [
            'header_data'            => $header_data,
            'footer_data'            => app(FooterService::class)([
                // Footer uses category links too; pass already loaded categories from header.
                'categories' => $header_data['categories'],
            ]),
            'page_type'              => $page_type,
            'category_page_settings' => $category_page_settings,
            'category_slug'          => $slug,
            'category_title'         => $category_context['title'],
            'breadcrumbs'            => $category_context['breadcrumbs'],
            ...$response_data,
        ];

        return view('catalog.pages.category', $data);
    }

    /**
     * We pass sort options in a stable DTO-like shape for Blade and JS.
     * `value` is query-string value, `code` is internal code for active-state checks.
     *
     * @return array<int, array{code: string, value: string, label: string}>
     */
    private function buildSortOptions(PageSetting $page_setting): array
    {
        /** @var EloquentCollection<int, PageSettingItem> $sorting_items */
        $sorting_items = get_sorting_items($page_setting);

        return $sorting_items
            ->map(function ($sorting_item): array {
                $item_get = is_array($sorting_item->get) ? $sorting_item->get : [];

                return [
                    'code'  => (string)$sorting_item->code,
                    'value' => (string)Arr::get($item_get, 'value', (string)$sorting_item->code),
                    'label' => $this->resolveSortLabel((string)$sorting_item->code),
                ];
            })
            ->filter(fn(array $option): bool => filled($option['value']) && filled($option['label']))
            ->values()
            ->all();
    }

    private function resolveSortLabel(string $sort_code): string
    {
        $translation_key = 'catalog/default.sort.' . $sort_code;
        $label           = __($translation_key);

        if ($label === $translation_key) {
            return Str::headline((string)Str::replace('_', ' ', $sort_code));
        }

        return (string)$label;
    }

    /**
     * @return array{title: string, breadcrumbs: array<int, array{title: string, url: ?string}>}
     */
    private function resolveCategoryContext(string $slug, string $locale): array
    {
        $fallback_title = (string)__('catalog/default.texts.category_title_fallback');

        $breadcrumbs = [
            breadcrumb(
                title: (string)__('catalog/default.links.home'),
                url  : localizedRoute('localized.catalog.home'),
            ),
        ];

        $language = resolve_language_by_locale($locale);

        if (!$language instanceof Language) {
            return [
                'title'       => $fallback_title,
                'breadcrumbs' => $breadcrumbs,
            ];
        }

        $category = Category::findBySlug($slug, (int)$language->id);

        if (!$category instanceof Category) {
            return [
                'title'       => $fallback_title,
                'breadcrumbs' => $breadcrumbs,
            ];
        }

        $path_ids = new CategoryPath()
            ->getPathIdsByCategoryId((int)$category->id)
            ->pluck('path_id')
            ->map(fn(mixed $path_id): int => (int)$path_id)
            ->values()
            ->all();

        if ($path_ids === []) {
            $path_ids = [(int)$category->id];
        }

        $path_categories = Category::query()
            ->with([
                'categoryDescription' => function ($query) use ($language): void {
                    $query->where('language_id', (int)$language->id);
                },
                'slugs'               => function ($query) use ($language): void {
                    $query->where('language_id', (int)$language->id);
                },
            ])
            ->whereIn('id', $path_ids)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $resolved_breadcrumbs = $breadcrumbs;
        $resolved_title       = $fallback_title;

        $last_path_index = array_key_last($path_ids);

        foreach ($path_ids as $index => $path_id) {
            /** @var Category|null $path_category */
            $path_category = $path_categories->get($path_id);

            if (!$path_category instanceof Category) {
                continue;
            }

            $path_title     = $this->resolveCategoryTitle($path_category, (int)$language->id, $fallback_title);
            $resolved_title = $path_title;

            $is_last = $last_path_index === $index;

            $slug = (string)optional($path_category->slugs->first())->slug;
            $url  = null;

            if (!$is_last && filled($slug)) {
                $url = localizedRoute('localized.catalog.category.show', ['slug' => $slug]);
            }

            $resolved_breadcrumbs[] = breadcrumb(
                title: $path_title,
                url  : $url,
            );
        }

        return [
            'title'       => $resolved_title,
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

        $title = (string)($description->name ?? '');

        if (blank($title)) {
            return $fallback_title;
        }

        return $title;
    }
}
