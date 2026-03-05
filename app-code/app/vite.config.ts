import { defineConfig, UserConfig } from "vite";
import { fileURLToPath }            from 'url';
import { dirname }                  from 'node:path';
import path                         from "node:path";
import tailwindcss                  from "@tailwindcss/vite";
import laravel                      from "laravel-vite-plugin";

// Resolve __dirname and __filename for ESM
// This is necessary because ESM does not have __dirname and __filename by default
// And we can't use debagger to resolve them
const __filename: string = fileURLToPath(import.meta.url);
const __dirname: string  = dirname(__filename);

const entryPoints: string[] = [
    'resources/assets/filament/alyo-admin/theme.css',
    'resources/assets/catalog/css/app.css',
    'resources/assets/catalog/css/libs/leaflet.css',
    'node_modules/choices.js/src/styles/choices.scss',
    'node_modules/@fancyapps/ui/dist/fancybox/fancybox.css',
    'resources/assets/catalog/ts/index.ts',
];

export default defineConfig((): UserConfig => {
    return {
        build: {
            outDir:      path.resolve(__dirname, '../httpdocs/build'),
            assetsDir:   'assets',
            manifest:    'manifest.json',
            emptyOutDir: true,
            sourcemap:   true,
        },
        plugins: [
            laravel({
                publicDirectory: '../httpdocs',
                buildDirectory:  'build',
                input:           entryPoints,
                refresh:         ['resources/views/**', 'app/**', 'routes/**'],
            }),
            tailwindcss(),
        ],
        resolve: {
            alias: {
                '@ts-shared':   path.resolve(__dirname, './resources/assets/catalog/ts/shared'),
                '@ts-features': path.resolve(__dirname, './resources/assets/catalog/ts/features'),
                '@ts-stores':   path.resolve(__dirname, './resources/assets/catalog/ts/stores'),
            },
        }
    };
});
