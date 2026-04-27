import type { CartMutationResponse }                       from "@ts-features/cart/cartTypes.ts";
import { storeOrder, toggleCartLoader, validateOrder }     from "@ts-features/cart/cartCrud.ts";
import { setCartMode }                                     from "@ts-features/cart/cartModeStorage.ts";
import { handleParsePhone }                                from "@ts-shared/lib/parsePhone.ts";
import { findElem, getClosestParentEl, isEmpty, redirect } from "@ts-shared/lib/helpers.ts";
import { Cart }                                            from "@ts-features/cart/constants.ts";
import $FAST_ORDER = Cart.$FAST_ORDER;
import $REGULAR = Cart.$REGULAR;

const markFieldAsError = (field: HTMLInputElement, isError: boolean): void => {
    const errorClassName = '_error';

    if (isError) {
        field.classList.add(errorClassName);

        return;
    }

    field.classList.remove(errorClassName);
};

const hasValidationError = (response: CartMutationResponse, fieldName: string): boolean => {
    return Boolean(response.errors?.[fieldName] && !isEmpty(response.errors[fieldName]));
};

const buildOrderPayload = (form: HTMLFormElement): FormData => {
    const payload = new FormData(form);

    payload.set('cart_mode', $FAST_ORDER);
    payload.set('payment_method', 'cash_on_delivery');

    return payload;
};

const bindFastOrderSubmit = (): void => {
    if (document.body.dataset.fastOrderBound === '1') {
        return;
    }

    document.body.dataset.fastOrderBound = '1';

    document.addEventListener('submit', async (event: Event): Promise<void> => {
        const target = event.target as HTMLElement;
        const formEl = <HTMLFormElement | null>getClosestParentEl('[data-fast-order-form]', target);

        if (formEl === null) {
            return;
        }

        event.preventDefault();

        const firstNameInput = <HTMLInputElement | null>findElem('[data-order-first-name]', formEl);
        const lastNameInput  = <HTMLInputElement | null>findElem('[data-order-last-name]', formEl);
        const phoneInput     = <HTMLInputElement | null>findElem('[data-order-phone]', formEl);

        if (firstNameInput === null || lastNameInput === null || phoneInput === null) {
            return;
        }

        const payload = buildOrderPayload(formEl);

        toggleCartLoader(true);

        const validateResponse = await validateOrder(payload)
            .finally((): void => toggleCartLoader(false));

        markFieldAsError(firstNameInput, hasValidationError(validateResponse, 'first_name'));
        markFieldAsError(lastNameInput, hasValidationError(validateResponse, 'last_name'));
        markFieldAsError(phoneInput, hasValidationError(validateResponse, 'phone'));

        if (!validateResponse.success) {
            return;
        }

        toggleCartLoader(true);

        const storeResponse = await storeOrder(payload)
            .finally((): void => toggleCartLoader(false));

        if (storeResponse.redirect_url) {
            redirect(storeResponse.redirect_url);
        }
    });
};

export const handleFastOrderModal = (): void => {
    setCartMode($REGULAR);
    handleParsePhone();
    bindFastOrderSubmit();
};
