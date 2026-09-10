<?php

declare(strict_types=1);

namespace Modules\UkrPoshta\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UkrPoshta\Services\Storefront\UkrPoshtaCheckoutDataService;
use Modules\UkrPoshta\Support\UkrPoshtaCheckoutStateService;

class UkrPoshtaController extends Controller
{
    public function __construct(
        private readonly UkrPoshtaCheckoutDataService $checkout_data_service,
        private readonly UkrPoshtaCheckoutStateService $checkout_state_service,
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

    public function districts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region_id' => ['required', 'integer', 'min:1'],
        ]);
        /** @var array<string, mixed> $validated */

        $districts = $this->checkout_data_service->getDistrictRows(is_numeric($validated['region_id']) ? (int) $validated['region_id'] : 0);

        return response()->json([
            'items' => $districts->toArray(),
            'html' => $this->checkout_data_service->renderDistrictsHtml($districts),
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => ['required', 'integer', 'min:1'],
        ]);
        /** @var array<string, mixed> $validated */

        $cities = $this->checkout_data_service->getCityRows(is_numeric($validated['district_id']) ? (int) $validated['district_id'] : 0);

        return response()->json([
            'items' => $cities->toArray(),
            'html' => $this->checkout_data_service->renderCitiesHtml($cities),
        ]);
    }

    public function postOffices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => ['nullable', 'integer', 'min:1'],
            'city_id' => ['nullable', 'integer', 'min:1'],
        ]);
        /** @var array<string, mixed> $validated */

        $post_offices = $this->checkout_data_service->getPostOfficeRows(
            isset($validated['district_id']) && is_numeric($validated['district_id']) ? (int) $validated['district_id'] : null,
            isset($validated['city_id']) && is_numeric($validated['city_id']) ? (int) $validated['city_id'] : null,
        );

        return response()->json([
            'items' => $post_offices->toArray(),
            'html' => $this->checkout_data_service->renderPostOfficesHtml($post_offices),
        ]);
    }

    public function saveSelection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_method' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'array'],
            'district' => ['nullable', 'array'],
            'city' => ['nullable', 'array'],
            'delivery_point' => ['nullable', 'array'],
        ]);
        /** @var array<string, mixed> $validated */

        $state = $this->checkout_state_service->replaceState($validated);

        return response()->json([
            'state' => $state,
        ]);
    }
}
