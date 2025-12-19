import { HSDropdown, type IHTMLElementFloatingUI } from "flyonui/flyonui";
import { findElem }                           from "@ts-shared/lib/helpers.ts";

export const handle = () => {
    const dropdownLangMenuEl = <IHTMLElementFloatingUI | null>findElem('.dropdown-lang-menu');
    const dropdownLangMenuBtnEl = <IHTMLElementFloatingUI | null>findElem('.dropdown-lang-menu-btn');

    if (!dropdownLangMenuEl || !dropdownLangMenuBtnEl) {
        return;
    }

    const dropdown = new HSDropdown(dropdownLangMenuEl);

    dropdownLangMenuBtnEl.addEventListener('click', () => {
        dropdown.open();
    });
};
