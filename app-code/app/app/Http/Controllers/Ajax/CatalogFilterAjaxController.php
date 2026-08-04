<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Actions\FilterProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\CatalogFilterAjaxIndexRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class CatalogFilterAjaxController extends Controller
{
    /**
     * @throws Throwable
     */
    public function index(
        CatalogFilterAjaxIndexRequest $request,
        FilterProductsAction $filter_products_action,
        string $locale,
        ?string $slug,
    ): JsonResponse {
        $locale = normalize_locale($locale);

        try {
            $total_products = $filter_products_action->count(
                validated_data: $request->validated(),
                category_slug : (string) $slug,
                locale        : $locale,
            );

            return response()->json([
                'success' => true,
                'total_products' => $total_products,
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
