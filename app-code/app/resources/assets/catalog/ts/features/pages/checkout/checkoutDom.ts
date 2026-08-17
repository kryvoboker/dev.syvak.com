import { findArrayElems, findElem } from '@ts-shared/lib/helpers.ts';

export interface CheckoutDomElements {
    citySelectElement: HTMLSelectElement | null;
    branchSelectElement: HTMLSelectElement | null;
    deliveryAddressInputElement: HTMLInputElement | null;
    cityWarningElement: HTMLElement | null;
    deliveryMethodsWrapperElement: HTMLElement | null;
    deliveryMethodsLoaderElement: HTMLElement | null;
    deliveryAddressWrapperElement: HTMLElement | null;
    branchWrapperElement: HTMLElement | null;
    branchLabelElement: HTMLElement | null;
    mapButtonElement: HTMLButtonElement | null;
    deliveryMethodOptions: HTMLElement[];
    deliveryMethodInputs: HTMLInputElement[];
    paymentMethodInputs: HTMLInputElement[];
    checkoutFormElement: HTMLFormElement | null;
}

export const getCheckoutDomElements = (): CheckoutDomElements => ({
    citySelectElement: <HTMLSelectElement | null>findElem('#checkout-city'),
    branchSelectElement: <HTMLSelectElement | null>findElem('#checkout-branch'),
    deliveryAddressInputElement: <HTMLInputElement | null>findElem('#checkout-delivery-address'),
    cityWarningElement: <HTMLElement | null>findElem('[data-checkout-city-warning]'),
    deliveryMethodsWrapperElement: <HTMLElement | null>findElem('[data-checkout-delivery-methods-wrapper]'),
    deliveryMethodsLoaderElement: <HTMLElement | null>findElem('[data-checkout-delivery-methods-loader]'),
    deliveryAddressWrapperElement: <HTMLElement | null>findElem('[data-checkout-delivery-address-wrapper]'),
    branchWrapperElement: <HTMLElement | null>findElem('[data-checkout-branch-wrapper]'),
    branchLabelElement: <HTMLElement | null>findElem('[data-checkout-branch-label]'),
    mapButtonElement: <HTMLButtonElement | null>findElem('#find-on-map-btn'),
    deliveryMethodOptions: findArrayElems('[data-checkout-delivery-method-option]'),
    deliveryMethodInputs: findArrayElems('[data-checkout-delivery-method-input]') as HTMLInputElement[],
    paymentMethodInputs: findArrayElems('[data-checkout-payment-method-input]') as HTMLInputElement[],
    checkoutFormElement: <HTMLFormElement | null>findElem('[data-checkout-form]'),
});
