import { getAppParam } from '@ts-shared/lib/getAppParam.ts';
import { debounce, fetchFunc, findArrayElems, findElem, isArray, isEmpty } from '@ts-shared/lib/helpers.ts';
import Choices, { type InputChoice, type Options } from 'choices.js';

type CheckoutDeliveryMethod = 'nova_poshta' | 'ukr_poshta';

interface CheckoutCitySearchItem {
    city_description: string;
    nova_poshta_city_id: string | null;
    ukr_poshta_city_id: number | null;
    city_lat: number | null;
    city_lng: number | null;
}

interface CheckoutSelectionState {
    delivery_method?: string | null;
    city?: CheckoutCitySearchItem | null;
    delivery_point?: Record<string, unknown> | null;
}

interface CheckoutCitySearchResponse {
    items?: Record<string, unknown>[];
}

interface ChoiceSettings {
    allowHTML: boolean;
    searchFields: string[];
    shouldSort: boolean;
}

const MIN_SEARCH_LENGTH = 2;
const SEARCH_DEBOUNCE_MS = 700;

const toNumberOrNull = (value: unknown): number | null => {
    const numericValue = Number(value);

    return Number.isNaN(numericValue) ? null : numericValue;
};

const resolveCheckoutSelectionState = (): CheckoutSelectionState => {
    const state = getAppParam<CheckoutSelectionState>('checkout_selection_state');

    return state ?? {};
};

