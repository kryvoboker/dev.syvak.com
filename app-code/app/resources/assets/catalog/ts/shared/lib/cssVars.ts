import { findElem, toRem } from "@ts-shared/lib/helpers.ts";

type InitCallback = (doc: HTMLElement) => void;

export const handleCssVars = (initCb: InitCallback | null = null): void => {
    const doc: HTMLElement = document.documentElement;

    let init = null;

    if (typeof initCb === 'function') {
        init = initCb;
    } else {
        init = (doc: HTMLElement): void => {
            const headerEl: HTMLElement | null = findElem('header');

            if (headerEl) {
                doc.style.setProperty('--header-height', `${toRem(headerEl.offsetHeight)}`);
            }
        };
    }


    init(doc);

    window.addEventListener('resize', (): void => init(doc));
};
