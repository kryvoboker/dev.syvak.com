import type { CheckoutMapPoint } from '@ts-features/pages/checkout/checkoutLeafletMap.ts';
import { getAppParam } from '@ts-shared/lib/getAppParam.ts';
import {
    isArray,
    isEmpty,
    isFiniteNumber,
    isNaNValue,
    toNumber,
    toNumberOrNull,
    toStringValue,
    toTrimmedString,
} from '@ts-shared/lib/helpers.ts';
import { $DELIVERY_METHOD, type CheckoutDeliveryMethod } from './checkoutConstants.ts';

export type { CheckoutDeliveryMethod } from './checkoutConstants.ts';

export interface CheckoutCitySearchItem {
    city_description: string;
    nova_poshta_city_id: string | null;
    ukr_poshta_city_id: number | null;
    city_lat: number | null;
    city_lng: number | null;
}

export interface CheckoutDeliveryPointState {
    id?: number | string | null;
    ref?: string | null;
    city_ref?: string | null;
    postcode?: number | null;
    pdcity_id?: number | null;
    description?: string | null;
    city_description?: string | null;
    city_name?: string | null;
    region_ua?: string | null;
    district_ua?: string | null;
    latitude?: number | null;
    longitude?: number | null;
    schedule?: string | null;
    number?: number | null;
    site_key?: number | null;
    lock_code?: number | null;
    delivery_method?: CheckoutDeliveryMethod | null;
    branch_value?: string | null;
    label?: string | null;
}

export interface CheckoutSelectionState {
    delivery_method?: string | null;
    payment_method?: string | null;
    city?: CheckoutCitySearchItem | null;
    delivery_point?: CheckoutDeliveryPointState | null;
    delivery_address?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    phone?: string | null;
    email?: string | null;
    comment?: string | null;
    promo_code?: string | null;
    no_call?: boolean | null;
}

export interface CheckoutFormState {
    firstName: string;
    lastName: string;
    phone: string;
    email: string;
    comment: string;
    promoCode: string;
    noCall: boolean;
}

export interface CheckoutMapData {
    selected_city?: CheckoutCitySearchItem | null;
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
}

export interface CheckoutBranchSearchItem extends CheckoutDeliveryPointState {
    branch_value: string;
    branch_label: string;
    delivery_method: CheckoutDeliveryMethod;
}

export type CheckoutBranchPayload =
    | CheckoutDeliveryPointState
    | CheckoutBranchSearchItem
    | Record<string, unknown>
    | null;

interface CheckoutCitySearchResponse {
    items?: CheckoutCitySearchItem[];
}

interface CheckoutBranchSearchResponse {
    items?: CheckoutBranchSearchItem[];
}

export const MIN_SEARCH_CITY_LENGTH = 3;
export const MIN_SEARCH_POST_OFFICE_LENGTH = 1;

export const toStringOrNull = (value: unknown): string | null => {
    if (typeof value === 'string') {
        const normalizedValue = value.trim();

        return normalizedValue === '' ? null : normalizedValue;
    }

    if (typeof value === 'number' || typeof value === 'bigint') {
        return toStringValue(value);
    }

    return null;
};

export const parseCustomProperties = (value: string | undefined): Record<string, unknown> => {
    if (!value || value.trim() === '') {
        return {};
    }

    try {
        const parsedValue: unknown = JSON.parse(value);

        return parsedValue !== null && typeof parsedValue === 'object' && !isArray(parsedValue)
            ? (parsedValue as Record<string, unknown>)
            : {};
    } catch {
        return {};
    }
};

export const normalizeCityPayload = (city: CheckoutCitySearchItem | null): CheckoutCitySearchItem | null => {
    if (!city || isEmpty(city.city_description)) {
        return null;
    }

    return {
        city_description: toStringValue(city.city_description),
        nova_poshta_city_id:
            typeof city.nova_poshta_city_id === 'string' && city.nova_poshta_city_id !== ''
                ? city.nova_poshta_city_id
                : null,
        ukr_poshta_city_id:
            typeof city.ukr_poshta_city_id === 'number' && city.ukr_poshta_city_id > 0 ? city.ukr_poshta_city_id : null,
        city_lat: typeof city.city_lat === 'number' ? city.city_lat : null,
        city_lng: typeof city.city_lng === 'number' ? city.city_lng : null,
    };
};

