import { findElem } from '@ts-shared/lib/helpers.ts';
import HSAccordion from 'flyonui/src/js/plugins/accordion';

export const initAccordion = (accordionEl: HTMLElement): HSAccordion => {
    const accordionItem = accordionEl.matches('.accordion-item')
        ? accordionEl
        : <HTMLElement | null>findElem('.accordion-item', accordionEl);

    if (!accordionItem) {
        return new HSAccordion(accordionEl);
    }

    const existingInstance = (
        HSAccordion as typeof HSAccordion & {
            getInstance: (target: HTMLElement, isInstance?: boolean) => HTMLElement | { element: HSAccordion } | null;
        }
    ).getInstance(accordionItem, true);

    if (existingInstance && 'element' in existingInstance) {
        return existingInstance.element;
    }

    return new HSAccordion(accordionItem);
};
