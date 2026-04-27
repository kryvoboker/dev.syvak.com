<?php

declare(strict_types=1);

namespace App\Services\Trait;

use App\Http\Requests\Pages\CartStoreRequest;
use App\Http\Requests\Pages\CartDeleteRequest;
use App\Http\Requests\Pages\CartUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

trait CartTrait
{
    /**
     * @param CartStoreRequest $request
     * @param string|null      $locale
     *
     * @return JsonResponse|View
     * @throws Throwable
     */
    public function store(CartStoreRequest $request, ?string $locale): JsonResponse|View
    {
        $locale = normalize_locale($locale);
        $data   = [];

        if ((bool)$request->query('is_call_from_modal', false) === true) {
            return view('catalog.pages.cart', $data);
        }

        return response()->json([
            'html' => view('catalog.partials.cart.modal-items', $data)->render(),
        ]);
    }

    /**
     * @param CartUpdateRequest $request
     * @param string|null       $locale
     *
     * @return JsonResponse|View
     * @throws Throwable
     */
    public function update(CartUpdateRequest $request, ?string $locale): JsonResponse|View
    {
        $locale = normalize_locale($locale);
        $data   = [];

        if ((bool)$request->query('is_call_from_modal', false) === true) {
            return view('catalog.pages.cart', $data);
        }

        return response()->json([
            'html' => view('catalog.partials.cart.modal-items', $data)->render(),
        ]);
    }

    /**
     * @param CartDeleteRequest $request
     * @param string|null       $locale
     *
     * @return JsonResponse|View
     * @throws Throwable
     */
    public function delete(CartDeleteRequest $request, ?string $locale): JsonResponse|View
    {
        $locale = normalize_locale($locale);
        $data   = [];

        if ((bool)$request->query('is_call_from_modal', false) === true) {
            return view('catalog.pages.cart', $data);
        }

        return response()->json([
            'html' => view('catalog.partials.cart.modal-items', $data)->render(),
        ]);
    }
}
