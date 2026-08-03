import { storeOrder, validateOrder } from '@ts-features/cart/cartCrud.ts';
import { initializeCheckoutDeliveryLogic } from '@ts-features/pages/checkout/checkoutPage.ts';
import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';
import { $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import {
    findArrayElems,
    findElem,
    getDataset,
    redirect,
    setDataset,
    setTextContent,
    toggleClass,
    toTrimmedString,
} from '@ts-shared/lib/helpers.ts';
import { handleCheckoutPayment } from '@ts-shared/payment/checkoutPaymentRegistry.ts';

interface CheckoutOrderResponse {
    success?: boolean;
    errors?: Record<string, string[]>;
    payment?: {
        payment_method?: string;
        use_widget?: boolean;
        widget_data?: Record<string, unknown>;
        redirect_data?: {
            action?: string;
            method?: string;
            fields?: Record<string, unknown>;
        };
    };
}

const getErrorMessage = (response: CheckoutOrderResponse): string => {
    const errors = response.errors ?? {};

    return (
        Object.values(errors)
            .flat()
            .find((message: string): boolean => toTrimmedString(message) !== '') ?? ''
    );
};

const showCheckoutError = (message: string): void => {
    const errorElement = <HTMLElement | null>findElem('[data-checkout-order-error]');

    if (!errorElement) {
        return;
    }

    setTextContent(errorElement, message);
    toggleClass(errorElement, $HIDDEN_CLASS_NAME, toTrimmedString(message) === '');
};

const handleCheckoutSubmit = (): void => {
    const formElement = <HTMLFormElement | null>findElem('[data-checkout-form]');
    const submitElement = <HTMLButtonElement | null>findElem('[data-checkout-submit]');

    if (!formElement || !submitElement || getDataset(formElement, 'orderSubmitBound') === '1') {
        return;
    }

    setDataset(formElement, 'orderSubmitBound', '1');
    formElement.addEventListener('submit', (event: SubmitEvent): void => {
        event.preventDefault();
        showCheckoutError('');
        submitElement.disabled = true;

        const payload = new FormData(formElement);
        payload.set('cart_mode', 'regular');

        void (async (): Promise<void> => {
            try {
                const validationResponse = <CheckoutOrderResponse>await validateOrder(payload);

                if (validationResponse.success !== true) {
                    showCheckoutError(getErrorMessage(validationResponse) || 'Please check the checkout data.');

                    return;
                }

                const orderResponse = <CheckoutOrderResponse>await storeOrder(payload);

                if (orderResponse.success !== true) {
                    showCheckoutError(getErrorMessage(orderResponse) || 'The order could not be created.');

                    return;
                }

                const paymentMethod = toTrimmedString(orderResponse.payment?.payment_method);

                if (
                    paymentMethod !== '' &&
                    (await handleCheckoutPayment(
                        paymentMethod,
                        orderResponse as Record<string, unknown>,
                        showCheckoutError,
                    ))
                ) {
                    return;
                }

                const redirectUrl = toTrimmedString((orderResponse as Record<string, unknown>).redirect_url);

                if (redirectUrl !== '') {
                    redirect(redirectUrl);
                }
            } catch {
                showCheckoutError('The order could not be created. Please try again.');
            } finally {
                submitElement.disabled = false;
            }
        })();
    });
};

export const handleCheckoutPage = (): void => {
    const accordionElements = <HTMLElement[] | []>findArrayElems('[data-checkout-page] .accordion');

    accordionElements.forEach((accordionElement: HTMLElement): void => {
        initAccordion(accordionElement);
    });

    initializeCheckoutDeliveryLogic();
    handleCheckoutSubmit();
    document.dispatchEvent(new CustomEvent('checkout:initialized'));
};
