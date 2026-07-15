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
    moduleName: 'NovaPoshta',
    refresh: [
        'Modules/NovaPoshta/resources/views/**',
        'Modules/NovaPoshta/resources/assets/css/**',
        'Modules/NovaPoshta/app/**',
        'Modules/NovaPoshta/routes/**',
    ],
    alias: {
        '@nova-poshta-css': path.resolve(moduleRootPath, 'resources/assets/css'),
    },
    inject: {
        css: ['resources/assets/css/main.css'],
        ts: ['resources/assets/ts/main.ts'],
    },
};
