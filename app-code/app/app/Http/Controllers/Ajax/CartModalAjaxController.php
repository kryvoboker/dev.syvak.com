<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Services\Trait\CartTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CartModalAjaxController extends Controller
{
    use CartTrait;

    /**
     * @param Request     $request
     * @param string|null $locale
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function index(Request $request, ?string $locale): JsonResponse
    {
        $locale = normalize_locale($locale);

        $data = [];

        return response()->json([
            'html' => view('catalog.partials.cart.modal-items', $data)->render(),
        ]);
    }
}
