<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Actions\LoadMoreProductsByAjaxAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\LoadMoreProductsByAjaxIndexRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class LoadMoreProductsByAjaxController extends Controller
{
    /**
     * @throws Throwable
     */
    public function index(
        LoadMoreProductsByAjaxIndexRequest $request,
        LoadMoreProductsByAjaxAction $load_more_products_by_ajax_action,
        string $locale,
        ?string $slug,
    ): JsonResponse {
        $locale = normalize_locale($locale);

        try {
            $response_data = $load_more_products_by_ajax_action->handle(
                request: $request,
                category_slug: (string) $slug,
                locale: $locale,
            );

            return response()->json($response_data);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('catalog/default.errors.filtering_products'),
                'products' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 0,
                    'total' => 0,
                    'has_more_pages' => false,
                    'next_page_url' => null,
                    'prev_page_url' => null,
                ],
            ]);
        }
    }
}
