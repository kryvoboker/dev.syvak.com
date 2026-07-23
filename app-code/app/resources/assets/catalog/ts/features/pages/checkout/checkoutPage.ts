import { getAppParam } from '@ts-shared/lib/getAppParam.ts';
import { findElem, toStringValue, toTrimmedString } from '@ts-shared/lib/helpers.ts';
import { createBranchChoices, createCityChoices } from './checkoutChoices.ts';
import {
    buildCheckoutMapPoints,
    type CheckoutBranchSearchItem,
    type CheckoutCitySearchItem,
    type CheckoutFormState,
    type CheckoutMapData,
    MIN_SEARCH_CITY_LENGTH,
    MIN_SEARCH_POST_OFFICE_LENGTH,
    normalizeBranchPayload,
    normalizeCityPayload,
    readBranchFromOption,
    readCityFromOption,
    resolveCheckoutSelectionState,
    resolveCheckoutUrl,
} from './checkoutData.ts';
import { getCheckoutDomElements } from './checkoutDom.ts';
import { bindCheckoutEvents } from './checkoutEvents.ts';
import { createCheckoutSearch } from './checkoutSearch.ts';
import { createCheckoutUi } from './checkoutUi.ts';

export const initializeCheckoutDeliveryLogic = (): void => {
    const {
        citySelectElement,
        branchSelectElement,
        deliveryAddressInputElement,
        cityWarningElement,
        deliveryAddressWrapperElement,
        branchWrapperElement,
        branchLabelElement,
        mapButtonElement,
        deliveryMethodOptions,
        deliveryMethodInputs,
        paymentMethodInputs,
        checkoutFormElement,
    } = getCheckoutDomElements();
    const citySearchUrl = resolveCheckoutUrl('checkout_city_search_url');
    const branchSearchUrl = resolveCheckoutUrl('checkout_branch_search_url');
    const selectionSaveUrl = resolveCheckoutUrl('checkout_selection_save_url');
    const selectionState = resolveCheckoutSelectionState();
    const checkoutMapData = getAppParam<CheckoutMapData>('checkout_map_data') ?? {};

    if (!citySelectElement || !branchSelectElement) {
        return;
    }

    const emptyChoicesText = toStringValue(getAppParam('checkout_no_cities_text'));
    const cityChoices = createCityChoices(citySelectElement, emptyChoicesText, MIN_SEARCH_CITY_LENGTH);
    const branchChoices = createBranchChoices(branchSelectElement, emptyChoicesText, MIN_SEARCH_POST_OFFICE_LENGTH);

    let currentDeliveryMethod = toTrimmedString(selectionState.delivery_method);
    let currentPaymentMethod = toTrimmedString(selectionState.payment_method);
    let currentCity = normalizeCityPayload(
        selectionState.city ?? readCityFromOption(citySelectElement.selectedOptions[0] ?? null),
    );
    let currentBranch = normalizeBranchPayload(
        selectionState.delivery_point ?? readBranchFromOption(branchSelectElement.selectedOptions[0] ?? null),
    );
    let currentDeliveryAddress = String(
        selectionState.delivery_address ?? deliveryAddressInputElement?.value ?? '',
    ).trim();
    const checkoutFormState: CheckoutFormState = {
        firstName: toTrimmedString(
            selectionState.first_name ??
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="first_name"]')?.value,
        ),
        lastName: toTrimmedString(
            selectionState.last_name ??
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="last_name"]')?.value,
        ),
        phone: toTrimmedString(
            selectionState.phone ?? checkoutFormElement?.querySelector<HTMLInputElement>('[name="phone"]')?.value,
        ),
        email: toTrimmedString(
            selectionState.email ?? checkoutFormElement?.querySelector<HTMLInputElement>('[name="email"]')?.value,
        ),
        comment: toTrimmedString(
            selectionState.comment ??
                checkoutFormElement?.querySelector<HTMLTextAreaElement>('[name="comment"]')?.value,
        ),
        promoCode: toTrimmedString(
            selectionState.promo_code ??
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="promo_code"]')?.value,
        ),
        noCall:
            selectionState.no_call === true ||
            checkoutFormElement?.querySelector<HTMLInputElement>('[name="no_call"]')?.checked === true,
    };
    let latestCitySearchResults: CheckoutCitySearchItem[] = [];
    let latestBranchSearchResults: CheckoutBranchSearchItem[] = [];
    let currentBranchSearchStateKey = '';

    const checkoutState = {
        get city(): CheckoutCitySearchItem | null {
            return currentCity;
        },
        set city(value: CheckoutCitySearchItem | null) {
            currentCity = value;
        },
        get branch(): CheckoutBranchSearchItem | null {
            return currentBranch;
        },
        set branch(value: CheckoutBranchSearchItem | null) {
            currentBranch = value;
        },
        get deliveryMethod(): string {
            return currentDeliveryMethod;
        },
        set deliveryMethod(value: string) {
            currentDeliveryMethod = value;
        },
        get paymentMethod(): string {
            return currentPaymentMethod;
        },
        set paymentMethod(value: string) {
            currentPaymentMethod = value;
        },
        get deliveryAddress(): string {
            return currentDeliveryAddress;
        },
        set deliveryAddress(value: string) {
            currentDeliveryAddress = value;
        },
        form: checkoutFormState,
        get branchSearchStateKey(): string {
            return currentBranchSearchStateKey;
        },
        set branchSearchStateKey(value: string) {
            currentBranchSearchStateKey = value;
        },
        get latestCityResults(): CheckoutCitySearchItem[] {
            return latestCitySearchResults;
        },
        set latestCityResults(value: CheckoutCitySearchItem[]) {
            latestCitySearchResults = value;
        },
        get latestBranchResults(): CheckoutBranchSearchItem[] {
            return latestBranchSearchResults;
        },
        set latestBranchResults(value: CheckoutBranchSearchItem[]) {
            latestBranchSearchResults = value;
        },
    };

    const checkoutUi = createCheckoutUi(
        {
            cityWarningElement,
            deliveryAddressWrapperElement,
            deliveryAddressInputElement,
            branchWrapperElement,
            branchLabelElement,
            mapButtonElement,
            deliveryMethodOptions,
            cityChoices,
            branchChoices,
        },
        {
            getCity: (): CheckoutCitySearchItem | null => checkoutState.city,
            getBranch: (): CheckoutBranchSearchItem | null => checkoutState.branch,
            getDeliveryMethod: (): string => checkoutState.deliveryMethod,
            getLatestBranchResults: (): CheckoutBranchSearchItem[] => checkoutState.latestBranchResults,
            setDeliveryMethod: (deliveryMethod: string): void => {
                checkoutState.deliveryMethod = deliveryMethod;
            },
            setCity: (city: CheckoutCitySearchItem | null): void => {
                checkoutState.city = city;
            },
            setBranch: (branch: CheckoutBranchSearchItem | null): void => {
                checkoutState.branch = branch;
            },
            setDeliveryAddress: (deliveryAddress: string): void => {
                checkoutState.deliveryAddress = deliveryAddress;
            },
            setLatestCityResults: (cities: CheckoutCitySearchItem[]): void => {
                checkoutState.latestCityResults = cities;
            },
            setLatestBranchResults: (branches: CheckoutBranchSearchItem[]): void => {
                checkoutState.latestBranchResults = branches;
            },
            getChooseCityFirstText: (): string => toStringValue(getAppParam('checkout_choose_city_first_text')),
            getNoDeliveryMethodsText: (): string => toStringValue(getAppParam('checkout_no_delivery_methods_text')),
        },
    );
    const {
        applyBranchSearchResults,
        applyCitySearchResults,
        hideWarning,
        showWarning,
        updateBranchAvailability,
        updateBranchVisibility,
        updateCourierAddressVisibility,
        updateMapButtonState,
        updateDeliveryMethodVisibility,
    } = checkoutUi;

    const resolveSelectedCity = (): CheckoutCitySearchItem | null => {
        const selectedValue = citySelectElement.value.trim();

        if (selectedValue === '') {
            return null;
        }

        return (
            latestCitySearchResults.find((city) => city.city_description === selectedValue) ??
            readCityFromOption(citySelectElement.selectedOptions[0] ?? null)
        );
    };

    const resolveSelectedBranch = (): CheckoutBranchSearchItem | null => {
        const selectedValue = branchSelectElement.value.trim();

        if (selectedValue === '') {
            return null;
        }

        return (
            latestBranchSearchResults.find((branch) => branch.branch_value === selectedValue) ??
            readBranchFromOption(branchSelectElement.selectedOptions[0] ?? null)
        );
    };

    const checkoutSearch = createCheckoutSearch({
        citySearchUrl,
        branchSearchUrl,
        selectionSaveUrl,
        state: checkoutState,
        applyCityResults: applyCitySearchResults,
        applyBranchResults: applyBranchSearchResults,
        updateMapButtonState,
    });
    const { loadBranches, searchCities, syncSelectionToServer } = checkoutSearch;

    const handleCitySelectionChange = (): void => {
        currentCity = resolveSelectedCity();

        if (!currentCity) {
            latestCitySearchResults = [];
            latestBranchSearchResults = [];
            currentBranch = null;
            currentBranchSearchStateKey = '';
            updateDeliveryMethodVisibility();
            branchChoices.clearChoices();
            showWarning(toStringValue(getAppParam('checkout_choose_city_first_text')));
            syncSelectionToServer();

            return;
        }

        hideWarning();
        updateDeliveryMethodVisibility();
        updateBranchAvailability();
        loadBranches();
        updateMapButtonState();

        syncSelectionToServer();
    };

    const handleDeliveryMethodChange = (event: Event): void => {
        const target = event.target as HTMLInputElement | null;

        if (target?.type !== 'radio') {
            return;
        }

        const wasPickupStoreDelivery = currentDeliveryMethod === 'pickup_store';
        currentDeliveryMethod = target.value;

        if (currentDeliveryMethod === 'pickup_store') {
            currentCity = null;
            currentBranch = null;
            currentDeliveryAddress = toTrimmedString(deliveryAddressInputElement?.value);
            cityChoices.removeActiveItems();
            cityChoices.clearChoices();
            branchChoices.removeActiveItems();
            branchChoices.clearChoices();
            updateDeliveryMethodVisibility();
            updateBranchAvailability();
            syncSelectionToServer();

            return;
        }

        if (!currentCity && !wasPickupStoreDelivery) {
            target.checked = false;
            showWarning(toStringValue(getAppParam('checkout_choose_city_first_text')));
            currentDeliveryMethod = '';
            currentBranch = null;
            currentDeliveryAddress = '';
            branchChoices.clearChoices();
            updateCourierAddressVisibility();
            updateBranchVisibility();

            return;
        }

        hideWarning();
        currentDeliveryAddress = '';

        if (deliveryAddressInputElement) {
            deliveryAddressInputElement.value = '';
        }

        updateBranchAvailability();
        updateDeliveryMethodVisibility();
        loadBranches();
        updateMapButtonState();

        syncSelectionToServer();
    };

    const handlePaymentMethodChange = (event: Event): void => {
        const target = event.target as HTMLInputElement | null;

        if (target?.type !== 'radio') {
            return;
        }

        currentPaymentMethod = target.value.trim();
        syncSelectionToServer();
    };

    const handleDeliveryAddressChange = (): void => {
        currentDeliveryAddress = toTrimmedString(deliveryAddressInputElement?.value);

        if (currentDeliveryMethod === 'nova_poshta_courier') {
            syncSelectionToServer();
        }
    };

    const handleBranchSelectionChange = (): void => {
        currentBranch = resolveSelectedBranch();

        if (!currentCity) {
            currentBranch = null;
            branchChoices.clearChoices();
            showWarning(toStringValue(getAppParam('checkout_choose_city_first_text')));

            return;
        }

        hideWarning();

        if (currentBranch === null) {
            syncSelectionToServer();

            return;
        }

        syncSelectionToServer();
    };

    const handleMapBranchSelection = (mapPointId: string): void => {
        const selectedBranch =
            latestBranchSearchResults.find(
                (branch: CheckoutBranchSearchItem): boolean => branch.branch_value === mapPointId,
            ) ?? (currentBranch?.branch_value === mapPointId ? currentBranch : null);

        if (!selectedBranch || !currentCity) {
            return;
        }

        currentBranch = selectedBranch;
        branchChoices.setChoiceByValue(selectedBranch.branch_value);
        hideWarning();
        syncSelectionToServer();
        updateMapButtonState();
    };

    const openCheckoutMap = async (): Promise<void> => {
        if (!currentCity) {
            showWarning(toStringValue(getAppParam('checkout_choose_city_first_text')));

            return;
        }

        const branchCandidates =
            latestBranchSearchResults.length > 0 ? latestBranchSearchResults : currentBranch ? [currentBranch] : [];
        const mapPoints = buildCheckoutMapPoints(branchCandidates);

        if (mapPoints.length === 0) {
            return;
        }

        const module = await import('@ts-features/pages/checkout/checkoutLeafletMap.ts');

        module.openCheckoutLeafletMap({
            city: currentCity,
            points: mapPoints,
            selectedPointId: currentBranch?.branch_value ?? mapPoints[0]?.id ?? null,
            selectedDeliveryMethod: currentDeliveryMethod,
            markerIcons: checkoutMapData.marker_icons ?? null,
            texts: checkoutMapData.texts ?? null,
            callback: handleMapBranchSelection,
        });
    };

    const bindMapButton = (): void => {
        if (!mapButtonElement || mapButtonElement.dataset.checkoutMapBound === '1') {
            return;
        }

        mapButtonElement.dataset.checkoutMapBound = '1';

        mapButtonElement.addEventListener('click', (): void => {
            void openCheckoutMap();
        });
    };

    bindCheckoutEvents({
        citySelectElement,
        branchSelectElement,
        deliveryAddressInputElement,
        deliveryMethodInputs,
        paymentMethodInputs,
        checkoutFormElement,
        onCitySearch: (value: string): void => searchCities(value),
        onCityChange: handleCitySelectionChange,
        onBranchChange: handleBranchSelectionChange,
        onDeliveryAddressChange: handleDeliveryAddressChange,
        onDeliveryMethodChange: handleDeliveryMethodChange,
        onPaymentMethodChange: handlePaymentMethodChange,
        onCheckoutFormChange: (): void => {
            checkoutFormState.firstName = toTrimmedString(
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="first_name"]')?.value,
            );
            checkoutFormState.lastName = toTrimmedString(
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="last_name"]')?.value,
            );
            checkoutFormState.phone = toTrimmedString(
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="phone"]')?.value,
            );
            checkoutFormState.email = toTrimmedString(
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="email"]')?.value,
            );
            checkoutFormState.comment = toTrimmedString(
                checkoutFormElement?.querySelector<HTMLTextAreaElement>('[name="comment"]')?.value,
            );
            checkoutFormState.promoCode = toTrimmedString(
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="promo_code"]')?.value,
            );
            checkoutFormState.noCall =
                checkoutFormElement?.querySelector<HTMLInputElement>('[name="no_call"]')?.checked === true;
            syncSelectionToServer();
        },
    });

    if (!currentPaymentMethod) {
        const checkedPaymentMethod = <HTMLInputElement | null>findElem('[data-checkout-payment-method-input]:checked');
        currentPaymentMethod = checkedPaymentMethod?.value ?? '';
    }

    bindMapButton();

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
        loadBranches();
        syncSelectionToServer();
    } else {
        updateDeliveryMethodVisibility();
        updateBranchVisibility();
        updateCourierAddressVisibility();
        updateMapButtonState();
    }
};
