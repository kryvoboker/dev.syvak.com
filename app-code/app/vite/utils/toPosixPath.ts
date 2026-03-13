import path from 'node:path';

export const toPosixPath = (inputPath: string): string => {
    return inputPath.split(path.sep).join(path.posix.sep);
};