export const normalizeBranchPayload = (branch: CheckoutBranchPayload): CheckoutBranchSearchItem | null => {
    if (!branch) {
        return null;
    }

    const branchData = branch as Record<string, unknown>;
    const description = toTrimmedString(branchData.description ?? branchData.label);
    const branchValue = toTrimmedString(
        branchData.branch_value ?? branchData.id ?? branchData.ref ?? branchData.postcode,
    );

    if (description === '' || branchValue === '') {
        return null;
    }

    const deliveryMethod = toTrimmedString(branchData.delivery_method) as CheckoutDeliveryMethod | '';

    return {
        id: toStringOrNull(branchData.id),
        ref: toStringOrNull(branchData.ref),
        city_ref: toStringOrNull(branchData.city_ref),
        postcode: toNumberOrNull(branchData.postcode),
        pdcity_id: toNumberOrNull(branchData.pdcity_id),
        description,
        city_description: toStringOrNull(branchData.city_description),
        city_name: toStringOrNull(branchData.city_name),
        region_ua: toStringOrNull(branchData.region_ua),
        district_ua: toStringOrNull(branchData.district_ua),
        latitude: toNumberOrNull(branchData.latitude),
        longitude: toNumberOrNull(branchData.longitude),
        schedule: toStringOrNull(branchData.schedule),
        number: toNumberOrNull(branchData.number),
        site_key: toNumberOrNull(branchData.site_key),
        lock_code: toNumberOrNull(branchData.lock_code),
        delivery_method:
            deliveryMethod === $DELIVERY_METHOD.NOVA_POSHTA ||
            deliveryMethod === $DELIVERY_METHOD.NOVA_POSHTA_POSHTOMAT ||
            deliveryMethod === $DELIVERY_METHOD.NOVA_POSHTA_COURIER ||
            deliveryMethod === $DELIVERY_METHOD.UKR_POSHTA
                ? deliveryMethod
                : branchData.pdcity_id === null
                  ? $DELIVERY_METHOD.NOVA_POSHTA
                  : $DELIVERY_METHOD.UKR_POSHTA,
        branch_value: branchValue,
        branch_label: toStringValue(branchData.branch_label ?? branchData.label ?? description),
        label: toStringValue(branchData.branch_label ?? branchData.label ?? description),
    };
};

export const readCityFromOption = (option: HTMLOptionElement | null): CheckoutCitySearchItem | null => {
    if (!option) {
        return null;
    }

    const cityDescription = option.value.trim();

    if (cityDescription === '') {
        return null;
    }

    const novaPoshtaCityId = option.dataset.novaPoshtaCityId?.trim() ?? '';
    const ukrPoshtaCityId = toNumber(option.dataset.ukrPoshtaCityId);

    return normalizeCityPayload({
        city_description: cityDescription,
        nova_poshta_city_id: novaPoshtaCityId === '' ? null : novaPoshtaCityId,
        ukr_poshta_city_id: isNaNValue(ukrPoshtaCityId) || ukrPoshtaCityId <= 0 ? null : ukrPoshtaCityId,
        city_lat: toNumberOrNull(option.dataset.cityLat),
        city_lng: toNumberOrNull(option.dataset.cityLng),
    });
};

export const readBranchFromOption = (option: HTMLOptionElement | null): CheckoutBranchSearchItem | null => {
    if (!option) {
        return null;
    }

    const branchData = parseCustomProperties(option.dataset.customProperties);

    return normalizeBranchPayload({
        ...branchData,
        id: branchData.id ?? option.value.trim() ?? null,
        description: branchData.description ?? option.textContent?.trim() ?? null,
        label: branchData.label ?? option.textContent?.trim() ?? null,
        branch_value: branchData.branch_value ?? option.value.trim() ?? null,
    });
};

export const buildSelectionPayload = (
    city: CheckoutCitySearchItem | null,
    deliveryMethod: string,
    deliveryPoint: CheckoutBranchSearchItem | null,
    deliveryAddress: string,
    paymentMethod: string,
    formState: CheckoutFormState,
): FormData => {
    const payload = new FormData();

    if (city) {
        payload.append('city[city_description]', city.city_description);
        payload.append('city[nova_poshta_city_id]', city.nova_poshta_city_id ?? '');
        payload.append(
            'city[ukr_poshta_city_id]',
            city.ukr_poshta_city_id === null ? '' : toStringValue(city.ukr_poshta_city_id),
        );
        payload.append('city[city_lat]', city.city_lat === null ? '' : toStringValue(city.city_lat));
        payload.append('city[city_lng]', city.city_lng === null ? '' : toStringValue(city.city_lng));
    }

    if (deliveryPoint) {
        Object.entries({ ...deliveryPoint, delivery_method: deliveryMethod }).forEach(([key, value]): void => {
            if (value !== null && value !== undefined && value !== '') {
                payload.append(`delivery_point[${key}]`, toStringValue(value));
            }
        });
    }

    if (deliveryMethod === $DELIVERY_METHOD.NOVA_POSHTA_COURIER || deliveryMethod === $DELIVERY_METHOD.PICKUP_STORE) {
        payload.append('delivery_address', deliveryAddress);
    }

    payload.append('delivery_method', deliveryMethod);
    payload.append('payment_method', paymentMethod);
    payload.append('first_name', formState.firstName);
    payload.append('last_name', formState.lastName);
    payload.append('phone', formState.phone);
    payload.append('email', formState.email);
    payload.append('comment', formState.comment);
    payload.append('promo_code', formState.promoCode);
    payload.append('no_call', formState.noCall ? '1' : '0');

    return payload;
};

