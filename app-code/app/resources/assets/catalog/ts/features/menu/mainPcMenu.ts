import { initDrawer } from '@ts-shared/drawer/initDrawer.ts';
import { findArrayElems, findElem, isContainsClass } from '@ts-shared/lib/helpers.ts';

const pcMenuItemInitializedClassName = 'is-pc-menu-item-initialized';

export const handleMainPcMenu = (): void => {
    initDrawer({
        drawerSelector: '.main-pc-menu',
        triggerSelector: '.open-main-pc-menu-btn',
    });

    const menuElement = <HTMLElement | null>findElem('.main-pc-menu');

    if (!menuElement) {
        return;
    }

    const menuItems = <HTMLElement[]>findArrayElems<HTMLElement>('[data-pc-menu-item]', menuElement);
    const menuPreviews = <HTMLElement[]>findArrayElems<HTMLElement>('[data-pc-menu-preview]', menuElement);

    const showPreview = (menuItem: HTMLElement): void => {
        const previewTargetId = menuItem.dataset.previewTarget;

        if (!previewTargetId) {
            return;
        }

        menuPreviews.forEach((menuPreview: HTMLElement): void => {
            const isActive = menuPreview.id === previewTargetId;

            menuPreview.classList.toggle('hidden', !isActive);
            menuPreview.setAttribute('aria-hidden', String(!isActive));
        });
    };

    menuItems.forEach((menuItem: HTMLElement): void => {
        if (isContainsClass(menuItem, pcMenuItemInitializedClassName)) {
            return;
        }

        menuItem.classList.add(pcMenuItemInitializedClassName);
        menuItem.addEventListener('mouseenter', (): void => showPreview(menuItem));
        menuItem.addEventListener('focus', (): void => showPreview(menuItem));
    });

    if (menuItems[0]) {
        showPreview(menuItems[0]);
    }
};
