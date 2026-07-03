import { findArrayElems, findElem, isContainsClass } from '@ts-shared/lib/helpers.ts';
import HSOverlay from 'flyonui/src/js/plugins/overlay/index';

interface InitDrawerOptions {
    drawerSelector: string;
    triggerSelector: string;
    initializedClassName?: string;
}

/**
 * Initializes FlyonUI drawer (overlay) and binds open triggers.
 * Reusable for mobile menu, category filters, and other offcanvas panels.
 */
export const initDrawer = ({
    drawerSelector,
    triggerSelector,
    initializedClassName = 'is-drawer-trigger-initialized',
}: InitDrawerOptions): void => {
    const drawerElement = <HTMLElement | null>findElem(drawerSelector);

    if (!drawerElement) {
        return;
    }

    const drawerInstance = new HSOverlay(drawerElement);
    const triggerButtons = <HTMLElement[] | []>findArrayElems(triggerSelector);

    triggerButtons.forEach((triggerButton: HTMLElement): void => {
        if (isContainsClass(triggerButton, initializedClassName)) {
            return;
        }

        triggerButton.classList.add(initializedClassName);

        triggerButton.addEventListener('click', (): void => {
            drawerInstance.open();
        });
    });
};
