document.addEventListener('DOMContentLoaded', (): void => {
    window.$hsDropdownCollection  = window.$hsDropdownCollection || [];
    window.$hsOverlayCollection   = window.$hsOverlayCollection || [];
    const pageType: string | null = window.app_params?.page_type ?? null;

    import('@ts-shared/lib/validateForm.ts')
        .then(module => module.handleValidateForms());

    import('@ts-features/menu/language.ts')
        .then(module => module.handleLanguageMenu());

    import('@ts-features/menu/main-mob-menu.ts')
        .then(module => module.handleMainMobMenu());

    import('@ts-features/search/mob-search.ts')
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

    /**
     * TODO: replace hardcoded category check with final runtime page-type
     * strategy if project-level routing/page context changes.
     */
    if (pageType === 'category') {
        window.$hsAccordionCollection = window.$hsAccordionCollection || [];

        import('@ts-features/category/products-list.ts')
            .then(module => module.handleCategoryProductsList());

        import('@ts-features/category/products-filter.ts')
            .then(module => module.handleProductsFilter());
    }
});
