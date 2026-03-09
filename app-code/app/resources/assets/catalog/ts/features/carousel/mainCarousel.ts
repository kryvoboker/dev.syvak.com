import { getCarouselElements } from '@ts-features/carousel/lib/getCarouselElements.ts';
import { initCarousel } from '@ts-features/carousel/lib/initCarousel.ts';

export function handleMainCarousel(): void {
    const carouselElements = getCarouselElements();

    if (carouselElements.length === 0) {
        return;
    }

    carouselElements.forEach((carouselEl: HTMLElement): void => {
        initCarousel(carouselEl);
    });
}
