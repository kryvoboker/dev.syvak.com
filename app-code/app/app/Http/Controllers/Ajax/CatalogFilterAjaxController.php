<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Actions\FilterProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\CatalogFilterAjaxIndexRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Throwable;

class CatalogFilterAjaxController extends Controller
{
    /**
     * @param CatalogFilterAjaxIndexRequest $request
     * @param FilterProductsAction          $filter_products_action
     * @param string                        $slug
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function index(
        CatalogFilterAjaxIndexRequest $request,
        FilterProductsAction          $filter_products_action,
        string                        $slug,
    ): JsonResponse {
        $response_data = $filter_products_action->handle([
            'validated_data'      => $request->validated(),
            'category_slug'       => $slug,
            'is_get_filters_data' => false,
        ],
            locale: app()->getLocale(),
        );

        try {
            /** @var LengthAwarePaginator|null $paginator */
            $paginator      = Arr::get($response_data, 'paginator');
            $total_products = (int)$paginator?->total();

            return response()->json([
                'success'        => true,
                'total_products' => $total_products,
                'products'       => Arr::get($response_data, 'products', []),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('catalog/default.errors.filtering_products'),
            ]);
        }
    }
}
