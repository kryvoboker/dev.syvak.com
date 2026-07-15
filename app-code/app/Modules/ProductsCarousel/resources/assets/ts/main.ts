import { findElem } from '@ts-shared/lib/helpers';

export const handleProductsCarousel = (): void => {
    window.$hsCarouselCollection = window.$hsCarouselCollection || [];

    const productsCarouselExists = findElem('[data-products-carousel]') !== null;

    if (!productsCarouselExists) {
        return;
    }

    import('@products-carousel-ts/features/mainProductsCarousel.ts').then((module): void => {
        module.handleProductsCarousel();
    });
};
