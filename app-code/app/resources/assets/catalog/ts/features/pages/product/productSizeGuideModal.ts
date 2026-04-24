import { initDrawer } from "@ts-shared/drawer/initDrawer.ts";

export const handleProductSizeGuideModal = (): void => {
    initDrawer({
        drawerSelector:  '.product-size-guide__modal',
        triggerSelector: '.product-size-guide__open-btn',
    });
};
