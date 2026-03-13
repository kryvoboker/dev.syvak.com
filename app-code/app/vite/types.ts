export interface ModuleViteConfigShape
{
    moduleName?: string;
    refresh?: string[];
    alias?: Record<string, string>;
    inject?: {
        css?: string[];
        ts?: string[];
    };
}

export interface LoadedModuleViteConfig
{
    moduleName: string;
    moduleRootPath: string;
    config: ModuleViteConfigShape;
}
