import { getAppParam }    from "@ts-shared/lib/getAppParam";
import { $PAGE_TYPE_KEY } from "@ts-shared/lib/constants";

/**
 * Temporary page eligibility guard for ProductsCarousel module scripts.
 */
function canLoadProductsCarouselScript(): boolean {
    const pageType = getAppParam<string>($PAGE_TYPE_KEY);

    // TODO: Replace this stub with real check against module settings page_type when that field is added.
    void pageType;

    return true;
}

document.addEventListener('DOMContentLoaded', (): void => {
    if (!canLoadProductsCarouselScript()) {
        return;
    }

    window.$hsCarouselCollection = window.$hsCarouselCollection || [];


});
