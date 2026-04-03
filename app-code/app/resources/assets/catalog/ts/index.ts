import { $CATEGORY_PAGE_TYPE, $SEARCH_PAGE_TYPE } from "@ts-shared/lib/constants.ts";

document.addEventListener('DOMContentLoaded', (): void => {
    window.$hsDropdownCollection  = window.$hsDropdownCollection || [];
    window.$hsOverlayCollection   = window.$hsOverlayCollection || [];
    const pageType: string | null = window.app_params?.page_type ?? null;

    import('@ts-shared/lib/validateForm.ts')
        .then(module => module.handleValidateForms());

    import('@ts-features/menu/language.ts')
        .then(module => module.handleLanguageMenu());

    import('@ts-features/menu/mainMobMenu.ts')
        .then(module => module.handleMainMobMenu());

    import('@ts-features/search/mobSearch.ts')
        .then(module => {
            module.handleMobSearch({
                openSearchBtn:   '.open-mob-search-btn',
                searchContainer: '.mob-search-container',
                searchInput:     '.mob-search-input',
                searchResults:   '.mob-search-results',
                searchForm:      '.mob-search-form',
            });

            module.handleMobSearch({
                openSearchBtn:   '.open-pc-search-btn',
                searchContainer: '.pc-search-container',
                searchInput:     '.pc-search-input',
                searchResults:   '.pc-search-results',
                searchForm:      '.pc-search-form',
            })
        });

    if (pageType === $CATEGORY_PAGE_TYPE) {
        window.$hsAccordionCollection = window.$hsAccordionCollection || [];

        import('@ts-features/products/productsList.ts')
            .then(module => module.handleCategoryProductsList());

        import('@ts-features/products/productsFilter.ts')
            .then(module => module.handleProductsFilter());
    }

    if (pageType === $CATEGORY_PAGE_TYPE || pageType === $SEARCH_PAGE_TYPE) {
        import('@ts-features/products/loadMoreProducts.ts')
            .then(module => module.handleLoadMoreProducts());
    }
});
