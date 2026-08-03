import { $DEBOUNCE_DELAY } from '@ts-shared/lib/constants.ts';
import { debounce, fetchFunc, isArray, isEmpty, showErrorInConsole, toTrimmedString } from '@ts-shared/lib/helpers.ts';
import {
    buildBranchLoadPayload,
    buildSelectionPayload,
    type CheckoutBranchSearchItem,
    type CheckoutBranchSearchResponse,
    type CheckoutCitySearchItem,
    type CheckoutCitySearchResponse,
    type CheckoutFormState,
    normalizeBranchPayload,
    normalizeCityPayload,
} from './checkoutData.ts';

interface CheckoutSearchState {
    city: CheckoutCitySearchItem | null;
    deliveryMethod: string;
    branch: CheckoutBranchSearchItem | null;
    deliveryAddress: string;
    paymentMethod: string;
    form: CheckoutFormState;
    branchSearchStateKey: string;
    latestCityResults: CheckoutCitySearchItem[];
    latestBranchResults: CheckoutBranchSearchItem[];
}

interface CheckoutSearchOptions {
    citySearchUrl: string;
    branchSearchUrl: string;
    selectionSaveUrl: string;
    state: CheckoutSearchState;
    applyCityResults: (cities: CheckoutCitySearchItem[]) => void;
    applyBranchResults: (branches: CheckoutBranchSearchItem[]) => void;
    updateMapButtonState: () => void;
    onCartUpdated?: (cart: Record<string, unknown>) => void;
}

export const createCheckoutSearch = (options: CheckoutSearchOptions) => {
    const { state } = options;

    const resolveBranchSearchStateKey = (): string => {
        const cityKey = state.city?.nova_poshta_city_id ?? state.city?.ukr_poshta_city_id ?? '';

        return `${state.deliveryMethod}:${String(cityKey)}`;
    };

    const loadBranches = debounce(async (): Promise<void> => {
        if (
            !state.city ||
            state.deliveryMethod === 'nova_poshta_courier' ||
            !['nova_poshta', 'nova_poshta_poshtomat', 'ukr_poshta'].includes(state.deliveryMethod) ||
            isEmpty(options.branchSearchUrl)
        ) {
            state.branchSearchStateKey = '';
            state.latestBranchResults = [];
            options.applyBranchResults([]);
            return;
        }

        const searchStateKey = resolveBranchSearchStateKey();

        if (searchStateKey === state.branchSearchStateKey && !isEmpty(state.latestBranchResults)) {
            options.applyBranchResults(state.latestBranchResults);
            return;
        }

        state.branchSearchStateKey = searchStateKey;

        try {
            const response = await fetchFunc<CheckoutBranchSearchResponse>(
                options.branchSearchUrl,
                buildBranchLoadPayload(state.city, state.deliveryMethod),
            );
            const items = isArray(response?.items) ? response.items : [];

            state.latestBranchResults = items
                .map((item) => normalizeBranchPayload(item))
                .filter((item): item is CheckoutBranchSearchItem => item !== null);
            options.applyBranchResults(state.latestBranchResults);
        } catch {
            state.latestBranchResults = [];
            options.applyBranchResults(state.branch ? [state.branch] : []);
            options.updateMapButtonState();
        }
    }, $DEBOUNCE_DELAY);

    const searchCities = debounce(async (searchValue: string): Promise<void> => {
        const normalizedValue = toTrimmedString(searchValue);

        if (normalizedValue.length < 3 || isEmpty(options.citySearchUrl)) {
            state.latestCityResults = [];
            options.applyCityResults(state.city ? [state.city] : []);
            return;
        }

        try {
            const response = await fetchFunc<CheckoutCitySearchResponse>(
                `${options.citySearchUrl}?city_keyword=${encodeURIComponent(normalizedValue)}`,
                {} as Record<string, string | number>,
                'GET',
            );
            const items = isArray(response?.items) ? response.items : [];

            state.latestCityResults = items
                .map((item) => normalizeCityPayload(item))
                .filter((item): item is CheckoutCitySearchItem => item !== null);
            options.applyCityResults(state.latestCityResults);
        } catch {
            state.latestCityResults = [];
            options.applyCityResults(state.city ? [state.city] : []);
        }
    }, $DEBOUNCE_DELAY);

    const saveSelection = async (): Promise<void> => {
        if (isEmpty(options.selectionSaveUrl)) {
            return;
        }

        try {
            const response = await fetchFunc<{ cart?: Record<string, unknown> }>(
                options.selectionSaveUrl,
                buildSelectionPayload(
                    state.city,
                    state.deliveryMethod,
                    state.branch,
                    state.deliveryAddress,
                    state.paymentMethod,
                    state.form,
                ),
            );

            if (response?.cart && options.onCartUpdated) {
                options.onCartUpdated(response.cart);
            }
        } catch {
            showErrorInConsole('[checkout] Failed to synchronize delivery selection.');
        }
    };

    return {
        loadBranches,
        saveSelection,
        searchCities,
        syncSelectionToServer: debounce(async (): Promise<void> => saveSelection(), $DEBOUNCE_DELAY),
    };
};
