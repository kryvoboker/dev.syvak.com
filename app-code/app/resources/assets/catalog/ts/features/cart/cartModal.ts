import {
    addCartItem,
    loadCartSnapshot,
    removeCartItem,
    toggleCartLoader,
    updateCartItemQuantity,
} from '@ts-features/cart/cartCrud.ts';
import { getCartMode, setCartMode } from '@ts-features/cart/cartModeStorage.ts';
import type { CartMode } from '@ts-features/cart/cartTypes.ts';
import { Cart } from '@ts-features/cart/constants.ts';
import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';
import { initDrawer } from '@ts-shared/drawer/initDrawer.ts';
import { findArrayElems, findElem, getClosestParentEl, sprintF } from '@ts-shared/lib/helpers.ts';

import $FAST_ORDER = Cart.$FAST_ORDER;
import $REGULAR = Cart.$REGULAR;

const openSelectedCartDrawer = async (mode: CartMode): Promise<void> => {
    const selector = mode === $FAST_ORDER ? '.open-cart-modal-fast-order-trigger' : '.open-cart-modal-regular-trigger';
    const trigger = <HTMLButtonElement | null>findElem(selector);

    if (!trigger) {
        return;
    }

    trigger.click();

    toggleCartLoader(true);

    await loadCartSnapshot(mode).finally((): void => toggleCartLoader(false));

    const accordionElement = <HTMLElement | null>findElem('[data-cart-extra-items-accordion]');

    if (accordionElement) {
        initAccordion(accordionElement);
    }

    updateSelectedCartItemsSummary(mode);
};

const updateSelectedCartItemsSummary = (mode: CartMode): void => {
    const cartRoot = <HTMLElement | null>findElem(`[data-cart-root][data-cart-mode="${mode}"]`);

    if (!cartRoot) {
        return;
    }

    const itemCheckboxes = (<HTMLInputElement[] | []>(
        findArrayElems('[data-cart-item-select]', cartRoot)
    )) as HTMLInputElement[];
    const selectAllCheckbox = <HTMLInputElement | null>findElem('[data-cart-select-all]', cartRoot);
    const summaryElement = <HTMLElement | null>findElem('[data-cart-selected-summary]', cartRoot);

    const totalCount = itemCheckboxes.length;
    const selectedCount = itemCheckboxes.filter((checkbox: HTMLInputElement): boolean => checkbox.checked).length;
    const removeSelectedButton = <HTMLElement | null>findElem('[data-remove-selected-cart-items]', cartRoot);

    if (selectAllCheckbox) {
        selectAllCheckbox.checked = totalCount > 0 && selectedCount === totalCount;
        selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < totalCount;
    }

    if (summaryElement) {
        const template: string = summaryElement.dataset.template ?? 'Вибрано %d з %d';

        summaryElement.textContent = sprintF(template, selectedCount, totalCount);
    }

    if (removeSelectedButton) {
        removeSelectedButton.classList.toggle('hidden', selectedCount === 0);
    }
};

const bindAddToCartButtons = (): void => {
    if (document.body.dataset.cartAddBound === '1') {
        return;
    }

    document.body.dataset.cartAddBound = '1';

    document.addEventListener('click', async (event: Event): Promise<void> => {
        const target = event.target as HTMLElement;
        const regularButton = <HTMLElement | null>getClosestParentEl('[data-add-to-cart]', target);
        const fastOrderButton = <HTMLElement | null>getClosestParentEl('[data-fast-order]', target);

        if (regularButton === null && fastOrderButton === null) {
            return;
        }

        const isFastOrder: boolean = fastOrderButton !== null;
        const sourceButton: HTMLElement | null = isFastOrder ? fastOrderButton : regularButton;

        if (!sourceButton) {
            return;
        }

        const rawVariantId: string | undefined = isFastOrder
            ? fastOrderButton?.dataset?.fastOrder
            : regularButton?.dataset?.addToCart;
        const variantId: number = Number(rawVariantId ?? 0);

        if (!Number.isInteger(variantId) || variantId <= 0) {
            return;
        }

        const mode: CartMode = isFastOrder ? $FAST_ORDER : $REGULAR;

        setCartMode(mode);
        toggleCartLoader(true);

        await addCartItem(variantId, mode).finally((): void => toggleCartLoader(false));

        await openSelectedCartDrawer(mode);
    });
};

