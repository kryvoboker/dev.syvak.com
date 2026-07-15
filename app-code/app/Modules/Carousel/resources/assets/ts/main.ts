import { findElem } from '@ts-shared/lib/helpers';

export const handleCarousel = (): void => {
    window.$hsCarouselCollection = window.$hsCarouselCollection || [];

    const carouselExists = findElem('[data-main-carousel]') !== null;

    if (!carouselExists) {
        return;
    }

    import('@carousel-ts/features/mainCarousel.ts').then((module): void => {
        module.handleMainCarousel();
    });
};
