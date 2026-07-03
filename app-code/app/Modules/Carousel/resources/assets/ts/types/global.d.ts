import type HSCarousel from 'flyonui/src/js/plugins/carousel';

declare global {
    interface Window {
        HSCarousel?: typeof HSCarousel;
        $hsCarouselCollection?: HSCarousel[];
    }
}
