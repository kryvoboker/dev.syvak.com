<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Actions\SearchProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchProductIndexRequest;
use Illuminate\Http\JsonResponse;

class SearchProductController extends Controller
{
    /**
     * @param SearchProductIndexRequest $request
     *
     * @return JsonResponse
     */
    public function index(SearchProductIndexRequest $request): JsonResponse
    {
        $search_products = app(SearchProductAction::class)(
            $request->input('keyword'),
            (int)config('app.products.search_products_per_page')
        );

        return response()->json($search_products->toArray($request));
    }
}
