import { findArrayElems, isEmpty }     from "@ts-shared/lib/helpers.ts";
import HSDropdown                      from "flyonui/src/js/plugins/dropdown/index";
import type { IHTMLElementFloatingUI } from "flyonui/flyonui";

export const handleLanguageMenu = (): void => {
    const dropdownLangMenuEls       = <IHTMLElementFloatingUI[] | []>findArrayElems('.dropdown-lang-menu');
    const closeDropdownLangMenuBtns = <HTMLElement[] | []>findArrayElems('.close-dropdown-lang-menu-btn');

    if (isEmpty(dropdownLangMenuEls)) {
        return;
    }

    dropdownLangMenuEls.forEach((menuEl: IHTMLElementFloatingUI, index: number): void => {
        const dropdownInstance = new HSDropdown(menuEl);

        closeDropdownLangMenuBtns[index]?.addEventListener('click', (): void => {
            dropdownInstance.close();
        });
    });
};
