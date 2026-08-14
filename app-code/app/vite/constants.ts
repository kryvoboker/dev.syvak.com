export const appCssEntryPath: string = 'resources/assets/catalog/css/app.css';
export const appTsEntryPath: string = 'resources/assets/catalog/ts/index.ts';

export const baseEntryPoints: string[] = [
    'resources/assets/filament/alyo-admin/theme.css',
    appCssEntryPath,
    'node_modules/nouislider/dist/nouislider.css',
    'resources/assets/catalog/css/libs/leaflet.css',
    'node_modules/choices.js/src/styles/choices.scss',
    'node_modules/@fancyapps/ui/dist/fancybox/fancybox.css',
    appTsEntryPath,
];

export const baseRefreshGlobs: string[] = ['resources/views/**', 'app/**', 'routes/**'];
