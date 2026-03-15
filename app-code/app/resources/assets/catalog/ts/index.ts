import { getAppParam } from '@ts-shared/lib/getAppParam.ts';
import { $PAGE_TYPE_KEY } from '@ts-shared/lib/constants.ts';

document.addEventListener('DOMContentLoaded', (): void => {
    window.$hsDropdownCollection = window.$hsDropdownCollection || [];
    window.$hsOverlayCollection = window.$hsOverlayCollection || [];

    const pageType = getAppParam<string>($PAGE_TYPE_KEY);

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

    if (pageType === 'home') {
        import('@ts-features/carousel/mainCarousel.ts')
            .then(module => module.handleMainCarousel());
    }
});
