import type { Plugin } from 'vite';
import { toPosixPath } from '../utils/toPosixPath';

interface CreateInjectModuleImportsPluginParams {
    pluginName: string;
    targetEntryAbsolutePath: string;
    importPaths: string[];
    importStatementBuilder: (importPath: string) => string;
    prependImports: boolean;
}

export const createInjectModuleImportsPlugin = (params: CreateInjectModuleImportsPluginParams): Plugin => {
    return {
        name:    params.pluginName,
        enforce: 'pre',
        transform(
            sourceCode: string,
            id: string,
        ): {
               code: string;
               map: null;
           } | null {
            if (params.importPaths.length === 0) {
                return null;
            }

            const normalizedId: string = toPosixPath(id.split('?')[0]);

            if (normalizedId !== params.targetEntryAbsolutePath) {
                return null;
            }

            const importBlock: string = `${params.importPaths.map(params.importStatementBuilder).join('\n')}\n`;

            if (params.prependImports) {
                return {
                    code: `${importBlock}${sourceCode}`,
                    map:  null,
                };
            }

            return {
                code: `${sourceCode}\n${importBlock}`,
                map:  null,
            };
        },
    };
};
