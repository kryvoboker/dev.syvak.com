import type { CartMode, CartMutationResponse } from "@ts-features/cart/cartTypes.ts";
import { findElem }                            from "@ts-shared/lib/helpers.ts";

const cartFormFieldNames: string[] = ['first_name', 'last_name', 'phone'];

const getCartModalGeneralErrorElement = (mode: CartMode): HTMLElement | null => {
    return <HTMLElement | null>findElem(`[data-cart-modal-content="${mode}"] [data-cart-general-error]`);
};

export const extractCartGeneralErrorMessage = (response: CartMutationResponse): string => {
    const errorEntries = Object.entries(response.errors ?? {});

    for (const [fieldName, messages] of errorEntries) {
        if (cartFormFieldNames.includes(fieldName)) {
            continue;
        }

        if (Array.isArray(messages) && messages.length > 0) {
            const message = String(messages[0] ?? '').trim();

            if (message !== '') {
                return message;
            }
        }
    }

    return typeof response.message === 'string' ? response.message.trim() : '';
};

export const setCartModalGeneralError = (mode: CartMode, message: string): void => {
    const errorElement = getCartModalGeneralErrorElement(mode);

    if (!errorElement) {
        return;
    }

    errorElement.textContent = message;
    errorElement.classList.toggle('hidden', message === '');
};

export const clearCartModalGeneralError = (mode: CartMode): void => {
    setCartModalGeneralError(mode, '');
};
