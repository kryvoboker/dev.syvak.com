import {
    $CART_PAGE_TYPE,
    $CATEGORY_PAGE_TYPE,
    $CHECKOUT_PAGE_TYPE,
    $HOME_PAGE_TYPE,
    $PRODUCT_PAGE_TYPE,
    $SEARCH_PAGE_TYPE,
    $THANK_YOU_PAGE_TYPE,
} from '@ts-shared/lib/constants.ts';

document.addEventListener('DOMContentLoaded', (): void => {
    window.$hsDropdownCollection = window.$hsDropdownCollection || [];
    window.$hsOverlayCollection = window.$hsOverlayCollection || [];
    window.$hsAccordionCollection = window.$hsAccordionCollection || [];
    window.$hsCarouselCollection = window.$hsCarouselCollection || [];
    const pageType: string | null = window.app_params?.page_type ?? null;

    if (document.querySelector('[data-main-carousel]')) {
        import('@carousel-ts/main.ts');
    }

    if (document.querySelector('[data-products-carousel]')) {
        import('@products-carousel-ts/main.ts');
    }

    import('@ts-shared/lib/helpers.ts').then((module) => {
        const goToPreviousPageBtnEl = <HTMLButtonElement | null>module.findElem('.go-to-previous-page__btn');

        goToPreviousPageBtnEl?.addEventListener('click', (): void => {
            module.goBack(location.origin);
        });
    });

    import('@ts-shared/lib/validateForm.ts').then((module) => module.handleValidateForms());

    import('@ts-features/menu/language.ts').then((module) => module.handleLanguageMenu());

    import('@ts-features/menu/mainMobMenu.ts').then((module) => module.handleMainMobMenu());

    import('@ts-features/search/mobSearch.ts').then((module) => {
        module.handleMobSearch({
            openSearchBtn: '.open-mob-search-btn',
            searchContainer: '.mob-search-container',
            searchInput: '.mob-search-input',
            searchResults: '.mob-search-results',
            searchForm: '.mob-search-form',
        });

        module.handleMobSearch({
            openSearchBtn: '.open-pc-search-btn',
            searchContainer: '.pc-search-container',
            searchInput: '.pc-search-input',
            searchResults: '.pc-search-results',
            searchForm: '.pc-search-form',
        });
    });

    if (pageType === $HOME_PAGE_TYPE) {
        import('@carousel-ts/main.ts').then((module) => module.handleCarousel());
        import('@products-carousel-ts/main.ts').then((module) => module.handleProductsCarousel());
    } else if (pageType === $CATEGORY_PAGE_TYPE) {
        import('@ts-features/common/products/productsList.ts').then((module) => module.handleCategoryProductsList());

        import('@ts-features/common/products/productsFilter.ts').then((module) => module.handleProductsFilter());
    } else if (pageType === $PRODUCT_PAGE_TYPE) {
        import('@ts-features/pages/product/productCarousel.ts').then((module) => module.handleProductCarousel());

        import('@ts-features/pages/product/productImageGallery.ts').then((module) =>
            module.handleProductImageGallery(),
        );

        import('@ts-features/pages/product/productSizeGuideModal.ts').then((module) =>
            module.handleProductSizeGuideModal(),
        );
    }

    if (pageType === $CATEGORY_PAGE_TYPE || pageType === $SEARCH_PAGE_TYPE) {
        import('@ts-features/common/products/loadMoreProducts.ts').then((module) => module.handleLoadMoreProducts());
    }

    if (pageType === $CART_PAGE_TYPE) {
        import('@ts-features/cart/cartModal.ts').then((module) => module.handleCartModal());

        import('@ts-features/cart/cartPage.ts').then((module) => module.handleCartPage());
    } else {
        import('@ts-features/cart/cartModal.ts').then((module) => module.handleCartModal());

        import('@ts-features/cart/fastOrderModal.ts').then((module) => module.handleFastOrderModal());

        if (pageType === $CHECKOUT_PAGE_TYPE) {
            import('@ts-features/order/index.ts').then((module) => module.handleCheckoutPage());
            import('@bank-transfer-ts/main.ts');
            import('@pickup-ts/main.ts');
            import('@ukr-poshta-ts/main.ts');
            import('@wayforpay-ts/main.ts');
        }
    }

    if (pageType === $THANK_YOU_PAGE_TYPE) {
        import('@ts-features/pages/thank-you/thankYouPage.ts').then((module) => module.handleThankYouPage());
    }
});
