import { findElem } from "@ts-shared/lib/helpers.ts";
import HSOverlay    from "flyonui/src/js/plugins/overlay/index";

export const handleMainMobMenu = (): void => {
    const openMenuBtn     = <HTMLButtonElement | null>findElem('.open-main-mob-menu-btn');
    const menuContainerEl = <HTMLElement | null>findElem('.main-mob-menu');

    if (!menuContainerEl) {
        return;
    }

    const modalInstance = new HSOverlay(menuContainerEl);

    openMenuBtn?.addEventListener('click', (): void => {
        modalInstance.open()
                     ?.catch((error: Error): void => console.error(error.message));
    });
};
