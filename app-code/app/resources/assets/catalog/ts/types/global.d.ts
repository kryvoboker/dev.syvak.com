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
        },
        range?: {
            min: number;
            max: number;
            step: number;
            selected_from: number | null;
            selected_to: number | null;
        }
    }
    is_has_more_pages?: boolean;
    load_more_products_ajax_url?: string;
    next_page?: number | null;
    cart_mode?: string;
    cart_modal_ajax_url?: string;
    cart_store_url?: string;
    cart_update_url_pattern?: string;
    cart_delete_url_pattern?: string;
    order_validate_url?: string;
    order_store_url?: string;
    checkout_city_search_url?: string | null;
    checkout_selection_save_url?: string | null;
    checkout_choose_city_first_text?: string | null;
    checkout_no_delivery_methods_text?: string | null;
    checkout_no_cities_text?: string | null;
    checkout_selection_state?: {
        delivery_method?: string | null;
        city?: {
            city_description?: string | null;
            nova_poshta_city_id?: string | null;
            ukr_poshta_city_id?: number | null;
            city_lat?: number | null;
            city_lng?: number | null;
        } | null;
        delivery_point?: Record<string, unknown> | null;
    } | null;

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
