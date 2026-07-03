import type { CartMode } from '@ts-features/cart/cartTypes.ts';

export namespace Cart {
    export const $FAST_ORDER: CartMode = 'fast_order';
    export const $REGULAR: CartMode = 'regular';
}
