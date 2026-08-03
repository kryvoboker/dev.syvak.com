import { findArrayElems } from '@ts-shared/lib/helpers.ts';

interface CheckoutEventBindings {
    citySelectElement: HTMLSelectElement;
    branchSelectElement: HTMLSelectElement;
    deliveryAddressInputElement: HTMLInputElement | null;
    deliveryMethodInputs: HTMLInputElement[];
    paymentMethodInputs: HTMLInputElement[];
    checkoutFormElement: HTMLFormElement | null;
    onCitySearch: (value: string) => void;
    onCityChange: () => void;
    onBranchChange: () => void;
    onDeliveryAddressChange: () => void;
    onDeliveryMethodChange: (event: Event) => void;
    onPaymentMethodChange: (event: Event) => void;
    onCheckoutFormChange: () => void;
}

export const bindCheckoutEvents = ({
    citySelectElement,
    branchSelectElement,
    deliveryAddressInputElement,
    deliveryMethodInputs,
    paymentMethodInputs,
    onCitySearch,
    onCityChange,
    onBranchChange,
    onDeliveryAddressChange,
    onDeliveryMethodChange,
    onPaymentMethodChange,
    checkoutFormElement,
    onCheckoutFormChange,
}: CheckoutEventBindings): void => {
    citySelectElement.addEventListener('search', (event: Event): void => {
        const searchEvent = event as CustomEvent<{ value: string }>;

        onCitySearch(searchEvent.detail?.value ?? '');
    });

    citySelectElement.addEventListener('change', onCityChange);
    branchSelectElement.addEventListener('change', onBranchChange);
    deliveryAddressInputElement?.addEventListener('input', onDeliveryAddressChange);
    deliveryAddressInputElement?.addEventListener('change', onDeliveryAddressChange);

    deliveryMethodInputs.forEach((input: HTMLInputElement): void => {
        input.addEventListener('change', onDeliveryMethodChange);
    });

    paymentMethodInputs.forEach((input: HTMLInputElement): void => {
        input.addEventListener('change', onPaymentMethodChange);
    });

    (<(HTMLInputElement | HTMLTextAreaElement)[] | []>(
        findArrayElems(
            'input[name="first_name"], input[name="last_name"], input[name="phone"], input[name="email"], textarea[name="comment"], input[name="promo_code"], input[name="no_call"]',
            checkoutFormElement,
        )
    )).forEach((input: HTMLInputElement | HTMLTextAreaElement): void => {
        input.addEventListener('input', onCheckoutFormChange);
        input.addEventListener('change', onCheckoutFormChange);
    });
};
