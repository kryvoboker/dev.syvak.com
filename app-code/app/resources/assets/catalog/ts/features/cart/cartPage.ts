import { setCartMode } from "@ts-features/cart/cartModeStorage.ts";
import { findElem }    from "@ts-shared/lib/helpers.ts";
import { Cart }        from "@ts-features/cart/constants.ts";
import $REGULAR = Cart.$REGULAR;

export const handleCartPage = (): void => {
    const cartPageRoot: HTMLElement | null = findElem('#cart-page-root');

    if (!cartPageRoot) {
        return;
    }

    setCartMode($REGULAR);
};
