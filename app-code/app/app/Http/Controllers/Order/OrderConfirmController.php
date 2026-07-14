<?php

declare(strict_types=1);

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\FastOrderStoreRequest;
use App\Http\Requests\Order\FastOrderValidateRequest;
use App\Http\Requests\Order\SimpleOrderStoreRequest;
use App\Http\Requests\Order\SimpleOrderValidateRequest;
use App\Services\Order\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class OrderConfirmController extends Controller
{
    public function storeSimpleOrder(
        SimpleOrderStoreRequest $request,
        OrderCreationService $order_creation_service,
        ?string $locale,
    ): RedirectResponse|JsonResponse {
        $locale = normalize_locale($locale);
        $result_data = $order_creation_service->createSimpleOrder($request->validated(), $locale);

        if ($request->expectsJson()) {
            return response()->json($result_data);
        }

        return redirect((string) Arr::get(
            $result_data,
            'redirect_url',
            localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
        ));
    }

    public function storeFastOrder(
        FastOrderStoreRequest $request,
        OrderCreationService $fast_order_creation_service,
        ?string $locale,
    ): RedirectResponse|JsonResponse {
        $locale = normalize_locale($locale);
        $result_data = $fast_order_creation_service->createFastOrder($request->validated(), $locale);

        if ($request->expectsJson()) {
            return response()->json($result_data);
        }

        $redirect_url = (string) Arr::get(
            $result_data,
            'redirect_url',
            localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
        );

        return redirect($redirect_url);
    }

    public function validateSimpleOrder(
        SimpleOrderValidateRequest $request,
        OrderCreationService $order_creation_service,
        ?string $locale,
    ): JsonResponse {
        $locale = normalize_locale($locale);
        $result_data = $order_creation_service->validateSimpleOrderData($request->validated(), $locale);

        return response()->json($result_data);
    }

    public function validateFastOrder(
        FastOrderValidateRequest $request,
        OrderCreationService $fast_order_creation_service,
        ?string $locale,
    ): JsonResponse {
        $locale = normalize_locale($locale);
        $result_data = $fast_order_creation_service->validateFastOrderData($request->validated(), $locale);

        return response()->json($result_data);
    }
}
