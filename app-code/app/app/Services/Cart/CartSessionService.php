<?php

declare(strict_types=1);

namespace App\Services\Cart;

use Illuminate\Support\Arr;

class CartSessionService
{
    private const SESSION_KEY = 'catalog.cart.items';

    /**
     * @return array<int, array{product_variant_id: int, quantity: int, added_at: string}>
     */
    public function getItems(): array
    {
        $stored_items = session(self::SESSION_KEY, []);

        if (! is_array($stored_items)) {
            return [];
        }

        return collect($stored_items)
            ->map(function (mixed $item_data, int|string $variant_id): ?array {
                if (! is_array($item_data)) {
                    return null;
                }

                $resolved_variant_id = is_int($variant_id) || ctype_digit((string) $variant_id)
                    ? (int) $variant_id
                    : (int) Arr::get($item_data, 'product_variant_id', 0);

                if ($resolved_variant_id <= 0) {
                    return null;
                }

                $quantity = max(1, (int) Arr::get($item_data, 'quantity', 1));
                $added_at = (string) Arr::get($item_data, 'added_at', now(config('app.timezone'))->toDateTimeString());

                return [
                    'product_variant_id' => $resolved_variant_id,
                    'quantity'           => $quantity,
                    'added_at'           => $added_at,
                ];
            })
            ->filter(fn (?array $item_data): bool => is_array($item_data))
            ->values()
            ->all();
    }

    public function addItem(int $product_variant_id, int $quantity = 1): void
    {
        $items_map = $this->getItemsMap();

        $existing_quantity = (int) Arr::get($items_map, $product_variant_id . '.quantity', 0);
        $added_at          = (string) Arr::get(
            $items_map,
            $product_variant_id . '.added_at',
            now(config('app.timezone'))->toDateTimeString(),
        );

        $items_map[(string) $product_variant_id] = [
            'product_variant_id' => $product_variant_id,
            'quantity'           => max(1, $existing_quantity + max(1, $quantity)),
            'added_at'           => $added_at,
        ];

        session([self::SESSION_KEY => $items_map]);
    }

    public function updateItem(int $product_variant_id, int $quantity): void
    {
        $items_map = $this->getItemsMap();

        if (! isset($items_map[(string) $product_variant_id])) {
            return;
        }

        $items_map[(string) $product_variant_id]['quantity'] = max(1, $quantity);

        session([self::SESSION_KEY => $items_map]);
    }

    public function removeItem(int $product_variant_id): void
    {
        $items_map = $this->getItemsMap();

        unset($items_map[(string) $product_variant_id]);

        session([self::SESSION_KEY => $items_map]);
    }

    public function clearCart(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @return array<string, array{product_variant_id: int, quantity: int, added_at: string}>
     */
    private function getItemsMap(): array
    {
        return collect($this->getItems())
            ->mapWithKeys(fn (array $item_data): array => [
                (string) $item_data['product_variant_id'] => $item_data,
            ])
            ->all();
    }
}
