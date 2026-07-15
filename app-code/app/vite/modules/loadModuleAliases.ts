import path from 'node:path';
import { existsSync, readdirSync } from 'node:fs';
import { pathToFileURL } from 'node:url';
import type { ModuleViteConfigShape } from '../types';

interface LoadModuleAliasesParams {
    modulesRootPath: string;
}

export const loadModuleAliases = async ({
    modulesRootPath,
}: LoadModuleAliasesParams): Promise<Record<string, string>> => {
    if (!existsSync(modulesRootPath)) {
        return {};
    }

    const aliases: Record<string, string> = {};
    const moduleDirectories = readdirSync(modulesRootPath, { withFileTypes: true }).filter((directoryEntry) =>
        directoryEntry.isDirectory(),
    );

    for (const moduleDirectory of moduleDirectories) {
        const moduleConfigPath = path.resolve(modulesRootPath, moduleDirectory.name, 'vite.config.module.ts');

        if (!existsSync(moduleConfigPath)) {
            continue;
        }

        const moduleConfigImport = await import(pathToFileURL(moduleConfigPath).href);
        const moduleConfig = (moduleConfigImport.moduleViteConfig ?? moduleConfigImport.default ?? null) as
            | ModuleViteConfigShape
            | null;

        if (!moduleConfig?.alias) {
            continue;
        }

        Object.assign(aliases, moduleConfig.alias);
    }

    return aliases;
};
