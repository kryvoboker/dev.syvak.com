export type CartMode = 'regular' | 'fast_order';

export interface CartTotalsLine {
    code: string;
    label: string;
    amount: number;
    formatted: string;
    is_visible: boolean;
    include_in_grand_total: boolean;
}

export interface CartTotals {
    lines: CartTotalsLine[];
    grand_total: number;
    grand_total_formatted: string;
}

export interface CartItem {
    variant_id: number;
    product_id: number;
    name: string;
    url: string;
    quantity: number;
    minimum_quantity: number;
    available_quantity: number;
    line_total_formatted: string;
}

export interface CartData {
    mode: CartMode;
    items: CartItem[];
    totals: CartTotals;
    is_empty: boolean;
    items_count: number;
    total_quantity: number;
}

export interface CartMutationResponse {
    success: boolean;
    message?: string;
    mode?: CartMode;
    cart?: CartData;
    rendered?: {
        modal_items_html?: string;
        cart_page_html?: string;
    };
    redirect_url?: string;
    errors?: Record<string, string[]>;
}
