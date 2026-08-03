import {
    createHtmlElement,
    findElem,
    isArray,
    setDataset,
    showErrorInConsole,
    toStringValue,
    toTrimmedString,
} from '@ts-shared/lib/helpers.ts';
import { registerCheckoutPaymentHandler } from '@ts-shared/payment/checkoutPaymentRegistry.ts';

interface WayForPayWidget {
    run: (
        data: Record<string, unknown>,
        approved: (response: unknown) => void,
        declined: (response: unknown) => void,
        pending: (response: unknown) => void,
    ) => void;
}

interface WayForPayPaymentResponse {
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

const loadWidget = (): Promise<void> => {
    if (window.Wayforpay) {
        return Promise.resolve();
    }

    const existingScript = <HTMLScriptElement | null>findElem('script[data-wayforpay-widget]');

    if (existingScript) {
        return new Promise((resolve, reject): void => {
            existingScript.addEventListener('load', (): void => resolve(), { once: true });
            existingScript.addEventListener('error', (): void => reject(new Error('WayForPay widget failed to load')), {
                once: true,
            });
        });
    }

    return new Promise((resolve, reject): void => {
        const script = <HTMLScriptElement>createHtmlElement('script');
        script.async = true;
        script.defer = true;
        setDataset(script, 'wayforpayWidget', '1');
        script.src = toStringValue(window.app_params?.wayforpay_widget_script_url);
        script.addEventListener('load', (): void => resolve(), { once: true });
        script.addEventListener('error', (): void => reject(new Error('WayForPay widget failed to load')), {
            once: true,
        });
        document.head.appendChild(script);
    });
};

const submitPostForm = (action: string, fields: Record<string, unknown>): void => {
    const form = <HTMLFormElement>createHtmlElement('form');
    form.method = 'POST';
    form.action = action;
    form.style.display = 'none';

    Object.entries(fields).forEach(([key, value]: [string, unknown]): void => {
        if (isArray(value)) {
            value.forEach((item: unknown): void => {
                const input = <HTMLInputElement>createHtmlElement('input');
                input.name = `${key}[]`;
                input.value = toStringValue(item);
                form.appendChild(input);
            });

            return;
        }

        const input = <HTMLInputElement>createHtmlElement('input');
        input.name = key;
        input.value = toStringValue(value);
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
};

const submitRedirectFallback = (
    redirectData: NonNullable<NonNullable<WayForPayPaymentResponse['payment']>['redirect_data']>,
    showError: (message: string) => void,
): void => {
    const action = toTrimmedString(redirectData.action);
    const method = toTrimmedString(
        redirectData.method ?? window.app_params?.wayforpay_redirect_method ?? 'POST',
    ).toUpperCase();

    if (action === '' || method !== toStringValue(window.app_params?.wayforpay_redirect_method, 'POST').toUpperCase()) {
        showError('The payment redirect is unavailable.');

        return;
    }

    submitPostForm(action, redirectData.fields ?? {});
};

const openPayment = async (
    response: Record<string, unknown>,
    showError: (message: string) => void,
): Promise<boolean> => {
    const paymentResponse = response as WayForPayPaymentResponse;
    const payment = paymentResponse.payment;
    const widgetData = payment?.widget_data;
    const redirectData = payment?.redirect_data;

    if (payment?.use_widget !== true || !widgetData) {
        if (redirectData) {
            submitRedirectFallback(redirectData, showError);
        } else {
            showError('The payment redirect is unavailable.');
        }

        return true;
    }

    try {
        await loadWidget();

        if (!window.Wayforpay) {
            throw new Error('WayForPay widget is unavailable');
        }

        const wayforpay = new window.Wayforpay();
        const redirectToReturnUrl = (widgetResponse: unknown): void => {
            const returnUrl = toTrimmedString(widgetData.returnUrl);

            if (returnUrl === '' || typeof widgetResponse !== 'object' || widgetResponse === null) {
                showError('The payment response is unavailable.');

                return;
            }

            submitPostForm(returnUrl, widgetResponse as Record<string, unknown>);
        };

        wayforpay.run(
            widgetData,
            redirectToReturnUrl,
            (): void => showError('The payment was declined.'),
            (): void => showError('The payment is being processed.'),
        );
    } catch (error) {
        showErrorInConsole('[checkout] Failed to open WayForPay widget.');

        if (redirectData) {
            submitRedirectFallback(redirectData, showError);
        } else {
            showError(error instanceof Error ? error.message : 'The payment window could not be opened.');
        }
    }

    return true;
};

registerCheckoutPaymentHandler('wayforpay', openPayment);
