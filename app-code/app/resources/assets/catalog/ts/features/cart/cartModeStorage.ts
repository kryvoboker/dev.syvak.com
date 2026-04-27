import type { CartMode }                    from "@ts-features/cart/cartTypes.ts";
import { getLocalStorage, setLocalStorage } from "@ts-shared/lib/helpers.ts";
import { Cart }                             from "@ts-features/cart/constants.ts";
import $FAST_ORDER = Cart.$FAST_ORDER;
import $REGULAR = Cart.$REGULAR;

const CART_MODE_STORAGE_KEY: string = 'cart_modal_mode';

export const setCartMode = (mode: CartMode): void => {
    setLocalStorage(CART_MODE_STORAGE_KEY, mode);
};

export const getCartMode = (): CartMode => {
    const mode: string = (getLocalStorage(CART_MODE_STORAGE_KEY, $REGULAR) ?? $REGULAR).toString();

    return mode === $FAST_ORDER ? $FAST_ORDER : $REGULAR;
};
