import path, { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

export interface ModuleViteConfig {
    moduleName: string;
    refresh: string[];
    alias: Record<string, string>;
}

const __filename: string = fileURLToPath(import.meta.url);
const __dirname: string = dirname(__filename);

export const moduleViteConfig: ModuleViteConfig = {
    moduleName: 'BankTransfer',
    refresh: [
        'Modules/BankTransfer/resources/views/**',
        'Modules/BankTransfer/resources/assets/ts/**',
        'Modules/BankTransfer/app/**',
    ],
    alias: {
        '@bank-transfer-ts': path.resolve(__dirname, 'resources/assets/ts'),
    },
};
