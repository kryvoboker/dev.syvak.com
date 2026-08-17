import { initCarousel } from '@ts-shared/carousel/initCarousel.ts';
import { $PAGE_TYPE_KEY } from '@ts-shared/lib/constants.ts';
import { getAppParam } from '@ts-shared/lib/getAppParam.ts';
import { arrayFrom, findArrayElems, fromJson, getDataset, isArray, isEmpty, toRem } from '@ts-shared/lib/helpers.ts';
import { reportCriticalFrontendError } from '@ts-shared/lib/reportCriticalError.ts';

function isPageTypeAllowed(carouselElement: HTMLElement): boolean {
    const pageType: string | null = getAppParam<string>($PAGE_TYPE_KEY);

    if (typeof pageType !== 'string' || isEmpty(pageType)) {
        return true;
    }

    const allowedPageTypesRaw: string | null = getDataset(carouselElement, 'pageTypes');

    if (typeof allowedPageTypesRaw !== 'string' || isEmpty(allowedPageTypesRaw)) {
        return true;
    }

    try {
        const allowedPageTypes = fromJson(allowedPageTypesRaw);

        if (!isArray(allowedPageTypes)) {
            return true;
        }

        return allowedPageTypes
            .filter((value: unknown): value is string => typeof value === 'string' && !isEmpty(value))
            .includes(pageType);
    } catch (error) {
        reportCriticalFrontendError(error);

        return true;
    }
}

function setCardsHeight(): void {
    const carouselsBodiesEls = <HTMLElement[] | []>findArrayElems('.products-carousel-body');
    let minHeight: number = 0;

    carouselsBodiesEls.forEach((carouselBodyEl: HTMLElement): void => {
        const slidersEls = <HTMLElement[] | []>findArrayElems('.products-carousel-slide', carouselBodyEl);

        slidersEls.forEach((sliderElChildrenEl: HTMLElement): void => {
            const children = <HTMLElement[] | []>arrayFrom(sliderElChildrenEl.children);

            const sliderElChildrenElsHeight: number = children.reduce(
                (acc2: number, sliderElChildEl: HTMLElement): number => {
                    return acc2 + sliderElChildEl.offsetHeight;
                },
                0,
            );

            if (sliderElChildrenElsHeight > minHeight) {
                minHeight = sliderElChildrenElsHeight;
            }
        });

        if (minHeight > 0) {
            carouselBodyEl.style.setProperty('height', `${toRem(minHeight)}`);

            minHeight = 0;
        }
    });
}

export function handleProductsCarousel(): void {
    const carouselElements = <HTMLElement[] | []>findArrayElems('[data-products-carousel]');

    if (isEmpty(carouselElements)) {
        return;
    }

    carouselElements.forEach((carouselElement: HTMLElement): void => {
        if (!isPageTypeAllowed(carouselElement)) {
            return;
        }

        initCarousel(carouselElement);

        setTimeout((): void => setCardsHeight(), 100);
    });
}
