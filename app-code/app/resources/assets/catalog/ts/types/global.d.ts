import { HSDropdown, HSOverlay } from "flyonui/flyonui"
import type HSCarousel from "flyonui/src/js/plugins/carousel"

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
