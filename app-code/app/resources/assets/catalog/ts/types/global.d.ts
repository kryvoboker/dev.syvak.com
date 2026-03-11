import type HSCarousel from "flyonui/src/js/plugins/carousel"
import type HSDropdown from "flyonui/src/js/plugins/dropdown"
import type HSOverlay  from "flyonui/src/js/plugins/overlay"

interface AppParams {
    page_type?: string | null;

    [key: string]: unknown;
}

declare global {
    interface Window {
        HSDropdown: typeof HSDropdown;
        HSOverlay: typeof HSOverlay;
        HSCarousel?: typeof HSCarousel;
        app_params?: AppParams;
    }
}

export {};
