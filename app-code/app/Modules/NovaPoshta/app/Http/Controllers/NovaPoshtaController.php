<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\NovaPoshta\Services\Storefront\NovaPoshtaCheckoutDataService;
use Modules\NovaPoshta\Support\NovaPoshtaCheckoutStateService;

class NovaPoshtaController extends Controller
{
    public function __construct(
        private readonly NovaPoshtaCheckoutDataService $checkout_data_service,
        private readonly NovaPoshtaCheckoutStateService $checkout_state_service,
    ) {
    }

    public function state(): JsonResponse
    {
        return response()->json([
            'state' => $this->checkout_state_service->getState(),
        ]);
    }

    public function regions(): JsonResponse
    {
        $regions = $this->checkout_data_service->getRegionRows();

        return response()->json([
            'items' => $regions->toArray(),
            'html' => $this->checkout_data_service->renderRegionsHtml($regions),
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region_ref' => ['required', 'string', 'max:255'],
        ]);

        $cities = $this->checkout_data_service->getCityRows((string) $validated['region_ref']);

        return response()->json([
            'items' => $cities->toArray(),
            'html' => $this->checkout_data_service->renderCitiesHtml($cities),
        ]);
    }

    public function postOffices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_ref' => ['required', 'string', 'max:255'],
        ]);

        $post_offices = $this->checkout_data_service->getPostOfficeRows((string) $validated['city_ref']);

        return response()->json([
            'items' => $post_offices->toArray(),
            'html' => $this->checkout_data_service->renderPostOfficesHtml($post_offices),
        ]);
    }

    public function poshtomats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_ref' => ['required', 'string', 'max:255'],
        ]);

        $poshtomats = $this->checkout_data_service->getPoshtomatRows((string) $validated['city_ref']);

        return response()->json([
            'items' => $poshtomats->toArray(),
            'html' => $this->checkout_data_service->renderPoshtomatsHtml($poshtomats),
        ]);
    }

    public function saveSelection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_method' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'array'],
            'city' => ['nullable', 'array'],
            'delivery_point' => ['nullable', 'array'],
        ]);

        $state = $this->checkout_state_service->replaceState($validated);

        return response()->json([
            'state' => $state,
        ]);
    }
}
