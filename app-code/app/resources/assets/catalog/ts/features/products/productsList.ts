import { initDrawer } from '@ts-shared/drawer/initDrawer.ts';
import { initDropdown } from '@ts-shared/dropdown/initDropdown.ts';

/**
 * Category products-list interactions.
 * Currently initialized for placeholder UI until backend sorting/filtering is connected.
 */
export const handleCategoryProductsList = (): void => {
    initDropdown({
        dropdownSelector: '.category-sort-dropdown',
        closeButtonSelector: '.close-category-sort-dropdown-btn',
    });

    initDrawer({
        drawerSelector: '.category-filter-drawer',
        triggerSelector: '.open-category-filter-drawer-btn',
    });
};
