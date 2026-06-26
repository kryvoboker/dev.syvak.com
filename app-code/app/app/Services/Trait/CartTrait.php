<?php

declare(strict_types=1);

namespace App\Services\Trait;

use App\Enums\CartModeEnum;
use App\Enums\CartRequestKeyEnum;
use App\Http\Requests\Pages\CartDeleteRequest;
use App\Http\Requests\Pages\CartStoreRequest;
use App\Http\Requests\Pages\CartUpdateRequest;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Throwable;

trait CartTrait
{
    /**
     * @throws Throwable
     */
    public function store(CartStoreRequest $request, ?string $locale): JsonResponse
    {
        $locale = normalize_locale($locale);
        $mode = $this->resolveCartMode($request->validated());

        $result_data = app(CartService::class)->addItem(
            product_variant_id: (int) Arr::get($request->validated(), 'product_variant_id', 0),
            quantity          : (int) Arr::get($request->validated(), 'quantity', 1),
            locale            : $locale,
            mode              : $mode,
            chosen_attributes : (array) Arr::get($request->validated(), 'chosen_attributes', []),
        );

        return $this->buildMutationResponse($result_data, $mode);
    }

    /**
     * @throws Throwable
     */
    public function update(CartUpdateRequest $request, ?string $locale): JsonResponse
    {
        $locale = normalize_locale($locale);
        $mode = $this->resolveCartMode($request->validated());

        $result_data = app(CartService::class)->updateItem(
            cart_id : (int) Arr::get($request->validated(), 'cart_id', 0),
            quantity: (int) Arr::get($request->validated(), 'quantity', 1),
            locale  : $locale,
            mode    : $mode,
        );

        return $this->buildMutationResponse($result_data, $mode);
    }

    /**
     * @throws Throwable
     */
    public function delete(CartDeleteRequest $request, ?string $locale): JsonResponse
    {
        $locale = normalize_locale($locale);
        $mode = $this->resolveCartMode($request->validated());

        $result_data = app(CartService::class)->removeItem(
            cart_id: (int) Arr::get($request->validated(), 'cart_id', 0),
            locale : $locale,
            mode   : $mode,
        );

        return $this->buildMutationResponse($result_data, $mode);
    }

    /**
     * @param  array<string, mixed>  $result_data
     *
     * @throws Throwable
     */
    private function buildMutationResponse(array $result_data, string $mode): JsonResponse
    {
        $cart_data = Arr::get($result_data, 'cart', []);

        return response()->json([
            'success' => (bool) Arr::get($result_data, 'success', false),
            'message' => (string) Arr::get($result_data, 'message', ''),
            'mode' => $mode,
            'cart' => $cart_data,
            'rendered' => [
                'modal_items_html' => view('catalog.partials.cart.modal-items', [
                    'cart_data' => $cart_data,
                    CartRequestKeyEnum::CartMode->value => $mode,
                ])->render(),
                'cart_page_html' => view('catalog.partials.cart.page-content', [
                    'cart_data' => $cart_data,
                ])->render(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated_data
     */
    private function resolveCartMode(array $validated_data): string
    {
        $mode = (string) Arr::get($validated_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value);

        return in_array($mode, array_column(CartModeEnum::cases(), 'value'), true)
            ? $mode
            : CartModeEnum::Regular->value;
    }
}