const bindMutationHandlers = (): void => {
    if (document.body.dataset.cartMutationBound === '1') {
        return;
    }

    document.body.dataset.cartMutationBound = '1';

    document.addEventListener('change', async (event: Event): Promise<void> => {
        const target = event.target as HTMLElement;
        const itemSelectCheckbox = <HTMLInputElement | null>getClosestParentEl('[data-cart-item-select]', target);
        const selectAllCheckbox = <HTMLInputElement | null>getClosestParentEl('[data-cart-select-all]', target);

        if (itemSelectCheckbox) {
            const cartRoot = <HTMLElement | null>getClosestParentEl('[data-cart-root]', itemSelectCheckbox);

            if (cartRoot) {
                updateSelectedCartItemsSummary((cartRoot.dataset.cartMode as CartMode) ?? $REGULAR);
            }

            return;
        }

        if (selectAllCheckbox) {
            const cartRoot = <HTMLElement | null>getClosestParentEl('[data-cart-root]', selectAllCheckbox);

            if (cartRoot) {
                const itemCheckboxes = (<HTMLInputElement[] | []>(
                    findArrayElems('[data-cart-item-select]', cartRoot)
                )) as HTMLInputElement[];

                itemCheckboxes.forEach((checkbox: HTMLInputElement): void => {
                    checkbox.checked = selectAllCheckbox.checked;
                });

                updateSelectedCartItemsSummary((cartRoot.dataset.cartMode as CartMode) ?? $REGULAR);
            }
            return;
        }

        const quantityInput = <HTMLInputElement | null>getClosestParentEl('[data-cart-item-quantity]', target);

        if (!quantityInput) {
            return;
        }

        const mode: CartMode = getCartMode();
        const cartId: number = Number(quantityInput.dataset.cartId ?? 0);
        const quantity: number = Number(quantityInput.value ?? 1);

        if (!Number.isInteger(cartId) || cartId <= 0) {
            return;
        }

        toggleCartLoader(true);

        await updateCartItemQuantity(cartId, Math.max(1, quantity), mode).finally((): void => toggleCartLoader(false));

        const accordionElement = <HTMLElement | null>findElem('[data-cart-extra-items-accordion]');

        if (accordionElement) {
            initAccordion(accordionElement);
        }

        updateSelectedCartItemsSummary(mode);
    });

    document.addEventListener('click', async (event: Event): Promise<void> => {
        const target = event.target as HTMLElement;
        const removeSelectedButton = <HTMLElement | null>(
            getClosestParentEl('[data-remove-selected-cart-items]', target)
        );

        if (removeSelectedButton) {
            const cartRoot = <HTMLElement | null>getClosestParentEl('[data-cart-root]', removeSelectedButton);

            if (!cartRoot) {
                return;
            }

            const mode: CartMode = (cartRoot.dataset.cartMode as CartMode) ?? getCartMode();
            const selectedCheckboxes = (<HTMLInputElement[] | []>(
                findArrayElems('[data-cart-item-select]:checked', cartRoot)
            )) as HTMLInputElement[];
            const selectedCartIds = selectedCheckboxes
                .map((checkbox: HTMLInputElement): number => Number(checkbox.dataset.cartId ?? 0))
                .filter((cartId: number): boolean => Number.isInteger(cartId) && cartId > 0);

            if (selectedCartIds.length === 0) {
                return;
            }

            toggleCartLoader(true);

            try {
                for (const cartId of selectedCartIds) {
                    await removeCartItem(cartId, mode);
                }
            } finally {
                toggleCartLoader(false);
            }

            const accordionElement = <HTMLElement | null>findElem('[data-cart-extra-items-accordion]');

            if (accordionElement) {
                initAccordion(accordionElement);
            }

            updateSelectedCartItemsSummary(mode);
            return;
        }

        const removeButton = <HTMLElement | null>getClosestParentEl('[data-remove-cart-item]', target);

        if (!removeButton) {
            return;
        }

        const cartRoot = <HTMLElement | null>getClosestParentEl('[data-cart-root]', removeButton);
        const mode: CartMode = (cartRoot?.dataset?.cartMode as CartMode) ?? getCartMode();
        const cartId: number = Number(removeButton.dataset.cartId ?? 0);

        if (!Number.isInteger(cartId) || cartId <= 0) {
            return;
        }

        toggleCartLoader(true);

        await removeCartItem(cartId, mode).finally((): void => toggleCartLoader(false));

        const accordionElement = <HTMLElement | null>findElem('[data-cart-extra-items-accordion]');

        if (accordionElement) {
            initAccordion(accordionElement);
        }

        updateSelectedCartItemsSummary(mode);
    });
};

const bindOpenCartButton = (): void => {
    const openButton = <HTMLButtonElement | null>findElem('#open-cart-modal-btn');

    if (!openButton || openButton.dataset.cartOpenBound === '1') {
        return;
    }

    openButton.dataset.cartOpenBound = '1';

    openButton.addEventListener('click', async (): Promise<void> => {
        const mode: CartMode = getCartMode();

        await openSelectedCartDrawer(mode);
    });
};

export const handleCartModal = (): void => {
    initDrawer({
        drawerSelector: '#cart-modal',
        triggerSelector: '.open-cart-modal-regular-trigger',
    });

    initDrawer({
        drawerSelector: '#fast-order-cart-modal',
        triggerSelector: '.open-cart-modal-fast-order-trigger',
    });

    bindOpenCartButton();
    bindAddToCartButtons();
    bindMutationHandlers();
    updateSelectedCartItemsSummary($REGULAR);
    updateSelectedCartItemsSummary($FAST_ORDER);
};
