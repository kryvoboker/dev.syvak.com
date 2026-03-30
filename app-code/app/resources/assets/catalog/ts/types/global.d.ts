import type HSCarousel  from "flyonui/src/js/plugins/carousel"
import type HSDropdown  from "flyonui/src/js/plugins/dropdown"
import type HSOverlay   from "flyonui/src/js/plugins/overlay"
import type HSAccordion from "flyonui/src/js/plugins/accordion"

export interface WindowAppParams {
    page_type?: string | null;
    catalog_filter_ajax_url?: string | null;
    catalog_filter_price_data?: {
        get_extra?: {
            from_key: string;
            to_key: string;
        }
    }

    [key: string]: unknown;
}

declare global {
    interface Window {
        HSDropdown: typeof HSDropdown;
        HSOverlay: typeof HSOverlay;
        HSCarousel?: typeof HSCarousel;
        HSAccordion?: typeof HSAccordion;
        app_params?: WindowAppParams;
    }
}

export {};
