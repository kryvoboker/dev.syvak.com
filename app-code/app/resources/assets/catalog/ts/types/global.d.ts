import type HSAccordion from 'flyonui/src/js/plugins/accordion';
import type HSCarousel from 'flyonui/src/js/plugins/carousel';
import type HSDropdown from 'flyonui/src/js/plugins/dropdown';
import type HSOverlay from 'flyonui/src/js/plugins/overlay';

export interface WindowAppParams {
    page_type?: string | null;
    current_device_type?: string | null;
    catalog_filter_ajax_url?: string | null;
    catalog_filter_price_data?: {
        get_extra?: {
            from_key: string;
            to_key: string;
        };
        range?: {
            min: number;
            max: number;
            step: number;
            selected_from: number | null;
            selected_to: number | null;
        };
    };
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
    frontend_error_log_url?: string;
    frontend_critical_error_reporting_enabled?: boolean;
    wayforpay_widget_script_url?: string;
    wayforpay_payment_method?: string;
    wayforpay_redirect_method?: string;
    checkout_city_search_url?: string | null;
    checkout_branch_search_url?: string | null;
    checkout_selection_save_url?: string | null;
    checkout_choose_city_first_text?: string | null;
    checkout_no_delivery_methods_text?: string | null;
    checkout_no_cities_text?: string | null;
    checkout_map_data?: {
        selected_city?: {
            city_description?: string | null;
            nova_poshta_city_id?: string | null;
            ukr_poshta_city_id?: number | null;
            city_lat?: number | null;
            city_lng?: number | null;
        } | null;
        selected_delivery_method?: string | null;
        marker_icons?: {
            nova_poshta?: string | null;
            ukr_poshta?: string | null;
        } | null;
        texts?: {
            title?: string | null;
            search_placeholder?: string | null;
            list_title?: string | null;
            empty?: string | null;
            choose_city_first?: string | null;
            deliver_here?: string | null;
            close?: string | null;
            work_schedule?: string | null;
            day_off?: string | null;
        } | null;
    } | null;
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
        delivery_address?: string | null;
        first_name?: string | null;
        last_name?: string | null;
        phone?: string | null;
        email?: string | null;
        comment?: string | null;
        promo_code?: string | null;
        no_call?: boolean | null;
    } | null;

    [key: string]: unknown;
}

declare global {
    interface Window {
        HSDropdown: typeof HSDropdown;
        HSOverlay: typeof HSOverlay;
        HSCarousel?: typeof HSCarousel;
        HSAccordion?: typeof HSAccordion;
        $hsDropdownCollection?: HSDropdown[];
        $hsOverlayCollection?: HSOverlay[];
        $hsAccordionCollection?: HSAccordion[];
        $hsCarouselCollection?: Array<{ id: string | number; element: HSCarousel }>;
        app_params?: WindowAppParams;
    }
}
