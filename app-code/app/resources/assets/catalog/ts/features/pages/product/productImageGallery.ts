import { Fancybox }                        from "@fancyapps/ui";
import { findArrayElems, isContainsClass } from "@ts-shared/lib/helpers.ts";
import { $ACTIVE_CLASS_NAME }              from "@ts-shared/lib/constants.ts";

const checkStartIndex = (imageLinkEls: HTMLLinkElement[] | []): number => {
    const imageLinkElsLength: number = imageLinkEls.length;

    for (let index = 0; index < imageLinkElsLength; index++) {
        const imgLinkEl: HTMLElement = imageLinkEls[index];

        if (isContainsClass(imgLinkEl.parentElement, $ACTIVE_CLASS_NAME)) {
            return index;
        }
    }

    return 0;
};

export const handleProductImageGallery = (): void => {
    const imageLinkEls = <HTMLLinkElement[] | []>findArrayElems('[data-fancybox]');

    imageLinkEls.forEach((imgLink: HTMLLinkElement): void => {
        imgLink.addEventListener('click', (e: PointerEvent): void => {
            e.preventDefault();


            Fancybox.fromNodes(imageLinkEls, {
                startIndex: checkStartIndex(imageLinkEls),
            });
        });
    });
};
