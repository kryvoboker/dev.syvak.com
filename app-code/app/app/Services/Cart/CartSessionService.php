<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Enums\Cart\CartModeEnum;
use App\Models\Carts\Cart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartSessionService
{
    /**
     * @return array<int, array{cart_id: int, product_variant_id: int, quantity: int, chosen_attributes: array<int|string, mixed>, added_at: string}>
     */
    public function getItems(string $mode = CartModeEnum::Regular->value): array
    {
        $owner_context = $this->resolveOwnerContext();

        if ($owner_context === null) {
            return [];
        }

        $this->migrateGuestItemsToUserScope($owner_context, $mode);

        $cart_rows = $this->resolveOwnerQuery($owner_context)
            ->where('cart_mode', $mode)
            ->select([
                'id',
                'product_variant_id',
                'quantity',
                'chosen_attributes',
                'created_at',
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->all();

        $resolved_items = [];

        foreach ($cart_rows as $cart_row) {
            if (!$cart_row instanceof Cart) {
                continue;
            }

            $resolved_items[] = [
                'cart_id' => (int)$cart_row->id,
                'product_variant_id' => (int)$cart_row->product_variant_id,
                'quantity' => max(1, (int)$cart_row->quantity),
                'chosen_attributes' => is_array($cart_row->chosen_attributes) ? $cart_row->chosen_attributes : [],
                'added_at' => $cart_row->created_at?->toDateTimeString() ?? get_now_date()->toDateTimeString(),
            ];
        }

        return $resolved_items;
    }

    /**
     * @param array<int|string, mixed> $chosen_attributes
     */
    public function addItem(
        int $product_variant_id,
        int $quantity = 1,
        string $mode = CartModeEnum::Regular->value,
        array $chosen_attributes = [],
    ): void {
        $owner_context = $this->resolveOwnerContext();

        if ($owner_context === null) {
            return;
        }

        $this->migrateGuestItemsToUserScope($owner_context, $mode);

        $normalized_quantity = max(1, $quantity);
        $normalized_attributes = $this->normalizeAttributes($chosen_attributes);
        $attributes_signature = $this->buildAttributesSignature($normalized_attributes);

        $matching_rows = $this->resolveOwnerQuery($owner_context)
            ->where('cart_mode', $mode)
            ->where('product_variant_id', $product_variant_id)
            ->get()
            ->all();

        $existing_item = null;

        foreach ($matching_rows as $matching_row) {
            if (!$matching_row instanceof Cart) {
                continue;
            }

            $item_attributes = is_array($matching_row->chosen_attributes) ? $matching_row->chosen_attributes : [];

            if ($this->buildAttributesSignature($item_attributes) === $attributes_signature) {
                $existing_item = $matching_row;

                break;
            }
        }

        if ($existing_item instanceof Cart) {
            $existing_item->quantity = max(1, (int)$existing_item->quantity + $normalized_quantity);
            $existing_item->save();

            return;
        }

        Cart::query()->create([
            'session_id' => $owner_context['session_id'],
            'user_id' => $owner_context['user_id'],
            'cart_mode' => $mode,
            'product_variant_id' => $product_variant_id,
            'quantity' => $normalized_quantity,
            'chosen_attributes' => $normalized_attributes,
        ]);
    }

    public function updateItem(int $cart_id, int $quantity, string $mode = CartModeEnum::Regular->value): bool
    {
        $owner_context = $this->resolveOwnerContext();

        if ($owner_context === null) {
            return false;
        }

        $this->migrateGuestItemsToUserScope($owner_context, $mode);

        $cart_item = $this->resolveOwnerQuery($owner_context)
            ->where('cart_mode', $mode)
            ->whereKey($cart_id)
            ->first();

        if (!$cart_item instanceof Cart) {
            Log::channel('stack')->warning('Cart item update rejected due to missing row in owner scope.', [
                'cart_id' => $cart_id,
                'mode' => $mode,
                'user_id' => $owner_context['user_id'],
            ]);

            return false;
        }

        $cart_item->quantity = max(1, $quantity);
        $cart_item->save();

        return true;
    }

    public function removeItem(int $cart_id, string $mode = CartModeEnum::Regular->value): bool
    {
        $owner_context = $this->resolveOwnerContext();

        if ($owner_context === null) {
            return false;
        }

        $this->migrateGuestItemsToUserScope($owner_context, $mode);

        $deleted_rows = $this->resolveOwnerQuery($owner_context)
            ->where('cart_mode', $mode)
            ->whereKey($cart_id)
            ->delete();

        if ($deleted_rows <= 0) {
            Log::channel('stack')->warning('Cart item delete rejected due to missing row in owner scope.', [
                'cart_id' => $cart_id,
                'mode' => $mode,
                'user_id' => $owner_context['user_id'],
            ]);

            return false;
        }

        return true;
    }

    public function clearCart(?string $mode = null): void
    {
        $owner_context = $this->resolveOwnerContext();

        if ($owner_context === null) {
            return;
        }

        $query = $this->resolveOwnerQuery($owner_context);

        if (is_string($mode) && $mode !== '') {
            $query->where('cart_mode', $mode);
        }

        $query->delete();
    }

    /**
     * @return int
     */
    public function getTotalProducts(?string $mode = null): int
    {
        $owner_context = $this->resolveOwnerContext();

        if ($owner_context === null) {
            return 0;
        }

        $query = $this->resolveOwnerQuery($owner_context);

        if (is_string($mode) && $mode !== '') {
            $query->where('cart_mode', $mode);
        }

        return (int)$query->sum('quantity');
    }

    /**
     * @return array{session_id: ?string, user_id: ?int}|null
     */
    private function resolveOwnerContext(): ?array
    {
        $session_id = session()->getId();

        if (!is_string($session_id) || $session_id === '') {
            session()->start();
            $session_id = session()->getId();
        }

        $user_id = Auth::check() ? (int)Auth::id() : null;

        if (($user_id === null || $user_id <= 0) && (!is_string($session_id) || $session_id === '')) {
            Log::channel('stack')->warning('Cart owner context could not be resolved.');

            return null;
        }

        return [
            'session_id' => is_string($session_id) && $session_id !== '' ? $session_id : null,
            'user_id' => $user_id !== null && $user_id > 0 ? $user_id : null,
        ];
    }

    /**
     * @param array{session_id: ?string, user_id: ?int} $owner_context
     */
    private function resolveOwnerQuery(array $owner_context): Builder
    {
        $query = Cart::query();

        if (($owner_context['user_id'] ?? null) !== null) {
            return $query->where('user_id', (int)$owner_context['user_id']);
        }

        return $query
            ->whereNull('user_id')
            ->where('session_id', (string)$owner_context['session_id']);
    }

    /**
     * @param array{session_id: ?string, user_id: ?int} $owner_context
     */
    private function migrateGuestItemsToUserScope(array $owner_context, string $mode): void
    {
        $user_id = (int)($owner_context['user_id'] ?? 0);
        $session_id = (string)($owner_context['session_id'] ?? '');

        if ($user_id <= 0 || $session_id === '') {
            return;
        }

        $guest_rows = Cart::query()
            ->whereNull('user_id')
            ->where('session_id', $session_id)
            ->where('cart_mode', $mode)
            ->select([
                'id',
                'session_id',
                'user_id',
                'cart_mode',
                'product_variant_id',
                'quantity',
                'chosen_attributes',
            ])
            ->get();

        if ($guest_rows->isEmpty()) {
            return;
        }

        foreach ($guest_rows as $guest_row) {
            $item_attributes = is_array($guest_row->chosen_attributes) ? $guest_row->chosen_attributes : [];
            $signature = $this->buildAttributesSignature($item_attributes);

            $user_rows = Cart::query()
                ->where('user_id', $user_id)
                ->where('cart_mode', $mode)
                ->where('product_variant_id', (int)$guest_row->product_variant_id)
                ->select([
                    'id',
                    'product_variant_id',
                    'quantity',
                    'chosen_attributes',
                ])
                ->get()
                ->all();

            $existing_user_row = null;

            foreach ($user_rows as $user_row) {
                if (!$user_row instanceof Cart) {
                    continue;
                }

                $user_attributes = is_array($user_row->chosen_attributes) ? $user_row->chosen_attributes : [];

                if ($this->buildAttributesSignature($user_attributes) === $signature) {
                    $existing_user_row = $user_row;

                    break;
                }
            }

            if ($existing_user_row instanceof Cart) {
                $existing_user_row->quantity = max(1, (int)$existing_user_row->quantity + (int)$guest_row->quantity);
                $existing_user_row->save();
                $guest_row->delete();

                continue;
            }

            $guest_row->user_id = $user_id;
            $guest_row->save();
        }
    }

    /**
     * @param array<int|string, mixed> $attributes
     *
     * @return array<int|string, mixed>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $normalized_attributes = [];

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $normalized_attributes[$key] = $this->normalizeAttributes($value);

                continue;
            }

            $normalized_attributes[$key] = $value;
        }

        ksort($normalized_attributes);

        return $normalized_attributes;
    }

    /**
     * @param array<int|string, mixed> $attributes
     */
    private function buildAttributesSignature(array $attributes): string
    {
        $normalized_attributes = $this->normalizeAttributes($attributes);

        return (string)json_encode($normalized_attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
