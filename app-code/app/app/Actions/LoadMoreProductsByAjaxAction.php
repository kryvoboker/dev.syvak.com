<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Requests\Ajax\LoadMoreProductsByAjaxIndexRequest;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Supports\Services\Products\ProductsLimitService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Throwable;

readonly class LoadMoreProductsByAjaxAction
{
    public function __construct(
        private FilterProductsAction $filter_products_action,
    ) {}

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function handle(
        LoadMoreProductsByAjaxIndexRequest $request,
        ?string                            $locale,
        string                             $category_slug,
        array                              $params
    ): array {
        $page_type                       = Arr::get($params, 'page_type');
        $page_path                       = $this->resolvePagePath($page_type, $category_slug);
        $page_settings_bootstrap_service = app(PageSettingsBootstrapService::class);

        if ($page_type === config('page-settings.page_type.category')) {
            $page_setting = $page_settings_bootstrap_service->bootstrapCategoryPageSetting();
        } else {
            $page_setting = $page_settings_bootstrap_service->bootstrapSearchPageSetting();
        }

        $page_settings_arr       = get_page_settings($page_setting);
        $products_per_page_limit = ProductsLimitService::getProductsCategoryLimit($page_settings_arr);
        $response_data           = $this->filter_products_action->handle([
            'validated_data'      => $request->validated(),
            'category_slug'       => $category_slug,
            'is_get_filters_data' => false,
            'page_path'           => $page_path,
        ], locale: $locale);

        /** @var LengthAwarePaginator|null $paginator */
        $paginator                        = Arr::get($response_data, 'paginator');
        $current_page                     = $paginator instanceof LengthAwarePaginator ? (int)$paginator->currentPage() : null;
        $is_has_more_pages                = $paginator instanceof LengthAwarePaginator ? $paginator->hasMorePages() : false;
        $is_ajax_products_loading_enabled = (bool)Arr::get($page_settings_arr, 'pagination.ajax_products_loading_enabled') === true
            && $products_per_page_limit < (int)($paginator instanceof LengthAwarePaginator ? $paginator->total() : 0);

        return [
            'success'                          => true,
            'products'                         => (array)Arr::get($response_data, 'products', []),
            'is_has_more_pages'                => $is_has_more_pages,
            'next_page'                        => $current_page !== null ? ($current_page + 1) : null,
            'is_ajax_products_loading_enabled' => $is_ajax_products_loading_enabled,
            'paginator'                        => $paginator,
            'applied_filters'                  => (array)Arr::get($response_data, 'applied_filters', []),
            'active_sort_code'                 => (string)Arr::get($response_data, 'active_sort_code', 'default'),
            'selected_sort_value'              => (string)Arr::get($response_data, 'selected_sort_value', ''),
            'is_filter_enabled'                => (bool)Arr::get($response_data, 'is_filter_enabled', false),
        ];
    }

    private function resolvePagePath(mixed $page_type, string $category_slug): string
    {
        if ($page_type === config('page-settings.page_type.search')) {
            return localizedRoute('localized.catalog.search-products.index', absolute: false);
        }

        return localizedRoute('localized.catalog.category.show', ['slug' => $category_slug], absolute: false);
    }
}
