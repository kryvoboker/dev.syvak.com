export function getAppParam<T>(key: string | number): T | null {
    if (typeof window.app_params !== 'object' || window.app_params === null) {
        return null;
    }

    const value: unknown | any | undefined = window.app_params[key];

    return value === undefined ? null : value as T;
}
