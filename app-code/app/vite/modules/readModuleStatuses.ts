import { existsSync, readFileSync } from 'node:fs';

export const readModuleStatuses = (modulesStatusesPath: string): Record<string, boolean> => {
    if (!existsSync(modulesStatusesPath)) {
        return {};
    }

    const moduleStatusesData: unknown = JSON.parse(readFileSync(modulesStatusesPath, 'utf-8'));

    if (moduleStatusesData === null || typeof moduleStatusesData !== 'object' || Array.isArray(moduleStatusesData)) {
        return {};
    }

    return moduleStatusesData as Record<string, boolean>;
};
