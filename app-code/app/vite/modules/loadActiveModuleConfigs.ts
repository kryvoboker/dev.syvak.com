import { existsSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import type { LoadedModuleViteConfig, ModuleViteConfigShape } from '../types';
import { readModuleStatuses } from './readModuleStatuses';

interface LoadActiveModuleConfigsParams {
    modulesRootPath: string;
    modulesStatusesPath: string;
}

export const loadActiveModuleConfigs = async (
    params: LoadActiveModuleConfigsParams,
): Promise<LoadedModuleViteConfig[]> => {
    if (!existsSync(params.modulesRootPath)) {
        return [];
    }

    const moduleStatuses: Record<string, boolean> = readModuleStatuses(params.modulesStatusesPath);
    const moduleDirectories = readdirSync(params.modulesRootPath, { withFileTypes: true }).filter(
        (directoryEntry: { isDirectory(): boolean; name: string }): boolean => directoryEntry.isDirectory(),
    );

    const loadedConfigs: LoadedModuleViteConfig[] = [];

    for (const moduleDirectory of moduleDirectories) {
        const moduleName: string = moduleDirectory.name;

        if (!moduleStatuses[moduleName]) {
            continue;
        }

        const moduleRootPath: string = path.resolve(params.modulesRootPath, moduleName);
        const moduleConfigPath: string = path.resolve(moduleRootPath, 'vite.config.module.ts');

        if (!existsSync(moduleConfigPath)) {
            continue;
        }

        const moduleConfigImport = await import(pathToFileURL(moduleConfigPath).href);
        const moduleConfig: unknown = moduleConfigImport.moduleViteConfig ?? moduleConfigImport.default ?? null;

        if (moduleConfig === null || typeof moduleConfig !== 'object' || Array.isArray(moduleConfig)) {
            continue;
        }

        loadedConfigs.push({
            moduleName,
            moduleRootPath,
            config: moduleConfig as ModuleViteConfigShape,
        });
    }

    return loadedConfigs;
};
