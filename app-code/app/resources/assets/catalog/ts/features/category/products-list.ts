import { initDrawer }   from "@ts-features/common/drawer.ts";
import { initDropdown } from "@ts-features/common/dropdown.ts";

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
