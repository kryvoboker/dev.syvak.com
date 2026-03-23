<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Actions\FilterProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\CatalogFilterAjaxIndexRequest;
use Illuminate\Http\JsonResponse;

class CatalogFilterAjaxController extends Controller
{
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

        return response()->json([
            'success' => true,
            ...$response_data,
        ]);
    }
}
