<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Actions\FilterProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\CatalogFilterAjaxIndexRequest;
use App\Models\PageSettings\PageSetting;
use App\Models\PageSettings\PageSettingItem;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Supports\Services\Products\ProductsLimitService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
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
        string $slug,
    ): View|Factory {
        $header_data           = app(HeaderService::class)();
        $page_type             = try_detect_page_type($request);
        $page_setting          = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();
        $page_setting_settings = get_page_settings($page_setting);

        $category_page_settings = [
            'products_per_page_limit'          => ProductsLimitService::getProductsCategoryLimit($page_setting_settings),
            'is_ajax_products_loading_enabled' => (bool) Arr::get(
                $page_setting_settings,
                'pagination.ajax_products_loading_enabled',
                (bool) config('app.page_settings.category.ajax_products_loading_enabled', true),
            ),
            'sort_options' => $this->buildSortOptions($page_setting),
        ];

        $response_data = $filter_products_action->handle(
            validated_data: $request->validated(),
            category_slug: $slug,
            locale: app()->getLocale(),
        );

        $data = [
            'header_data' => $header_data,
            'footer_data' => app(FooterService::class)([
                // Footer uses category links too; pass already loaded categories from header.
                'categories' => $header_data['categories'],
            ]),
            'page_type'              => $page_type,
            'category_page_settings' => $category_page_settings,
            'category_slug'          => $slug,
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
                    'code'  => (string) $sorting_item->code,
                    'value' => (string) Arr::get($item_get, 'value', (string) $sorting_item->code),
                    'label' => $this->resolveSortLabel((string) $sorting_item->code),
                ];
            })
            ->filter(fn (array $option): bool => filled($option['value']) && filled($option['label']))
            ->values()
            ->all();
    }

    private function resolveSortLabel(string $sort_code): string
    {
        $labels = [
            'uk' => [
                'default'     => 'За замовчуванням',
                'newest'      => 'Спочатку нові',
                'bestsellers' => 'Бестселери',
                'price_asc'   => 'Спочатку дешеві',
                'price_desc'  => 'Спочатку дорогі',
            ],
            'en' => [
                'default'     => 'Default',
                'newest'      => 'Newest first',
                'bestsellers' => 'Bestsellers',
                'price_asc'   => 'Lowest price first',
                'price_desc'  => 'Highest price first',
            ],
        ];

        $locale        = app()->getLocale();
        $locale_labels = Arr::get($labels, $locale, Arr::get($labels, 'en', []));

        return (string) Arr::get($locale_labels, $sort_code, $sort_code);
    }
}
