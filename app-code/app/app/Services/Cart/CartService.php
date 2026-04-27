<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\Catalogs\Products\ProductVariant;

readonly class CartService
{
    public function __construct(
        private CartSessionService $cart_session_service,
        private CartViewDataBuilderService $cart_view_data_builder_service,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getSnapshot(string $locale, string $mode = 'regular'): array
    {
        $session_items = $this->cart_session_service->getItems();

        return $this->cart_view_data_builder_service->build($session_items, $locale, $mode);
    }

    /**
     * @return array<string, mixed>
     */
    public function addItem(int $product_variant_id, int $quantity, string $locale, string $mode = 'regular'): array
    {
        if (! $this->isVariantAvailableForCart($product_variant_id)) {
            return [
                'success' => false,
                'message' => __('catalog/default.cart.messages.variant_not_found'),
                'cart'    => $this->getSnapshot($locale, $mode),
            ];
        }

        $this->cart_session_service->addItem($product_variant_id, $quantity);

        return [
            'success' => true,
            'message' => __('catalog/default.cart.messages.item_added'),
            'cart'    => $this->getSnapshot($locale, $mode),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateItem(int $product_variant_id, int $quantity, string $locale, string $mode = 'regular'): array
    {
        if (! $this->isVariantAvailableForCart($product_variant_id)) {
            return [
                'success' => false,
                'message' => __('catalog/default.cart.messages.variant_not_found'),
                'cart'    => $this->getSnapshot($locale, $mode),
            ];
        }

        $this->cart_session_service->updateItem($product_variant_id, $quantity);

        return [
            'success' => true,
            'message' => __('catalog/default.cart.messages.item_updated'),
            'cart'    => $this->getSnapshot($locale, $mode),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function removeItem(int $product_variant_id, string $locale, string $mode = 'regular'): array
    {
        $this->cart_session_service->removeItem($product_variant_id);

        return [
            'success' => true,
            'message' => __('catalog/default.cart.messages.item_removed'),
            'cart'    => $this->getSnapshot($locale, $mode),
        ];
    }

    public function clearCart(): void
    {
        $this->cart_session_service->clearCart();
    }

    private function isVariantAvailableForCart(int $product_variant_id): bool
    {
        return ProductVariant::query()
            ->whereKey($product_variant_id)
            ->where('is_active', true)
            ->exists();
    }
}
