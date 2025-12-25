import { findElem } from "@ts-shared/lib/helpers.ts";
import HSOverlay    from "flyonui/src/js/plugins/overlay/index";

export const handleMobSearch = (): void => {
    const openMenuBtn     = <HTMLButtonElement | null>findElem('.open-mob-search-btn');
    const menuContainerEl = <HTMLElement | null>findElem('.mob-search');

    if (!menuContainerEl) {
        return;
    }

    const modalInstance = new HSOverlay(menuContainerEl);

    openMenuBtn?.addEventListener('click', (): void => {
        modalInstance.open();
    });
};
