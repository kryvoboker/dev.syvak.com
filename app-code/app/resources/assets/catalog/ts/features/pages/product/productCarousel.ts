import { addClass, findArrayElems, findElem, isContainsClass, isEmpty, removeClass } from "@ts-shared/lib/helpers.ts";
import { initCarousel }                                                              from "@ts-shared/carousel/initCarousel.ts";
import HSCarousel                                                                    from "flyonui/src/js/plugins/carousel";
import { $ACTIVE_CLASS_NAME }                                                        from "@ts-shared/lib/constants.ts";

export const handleProductCarousel = (): void => {
    const carouselEl = <HTMLElement | null>findElem('.product-carousel--init');

    if (isEmpty(carouselEl)) {
        return;
    }

    const carouselInstance: HSCarousel      = initCarousel(<HTMLElement>carouselEl);
    const slides                            = <HTMLElement[] | []>findArrayElems('.carousel-slide', carouselEl);
    const carouselThumbsPaginationEls       = <HTMLElement[] | []>findArrayElems('.product-content .carousel-pagination-item');
    const clearActiveClassFromPaginationEls = (): void => {
        carouselThumbsPaginationEls.forEach((el: HTMLElement): void | null => removeClass(el, $ACTIVE_CLASS_NAME));
    };

    carouselThumbsPaginationEls.forEach((paginationEl: HTMLElement, index: number): void => {
        paginationEl.addEventListener('click', (): void => {
            carouselInstance.goTo(index);

            clearActiveClassFromPaginationEls();
            addClass(paginationEl, $ACTIVE_CLASS_NAME);
        });
    });

    const syncPagination = (): void => {
        const activeIndex: number = slides.findIndex((slide: HTMLElement): boolean => isContainsClass(slide, $ACTIVE_CLASS_NAME));

        clearActiveClassFromPaginationEls();
        carouselThumbsPaginationEls.forEach((el: HTMLElement, index: number): void | null => {
            if (activeIndex === index) {
                addClass(el, $ACTIVE_CLASS_NAME);
            }
        });
    };

    const observer = new MutationObserver((): void => {
        syncPagination();
    });

    slides.forEach((slide: HTMLElement): void => {
        observer.observe(slide, {
            attributes:      true,
            attributeFilter: ['class'],
        });
    });

    syncPagination();
};
