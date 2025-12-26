document.addEventListener('DOMContentLoaded', (): void => {
    window.$hsDropdownCollection = [];
    window.$hsOverlayCollection = [];

    import('@ts-shared/lib/validateForm.ts')
        .then(module => module.handleValidateForms());

    import('@ts-features/menu/language.ts')
        .then(module => module.handleLanguageMenu());

    import('@ts-features/menu/main-mob-menu.ts')
        .then(module => module.handleMainMobMenu());

    import('@ts-features/search/mob-search.ts')
        .then(module => module.handleMobSearch());
});
