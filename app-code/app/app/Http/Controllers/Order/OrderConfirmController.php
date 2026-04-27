<?php

declare(strict_types=1);

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderConfirmStoreRequest;
use App\Http\Requests\Order\OrderConfirmValidateRequest;
use App\Services\Order\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class OrderConfirmController extends Controller
{
    public function store(
        OrderConfirmStoreRequest $request,
        OrderCreationService $order_creation_service,
        ?string $locale,
    ): RedirectResponse|JsonResponse {
        $locale      = normalize_locale($locale);
        $result_data = $order_creation_service->createOrder($request->validated(), $locale);

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

    public function validate(
        OrderConfirmValidateRequest $request,
        OrderCreationService $order_creation_service,
        ?string $locale,
    ): JsonResponse {
        $locale      = normalize_locale($locale);
        $result_data = $order_creation_service->validateOrderData($request->validated(), $locale);

        return response()->json($result_data);
    }
}
