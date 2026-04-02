<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\Requests\Ajax\LoadMoreProductsByAjaxIndexRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Throwable;

readonly class LoadMoreProductsByAjaxAction
{
    public function __construct(
        private FilterProductsAction $filter_products_action,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function handle(
        LoadMoreProductsByAjaxIndexRequest $request,
        string                             $category_slug,
        ?string                            $locale = null,
    ): array {
        $response_data = $this->filter_products_action->handle([
            'validated_data'      => $request->validated(),
            'category_slug'       => $category_slug,
            'is_get_filters_data' => false,
        ], locale: $locale);

        /** @var LengthAwarePaginator|null $paginator */
        $paginator = Arr::get($response_data, 'paginator');

        return [
            'success'             => true,
            'products'            => (array)Arr::get($response_data, 'products', []),
            'pagination'          => [
                'current_page'   => ($paginator?->currentPage() ?? 1),
                'last_page'      => ($paginator?->lastPage() ?? 1),
                'per_page'       => ($paginator?->perPage() ?? 0),
                'total'          => ($paginator?->total() ?? 0),
                'has_more_pages' => ($paginator?->hasMorePages() ?? false),
                'next_page_url'  => $paginator?->nextPageUrl(),
                'prev_page_url'  => $paginator?->previousPageUrl(),
            ],
            'applied_filters'     => (array)Arr::get($response_data, 'applied_filters', []),
            'active_sort_code'    => (string)Arr::get($response_data, 'active_sort_code', 'default'),
            'selected_sort_value' => (string)Arr::get($response_data, 'selected_sort_value', ''),
            'is_filter_enabled'   => (bool)Arr::get($response_data, 'is_filter_enabled', false),
        ];
    }
}
