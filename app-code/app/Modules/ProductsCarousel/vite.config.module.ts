import path, { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

export interface ModuleViteConfig {
    moduleName: string;
    refresh: string[];
    alias: Record<string, string>;
    inject?: {
        css?: string[];
        ts?: string[];
    };
}

// Resolve __dirname and __filename for ESM
// This is necessary because ESM does not have __dirname and __filename by default
// And we can't use debagger to resolve them
const __filename: string = fileURLToPath(import.meta.url);
const __dirname: string = dirname(__filename);

const moduleRootPath: string = __dirname;

export const moduleViteConfig: ModuleViteConfig = {
    moduleName: 'ProductsCarousel',
    refresh: [
        'Modules/ProductsCarousel/resources/views/**',
        'Modules/ProductsCarousel/resources/assets/css/**',
        'Modules/ProductsCarousel/resources/assets/ts/**',
        'Modules/ProductsCarousel/app/**',
        'Modules/ProductsCarousel/routes/**',
    ],
    alias: {
        '@products-carousel-ts': path.resolve(moduleRootPath, 'resources/assets/ts'),
        '@products-carousel-css': path.resolve(moduleRootPath, 'resources/assets/css'),
    },
    inject: {
        css: ['resources/assets/css/main.css'],
        ts: ['resources/assets/ts/main.ts'],
    },
};
