interface CheckoutEventBindings {
    citySelectElement: HTMLSelectElement;
    branchSelectElement: HTMLSelectElement;
    deliveryAddressInputElement: HTMLInputElement | null;
    deliveryMethodInputs: HTMLInputElement[];
    paymentMethodInputs: HTMLInputElement[];
    onCitySearch: (value: string) => void;
    onCityChange: () => void;
    onBranchChange: () => void;
    onDeliveryAddressChange: () => void;
    onDeliveryMethodChange: (event: Event) => void;
    onPaymentMethodChange: (event: Event) => void;
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
};
