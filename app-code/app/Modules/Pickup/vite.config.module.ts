import path, { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

export interface ModuleViteConfig {
    moduleName: string;
    refresh: string[];
    alias: Record<string, string>;
    inject?: {
        css?: string[];
    };
}

// Resolve __dirname and __filename for ESM
// This is necessary because ESM does not have __dirname and __filename by default
// And we can't use debagger to resolve them
const __filename: string = fileURLToPath(import.meta.url);
const __dirname: string = dirname(__filename);

const moduleRootPath: string = __dirname;

export const moduleViteConfig: ModuleViteConfig = {
    moduleName: 'Pickup',
    refresh: [
        'Modules/Pickup/resources/views/**',
        'Modules/Pickup/resources/assets/css/**',
        'Modules/Pickup/resources/assets/ts/**',
        'Modules/Pickup/app/**',
        'Modules/Pickup/routes/**',
    ],
    alias: {
        '@pickup-ts': path.resolve(moduleRootPath, 'resources/assets/ts'),
        '@pickup-css': path.resolve(moduleRootPath, 'resources/assets/css'),
    },
    inject: {
        css: ['resources/assets/css/main.css'],
    },
};
