import type { CartMode, CartMutationResponse } from "@ts-features/cart/cartTypes.ts";
import { findElem }                            from "@ts-shared/lib/helpers.ts";

const cartFormFieldNames: string[] = ['first_name', 'last_name', 'phone'];
const errorClasses: string[] = ['border-light-red/40', 'bg-light-red/10', 'text-light-red'];
const successClasses: string[] = ['border-emerald-400/40', 'bg-emerald-400/10', 'text-emerald-300'];

const getCartModalGeneralErrorElement = (mode: CartMode): HTMLElement | null => {
    const selectors: string[] = [
        `[data-cart-modal-content="${mode}"] [data-cart-general-error]`,
        `[data-cart-page-content] [data-cart-root][data-cart-mode="${mode}"] [data-cart-general-error]`,
        `[data-cart-root][data-cart-mode="${mode}"] [data-cart-general-error]`,
    ];

    for (const selector of selectors) {
        const errorElement = <HTMLElement | null>findElem(selector);

        if (errorElement) {
            return errorElement;
        }
    }

    return null;
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

export const setCartModalGeneralError = (mode: CartMode, message: string, isSuccess: boolean = false): void => {
    const errorElement = getCartModalGeneralErrorElement(mode);

    if (!errorElement) {
        return;
    }

    errorElement.textContent = message;
    errorElement.classList.toggle('hidden', message === '');
    errorElement.classList.remove(...errorClasses, ...successClasses);
    errorElement.classList.add(...(isSuccess ? successClasses : errorClasses));
};

export const clearCartModalGeneralError = (mode: CartMode): void => {
    setCartModalGeneralError(mode, '');
};
