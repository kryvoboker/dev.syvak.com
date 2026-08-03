import { setCartMode } from '@ts-features/cart/cartModeStorage.ts';
import { Cart } from '@ts-features/cart/constants.ts';
import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';
import { findElem } from '@ts-shared/lib/helpers.ts';

import $REGULAR = Cart.$REGULAR;

export const initCartPageAccordion = (cartPageRoot: HTMLElement): void => {
    const accordionElement = <HTMLElement | null>findElem('[data-cart-extra-items-accordion]', cartPageRoot);

    if (accordionElement) {
        initAccordion(accordionElement);
    }
};

export const handleCartPage = (): void => {
    const cartPageRoot: HTMLElement | null = findElem('#cart-page-root');

    if (!cartPageRoot) {
        return;
    }

    setCartMode($REGULAR);
    initCartPageAccordion(cartPageRoot);
};
