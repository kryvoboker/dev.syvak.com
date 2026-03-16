document.addEventListener('DOMContentLoaded', (): void => {
    window.$hsDropdownCollection = window.$hsDropdownCollection || [];
    window.$hsOverlayCollection = window.$hsOverlayCollection || [];

    import('@ts-shared/lib/validateForm.ts')
        .then(module => module.handleValidateForms());

    import('@ts-features/menu/language.ts')
        .then(module => module.handleLanguageMenu());

    import('@ts-features/menu/main-mob-menu.ts')
        .then(module => module.handleMainMobMenu());

    import('@ts-features/search/mob-search.ts')
        .then(module => {
            module.handleMobSearch({
                openSearchBtn:    '.open-mob-search-btn',
                searchContainer:  '.mob-search-container',
                searchInput:      '.mob-search-input',
                searchResults:    '.mob-search-results',
                searchForm:       '.mob-search-form',
            });

            module.handleMobSearch({
                openSearchBtn:    '.open-pc-search-btn',
                searchContainer:  '.pc-search-container',
                searchInput:      '.pc-search-input',
                searchResults:    '.pc-search-results',
                searchForm:       '.pc-search-form',
            })
        });
});
