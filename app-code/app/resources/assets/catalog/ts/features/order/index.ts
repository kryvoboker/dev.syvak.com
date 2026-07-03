import { initAccordion } from "@ts-shared/accordion/initAccordion.ts";
import { findArrayElems } from "@ts-shared/lib/helpers.ts";
import { handleCheckoutDeliverySelection } from "@ts-features/checkout/checkoutPage.ts";

export const handleCheckoutPage = (): void => {
    const accordionItems = <HTMLElement[] | []>findArrayElems('[data-checkout-page] .accordion-item');

    accordionItems.forEach((accordionItem: HTMLElement): void => {
        initAccordion(accordionItem);
    });

    handleCheckoutDeliverySelection();
};
