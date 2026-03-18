import { initCarousel }                    from '@ts-features/carousel/lib/initCarousel.ts';
import { $PAGE_TYPE_KEY }                  from '@ts-shared/lib/constants.ts';
import { getAppParam }                     from '@ts-shared/lib/getAppParam.ts';
import { findArrayElems, findElem, toRem } from '@ts-shared/lib/helpers.ts';
import { handleCssVars }                   from "@ts-shared/lib/cssVars.ts";

function isPageTypeAllowed(carouselElement: HTMLElement): boolean {
    const pageType = getAppParam<string>($PAGE_TYPE_KEY);

    if (typeof pageType !== 'string' || pageType.trim().length === 0) {
        return true;
    }

    const allowedPageTypesRaw = carouselElement.dataset.pageTypes;

    if (typeof allowedPageTypesRaw !== 'string' || allowedPageTypesRaw.trim().length === 0) {
        return true;
    }

    try {
        const allowedPageTypes = JSON.parse(allowedPageTypesRaw);

        if (!Array.isArray(allowedPageTypes)) {
            return true;
        }

        return allowedPageTypes
            .filter((value: unknown): value is string => typeof value === 'string' && value.trim().length > 0)
            .includes(pageType);
    } catch {
        return true;
    }
}

export function handleProductsCarousel(): void {
    const carouselElements = <HTMLElement[] | []>findArrayElems('[data-products-carousel]');

    if (carouselElements.length === 0) {
        return;
    }

    carouselElements.forEach((carouselElement: HTMLElement): void => {
        if (!isPageTypeAllowed(carouselElement)) {
            return;
        }

        initCarousel(carouselElement);

        const initCb = (doc: HTMLElement): void => {
            const cardsEls        = <HTMLElement[] | []>findArrayElems('.products-carousel-card');
            let minHeight: number = 0;

            cardsEls.forEach((cardEl: HTMLElement): void => {
                const imgContainerEl  = <HTMLElement | null>findElem('.products-carousel-img-container', cardEl);
                const infoContainerEl = <HTMLElement | null>findElem('.products-carousel-info', cardEl);

                if (imgContainerEl !== null && infoContainerEl !== null) {
                    const totalSlideContainerHeight: number = imgContainerEl.offsetHeight + infoContainerEl.offsetHeight;

                    if (totalSlideContainerHeight > minHeight) {
                        minHeight = totalSlideContainerHeight;
                    }
                }
            });

            if (minHeight > 0) {
                doc.style.setProperty('--products-carousel-card', `${toRem(minHeight)}`);
            }
        };

        handleCssVars(initCb)
    });
}
