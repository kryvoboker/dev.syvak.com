import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';
import { findElem } from '@ts-shared/lib/helpers.ts';

export const handleThankYouPage = (): void => {
    const pageRoot = <HTMLElement | null>findElem('[data-thank-you-page]');
    const accordion = <HTMLElement | null>findElem('[data-thank-you-products-accordion]', pageRoot);

    if (accordion) {
        initAccordion(accordion);
    }
};
