import { defineConfig, UserConfig }          from 'vite';
import { fileURLToPath }                     from 'node:url';
import { dirname }                           from 'node:path';
import path                                  from 'node:path';
import tailwindcss                           from '@tailwindcss/vite';
import laravel                               from 'laravel-vite-plugin';
import {
    appCssEntryPath, appTsEntryPath, baseEntryPoints,
    baseRefreshGlobs
}                                            from './vite/constants';
import { createInjectModuleImportsPlugin }   from './vite/plugins/createInjectModuleImportsPlugin';
import { loadActiveModuleConfigs }           from './vite/modules/loadActiveModuleConfigs';
import { resolveExistingModuleAssetImports } from './vite/modules/resolveExistingModuleAssetImports';
import { LoadedModuleViteConfig }            from './vite/types';
import { toPosixPath }                       from './vite/utils/toPosixPath';

// Resolve __dirname and __filename for ESM
// This is necessary because ESM does not have __dirname and __filename by default
// And we can't use debagger to resolve them
const __filename: string = fileURLToPath(import.meta.url);
const __dirname: string  = dirname(__filename);

const modulesStatusesPath: string = path.resolve(__dirname, 'modules_statuses.json');
const modulesRootPath: string     = path.resolve(__dirname, 'Modules');

export default defineConfig(async (): Promise<UserConfig> => {
    const loadedModuleConfigs: LoadedModuleViteConfig[] = await loadActiveModuleConfigs({
        modulesRootPath,
        modulesStatusesPath,
    });

    const moduleAliases: Record<string, string> = {};

    for (const loadedModuleConfig of loadedModuleConfigs) {
        const rawAliases: Record<string, unknown> = loadedModuleConfig.config.alias ?? {};

        for (const [aliasKey, aliasPath] of Object.entries(rawAliases)) {
            if (typeof aliasPath !== 'string' || aliasPath.trim() === '') {
                continue;
            }

            moduleAliases[aliasKey] = aliasPath;
        }
    }

    const baseAliases: Record<string, string> = {
        '@ts-shared':   path.resolve(__dirname, './resources/assets/catalog/ts/shared'),
        '@ts-features': path.resolve(__dirname, './resources/assets/catalog/ts/features'),
        '@ts-stores':   path.resolve(__dirname, './resources/assets/catalog/ts/stores'),
    };

    const refreshGlobs: string[] = [
        ... baseRefreshGlobs,
        ... loadedModuleConfigs.flatMap((loadedModuleConfig: LoadedModuleViteConfig): string[] => {
            return loadedModuleConfig.config.refresh ?? [];
        }),
    ];

    const cssModuleImports: string[] = resolveExistingModuleAssetImports({
        loadedModuleConfigs,
        appRootPath:     __dirname,
        appEntryPath:    appCssEntryPath,
        moduleAssetType: 'css',
    });

    const tsModuleImports: string[] = resolveExistingModuleAssetImports({
        loadedModuleConfigs,
        appRootPath:     __dirname,
        appEntryPath:    appTsEntryPath,
        moduleAssetType: 'ts',
    });

    return {
        build:   {
            outDir:      path.resolve(__dirname, '../httpdocs/build'),
            assetsDir:   'assets',
            manifest:    'manifest.json',
            emptyOutDir: true,
            sourcemap:   true,
        },
        plugins: [
            createInjectModuleImportsPlugin({
                pluginName:              'inject-active-module-styles',
                targetEntryAbsolutePath: toPosixPath(path.resolve(__dirname, appCssEntryPath)),
                importPaths:             cssModuleImports,
                importStatementBuilder:  (importPath: string): string => `@import "${importPath}";`,
                prependImports:          false,
            }),
            createInjectModuleImportsPlugin({
                pluginName:              'inject-active-module-scripts',
                targetEntryAbsolutePath: toPosixPath(path.resolve(__dirname, appTsEntryPath)),
                importPaths:             tsModuleImports,
                importStatementBuilder:  (importPath: string): string => `import "${importPath}";`,
                prependImports:          true,
            }),
            laravel({
                publicDirectory: '../httpdocs',
                buildDirectory:  'build',
                input:           baseEntryPoints,
                refresh:         [... new Set(refreshGlobs)],
            }),
            tailwindcss(),
        ],
        resolve: {
            alias: {
                ... moduleAliases,
                ... baseAliases,
            },
        },
    };
});
