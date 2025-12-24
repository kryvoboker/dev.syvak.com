document.addEventListener('DOMContentLoaded', (): void => {

    window.$hsDropdownCollection = [];
    window.$hsOverlayCollection = [];

    import('@ts-features/menu/language.ts')
        .then(module => module.handleLanguageMenu());

    import('@ts-features/menu/main-mob-menu.ts')
        .then(module => module.handleMainMobMenu());
});
