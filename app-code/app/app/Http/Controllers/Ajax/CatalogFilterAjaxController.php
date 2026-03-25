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
     * @throws Throwable
     */
    public function index(
        CatalogFilterAjaxIndexRequest $request,
        FilterProductsAction          $filter_products_action,
        string                        $slug,
    ): JsonResponse {
        $response_data = $filter_products_action->handle(
            validated_data: $request->validated(),
            category_slug : $slug,
            locale        : app()->getLocale(),
        );

        /** @var LengthAwarePaginator|null $paginator */
        $paginator      = Arr::get($response_data, 'paginator');
        $total_products = (int)$paginator?->total();

        return response()->json([
            'success'        => true,
            'total_products' => $total_products,
            ...$response_data,
        ]);
    }
}
