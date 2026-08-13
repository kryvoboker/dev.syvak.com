import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';
import { $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import { fetchFunc, findArrayElems, findElem, toggleClass, toTrimmedString } from '@ts-shared/lib/helpers.ts';
import { handleCheckoutPayment } from '@ts-shared/payment/checkoutPaymentRegistry.ts';

interface FailureResponse {
    success?: boolean;
    redirect_url?: string;
    payment?: Record<string, unknown>;
    errors?: Record<string, string[]>;
}

const getErrorMessage = (response: FailureResponse): string =>
    Object.values(response.errors ?? {})
        .flat()
        .find((message: string): boolean => toTrimmedString(message) !== '') ?? '';

const showError = (root: HTMLElement, message: string): void => {
    const errorElement = <HTMLElement | null>findElem('[data-failure-error]', root);

    if (!errorElement) {
        return;
    }

    errorElement.textContent = message;
    toggleClass(errorElement, $HIDDEN_CLASS_NAME, message === '');
};

const submitPayment = async (root: HTMLElement, url: string, paymentMethod: string): Promise<boolean> => {
    if (url === '' || paymentMethod === '') {
        return false;
    }

    const payload = new FormData();
    payload.append('payment_method', paymentMethod);

    const response = await fetchFunc<FailureResponse>(url, payload);

    if (response.success !== true) {
        showError(root, getErrorMessage(response) || 'The payment attempt failed.');

        return false;
    }

    if (
        response.payment &&
        (await handleCheckoutPayment(paymentMethod, response as Record<string, unknown>, (message: string): void =>
            showError(root, message),
        ))
    ) {
        return true;
    }

    if (toTrimmedString(response.redirect_url) !== '') {
        window.location.href = toTrimmedString(response.redirect_url);
    }

    return true;
};

const initializePaymentAccordion = (root: HTMLElement): void => {
    const accordion = <HTMLElement | null>findElem('[data-failure-payment-accordion]', root);
    const toggle = <HTMLButtonElement | null>findElem('[data-failure-payment-toggle]', root);

    if (!accordion || !toggle) {
        return;
    }

    initAccordion(accordion);
};

export const handleFailurePage = (): void => {
    const root = <HTMLElement | null>findElem('[data-failure-page]');

    if (!root) {
        return;
    }

    initializePaymentAccordion(root);

    const retryButton = <HTMLButtonElement | null>findElem('[data-failure-retry]', root);
    const retryUrl = toTrimmedString(root.dataset.retryUrl);
    const retryPaymentMethod = toTrimmedString(root.dataset.retryPaymentMethod);
    const paymentToggle = <HTMLButtonElement | null>findElem('[data-failure-payment-toggle]', root);
    const paymentButtons = <HTMLButtonElement[]>findArrayElems('[data-failure-payment-method]', root);

    retryButton?.addEventListener('click', async (): Promise<void> => {
        retryButton.disabled = true;
        showError(root, '');

        try {
            const isSuccess = await submitPayment(root, retryUrl, retryPaymentMethod);

            if (!isSuccess) {
                const retryAttempts = Number(root.dataset.retryAttempts ?? '0') + 1;
                root.dataset.retryAttempts = `${retryAttempts}`;

                if (retryAttempts >= 2 || paymentButtons.length === 0) {
                    retryButton.remove();

                    if (paymentToggle && paymentButtons.length > 0) {
                        paymentToggle.classList.remove($HIDDEN_CLASS_NAME);
                    }
                }
            }
        } catch {
            showError(root, 'The payment attempt failed.');
        } finally {
            retryButton.disabled = false;
        }
    });

    paymentButtons.forEach((button: HTMLButtonElement | HTMLElement): void => {
        button.addEventListener('click', async (): Promise<void> => {
            const paymentMethod = toTrimmedString(button.dataset.failurePaymentMethod);
            button.setAttribute('aria-busy', 'true');

            try {
                await submitPayment(root, toTrimmedString(root.dataset.paymentUrl), paymentMethod);
            } catch {
                showError(root, 'The payment attempt failed.');
            } finally {
                button.removeAttribute('aria-busy');
            }
        });
    });
};
