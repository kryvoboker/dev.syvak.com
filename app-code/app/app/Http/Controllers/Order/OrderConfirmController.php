<?php

declare(strict_types=1);

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderConfirmStoreRequest;
use App\Http\Requests\Order\OrderConfirmValidateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class OrderConfirmController extends Controller
{
    /**
     * @param OrderConfirmStoreRequest $request
     * @param string|null              $locale
     *
     * @return RedirectResponse
     */
	public function store(OrderConfirmStoreRequest $request, ?string $locale): RedirectResponse
	{
        $locale = normalize_locale($locale);

        $is_success_create_order = true; // TODO: need replace it by real code

        if ($is_success_create_order === true) {
            redirect(localized_route('localized.catalog.thank-you.index'), [
                'locale' => $locale,
            ]);
        }

        return  redirect(localized_route('localized.catalog.failure-order.index'), [
            'locale' => $locale,
        ]);
	}

    /**
     * @param OrderConfirmValidateRequest $request
     * @param string|null                 $locale
     *
     * @return JsonResponse
     */
    public function validate(OrderConfirmValidateRequest $request, ?string $locale): JsonResponse
    {
        $locale = normalize_locale($locale);

        $data = [];

        return response()->json($data);
    }
}