export const buildBranchLoadPayload = (city: CheckoutCitySearchItem | null, deliveryMethod: string): FormData => {
    const payload = new FormData();

    payload.append('delivery_method', deliveryMethod);

    if (city) {
        payload.append('city[city_description]', city.city_description);
        payload.append('city[nova_poshta_city_id]', city.nova_poshta_city_id ?? '');
        payload.append(
            'city[ukr_poshta_city_id]',
            city.ukr_poshta_city_id === null ? '' : toStringValue(city.ukr_poshta_city_id),
        );
    }

    return payload;
};

export const isBranchCompatibleWithSelection = (
    branch: CheckoutBranchSearchItem | null,
    city: CheckoutCitySearchItem | null,
    deliveryMethod: string,
): boolean => {
    if (
        !branch ||
        !city ||
        !(
            [
                $DELIVERY_METHOD.NOVA_POSHTA,
                $DELIVERY_METHOD.NOVA_POSHTA_POSHTOMAT,
                $DELIVERY_METHOD.UKR_POSHTA,
            ] as readonly CheckoutDeliveryMethod[]
        ).includes(deliveryMethod as CheckoutDeliveryMethod)
    ) {
        return false;
    }

    if (deliveryMethod === $DELIVERY_METHOD.NOVA_POSHTA || deliveryMethod === $DELIVERY_METHOD.NOVA_POSHTA_POSHTOMAT) {
        return (
            branch.delivery_method === deliveryMethod &&
            toStringValue(branch.city_ref) === toStringValue(city.nova_poshta_city_id)
        );
    }

    return (
        branch.delivery_method === $DELIVERY_METHOD.UKR_POSHTA &&
        toNumber(branch.pdcity_id) === toNumber(city.ukr_poshta_city_id)
    );
};

export const resolveCheckoutSelectionState = (): CheckoutSelectionState => {
    return getAppParam<CheckoutSelectionState>('checkout_selection_state') ?? {};
};

export const resolveCheckoutUrl = (
    key: 'checkout_city_search_url' | 'checkout_branch_search_url' | 'checkout_selection_save_url',
): string => {
    const value = getAppParam<string>(key);

    return typeof value === 'string' ? value : '';
};

export const resolveBranchLabelText = (deliveryMethod: string): string => {
    return toStringValue(
        getAppParam(
            deliveryMethod === 'nova_poshta_poshtomat' ? 'checkout_poshtomat_label_text' : 'checkout_branch_label_text',
        ),
    );
};

export const buildCheckoutMapPoints = (branches: CheckoutBranchSearchItem[]): CheckoutMapPoint[] => {
    return branches.flatMap((branch): CheckoutMapPoint[] => {
        const lat = toNumber(branch.latitude);
        const lng = toNumber(branch.longitude);
        const title = toTrimmedString(branch.branch_label ?? branch.label ?? branch.description);

        if (!isFiniteNumber(lat) || !isFiniteNumber(lng) || lat === 0 || lng === 0 || title === '') {
            return [];
        }

        return [
            {
                id: toTrimmedString(branch.branch_value ?? branch.id ?? branch.ref),
                title,
                description: toTrimmedString(branch.description ?? branch.branch_label ?? title),
                lat,
                lng,
                schedule: branch.schedule ?? null,
                delivery_method: toTrimmedString(branch.delivery_method),
                branch_value: toTrimmedString(branch.branch_value),
                city_ref: branch.city_ref ?? null,
                city_description: branch.city_description ?? null,
            },
        ];
    });
};

export type { CheckoutBranchSearchResponse, CheckoutCitySearchResponse };
