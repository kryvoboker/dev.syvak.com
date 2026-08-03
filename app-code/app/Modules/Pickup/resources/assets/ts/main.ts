import { $FLEX_CLASS_NAME, $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import { findElem, getTextContent, toggleClass, toTrimmedString } from '@ts-shared/lib/helpers.ts';

const handlePickupStoreVisibility = (): void => {
    const cityWrapper = <HTMLElement | null>findElem('[data-checkout-city-wrapper]');
    const branchWrapper = <HTMLElement | null>findElem('[data-checkout-branch-wrapper]');
    const citySelect = <HTMLSelectElement | null>findElem('#checkout-city');
    const deliveryAddressInput = <HTMLInputElement | null>findElem('#checkout-delivery-address');
    const pickupContent = <HTMLElement | null>findElem('[data-pickup-store-content]');
    const pickupAddress = <HTMLElement | null>findElem('[data-pickup-store-address]');

    if (!pickupContent) {
        return;
    }

    const isPickupSelected =
        findElem<HTMLInputElement>('[data-checkout-delivery-method-input][value="pickup_store"]:checked') !== null;

    toggleClass(cityWrapper, $HIDDEN_CLASS_NAME, isPickupSelected);
    toggleClass(branchWrapper, $HIDDEN_CLASS_NAME, isPickupSelected);
    toggleClass(pickupContent, $HIDDEN_CLASS_NAME, !isPickupSelected);
    toggleClass(pickupContent, $FLEX_CLASS_NAME, isPickupSelected);
    pickupContent.hidden = !isPickupSelected;

    if (citySelect) {
        citySelect.required = !isPickupSelected;
    }

    if (deliveryAddressInput) {
        deliveryAddressInput.required = false;

        if (isPickupSelected) {
            deliveryAddressInput.value = toTrimmedString(getTextContent(pickupAddress));
        } else {
            deliveryAddressInput.value = '';
        }
    }
};

const initializePickupStoreVisibility = (): void => {
    document.addEventListener('change', (event: Event): void => {
        const target = event.target as HTMLElement | null;

        if (target?.matches('[data-checkout-delivery-method-input]')) {
            handlePickupStoreVisibility();
        }
    });

    handlePickupStoreVisibility();
};

document.addEventListener('checkout:initialized', handlePickupStoreVisibility);

initializePickupStoreVisibility();
