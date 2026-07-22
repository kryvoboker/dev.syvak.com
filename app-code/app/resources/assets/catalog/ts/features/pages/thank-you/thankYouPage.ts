import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';

export const handleThankYouPage = (): void => {
    const pageRoot = document.querySelector<HTMLElement>('[data-thank-you-page]');
    const accordion = pageRoot?.querySelector<HTMLElement>('[data-thank-you-products-accordion]');

    if (accordion) {
        initAccordion(accordion);
    }
};
