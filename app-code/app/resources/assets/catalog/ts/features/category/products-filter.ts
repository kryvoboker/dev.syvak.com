import { findArrayElems } from "@ts-shared/lib/helpers.ts";
import { initAccordion }  from "@ts-shared/accordion/initAccordion.ts";

export const handleProductsFilter = (): void => {
    const filterAccordionsEls = <HTMLElement[] | []>findArrayElems('.accordion-item');

    filterAccordionsEls.forEach((accordionEl: HTMLElement): void => {
        initAccordion(accordionEl);
    });
};
