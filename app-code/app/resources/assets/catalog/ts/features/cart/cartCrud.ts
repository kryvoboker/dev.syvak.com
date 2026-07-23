import { updateCartBadgeFromResponse } from '@ts-features/cart/cartBadge.ts';
import {
    clearCartModalGeneralError,
    extractCartGeneralErrorMessage,
    setCartModalGeneralError,
} from '@ts-features/cart/cartErrors.ts';
import { initCartPageAccordion } from '@ts-features/cart/cartPage.ts';
import type { CartMode, CartMutationResponse } from '@ts-features/cart/cartTypes.ts';
import { $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import { getAppParam } from '@ts-shared/lib/getAppParam.ts';
import {
    fetchFunc,
    findArrayElems,
    findElem,
    isEmpty,
    toggleElement,
    toTrimmedString,
} from '@ts-shared/lib/helpers.ts';
import type { WindowAppParams } from '@ts-types/global';

const resolveUrl = (key: keyof WindowAppParams): string => {
    const value: string | null = getAppParam<string>(key);

    return typeof value === 'string' ? value : '';
};

const resolveMutationUrlByPattern = (patternKey: keyof WindowAppParams, cartId: number): string => {
    const pattern: string = resolveUrl(patternKey);

    return pattern.replace('__cart_id__', `${cartId}`);
};

const appendCommonPayload = (formData: FormData, mode: CartMode): void => {
    formData.append('cart_mode', mode);
    formData.append('is_call_from_modal', '1');
};

export const toggleCartLoader = (isShow: boolean): void => {
    const loaders = <HTMLElement[] | []>findArrayElems('.cart-loader');

    loaders.forEach((loader: HTMLElement): void => {
        toggleElement(loader, isShow);
    });
};

const renderMutationResponse = (response: CartMutationResponse, mode: CartMode): void => {
    updateCartBadgeFromResponse(response, mode);

    const modalContent = <HTMLElement | null>findElem(`[data-cart-modal-content="${mode}"]`);

    if (modalContent && response.rendered?.modal_items_html) {
        modalContent.innerHTML = response.rendered.modal_items_html;
    }

    const fastOrderFormWrapper = <HTMLElement | null>findElem('[data-fast-order-form-wrapper]');

    if (fastOrderFormWrapper && mode === 'fast_order') {
        const isCartEmpty = response.cart?.is_empty ?? true;

        fastOrderFormWrapper.classList.toggle($HIDDEN_CLASS_NAME, isCartEmpty);
    }

    const cartPageRoot = <HTMLElement | null>findElem('#cart-page-root');

    if (cartPageRoot && response.rendered?.cart_page_html) {
        cartPageRoot.innerHTML = response.rendered.cart_page_html;
        initCartPageAccordion(cartPageRoot);
    }

    const generalMessage =
        response.success === true ? toTrimmedString(response.message) : extractCartGeneralErrorMessage(response);

    if (generalMessage !== '') {
        setCartModalGeneralError(mode, generalMessage, response.success === true);
    } else {
        clearCartModalGeneralError(mode);
    }
};

export const loadCartSnapshot = async (mode: CartMode): Promise<CartMutationResponse> => {
    const modalIndexUrl: string = resolveUrl('cart_modal_ajax_url');

    if (isEmpty(modalIndexUrl)) {
        return { success: false };
    }

    const response = <CartMutationResponse>await fetchFunc(`${modalIndexUrl}?cart_mode=${mode}`, {}, 'GET');

    renderMutationResponse(response, mode);

    return response;
};

export const addCartItem = async (variantId: number, mode: CartMode): Promise<CartMutationResponse> => {
    const storeUrl: string = resolveUrl('cart_store_url');

    if (isEmpty(storeUrl)) {
        return { success: false };
    }

    const formData = new FormData();

    formData.append('product_variant_id', `${variantId}`);
    formData.append('quantity', '1');
    appendCommonPayload(formData, mode);

    const response = <CartMutationResponse>await fetchFunc(storeUrl, formData);

    renderMutationResponse(response, mode);

    return response;
};

export const updateCartItemQuantity = async (
    cartId: number,
    quantity: number,
    mode: CartMode,
): Promise<CartMutationResponse> => {
    const updateUrl: string = resolveMutationUrlByPattern('cart_update_url_pattern', cartId);

    if (isEmpty(updateUrl)) {
        return { success: false };
    }

    const formData = new FormData();

    formData.append('cart_id', `${cartId}`);
    formData.append('quantity', `${quantity}`);
    appendCommonPayload(formData, mode);

    const response = <CartMutationResponse>await fetchFunc(updateUrl, formData, 'PATCH');

    renderMutationResponse(response, mode);

    return response;
};

export const removeCartItem = async (cartId: number, mode: CartMode): Promise<CartMutationResponse> => {
    const deleteUrl: string = resolveMutationUrlByPattern('cart_delete_url_pattern', cartId);

    if (isEmpty(deleteUrl)) {
        return { success: false };
    }

    const formData = new FormData();

    formData.append('cart_id', `${cartId}`);
    appendCommonPayload(formData, mode);

    const response = <CartMutationResponse>await fetchFunc(deleteUrl, formData, 'DELETE');

    renderMutationResponse(response, mode);

    return response;
};

export const validateOrder = async (payload: FormData): Promise<CartMutationResponse> => {
    const validateUrl: string = resolveUrl('order_validate_url');

    if (isEmpty(validateUrl)) {
        return { success: false };
    }

    return <CartMutationResponse>await fetchFunc(validateUrl, payload);
};

export const storeOrder = async (payload: FormData): Promise<CartMutationResponse> => {
    const storeUrl: string = resolveUrl('order_store_url');

    if (isEmpty(storeUrl)) {
        return { success: false };
    }

    return <CartMutationResponse>await fetchFunc(storeUrl, payload);
};
