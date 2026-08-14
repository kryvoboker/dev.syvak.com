<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Actions\SearchProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\LiveSearchProductsAjaxIndexRequest;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Throwable;

class LiveSearchProductsAjaxController extends Controller
{
    /**
     * @throws Throwable
     */
    public function index(
        LiveSearchProductsAjaxIndexRequest $request,
        SearchProductsAction $search_product_action,
        PageSettingsBootstrapService $page_settings_bootstrap_service,
    ): ?JsonResponse {
        $products_per_page_limit = (int) config('app.page_settings.search.products_per_page_limit', 15);
        $not_found_img_data = [
            'path' => (string) Arr::get(config('app.page_settings.search', []), 'images.search_not_found.path', config('app.images.default_image_search_not_found')),
            'width' => (int) config('app.page_settings.search.images.search_not_found.width', 600),
            'height' => (int) config('app.page_settings.search.images.search_not_found.height', 600),
        ];

        try {
            $products_per_page_limit = $page_settings_bootstrap_service->getSearchProductsPerPageLimit();
            $not_found_img_data = $page_settings_bootstrap_service->getSearchNotFoundImageData();
        } catch (Throwable) {
            // Keep config fallback when page settings are not available.
        }

        $search_products = $search_product_action->handle(
            $request->query('keyword'),
            (int) ($request->query('per_page') ?: $products_per_page_limit),
        );
        $search_not_found_img_data['urls'] = multiple_convert_img_and_get_url(
            $not_found_img_data['path'],
            (int) $not_found_img_data['width'],
            is_square: false,
            bg_color : 'transparent',
        );

        $search_not_found_img_data['width'] = (int) $not_found_img_data['width'];
        $search_not_found_img_data['height'] = (int) ($not_found_img_data['height'] ?: $not_found_img_data['width']);

        if ($request->ajax()) {
            $rendered_html = view('storefront::components.common.search-result', [
                'products_data' => $search_products->toArray($request),
                'search_not_found_img_data' => $search_not_found_img_data,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $rendered_html,
                'total_products' => $search_products->count(),
            ]);
        }

        return null;
    }
}
