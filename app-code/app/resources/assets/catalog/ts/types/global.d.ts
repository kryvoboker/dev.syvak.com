import type HSCarousel  from "flyonui/src/js/plugins/carousel"
import type HSDropdown  from "flyonui/src/js/plugins/dropdown"
import type HSOverlay   from "flyonui/src/js/plugins/overlay"
import type HSAccordion from "flyonui/src/js/plugins/accordion"

interface AppParams {
    page_type?: string | null;

    [key: string]: unknown;
}

declare global {
    interface Window {
        HSDropdown: typeof HSDropdown;
        HSOverlay: typeof HSOverlay;
        HSCarousel?: typeof HSCarousel;
        HSAccordion?: typeof HSAccordion;
        app_params?: AppParams;
    }
}

export {};
