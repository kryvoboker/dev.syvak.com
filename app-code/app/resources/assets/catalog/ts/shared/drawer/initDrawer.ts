import { addClass, findArrayElems, findElem, isContainsClass } from '@ts-shared/lib/helpers.ts';
import HSOverlay from 'flyonui/src/js/plugins/overlay/index';

interface InitDrawerOptions {
    drawerSelector: string;
    triggerSelector?: string;
    initializedClassName?: string;
    onOpen?: (trigger: HTMLElement) => void;
}

export interface DrawerController {
    open: (trigger?: HTMLElement) => void;
    close: () => void;
}

/**
 * Initializes FlyonUI drawer (overlay) and binds open triggers.
 * Reusable for mobile menu, category filters, and other offcanvas panels.
 */
export const initDrawer = ({
    drawerSelector,
    triggerSelector = '',
    initializedClassName = 'is-drawer-trigger-initialized',
    onOpen,
}: InitDrawerOptions): DrawerController | null => {
    const drawerElement = <HTMLElement | null>findElem(drawerSelector);

    if (!drawerElement) {
        return null;
    }

    const drawerInstance = new HSOverlay(drawerElement);
    const drawerController: DrawerController = {
        open: (trigger?: HTMLElement): void => {
            if (trigger) {
                onOpen?.(trigger);
            }

            drawerInstance.open();
        },
        close: (): void => {
            drawerInstance.close();
        },
    };

    const triggerButtons = triggerSelector ? <HTMLElement[] | []>findArrayElems(triggerSelector) : [];

    triggerButtons.forEach((triggerButton: HTMLElement): void => {
        if (isContainsClass(triggerButton, initializedClassName)) {
            return;
        }

        addClass(triggerButton, initializedClassName);

        triggerButton.addEventListener('click', (): void => {
            drawerController.open(triggerButton);
        });
    });

    return drawerController;
};
