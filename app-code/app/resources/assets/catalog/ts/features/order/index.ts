import { handleCheckoutDeliverySelection } from '@ts-features/pages/checkout/checkoutPage.ts';
import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';
import { findArrayElems } from '@ts-shared/lib/helpers.ts';

export const handleCheckoutPage = (): void => {
    const accordionElements = <HTMLElement[] | []>findArrayElems('[data-checkout-page] .accordion');

    accordionElements.forEach((accordionElement: HTMLElement): void => {
        initAccordion(accordionElement);
    });

    handleCheckoutDeliverySelection();
};
