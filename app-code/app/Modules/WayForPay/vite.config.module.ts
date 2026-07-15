import path, { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

export interface ModuleViteConfig {
    moduleName: string;
    refresh: string[];
    alias: Record<string, string>;
    inject?: {
        ts?: string[];
    };
}

const __filename: string = fileURLToPath(import.meta.url);
const __dirname: string = dirname(__filename);

export const moduleViteConfig: ModuleViteConfig = {
    moduleName: 'WayForPay',
    refresh: [
        'Modules/WayForPay/resources/views/**',
        'Modules/WayForPay/resources/assets/ts/**',
        'Modules/WayForPay/app/**',
        'Modules/WayForPay/routes/**',
    ],
    alias: {
        '@wayforpay-ts': path.resolve(__dirname, 'resources/assets/ts'),
    },
    inject: {
        ts: ['resources/assets/ts/main.ts'],
    },
};
