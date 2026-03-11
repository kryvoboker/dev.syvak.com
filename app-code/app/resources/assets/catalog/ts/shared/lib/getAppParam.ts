export function getAppParam<T>(key: string): T | null {
    if (typeof window.app_params !== 'object' || window.app_params === null) {
        return null;
    }

    const value = window.app_params[key];

    return value === undefined ? null : value as T;
}
