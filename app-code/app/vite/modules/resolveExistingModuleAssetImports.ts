import path from 'node:path';
import { existsSync } from 'node:fs';
import { LoadedModuleViteConfig } from '../types';
import { toPosixPath } from '../utils/toPosixPath';

interface ResolveExistingModuleAssetImportsParams
{
    loadedModuleConfigs: LoadedModuleViteConfig[];
    appRootPath: string;
    appEntryPath: string;
    moduleAssetType: 'css' | 'ts';
}

export const resolveExistingModuleAssetImports = (
    params: ResolveExistingModuleAssetImportsParams,
): string[] => {
    const appEntryDirectoryPath: string = path.posix.dirname(params.appEntryPath);
    const moduleAssetImports: string[] = [];

    for (const loadedModuleConfig of params.loadedModuleConfigs) {
        const moduleAssetEntries: string[] = loadedModuleConfig.config.inject?.[params.moduleAssetType] ?? [];

        for (const moduleAssetEntry of moduleAssetEntries) {
            const moduleAssetAbsolutePath: string = path.resolve(loadedModuleConfig.moduleRootPath, moduleAssetEntry);

            if (!existsSync(moduleAssetAbsolutePath)) {
                continue;
            }

            const appRelativeModuleAssetPath: string = toPosixPath(path.posix.relative(
                appEntryDirectoryPath,
                toPosixPath(path.posix.relative(params.appRootPath, moduleAssetAbsolutePath)),
            ));

            moduleAssetImports.push(appRelativeModuleAssetPath);
        }
    }

    return [...new Set(moduleAssetImports)];
};