const resolveCheckoutUrl = (key: 'checkout_city_search_url' | 'checkout_selection_save_url'): string => {
    const value = getAppParam<string>(key);

    return typeof value === 'string' ? value : '';
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

const buildSelectionPayload = (city: CheckoutCitySearchItem | null, deliveryMethod: string): FormData => {
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

    payload.append('delivery_method', deliveryMethod);

    return payload;
};

export const handleCheckoutDeliverySelection = (): void => {
    const citySelectElement = <HTMLSelectElement | null>findElem('#checkout-city');
    const cityWarningElement = <HTMLElement | null>findElem('[data-checkout-city-warning]');
    const deliveryMethodOptions = <HTMLElement[] | []>findArrayElems('[data-checkout-delivery-method-option]');
    const deliveryMethodInputs = (<HTMLInputElement[] | []>(
        findArrayElems('[data-checkout-delivery-method-input]')
    )) as HTMLInputElement[];
    const citySearchUrl = resolveCheckoutUrl('checkout_city_search_url');
    const selectionSaveUrl = resolveCheckoutUrl('checkout_selection_save_url');
    const selectionState = resolveCheckoutSelectionState();

    if (!citySelectElement) {
        return;
    }

    const choices = new Choices(citySelectElement, {
        allowHTML: false,
        duplicateItemsAllowed: false,
        itemSelectText: '',
        noChoicesText: String(getAppParam('checkout_no_cities_text') ?? ''),
        noResultsText: String(getAppParam('checkout_no_cities_text') ?? ''),
        position: 'auto',
        renderChoiceLimit: 100,
        searchEnabled: true,
        searchChoices: false,
        searchFloor: MIN_SEARCH_LENGTH,
        searchPlaceholderValue: citySelectElement.dataset.placeholder ?? '',
        searchResultLimit: 100,
        shouldSort: false,
    } as Partial<Options> & ChoiceSettings);

    let currentCity = normalizeCityPayload(
        selectionState.city ?? readCityFromOption(citySelectElement.selectedOptions[0] ?? null),
    );
    let latestSearchResults: CheckoutCitySearchItem[] = [];

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

    const saveSelection = async (deliveryMethod: string): Promise<void> => {
        if (isEmpty(selectionSaveUrl)) {
            return;
        }

        try {
            await fetchFunc(selectionSaveUrl, buildSelectionPayload(currentCity, deliveryMethod));
        } catch {
            // Ignore transient network failures; checkout state remains usable locally.
        }
    };

    const applySearchResults = (cities: CheckoutCitySearchItem[]): void => {
        latestSearchResults = cities;

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

        choices.clearChoices();
        choices.setChoices(choicesData, 'value', 'label', true);

        if (currentCity) {
            choices.setChoiceByValue(currentCity.city_description);
        }
    };

    const resolveSelectedCity = (): CheckoutCitySearchItem | null => {
        const selectedValue = citySelectElement.value.trim();

        if (selectedValue === '') {
            return null;
        }

        const matchedSearchCity = latestSearchResults.find(
            (city: CheckoutCitySearchItem): boolean => city.city_description === selectedValue,
        );

        if (matchedSearchCity) {
            return matchedSearchCity;
        }

        return readCityFromOption(citySelectElement.selectedOptions[0] ?? null);
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

    const syncSelectionToServer = debounce(async (): Promise<void> => {
        const checkedDeliveryMethod = <HTMLInputElement | null>(
            findElem('[data-checkout-delivery-method-input]:checked')
        );
        const deliveryMethod = checkedDeliveryMethod?.value ?? '';

        await saveSelection(deliveryMethod);
    }, 0);

    const handleCitySelectionChange = (): void => {
        currentCity = resolveSelectedCity();

        if (!currentCity) {
            latestSearchResults = [];
            updateDeliveryMethodVisibility();
            showWarning(String(getAppParam('checkout_choose_city_first_text') ?? ''));
            saveSelection('');

            return;
        }

        hideWarning();
        updateDeliveryMethodVisibility();
        syncSelectionToServer();
    };

    const handleDeliveryMethodChange = (event: Event): void => {
        const target = event.target as HTMLInputElement | null;

        if (target?.type !== 'radio') {
            return;
        }

        if (!currentCity) {
            target.checked = false;
            showWarning(String(getAppParam('checkout_choose_city_first_text') ?? ''));

            return;
        }

        hideWarning();
        syncSelectionToServer();
    };

    const searchCities = debounce(async (searchValue: string): Promise<void> => {
        const normalizedValue = searchValue.trim();

        if (normalizedValue.length < MIN_SEARCH_LENGTH || isEmpty(citySearchUrl)) {
            latestSearchResults = [];

            if (currentCity) {
                applySearchResults([currentCity]);
            } else {
                choices.clearChoices();
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

            latestSearchResults = items
                .map((item: Record<string, unknown>): CheckoutCitySearchItem | null =>
                    normalizeCityPayload({
                        city_description: String(item.city_description ?? ''),
                        nova_poshta_city_id:
                            typeof item.nova_poshta_city_id === 'string' ? item.nova_poshta_city_id : null,
                        ukr_poshta_city_id: toNumberOrNull(item.ukr_poshta_city_id),
                        city_lat: toNumberOrNull(item.city_lat),
                        city_lng: toNumberOrNull(item.city_lng),
                    }),
                )
                .filter((item: CheckoutCitySearchItem | null): item is CheckoutCitySearchItem => item !== null);

            applySearchResults(latestSearchResults);
        } catch {
            latestSearchResults = [];

            if (currentCity) {
                applySearchResults([currentCity]);
            } else {
                choices.clearChoices();
            }
        }
    }, SEARCH_DEBOUNCE_MS);

    citySelectElement.addEventListener('search', (event: Event): void => {
        const searchEvent = event as CustomEvent<{
            value: string;
            resultCount: number;
        }>;

        searchCities(searchEvent.detail?.value ?? '');
    });

    citySelectElement.addEventListener('change', handleCitySelectionChange);

    deliveryMethodInputs.forEach((input: HTMLInputElement): void => {
        input.addEventListener('change', handleDeliveryMethodChange);
    });

    if (currentCity) {
        updateDeliveryMethodVisibility();
        hideWarning();
        syncSelectionToServer();
    } else {
        updateDeliveryMethodVisibility();
    }
};
