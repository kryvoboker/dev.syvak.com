import HSCarousel from 'flyonui/src/js/plugins/carousel';

export function initCarousel(carouselEl: HTMLElement): HSCarousel {
    return new HSCarousel(carouselEl);
}
