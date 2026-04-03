import { initDrawer } from "@ts-features/common/drawer.ts";

export const handleMainMobMenu = (): void => {
    initDrawer({
        drawerSelector: '.main-mob-menu',
        triggerSelector: '.open-main-mob-menu-btn',
    });
};
