import { initDropdown } from "@ts-shared/dropdown/initDropdown.ts";

export const handleLanguageMenu = (): void => {
    initDropdown({
        dropdownSelector: '.dropdown-lang-menu',
        closeButtonSelector: '.close-dropdown-lang-menu-btn',
    });
};
