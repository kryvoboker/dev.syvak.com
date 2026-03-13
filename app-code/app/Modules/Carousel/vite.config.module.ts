import path              from 'node:path';
import { fileURLToPath } from 'node:url';
import { dirname }       from 'node:path';

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
const __dirname: string  = dirname(__filename);

const moduleRootPath: string = __dirname;

export const moduleViteConfig: ModuleViteConfig = {
    moduleName: 'Carousel',
    refresh:    [
        'Modules/Carousel/resources/views/**',
        'Modules/Carousel/app/**',
        'Modules/Carousel/routes/**',
    ],
    alias:      {
        '@carousel-ts':  path.resolve(moduleRootPath, 'resources/assets/ts'),
        '@carousel-css': path.resolve(moduleRootPath, 'resources/assets/css'),
    },
    inject:     {
        css: ['resources/assets/css/main.css'],
        ts:  ['resources/assets/ts/main.ts'],
    },
};
