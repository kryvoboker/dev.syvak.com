import { defineConfig, UserConfig } from "vite";
import { resolve }                  from 'path';
import { fileURLToPath }            from 'url';
import { dirname }                  from 'node:path';
import path                         from "node:path";
import process                      from 'node:process';
import tailwindcss                  from "@tailwindcss/vite";
import laravel                      from "laravel-vite-plugin";

// Resolve __dirname and __filename for ESM
// This is necessary because ESM does not have __dirname and __filename by default
// And we can't use debagger to resolve them
const __filename = fileURLToPath(import.meta.url);
const __dirname  = dirname(__filename);

const isProduction = process.env.NODE_ENV === 'production';

export default defineConfig(() : UserConfig => {
    return {
        root:  resolve(__dirname, './'),
        base:  isProduction ? '../httpdocs/dist/' : '/',
        build: {
            outDir:    'dist',
            sourcemap: isProduction ? 'hidden' : true,
            manifest:  true,
        },
        plugins: [
            laravel({
                input:   [
                    './resources/assets/css/app.css',
                    './resources/assets/css/libs/leaflet.css',
                    './node_modules/choices.js/src/styles/choices.scss',
                    './node_modules/@fancyapps/ui/dist/fancybox/fancybox.css',

                    './resources/assets/ts/index.ts',
                ],
                refresh: true,
            }),
            tailwindcss(),
        ],
        resolve: {
            alias: {
                '@ts-shared':   path.resolve(__dirname, './resources/assets/ts/shared'),
                '@ts-features': path.resolve(__dirname, './resources/assets/ts/features'),
                '@ts-stores':   path.resolve(__dirname, './resources/assets/ts/stores'),
            },
        }
    };
});
