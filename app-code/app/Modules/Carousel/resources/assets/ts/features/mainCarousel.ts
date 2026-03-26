import { initCarousel }   from '@ts-shared/carousel/initCarousel.ts';
import { $PAGE_TYPE_KEY } from '@ts-shared/lib/constants.ts';
import { getAppParam }    from '@ts-shared/lib/getAppParam.ts';
import { findArrayElems } from '@ts-shared/lib/helpers.ts';

function isPageTypeAllowed(carouselElement: HTMLElement): boolean {
    const pageType = getAppParam<string>($PAGE_TYPE_KEY);

    // TODO: Replace this fallback with strict module/page_type matching once runtime page context policy is finalized.
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

function hasEnoughSlidesForCarousel(carouselElement: HTMLElement): boolean {
    const slidesCountFromData = Number.parseInt(carouselElement.dataset.slidesCount ?? '', 10);

    if (!Number.isNaN(slidesCountFromData)) {
        return slidesCountFromData > 1;
    }

    const slidesCount = carouselElement.querySelectorAll('.carousel-slide').length;

    return slidesCount > 1;
}

export function handleMainCarousel(): void {
    const carouselElements = findArrayElems('[data-main-carousel]');

    if (carouselElements.length === 0) {
        return;
    }

    carouselElements.forEach((carouselElement: HTMLElement): void => {
        if (!isPageTypeAllowed(carouselElement)) {
            return;
        }

        if (!hasEnoughSlidesForCarousel(carouselElement)) {
            return;
        }

        initCarousel(carouselElement);
    });
}
