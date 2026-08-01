import { $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import { findElem, toggleClass } from '@ts-shared/lib/helpers.ts';
import type Choices from 'choices.js';
import type { InputChoice, InputGroup } from 'choices.js';
import { buildBranchChoiceItem, buildChoiceItem } from './checkoutChoices.ts';
import {
    type CheckoutBranchSearchItem,
    type CheckoutCitySearchItem,
    type CheckoutDeliveryMethod,
    isBranchCompatibleWithSelection,
    resolveBranchLabelText,
} from './checkoutData.ts';

interface CheckoutUiElements {
    cityWarningElement: HTMLElement | null;
    deliveryAddressWrapperElement: HTMLElement | null;
    deliveryAddressInputElement: HTMLInputElement | null;
    branchWrapperElement: HTMLElement | null;
    branchLabelElement: HTMLElement | null;
    mapButtonElement: HTMLButtonElement | null;
    deliveryMethodOptions: HTMLElement[];
    cityChoices: Choices;
    branchChoices: Choices;
}

interface CheckoutUiState {
    getCity: () => CheckoutCitySearchItem | null;
    getBranch: () => CheckoutBranchSearchItem | null;
    getDeliveryMethod: () => string;
    getLatestBranchResults: () => CheckoutBranchSearchItem[];
    setDeliveryMethod: (deliveryMethod: string) => void;
    setCity: (city: CheckoutCitySearchItem | null) => void;
    setBranch: (branch: CheckoutBranchSearchItem | null) => void;
    setDeliveryAddress: (deliveryAddress: string) => void;
    setLatestCityResults: (cities: CheckoutCitySearchItem[]) => void;
    setLatestBranchResults: (branches: CheckoutBranchSearchItem[]) => void;
    getChooseCityFirstText: () => string;
    getNoDeliveryMethodsText: () => string;
}

export const createCheckoutUi = (elements: CheckoutUiElements, state: CheckoutUiState) => {
    const updateMapButtonState = (): void => {
        if (!elements.mapButtonElement) {
            return;
        }

        const hasMapPoints = state.getLatestBranchResults().length > 0 || state.getBranch() !== null;
        const isMapAvailable =
            state.getCity() !== null &&
            state.getDeliveryMethod() !== '' &&
            state.getDeliveryMethod() !== 'nova_poshta_courier' &&
            hasMapPoints;

        elements.mapButtonElement.toggleAttribute('disabled', !isMapAvailable);

        toggleClass(elements.mapButtonElement, 'cursor-not-allowed', !isMapAvailable);
        toggleClass(elements.mapButtonElement, 'opacity-60', !isMapAvailable);
    };

    const hideWarning = (): void => {
        elements.cityWarningElement?.classList.add($HIDDEN_CLASS_NAME);
        elements.cityWarningElement?.setAttribute('aria-hidden', 'true');
    };

    const showWarning = (message: string): void => {
        if (!elements.cityWarningElement) {
            return;
        }

        elements.cityWarningElement.textContent = message;
        elements.cityWarningElement.classList.remove($HIDDEN_CLASS_NAME);
        elements.cityWarningElement.setAttribute('aria-hidden', 'false');
    };

    const updateCourierAddressVisibility = (): void => {
        const isCourierDelivery = state.getDeliveryMethod() === 'nova_poshta_courier';

        toggleClass(elements.deliveryAddressWrapperElement, $HIDDEN_CLASS_NAME, !isCourierDelivery);

        if (elements.deliveryAddressInputElement) {
            elements.deliveryAddressInputElement.required = isCourierDelivery;
        }
    };

    const updateBranchVisibility = (): void => {
        const deliveryMethod = state.getDeliveryMethod();
        const isBranchSearchAvailable =
            deliveryMethod !== '' && deliveryMethod !== 'nova_poshta_courier' && deliveryMethod !== 'pickup_store';

        toggleClass(elements.branchWrapperElement, $HIDDEN_CLASS_NAME, !isBranchSearchAvailable);

        if (elements.branchLabelElement) {
            elements.branchLabelElement.textContent = resolveBranchLabelText(deliveryMethod);
        }

        updateMapButtonState();
    };

    const applyCitySearchResults = (cities: CheckoutCitySearchItem[]): void => {
        state.setLatestCityResults(cities);

        const choicesData: (InputChoice | InputGroup)[] = cities.map(buildChoiceItem);
        const currentCity = state.getCity();

        if (currentCity && !cities.some((city) => city.city_description === currentCity.city_description)) {
            choicesData.unshift(buildChoiceItem(currentCity));
        }

        elements.cityChoices.clearChoices();
        elements.cityChoices.setChoices(choicesData, 'value', 'label', true);

        if (currentCity) {
            elements.cityChoices.setChoiceByValue(currentCity.city_description);
        }

        updateMapButtonState();
    };

    const applyBranchSearchResults = (branches: CheckoutBranchSearchItem[]): void => {
        state.setLatestBranchResults(branches);

        const choicesData: (InputChoice | InputGroup)[] = branches.map(buildBranchChoiceItem);
        const currentBranch = state.getBranch();

        if (currentBranch && !branches.some((branch) => branch.branch_value === currentBranch.branch_value)) {
            choicesData.unshift(buildBranchChoiceItem(currentBranch));
        }

        elements.branchChoices.clearChoices();
        elements.branchChoices.setChoices(choicesData, 'value', 'label', true);

        if (currentBranch) {
            elements.branchChoices.setChoiceByValue(currentBranch.branch_value);
        }

        updateMapButtonState();
    };

    const updateDeliveryMethodVisibility = (): void => {
        const city = state.getCity();
        const hasCity = city !== null;
        const isNovaAvailable = Boolean(city?.nova_poshta_city_id);
        const isUkrAvailable = Boolean(city?.ukr_poshta_city_id);

        elements.deliveryMethodOptions.forEach((optionElement): void => {
            const method = optionElement.dataset.checkoutDeliveryMethodOption ?? '';
            const isVisible =
                method === 'pickup_store' || !hasCity || (method === 'ukr_poshta' ? isUkrAvailable : isNovaAvailable);

            toggleClass(optionElement, $HIDDEN_CLASS_NAME, !isVisible);

            if (!isVisible) {
                const input = <HTMLInputElement | null>findElem('[data-checkout-delivery-method-input]', optionElement);

                if (input?.checked) {
                    input.checked = false;
                }
            }
        });

        const availableMethods = [
            hasCity ? (isNovaAvailable ? 'nova_poshta' : null) : 'nova_poshta',
            hasCity ? (isNovaAvailable ? 'nova_poshta_courier' : null) : 'nova_poshta_courier',
            hasCity ? (isNovaAvailable ? 'nova_poshta_poshtomat' : null) : 'nova_poshta_poshtomat',
            hasCity ? (isUkrAvailable ? 'ukr_poshta' : null) : 'ukr_poshta',
            'pickup_store',
        ].filter((method): method is CheckoutDeliveryMethod => method !== null);

        const currentDeliveryMethod = state.getDeliveryMethod();

        if (
            currentDeliveryMethod !== '' &&
            !availableMethods.includes(currentDeliveryMethod as CheckoutDeliveryMethod)
        ) {
            state.setDeliveryMethod('');
            state.setBranch(null);
            state.setDeliveryAddress('');

            if (elements.deliveryAddressInputElement) {
                elements.deliveryAddressInputElement.value = '';
            }
        }

        if (currentDeliveryMethod === 'pickup_store') {
            hideWarning();
        } else if (hasCity && availableMethods.length === 0) {
            showWarning(state.getNoDeliveryMethodsText());
        } else if (hasCity) {
            hideWarning();
        }

        updateCourierAddressVisibility();
        updateBranchVisibility();
    };

    const updateBranchAvailability = (): void => {
        updateBranchVisibility();
        updateCourierAddressVisibility();

        const city = state.getCity();
        const deliveryMethod = state.getDeliveryMethod();

        if (!city || !deliveryMethod || deliveryMethod === 'nova_poshta_courier' || deliveryMethod === 'pickup_store') {
            state.setBranch(null);
            elements.branchChoices.removeActiveItems();
            elements.branchChoices.clearChoices();
            updateMapButtonState();
            return;
        }

        const branch = state.getBranch();

        if (branch && !isBranchCompatibleWithSelection(branch, city, deliveryMethod)) {
            state.setBranch(null);
            elements.branchChoices.removeActiveItems();
            elements.branchChoices.clearChoices();
        }

        updateMapButtonState();
    };

    return {
        applyBranchSearchResults,
        applyCitySearchResults,
        hideWarning,
        showWarning,
        updateBranchAvailability,
        updateBranchVisibility,
        updateCourierAddressVisibility,
        updateMapButtonState,
        updateDeliveryMethodVisibility,
    };
};
