import type { CartMode, CartMutationResponse } from '@ts-features/cart/cartTypes.ts';
import { Cart } from '@ts-features/cart/constants.ts';
import { $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import { findElem } from '@ts-shared/lib/helpers.ts';

import $REGULAR = Cart.$REGULAR;

const getCartBadge = (): HTMLElement | null => findElem('[data-cart-badge]');

export const updateCartBadge = (count: number): void => {
    const badge = getCartBadge();

    if (!badge) {
        return;
    }

    const normalizedCount = Math.max(0, Math.floor(count));

    badge.textContent = `${normalizedCount}`;
    badge.classList.toggle($HIDDEN_CLASS_NAME, normalizedCount === 0);
};

export const updateCartBadgeFromResponse = (response: CartMutationResponse, mode: CartMode): void => {
    if (mode !== $REGULAR || typeof response.cart?.total_quantity !== 'number') {
        return;
    }

    updateCartBadge(response.cart.total_quantity);
};
