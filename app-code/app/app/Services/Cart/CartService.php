<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Enums\CartModeEnum;
use App\Models\Catalogs\Products\ProductVariant;

readonly class CartService
{
    public function __construct(
        private CartSessionService $cart_session_service,
        private CartViewDataBuilderService $cart_view_data_builder_service,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getSnapshot(string $locale, string $mode = CartModeEnum::Regular->value): array
    {
        $cart_items = $this->cart_session_service->getItems($mode);

        return $this->cart_view_data_builder_service->build($cart_items, $locale, $mode);
    }

    /**
     * @param  array<int|string, mixed>  $chosen_attributes
     * @return array<string, mixed>
     */
    public function addItem(
        int $product_variant_id,
        int $quantity,
        string $locale,
        string $mode = CartModeEnum::Regular->value,
        array $chosen_attributes = [],
    ): array {
        if (! $this->isVariantAvailableForCart($product_variant_id)) {
            return [
                'success' => false,
                'message' => __('catalog/default.cart.messages.variant_not_found'),
                'cart' => $this->getSnapshot($locale, $mode),
            ];
        }

        $this->cart_session_service->addItem($product_variant_id, $quantity, $mode, $chosen_attributes);

        return [
            'success' => true,
            'message' => __('catalog/default.cart.messages.item_added'),
            'cart' => $this->getSnapshot($locale, $mode),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateItem(int $cart_id, int $quantity, string $locale, string $mode = CartModeEnum::Regular->value): array
    {
        $is_updated = $this->cart_session_service->updateItem($cart_id, $quantity, $mode);

        return [
            'success' => $is_updated,
            'message' => $is_updated
                ? __('catalog/default.cart.messages.item_updated')
                : __('catalog/default.cart.messages.variant_not_found'),
            'cart' => $this->getSnapshot($locale, $mode),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function removeItem(int $cart_id, string $locale, string $mode = CartModeEnum::Regular->value): array
    {
        $is_removed = $this->cart_session_service->removeItem($cart_id, $mode);

        return [
            'success' => $is_removed,
            'message' => $is_removed
                ? __('catalog/default.cart.messages.item_removed')
                : __('catalog/default.cart.messages.variant_not_found'),
            'cart' => $this->getSnapshot($locale, $mode),
        ];
    }

    public function clearCart(?string $mode = null): void
    {
        $this->cart_session_service->clearCart($mode);
    }

    /**
     * @return int
     */
    public function getTotalProducts(): int
    {
        return $this->cart_session_service->getTotalProducts();
    }

    private function isVariantAvailableForCart(int $product_variant_id): bool
    {
        return ProductVariant::query()
            ->whereKey($product_variant_id)
            ->where('is_active', true)
            ->exists();
    }
}
