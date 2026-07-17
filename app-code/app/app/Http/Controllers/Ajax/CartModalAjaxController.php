<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use App\Services\Trait\CartTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class CartModalAjaxController extends Controller
{
    use CartTrait;

    /**
     * @throws Throwable
     */
    public function index(Request $request, ?string $locale): JsonResponse
    {
        $locale = normalize_locale($locale);
        $cart_mode = Str::lower((string) $request->query(CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value));
        $cart_mode = in_array($cart_mode, array_column(CartModeEnum::cases(), 'value'), true)
            ? $cart_mode
            : CartModeEnum::Regular->value;
        $cart_data = app(CartService::class)->getSnapshot($locale, $cart_mode);

        return response()->json([
            'success' => true,
            'mode' => $cart_mode,
            'cart' => $cart_data,
            'rendered' => [
                'modal_items_html' => view('catalog.partials.cart.modal-items', [
                    'cart_data' => $cart_data,
                    CartRequestKeyEnum::CartMode->value => $cart_mode,
                ])->render(),
            ],
        ]);
    }
}
