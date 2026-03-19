import { initDropdown } from "@ts-features/common/dropdown.ts";

export const handleLanguageMenu = (): void => {
    initDropdown({
        dropdownSelector: '.dropdown-lang-menu',
        closeButtonSelector: '.close-dropdown-lang-menu-btn',
    });
};
