import { findElem, toRem } from "@ts-shared/lib/helpers.ts";

export const handleCssVars = () : void => {
    const doc : HTMLElement = document.documentElement;

    const init = () : void => {
        const headerEl : HTMLElement | null = findElem('header');

        if (headerEl) {
            doc.style.setProperty('--header-height', `${toRem(headerEl.offsetHeight)}`);
        }
    };

    init();

    window.addEventListener('resize', init);
};
