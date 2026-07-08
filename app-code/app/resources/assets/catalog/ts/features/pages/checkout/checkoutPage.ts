import { getAppParam }                                                     from '@ts-shared/lib/getAppParam.ts';
import { debounce, fetchFunc, findArrayElems, findElem, isArray, isEmpty } from '@ts-shared/lib/helpers.ts';
import Choices, { type InputChoice, type Options }                         from 'choices.js';

type CheckoutDeliveryMethod = 'nova_poshta' | 'ukr_poshta';

interface CheckoutCitySearchItem {
    city_description: string;
    nova_poshta_city_id: string | null;
    ukr_poshta_city_id: number | null;
    city_lat: number | null;
    city_lng: number | null;
}

interface CheckoutDeliveryPointState {
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

interface CheckoutSelectionState {
    delivery_method?: string | null;
    city?: CheckoutCitySearchItem | null;
    delivery_point?: CheckoutDeliveryPointState | null;
}

interface CheckoutCitySearchResponse {
    items?: CheckoutCitySearchItem[];
}

interface CheckoutBranchSearchItem extends CheckoutDeliveryPointState {
    branch_value: string;
    branch_label: string;
    delivery_method: CheckoutDeliveryMethod;
}

interface CheckoutBranchSearchResponse {
    items?: CheckoutBranchSearchItem[];
}

type CheckoutBranchPayload = CheckoutDeliveryPointState | CheckoutBranchSearchItem | Record<string, unknown> | null;

interface ChoiceSettings {
    allowHTML: boolean;
    searchFields: string[];
    shouldSort: boolean;
}

const MIN_SEARCH_CITY_LENGTH = 3;
const MIN_SEARCH_POST_OFFICE_LENGTH = 1;
const SEARCH_DEBOUNCE_MS = 700;

const toNumberOrNull = (value: unknown): number | null => {
    const numericValue = Number(value);

    return Number.isNaN(numericValue) ? null : numericValue;
};

const toStringOrNull = (value: unknown): string | null => {
    if (typeof value === 'string') {
        const normalizedValue = value.trim();

        return normalizedValue === '' ? null : normalizedValue;
    }

    if (typeof value === 'number' || typeof value === 'bigint') {
        return String(value);
    }

    return null;
};

const parseCustomProperties = (value: string | undefined): Record<string, unknown> => {
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

const normalizeCityPayload = (city: CheckoutCitySearchItem | null): CheckoutCitySearchItem | null => {
    if (!city || isEmpty(city.city_description)) {
        return null;
    }

    return {
        city_description: String(city.city_description ?? ''),
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

const normalizeBranchPayload = (branch: CheckoutBranchPayload): CheckoutBranchSearchItem | null => {
    if (!branch) {
        return null;
    }

    const branchData = branch as Record<string, unknown>;
    const description = String(branchData.description ?? branchData.label ?? '').trim();
    const branchValue = String(
        branchData.branch_value ?? branchData.id ?? branchData.ref ?? branchData.postcode ?? '',
    ).trim();

    if (description === '' || branchValue === '') {
        return null;
    }

    const deliveryMethod = String(branchData.delivery_method ?? '').trim() as CheckoutDeliveryMethod | '';

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
            deliveryMethod === 'nova_poshta' || deliveryMethod === 'ukr_poshta'
                ? deliveryMethod
                : branchData.pdcity_id === null ? 'nova_poshta' : 'ukr_poshta',
        branch_value: branchValue,
        branch_label: String(branchData.branch_label ?? branchData.label ?? description),
        label: String(branchData.branch_label ?? branchData.label ?? description),
    };
};

const readCityFromOption = (option: HTMLOptionElement | null): CheckoutCitySearchItem | null => {
    if (!option) {
        return null;
    }

    const cityDescription = option.value.trim();

    if (cityDescription === '') {
        return null;
    }

    const novaPoshtaCityId = option.dataset.novaPoshtaCityId?.trim() ?? '';
    const ukrPoshtaCityId = Number(option.dataset.ukrPoshtaCityId ?? 0);

    return normalizeCityPayload({
        city_description: cityDescription,
        nova_poshta_city_id: novaPoshtaCityId === '' ? null : novaPoshtaCityId,
        ukr_poshta_city_id: Number.isNaN(ukrPoshtaCityId) || ukrPoshtaCityId <= 0 ? null : ukrPoshtaCityId,
        city_lat: toNumberOrNull(option.dataset.cityLat),
        city_lng: toNumberOrNull(option.dataset.cityLng),
    });
};

const readBranchFromOption = (option: HTMLOptionElement | null): CheckoutBranchSearchItem | null => {
    if (!option) {
        return null;
    }

    const branchData = parseCustomProperties(option.dataset.customProperties);

    return normalizeBranchPayload({
        ... branchData,
        id:           branchData.id ?? option.value.trim() ?? null,
        description:  branchData.description ?? option.textContent?.trim() ?? null,
        label:        branchData.label ?? option.textContent?.trim() ?? null,
        branch_value: branchData.branch_value ?? option.value.trim() ?? null,
    });
};

const buildChoiceItem = (city: CheckoutCitySearchItem): InputChoice => ({
    value: city.city_description,
    label: city.city_description,
    customProperties: {
        city_description: city.city_description,
        nova_poshta_city_id: city.nova_poshta_city_id,
        ukr_poshta_city_id: city.ukr_poshta_city_id,
        city_lat: city.city_lat,
        city_lng: city.city_lng,
    },
});

const buildBranchChoiceItem = (branch: CheckoutBranchSearchItem): InputChoice => ({
    value: branch.branch_value,
    label: branch.branch_label,
    customProperties: {
        id: branch.id,
        ref: branch.ref,
        city_ref: branch.city_ref,
        postcode: branch.postcode,
        pdcity_id: branch.pdcity_id,
        description: branch.description,
        city_description: branch.city_description,
        city_name: branch.city_name,
        region_ua: branch.region_ua,
        district_ua: branch.district_ua,
        latitude: branch.latitude,
        longitude: branch.longitude,
        schedule: branch.schedule,
        number: branch.number,
        site_key: branch.site_key,
        lock_code: branch.lock_code,
        delivery_method: branch.delivery_method,
        branch_value: branch.branch_value,
        branch_label: branch.branch_label,
    },
});

const appendRecordToFormData = (payload: FormData, prefix: string, data: Record<string, unknown>): void => {
    Object.entries(data).forEach(([key, value]: [string, unknown]): void => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        payload.append(`${prefix}[${key}]`, String(value));
    });
};

const buildSelectionPayload = (
    city: CheckoutCitySearchItem | null,
    deliveryMethod: string,
    deliveryPoint: CheckoutBranchSearchItem | null,
): FormData => {
    const payload = new FormData();

    if (city) {
        payload.append('city[city_description]', city.city_description);
        payload.append('city[nova_poshta_city_id]', city.nova_poshta_city_id ?? '');
        payload.append(
            'city[ukr_poshta_city_id]',
            city.ukr_poshta_city_id === null ? '' : String(city.ukr_poshta_city_id),
        );
        payload.append('city[city_lat]', city.city_lat === null ? '' : String(city.city_lat));
        payload.append('city[city_lng]', city.city_lng === null ? '' : String(city.city_lng));
    }

    if (deliveryPoint) {
        appendRecordToFormData(payload, 'delivery_point', {
            ...deliveryPoint,
            delivery_method: deliveryMethod,
        });
    }

    payload.append('delivery_method', deliveryMethod);

    return payload;
};

const buildBranchSearchPayload = (
    city: CheckoutCitySearchItem | null,
    deliveryMethod: string,
    branchKeyword: string,
): FormData => {
    const payload = new FormData();

    payload.append('branch_keyword', branchKeyword);
    payload.append('delivery_method', deliveryMethod);

    if (city) {
        payload.append('city[city_description]', city.city_description);
        payload.append('city[nova_poshta_city_id]', city.nova_poshta_city_id ?? '');
        payload.append(
            'city[ukr_poshta_city_id]',
            city.ukr_poshta_city_id === null ? '' : String(city.ukr_poshta_city_id),
        );
    }

    return payload;
};

const isBranchCompatibleWithSelection = (
    branch: CheckoutBranchSearchItem | null,
    city: CheckoutCitySearchItem | null,
    deliveryMethod: string,
): boolean => {
    if (!branch || !city || !['nova_poshta', 'ukr_poshta'].includes(deliveryMethod)) {
        return false;
    }

    if (deliveryMethod === 'nova_poshta') {
        return (
            branch.delivery_method === 'nova_poshta' &&
            String(branch.city_ref ?? '') === String(city.nova_poshta_city_id ?? '')
        );
    }

    return (
        branch.delivery_method === 'ukr_poshta' &&
        Number(branch.pdcity_id ?? 0) === Number(city.ukr_poshta_city_id ?? 0)
    );
};

const resolveCheckoutSelectionState = (): CheckoutSelectionState => {
    const state = getAppParam<CheckoutSelectionState>('checkout_selection_state');

    return state ?? {};
};

const resolveCheckoutUrl = (
    key: 'checkout_city_search_url' | 'checkout_branch_search_url' | 'checkout_selection_save_url',
): string => {
    const value = getAppParam<string>(key);

    return typeof value === 'string' ? value : '';
};

export const handleCheckoutDeliverySelection = (): void => {
    const citySelectElement = <HTMLSelectElement | null>findElem('#checkout-city');
    const branchSelectElement = <HTMLSelectElement | null>findElem('#checkout-branch');
    const cityWarningElement = <HTMLElement | null>findElem('[data-checkout-city-warning]');
    const deliveryMethodOptions = <HTMLElement[] | []>findArrayElems('[data-checkout-delivery-method-option]');
    const deliveryMethodInputs = (<HTMLInputElement[] | []>(
        findArrayElems('[data-checkout-delivery-method-input]')
    )) as HTMLInputElement[];
    const citySearchUrl = resolveCheckoutUrl('checkout_city_search_url');
    const branchSearchUrl = resolveCheckoutUrl('checkout_branch_search_url');
    const selectionSaveUrl = resolveCheckoutUrl('checkout_selection_save_url');
    const selectionState = resolveCheckoutSelectionState();

    if (!citySelectElement || !branchSelectElement) {
        return;
    }

    const cityChoices = new Choices(citySelectElement, {
        allowHTML: false,
        duplicateItemsAllowed: false,
        itemSelectText: '',
        noChoicesText: String(getAppParam('checkout_no_cities_text') ?? ''),
        noResultsText: String(getAppParam('checkout_no_cities_text') ?? ''),
        position: 'auto',
        renderChoiceLimit: 100,
        searchEnabled: true,
        searchChoices: false,
        searchFloor: MIN_SEARCH_CITY_LENGTH,
        searchPlaceholderValue: citySelectElement.dataset.placeholder ?? '',
        searchResultLimit: 100,
        shouldSort: false,
    } as Partial<Options> & ChoiceSettings);

    const branchChoices = new Choices(branchSelectElement, {
        allowHTML: false,
        duplicateItemsAllowed: false,
        itemSelectText: '',
        noChoicesText: String(getAppParam('checkout_no_cities_text') ?? ''),
        noResultsText: String(getAppParam('checkout_no_cities_text') ?? ''),
        position: 'auto',
        renderChoiceLimit: 100,
        searchEnabled: true,
        searchChoices: false,
        searchFloor: MIN_SEARCH_POST_OFFICE_LENGTH,
        searchPlaceholderValue: branchSelectElement.dataset.placeholder ?? '',
        searchResultLimit: 100,
        shouldSort: false,
    } as Partial<Options> & ChoiceSettings);

    let currentDeliveryMethod = String(selectionState.delivery_method ?? '').trim();
    let currentCity = normalizeCityPayload(
        selectionState.city ?? readCityFromOption(citySelectElement.selectedOptions[0] ?? null),
    );
    let currentBranch = normalizeBranchPayload(
        selectionState.delivery_point ?? readBranchFromOption(branchSelectElement.selectedOptions[0] ?? null),
    );
    let latestCitySearchResults: CheckoutCitySearchItem[] = [];
    let latestBranchSearchResults: CheckoutBranchSearchItem[] = [];
    let currentBranchSearchValue = '';

    const hideWarning = (): void => {
        cityWarningElement?.classList.add('hidden');
        cityWarningElement?.setAttribute('aria-hidden', 'true');
    };

    const showWarning = (message: string): void => {
        if (!cityWarningElement) {
            return;
        }

        cityWarningElement.textContent = message;
        cityWarningElement.classList.remove('hidden');
        cityWarningElement.setAttribute('aria-hidden', 'false');
    };

    const applyCitySearchResults = (cities: CheckoutCitySearchItem[]): void => {
        latestCitySearchResults = cities;

        const choicesData: InputChoice[] = cities.map(
            (city: CheckoutCitySearchItem): InputChoice => buildChoiceItem(city),
        );

        if (
            currentCity &&
            !cities.some(
                (city: CheckoutCitySearchItem): boolean => city.city_description === currentCity?.city_description,
            )
        ) {
            choicesData.unshift(buildChoiceItem(currentCity));
        }

        cityChoices.clearChoices();
        cityChoices.setChoices(choicesData, 'value', 'label', true);

        if (currentCity) {
            cityChoices.setChoiceByValue(currentCity.city_description);
        }
    };

    const applyBranchSearchResults = (branches: CheckoutBranchSearchItem[]): void => {
        latestBranchSearchResults = branches;

        const choicesData: InputChoice[] = branches.map(
            (branch: CheckoutBranchSearchItem): InputChoice => buildBranchChoiceItem(branch),
        );

        if (
            currentBranch &&
            !branches.some(
                (branch: CheckoutBranchSearchItem): boolean => branch.branch_value === currentBranch?.branch_value,
            )
        ) {
            choicesData.unshift(buildBranchChoiceItem(currentBranch));
        }

        branchChoices.clearChoices();
        branchChoices.setChoices(choicesData, 'value', 'label', true);

        if (currentBranch) {
            branchChoices.setChoiceByValue(currentBranch.branch_value);
        }
    };

    const resolveSelectedCity = (): CheckoutCitySearchItem | null => {
        const selectedValue = citySelectElement.value.trim();

        if (selectedValue === '') {
            return null;
        }

        const matchedSearchCity = latestCitySearchResults.find(
            (city: CheckoutCitySearchItem): boolean => city.city_description === selectedValue,
        );

        if (matchedSearchCity) {
            return matchedSearchCity;
        }

        return readCityFromOption(citySelectElement.selectedOptions[0] ?? null);
    };

    const resolveSelectedBranch = (): CheckoutBranchSearchItem | null => {
        const selectedValue = branchSelectElement.value.trim();

        if (selectedValue === '') {
            return null;
        }

        const matchedSearchBranch = latestBranchSearchResults.find(
            (branch: CheckoutBranchSearchItem): boolean => branch.branch_value === selectedValue,
        );

        if (matchedSearchBranch) {
            return matchedSearchBranch;
        }

        return readBranchFromOption(branchSelectElement.selectedOptions[0] ?? null);
    };

    const updateDeliveryMethodVisibility = (): void => {
        const hasCity = currentCity !== null;
        const isNovaAvailable = Boolean(currentCity?.nova_poshta_city_id);
        const isUkrAvailable = Boolean(currentCity?.ukr_poshta_city_id);

        deliveryMethodOptions.forEach((optionElement: HTMLElement): void => {
            const method = optionElement.dataset.checkoutDeliveryMethodOption ?? '';
            const isVisible = !hasCity || (method === 'nova_poshta' ? isNovaAvailable : isUkrAvailable);

            optionElement.classList.toggle('hidden', !isVisible);

            if (!isVisible) {
                const input = <HTMLInputElement | null>findElem('[data-checkout-delivery-method-input]', optionElement);

                if (input?.checked) {
                    input.checked = false;
                }
            }
        });

        const availableMethods = [
            hasCity ? (isNovaAvailable ? 'nova_poshta' : null) : 'nova_poshta',
            hasCity ? (isUkrAvailable ? 'ukr_poshta' : null) : 'ukr_poshta',
        ].filter((method: string | null): method is CheckoutDeliveryMethod => method !== null);

        if (hasCity && availableMethods.length === 0) {
            showWarning(String(getAppParam('checkout_no_delivery_methods_text') ?? ''));
        } else if (hasCity) {
            hideWarning();
        }
    };

    const updateBranchAvailability = (): void => {
        if (!currentCity || !currentDeliveryMethod) {
            currentBranch = null;
            branchChoices.removeActiveItems();
            branchChoices.clearChoices();
            return;
        }

        if (
            currentBranch &&
            !isBranchCompatibleWithSelection(currentBranch, currentCity, currentDeliveryMethod)
        ) {
            currentBranch = null;
            branchChoices.removeActiveItems();
            branchChoices.clearChoices();
        }
    };

    const saveSelection = async (): Promise<void> => {
        if (isEmpty(selectionSaveUrl)) {
            return;
        }

        try {
            await fetchFunc(selectionSaveUrl, buildSelectionPayload(currentCity, currentDeliveryMethod, currentBranch));
        } catch {
            // Ignore transient network failures; checkout state remains usable locally.
        }
    };

    const syncSelectionToServer = debounce(async (): Promise<void> => {
        await saveSelection();
    }, 0);

    const searchCities = debounce(async (searchValue: string): Promise<void> => {
        const normalizedValue = searchValue.trim();

        if (normalizedValue.length < MIN_SEARCH_CITY_LENGTH || isEmpty(citySearchUrl)) {
            latestCitySearchResults = [];

            if (currentCity) {
                applyCitySearchResults([currentCity]);
            } else {
                cityChoices.clearChoices();
            }

            return;
        }

        try {
            const response = await fetchFunc<CheckoutCitySearchResponse>(
                `${citySearchUrl}?city_keyword=${encodeURIComponent(normalizedValue)}`,
                {},
                'GET',
            );
            const items = isArray(response?.items) ? response.items : [];

            latestCitySearchResults = items
                .map((item: CheckoutCitySearchItem): CheckoutCitySearchItem | null => normalizeCityPayload(item))
                .filter((item: CheckoutCitySearchItem | null): item is CheckoutCitySearchItem => item !== null);

            applyCitySearchResults(latestCitySearchResults);
        } catch {
            latestCitySearchResults = [];

            if (currentCity) {
                applyCitySearchResults([currentCity]);
            } else {
                cityChoices.clearChoices();
            }
        }
    }, SEARCH_DEBOUNCE_MS);

    const searchBranches = debounce(async (searchValue: string): Promise<void> => {
        const normalizedValue = searchValue.trim();

        if (!currentCity || !['nova_poshta', 'ukr_poshta'].includes(currentDeliveryMethod)) {
            currentBranchSearchValue = '';
            latestBranchSearchResults = [];
            branchChoices.clearChoices();
            showWarning(String(getAppParam('checkout_choose_city_first_text') ?? ''));
            return;
        }

        if (normalizedValue.length < MIN_SEARCH_POST_OFFICE_LENGTH || isEmpty(branchSearchUrl)) {
            currentBranchSearchValue = normalizedValue;
            latestBranchSearchResults = [];

            if (currentBranch && isBranchCompatibleWithSelection(currentBranch, currentCity, currentDeliveryMethod)) {
                applyBranchSearchResults([currentBranch]);
            } else {
                branchChoices.clearChoices();
            }

            return;
        }

        currentBranchSearchValue = normalizedValue;

        try {
            const response = await fetchFunc<CheckoutBranchSearchResponse>(
                branchSearchUrl,
                buildBranchSearchPayload(currentCity, currentDeliveryMethod, normalizedValue),
            );
            const items = isArray(response?.items) ? response.items : [];

            latestBranchSearchResults = items
                .map((item: CheckoutBranchSearchItem): CheckoutBranchSearchItem | null => normalizeBranchPayload(item))
                .filter((item: CheckoutBranchSearchItem | null): item is CheckoutBranchSearchItem => item !== null);

            applyBranchSearchResults(latestBranchSearchResults);
        } catch {
            latestBranchSearchResults = [];

            if (currentBranch && isBranchCompatibleWithSelection(currentBranch, currentCity, currentDeliveryMethod)) {
                applyBranchSearchResults([currentBranch]);
            } else {
                branchChoices.clearChoices();
            }
        }
    }, SEARCH_DEBOUNCE_MS);

    const handleCitySelectionChange = (): void => {
        currentCity = resolveSelectedCity();

        if (!currentCity) {
            latestCitySearchResults = [];
            latestBranchSearchResults = [];
            currentBranch = null;
            currentBranchSearchValue = '';
            updateDeliveryMethodVisibility();
            branchChoices.clearChoices();
            showWarning(String(getAppParam('checkout_choose_city_first_text') ?? ''));
            saveSelection();

            return;
        }

        hideWarning();
        updateDeliveryMethodVisibility();
        updateBranchAvailability();

        if (currentBranchSearchValue.length >= MIN_SEARCH_POST_OFFICE_LENGTH) {
            searchBranches(currentBranchSearchValue);
        } else if (
            currentBranch &&
            isBranchCompatibleWithSelection(currentBranch, currentCity, currentDeliveryMethod)
        ) {
            applyBranchSearchResults([currentBranch]);
        } else {
            branchChoices.clearChoices();
        }

        syncSelectionToServer();
    };

    const handleDeliveryMethodChange = (event: Event): void => {
        const target = event.target as HTMLInputElement | null;

        if (target?.type !== 'radio') {
            return;
        }

        currentDeliveryMethod = target.value;

        if (!currentCity) {
            target.checked = false;
            showWarning(String(getAppParam('checkout_choose_city_first_text') ?? ''));
            currentDeliveryMethod = '';
            currentBranch = null;
            branchChoices.clearChoices();

            return;
        }

        hideWarning();
        updateBranchAvailability();

        if (currentBranchSearchValue.length >= MIN_SEARCH_POST_OFFICE_LENGTH) {
            searchBranches(currentBranchSearchValue);
        } else if (
            currentBranch &&
            isBranchCompatibleWithSelection(currentBranch, currentCity, currentDeliveryMethod)
        ) {
            applyBranchSearchResults([currentBranch]);
        } else {
            branchChoices.clearChoices();
        }

        syncSelectionToServer();
    };

    const handleBranchSelectionChange = (): void => {
        currentBranch = resolveSelectedBranch();

        if (!currentCity) {
            currentBranch = null;
            branchChoices.clearChoices();
            showWarning(String(getAppParam('checkout_choose_city_first_text') ?? ''));

            return;
        }

        hideWarning();

        if (currentBranch === null) {
            syncSelectionToServer();

            return;
        }

        syncSelectionToServer();
    };

    citySelectElement.addEventListener('search', (event: Event): void => {
        const searchEvent = event as CustomEvent<{
            value: string;
            resultCount: number;
        }>;

        searchCities(searchEvent.detail?.value ?? '');
    });

    citySelectElement.addEventListener('change', handleCitySelectionChange);
    branchSelectElement.addEventListener('search', (event: Event): void => {
        const searchEvent = event as CustomEvent<{
            value: string;
            resultCount: number;
        }>;

        searchBranches(searchEvent.detail?.value ?? '');
    });
    branchSelectElement.addEventListener('change', handleBranchSelectionChange);

    deliveryMethodInputs.forEach((input: HTMLInputElement): void => {
        input.addEventListener('change', handleDeliveryMethodChange);
    });

    if (!currentDeliveryMethod) {
        const checkedDeliveryMethod = <HTMLInputElement | null>(
            findElem('[data-checkout-delivery-method-input]:checked')
        );
        currentDeliveryMethod = checkedDeliveryMethod?.value ?? '';
    }

    if (currentCity) {
        updateDeliveryMethodVisibility();
        updateBranchAvailability();
        hideWarning();
        if (currentBranch && isBranchCompatibleWithSelection(currentBranch, currentCity, currentDeliveryMethod)) {
            applyBranchSearchResults([currentBranch]);
        }
        syncSelectionToServer();
    } else {
        updateDeliveryMethodVisibility();
    }
};
