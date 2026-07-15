import type { CartMode } from '@ts-features/cart/cartTypes.ts';
import { Cart } from '@ts-features/cart/constants.ts';
import { getLocalStorage, setLocalStorage, toStringValue } from '@ts-shared/lib/helpers.ts';

import $FAST_ORDER = Cart.$FAST_ORDER;
import $REGULAR = Cart.$REGULAR;

const CART_MODE_STORAGE_KEY: string = 'cart_modal_mode';

export const setCartMode = (mode: CartMode): void => {
    setLocalStorage(CART_MODE_STORAGE_KEY, mode);
};

export const getCartMode = (): CartMode => {
    const mode: string = toStringValue(getLocalStorage(CART_MODE_STORAGE_KEY, $REGULAR), $REGULAR);

    return mode === $FAST_ORDER ? $FAST_ORDER : $REGULAR;
};
