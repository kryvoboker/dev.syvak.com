import { storeOrder, toggleCartLoader, validateOrder } from '@ts-features/cart/cartCrud.ts';
import {
    clearCartModalGeneralError,
    extractCartGeneralErrorMessage,
    setCartModalGeneralError,
} from '@ts-features/cart/cartErrors.ts';
import { setCartMode } from '@ts-features/cart/cartModeStorage.ts';
import type { CartMutationResponse } from '@ts-features/cart/cartTypes.ts';
import { Cart } from '@ts-features/cart/constants.ts';
import { findElem, getClosestParentEl, isEmpty, redirect } from '@ts-shared/lib/helpers.ts';
import { handleParsePhone } from '@ts-shared/lib/parsePhone.ts';

type FastOrderFieldName = 'first_name' | 'last_name' | 'phone';

const $FAST_ORDER = Cart.$FAST_ORDER;
const $REGULAR = Cart.$REGULAR;

const fieldSelectors: Record<FastOrderFieldName, string> = {
    first_name: '[data-order-first-name]',
    last_name: '[data-order-last-name]',
    phone: '[data-order-phone]',
};

const getFastOrderField = (form: HTMLFormElement, fieldName: FastOrderFieldName): HTMLInputElement | null => {
    return <HTMLInputElement | null>findElem(fieldSelectors[fieldName], form);
};

const getFastOrderErrorElement = (form: HTMLFormElement, fieldName: FastOrderFieldName): HTMLElement | null => {
    return <HTMLElement | null>findElem(`[data-order-field-error="${fieldName}"]`, form);
};

const getFieldValue = (field: HTMLInputElement): string => {
    return field.value.trim();
};

const getPhoneDigits = (field: HTMLInputElement): string => {
    return field.value.replace(/\D+/g, '');
};

const setFieldFeedback = (form: HTMLFormElement, field: HTMLInputElement, message: string): void => {
    const errorElement = getFastOrderErrorElement(form, field.name as FastOrderFieldName);

    field.setCustomValidity(message);
    field.toggleAttribute('aria-invalid', message !== '');

    if (errorElement) {
        errorElement.textContent = message;
    }
};

const clearFieldFeedback = (form: HTMLFormElement, field: HTMLInputElement): void => {
    setFieldFeedback(form, field, '');
};

const validateField = (form: HTMLFormElement, field: HTMLInputElement, fieldName: FastOrderFieldName): boolean => {
    const requiredMessage = field.dataset.errorRequired ?? '';
    const minMessage = field.dataset.errorMin ?? '';

    let errorMessage = '';

    if (fieldName === 'phone') {
        const digits = getPhoneDigits(field);

        if (digits === '') {
            errorMessage = requiredMessage;
        } else if (digits.length < 10) {
            errorMessage = minMessage;
        }
    } else {
        const value = getFieldValue(field);

        if (value === '') {
            errorMessage = requiredMessage;
        } else if (value.length < 2) {
            errorMessage = minMessage;
        }
    }

    setFieldFeedback(form, field, errorMessage);

    return errorMessage === '';
};

const validateFastOrderForm = (form: HTMLFormElement): boolean => {
    const firstNameField = getFastOrderField(form, 'first_name');
    const lastNameField = getFastOrderField(form, 'last_name');
    const phoneField = getFastOrderField(form, 'phone');

    if (firstNameField === null || lastNameField === null || phoneField === null) {
        return false;
    }

    clearFieldFeedback(form, firstNameField);
    clearFieldFeedback(form, lastNameField);
    clearFieldFeedback(form, phoneField);

    const isFirstNameValid = validateField(form, firstNameField, 'first_name');
    const isLastNameValid = validateField(form, lastNameField, 'last_name');
    const isPhoneValid = validateField(form, phoneField, 'phone');

    const isFormValid = isFirstNameValid && isLastNameValid && isPhoneValid;

    form.classList.toggle('_was-validated', !isFormValid);

    return isFormValid;
};

const applyServerValidationErrors = (form: HTMLFormElement, response: CartMutationResponse): void => {
    const firstNameField = getFastOrderField(form, 'first_name');
    const lastNameField = getFastOrderField(form, 'last_name');
    const phoneField = getFastOrderField(form, 'phone');

    if (firstNameField && !isEmpty(response.errors?.first_name)) {
        setFieldFeedback(form, firstNameField, response.errors?.first_name?.[0] ?? '');
    }

    if (lastNameField && !isEmpty(response.errors?.last_name)) {
        setFieldFeedback(form, lastNameField, response.errors?.last_name?.[0] ?? '');
    }

    if (phoneField && !isEmpty(response.errors?.phone)) {
        setFieldFeedback(form, phoneField, response.errors?.phone?.[0] ?? '');
    }

    const generalErrorMessage = extractCartGeneralErrorMessage(response);

    if (generalErrorMessage !== '') {
        setCartModalGeneralError($FAST_ORDER, generalErrorMessage);
    }

    form.classList.add('_was-validated');
};

const bindFastOrderFieldValidation = (): void => {
    if (document.body.dataset.fastOrderFieldValidationBound === '1') {
        return;
    }

    document.body.dataset.fastOrderFieldValidationBound = '1';

    document.addEventListener('input', (event: Event): void => {
        const target = event.target as HTMLElement;
        const formEl = <HTMLFormElement | null>getClosestParentEl('[data-fast-order-form]', target);

        if (formEl === null) {
            return;
        }

        const field = target as HTMLInputElement;

        if (!(field instanceof HTMLInputElement)) {
            return;
        }

        if (!(field.name in fieldSelectors)) {
            return;
        }

        clearCartModalGeneralError($FAST_ORDER);
        clearFieldFeedback(formEl, field);

        if (formEl.classList.contains('_was-validated')) {
            validateField(formEl, field, field.name as FastOrderFieldName);
        }
    });
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
        clearCartModalGeneralError($FAST_ORDER);

        if (!validateFastOrderForm(formEl)) {
            return;
        }

        const payload = buildOrderPayload(formEl);

        toggleCartLoader(true);

        try {
            const validateResponse = await validateOrder(payload);

            if (!validateResponse.success) {
                applyServerValidationErrors(formEl, validateResponse);

                return;
            }

            const storeResponse = await storeOrder(payload);

            if (storeResponse.redirect_url) {
                redirect(storeResponse.redirect_url);
            }
        } finally {
            toggleCartLoader(false);
        }
    });
};

export const handleFastOrderModal = (): void => {
    setCartMode($REGULAR);
    handleParsePhone();
    bindFastOrderFieldValidation();
    bindFastOrderSubmit();
};
