import { storeOrder, validateOrder } from '@ts-features/cart/cartCrud.ts';
import { handleCheckoutDeliverySelection } from '@ts-features/pages/checkout/checkoutPage.ts';
import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';
import { findArrayElems, findElem, showErrorInConsole } from '@ts-shared/lib/helpers.ts';

interface WayForPayWidget {
    run: (
        data: Record<string, unknown>,
        approved: (response: unknown) => void,
        declined: (response: unknown) => void,
        pending: (response: unknown) => void,
    ) => void;
}

interface WayForPayResponse {
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

declare global {
    interface Window {
        Wayforpay?: new () => WayForPayWidget;
    }
}

const getErrorMessage = (response: WayForPayResponse): string => {
    const errors = response.errors ?? {};

    return (
        Object.values(errors)
            .flat()
            .find((message: string): boolean => message.trim() !== '') ?? ''
    );
};

const showCheckoutError = (message: string): void => {
    const errorElement = <HTMLElement | null>findElem('[data-checkout-order-error]');

    if (!errorElement) {
        return;
    }

    errorElement.textContent = message;
    errorElement.classList.toggle('hidden', message.trim() === '');
};

const loadWayForPayWidget = (): Promise<void> => {
    if (window.Wayforpay) {
        return Promise.resolve();
    }

    const existingScript = document.querySelector<HTMLScriptElement>('script[data-wayforpay-widget]');

    if (existingScript) {
        return new Promise((resolve, reject): void => {
            existingScript.addEventListener('load', (): void => resolve(), { once: true });
            existingScript.addEventListener('error', (): void => reject(new Error('WayForPay widget failed to load')), {
                once: true,
            });
        });
    }

    return new Promise((resolve, reject): void => {
        const script = document.createElement('script');
        script.async = true;
        script.defer = true;
        script.dataset.wayforpayWidget = '1';
        script.src = String(window.app_params?.wayforpay_widget_script_url ?? '');
        script.addEventListener('load', (): void => resolve(), { once: true });
        script.addEventListener('error', (): void => reject(new Error('WayForPay widget failed to load')), {
            once: true,
        });
        document.head.appendChild(script);
    });
};

const submitRedirectFallback = (
    redirectData: NonNullable<NonNullable<WayForPayResponse['payment']>['redirect_data']>,
): void => {
    const action = String(redirectData.action ?? '').trim();
    const method = String(redirectData.method ?? window.app_params?.wayforpay_redirect_method ?? 'POST')
        .trim()
        .toUpperCase();

    if (action === '' || method !== String(window.app_params?.wayforpay_redirect_method ?? 'POST').toUpperCase()) {
        showCheckoutError('The payment redirect is unavailable.');

        return;
    }

    const form = document.createElement('form');
    form.method = method;
    form.action = action;
    form.style.display = 'none';

    Object.entries(redirectData.fields ?? {}).forEach(([key, value]: [string, unknown]): void => {
        if (Array.isArray(value)) {
            value.forEach((item: unknown): void => {
                const input = document.createElement('input');
                input.name = `${key}[]`;
                input.value = String(item ?? '');
                form.appendChild(input);
            });

            return;
        }

        const input = document.createElement('input');
        input.name = key;
        input.value = String(value ?? '');
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
};

const openWayForPayPayment = async (response: WayForPayResponse): Promise<void> => {
    const payment = response.payment;
    const widgetData = payment?.widget_data;
    const redirectData = payment?.redirect_data;

    if (payment?.use_widget !== true) {
        if (redirectData) {
            submitRedirectFallback(redirectData);
        } else {
            showCheckoutError('The payment redirect is unavailable.');
        }

        return;
    }

    if (!widgetData) {
        if (redirectData) {
            submitRedirectFallback(redirectData);
        } else {
            showCheckoutError('The payment window could not be opened.');
        }

        return;
    }

    try {
        await loadWayForPayWidget();

        if (!window.Wayforpay) {
            throw new Error('WayForPay widget is unavailable');
        }

        const wayforpay = new window.Wayforpay();
        const redirectToReturnUrl = (): void => {
            const returnUrl = String(widgetData.returnUrl ?? '').trim();

            if (returnUrl !== '') {
                window.location.assign(returnUrl);
            }
        };

        wayforpay.run(
            widgetData,
            redirectToReturnUrl,
            (): void => showCheckoutError('The payment was declined.'),
            (): void => {
                showCheckoutError('The payment is being processed.');
            },
        );
    } catch (error) {
        showErrorInConsole('[checkout] Failed to open WayForPay widget.');

        if (redirectData) {
            submitRedirectFallback(redirectData);

            return;
        }

        showCheckoutError(error instanceof Error ? error.message : 'The payment window could not be opened.');
    }
};

const handleCheckoutSubmit = (): void => {
    const formElement = <HTMLFormElement | null>findElem('[data-checkout-form]');
    const submitElement = <HTMLButtonElement | null>findElem('[data-checkout-submit]');

    if (!formElement || !submitElement || formElement.dataset.orderSubmitBound === '1') {
        return;
    }

    formElement.dataset.orderSubmitBound = '1';
    formElement.addEventListener('submit', (event: SubmitEvent): void => {
        event.preventDefault();
        showCheckoutError('');
        submitElement.disabled = true;

        const payload = new FormData(formElement);
        payload.set('cart_mode', 'regular');

        void (async (): Promise<void> => {
            try {
                const validationResponse = <WayForPayResponse>await validateOrder(payload);

                if (validationResponse.success !== true) {
                    showCheckoutError(getErrorMessage(validationResponse) || 'Please check the checkout data.');

                    return;
                }

                const orderResponse = <WayForPayResponse>await storeOrder(payload);

                if (orderResponse.success !== true) {
                    showCheckoutError(getErrorMessage(orderResponse) || 'The order could not be created.');

                    return;
                }

                if (orderResponse.payment?.payment_method === window.app_params?.wayforpay_payment_method) {
                    await openWayForPayPayment(orderResponse);

                    return;
                }

                const redirectUrl = String((orderResponse as Record<string, unknown>).redirect_url ?? '').trim();

                if (redirectUrl !== '') {
                    window.location.assign(redirectUrl);
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

    handleCheckoutDeliverySelection();
    handleCheckoutSubmit();
};
