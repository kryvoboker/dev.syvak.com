import { findElem } from '@ts-shared/lib/helpers';

document.addEventListener('DOMContentLoaded', (): void => {
    window.$hsCarouselCollection = window.$hsCarouselCollection || [];

    const productsCarouselExists = findElem('[data-products-carousel]') !== null;

    if (!productsCarouselExists) {
        return;
    }

    import('@products-carousel-ts/features/mainProductsCarousel.ts').then((module): void => {
        module.handleProductsCarousel();
    });
});
