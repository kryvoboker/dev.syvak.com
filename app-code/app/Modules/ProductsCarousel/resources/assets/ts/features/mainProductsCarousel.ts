import { initCarousel }   from '@ts-features/carousel/lib/initCarousel.ts';
import { findArrayElems } from '@ts-shared/lib/helpers.ts';

export function handleProductsCarousel(): void {
    const carouselElements = findArrayElems('[data-products-carousel]');

    if (carouselElements.length === 0) {
        return;
    }

    carouselElements.forEach((carouselElement: HTMLElement): void => {
        initCarousel(carouselElement);
    });
}
