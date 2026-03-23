<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Actions\SearchProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\LiveSearchProductsAjaxIndexRequest;
use App\Http\Requests\Search\SearchProductsShowRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class LiveSearchProductsAjaxController extends Controller
{
    /**
     * @throws Throwable
     */
    public function index(LiveSearchProductsAjaxIndexRequest $request, SearchProductsAction $search_product_action): ?JsonResponse
    {
        $search_products                   = $search_product_action->handle(
            $request->query('keyword'),
            (int)($request->query('per_page') ?: config('app.products.search_products_per_page')),
        );
        $not_found_img_sizes               = get_app_settings()->image_sizes->firstWhere('name', 'search_not_found') ?? [];
        $search_not_found_img_data['urls'] = multiple_convert_img_and_get_url(
            config('app.images.default_image_search_not_found'),
            (int)$not_found_img_sizes['width'],
            is_square: false,
            bg_color : 'transparent',
        );

        $search_not_found_img_data['width']  = (int)$not_found_img_sizes['width'];
        $search_not_found_img_data['height'] = (int)($not_found_img_sizes['height'] ?: $not_found_img_sizes['width']);

        if ($request->ajax()) {
            $rendered_html = view('catalog::components.common.search-result', [
                'products_data'             => $search_products->toArray($request),
                'search_not_found_img_data' => $search_not_found_img_data,
            ])->render();

            return response()->json([
                'success'        => true,
                'html'           => $rendered_html,
                'total_products' => $search_products->count(),
            ]);
        }

        return null;
    }

    public function show(SearchProductsShowRequest $request) {}
}
