<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Actions\LoadMoreProductsByAjaxAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\LoadMoreProductsByAjaxIndexRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Throwable;

class LoadMoreProductsByAjaxController extends Controller
{
    /**
     * @param LoadMoreProductsByAjaxIndexRequest $request
     * @param LoadMoreProductsByAjaxAction       $load_more_products_by_ajax_action
     * @param string                             $locale
     * @param string|null                        $slug
     *
     * @return JsonResponse
     */
    public function index(
        LoadMoreProductsByAjaxIndexRequest $request,
        LoadMoreProductsByAjaxAction       $load_more_products_by_ajax_action,
        string                             $locale,
        ?string                            $slug,
    ): JsonResponse {
        $locale    = normalize_locale($locale);
        $page_type = $request->query('page_type');

        try {
            $response_data = $load_more_products_by_ajax_action->handle(
                request      : $request,
                locale       : $locale,
                category_slug: (string)$slug,
                params       : compact('page_type')
            );

            $html = view('catalog.pages.partials.category.category-content-container', $response_data)->render();

            return response()->json([
                'html'              => $html,
                'success'           => Arr::get($response_data, 'success', false),
                'is_has_more_pages' => Arr::get($response_data, 'is_has_more_pages', false),
                'next_page'         => Arr::get($response_data, 'next_page'),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success'           => false,
                'message'           => __('catalog/default.errors.filtering_products'),
                'paginator'         => null,
                'next_page'         => null,
                'is_has_more_pages' => false,
            ]);
        }
    }
}
