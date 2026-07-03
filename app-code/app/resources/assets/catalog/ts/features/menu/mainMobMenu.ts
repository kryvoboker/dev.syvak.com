import { initDrawer } from '@ts-shared/drawer/initDrawer.ts';

export const handleMainMobMenu = (): void => {
    initDrawer({
        drawerSelector: '.main-mob-menu',
        triggerSelector: '.open-main-mob-menu-btn',
    });
};
