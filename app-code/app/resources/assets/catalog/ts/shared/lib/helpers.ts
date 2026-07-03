import { $FLEX_CLASS_NAME, $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import type { QueryValueType, URLParamsType } from '@ts-types/httpQueryBuild.ts';

type FetchFuncOptions = Record<string | number, string | number>;

export const findElem = <T extends HTMLElement>(
    searchVal: string,
    context: T | Document | null = document,
): T | HTMLElement | null => (context ? context.querySelector(searchVal) : null);
export const findElems = <T extends HTMLElement>(
    searchVal: string,
    context: T | Document | null = document,
): NodeListOf<T> | NodeListOf<HTMLElement> | null => (context ? context.querySelectorAll(searchVal) : null);
export const arrayFrom = <T>(pseudoArray: ArrayLike<T> | null): T[] => (pseudoArray ? Array.from(pseudoArray) : []);
export const findArrayElems = <T extends HTMLElement>(
    searchVal: string,
    context: T | Document | null = document,
): (T | HTMLElement)[] | [] => (context ? arrayFrom(findElems(searchVal, context)) : []);
export const isArray = (value: unknown): value is unknown[] => Array.isArray(value);
export const addClass = <T extends HTMLElement>(element: T | null, selector: string | string[]): undefined | null => {
    if (!element) {
        return null;
    }

    if (isArray(selector)) {
        element.classList.add(...selector);
    } else {
        element.classList.add(selector);
    }

    return undefined;
};
export const removeClass = <T extends HTMLElement>(
    element: T | null,
    selector: string | string[],
): undefined | null => {
    if (!element) {
        return null;
    }

    if (isArray(selector)) {
        element.classList.remove(...selector);
    } else {
        element.classList.remove(selector);
    }

    return undefined;
};
export const toggleClass = <T extends HTMLElement>(element: T | null, selector: string): boolean =>
    element ? element.classList.toggle(selector) : false;
export const toggleActive = (DOMElements: NodeListOf<HTMLElement> | HTMLElement[] = []): void =>
    arrayFrom(DOMElements).forEach((element: HTMLElement): void => {
        toggleClass(element, 'active');
    });
export const setLocalStorage = (key: string, value: string): void => localStorage.setItem(key, value);
export const getLocalStorage = (key: string, defaultValue: string | null = null): string | null =>
    localStorage.getItem(key) ?? defaultValue;
export const removeLocalStorage = (key: string): void => localStorage.removeItem(key);
export const setSessionStorage = (key: string, value: string): void => sessionStorage.setItem(key, value);
export const getSessionStorage = (key: string, defaultValue: string | null = null): string | null =>
    sessionStorage.getItem(key) ?? defaultValue;
export const removeSessionStorage = (key: string): void => sessionStorage.removeItem(key);
export const isContainsClass = <T extends HTMLElement>(element: T | null, className: string): boolean =>
    element ? element.classList.contains(className) : false;
export const redirect = (url: string): string => (location.href = url);
export const goBack = (fallbackUrl: string = '/'): void => {
    if (history.length > 1) {
        history.back();

        return;
    }

    const referrer: string = document.referrer?.trim() ?? '';

    if (!isEmpty(referrer)) {
        try {
            const referrerUrl: URL = new URL(referrer);

            if (referrerUrl.origin === location.origin && referrerUrl.href !== location.href) {
                redirect(referrerUrl.href);

                return;
            }
        } catch {
            // Ignore malformed referrer and use fallback.
        }
    }

    redirect(fallbackUrl);
};
export const removeElement = <T extends HTMLElement>(selector: string, context: T | Document = document): void =>
    findElem(selector, context)?.remove();
export const getSpinnerHtml = (selector: string = ''): string =>
    `<div class="spinner-border ${selector}" role="status"></div>`;
export const blockBody = (isBlock: boolean = true): string =>
    (document.body.style.overflow = isBlock ? $HIDDEN_CLASS_NAME : '');
export const showErrorInConsole = (errorMessage: string): void => console.error(new Error(errorMessage));
export const getFormDataInstance = <T extends HTMLFormElement>(form: T | null = null): FormData =>
    form ? new FormData(form) : new FormData();
export const getRandomNums = (): string => Math.random().toString(36).substring(2, 9);
export const windowMatchMedia = (query: string): boolean =>
    matchMedia(`(${query.replace(/^\(+/, '').replace(/\)+$/, '')})`).matches;
export const getClosestParentEl = <T extends HTMLElement>(selector: string, childEl: T | null): T | null =>
    childEl ? childEl.closest(selector) : null;
export const isClosestClass = <T extends HTMLElement>(selector: string, context: T | null): boolean =>
    getClosestParentEl(selector, context) !== null;
export const setHistoryState = (url: string, title: string = '', stateObj: Record<string, unknown> = {}): void =>
    history.pushState(stateObj, title, url);
export const sprintF = (str: string, ...args: (string | number)[]): string => {
    let index: number = 0;

    return str.replace(/%[sdif]/g, (match: string): string => {
        if (index >= args.length) {
            return match;
        }

        const arg: string | number = args[index];
        index++;

        switch (match) {
            case '%s':
                return String(arg);
            case '%d':
            case '%i':
                return parseInt(String(arg), 10).toString();
            case '%f':
                return parseFloat(String(arg)).toString();
            default:
                return match;
        }
    });
};

export const valueToString = (value: string | number | boolean | undefined | null): string => {
    if (value === undefined || value === null) {
        return '';
    }

    if (typeof value === 'boolean') {
        value = value ? '1' : '0';
    }

    return value.toString();
};

export const normalizeAndEncodeUriComponent = (uriComponent: boolean | string | null | undefined): string => {
    uriComponent = valueToString(uriComponent);

    if (isEmpty(uriComponent)) {
        return '';
    }

    return encodeURIComponent(decodeURIComponent(<string>uriComponent).trim());
};

export const httpBuildQueryString = (queries: URLParamsType, isWidthSearchParams: boolean = false): string => {
    const queryParamsMap: Map<string, string> = new Map();
    const queryParamsArray: string[] = [];

    for (const [queriesKey, queryValue] of Object.entries(queries)) {
        if (isEmpty(queryValue) && queryValue !== 0) {
            continue;
        }

        const key: string = queriesKey.trim();
        const value: QueryValueType | QueryValueType[] = queryValue;

        if (isArray(value)) {
            const values: string[] = [];

            value.forEach((item: QueryValueType): void => {
                values.push(valueToString(item));
            });

            queryParamsMap.set(key, values.join(','));

            continue;
        }

        queryParamsMap.set(key, valueToString(value));
    }

    if (isWidthSearchParams) {
        const searchParams: string = location.search;

        if (!isEmpty(searchParams)) {
            const searchParamsSplit: string[] = searchParams.slice(1).split('&');
            const searchParamsLength: number = searchParamsSplit.length;

            for (let index = 0; index < searchParamsLength; index++) {
                const searchParamsValues: string[] = searchParamsSplit[index].split('=');

                if (
                    queryParamsMap.has(searchParamsValues[0]) ||
                    typeof searchParamsValues[1] === 'undefined' ||
                    isEmpty(searchParamsValues[1])
                ) {
                    continue;
                }

                queryParamsMap.set(searchParamsValues[0], searchParamsValues[1]);
            }
        }
    }

    for (const [key, values] of queryParamsMap.entries()) {
        queryParamsArray.push(`${normalizeAndEncodeUriComponent(key)}=${normalizeAndEncodeUriComponent(values)}`);
    }

    return queryParamsArray.join('&');
};

export const isEmpty = (value: string | number | boolean | null | undefined | unknown[] | object): boolean => {
    if (typeof value === 'number') {
        return Number.isNaN(value) ? false : value === 0;
    } else if (typeof value === 'boolean') {
        return false;
    } else if (isArray(value) || typeof value === 'string') {
        if (typeof value === 'string') {
            value = value.trim();
        }

        if (value === '0') {
            return true;
        }

        return value.length === 0;
    } else if (value === null || value === undefined) {
        return true;
    } else {
        return !(value instanceof HTMLElement) && Object.keys(value).length === 0;
    }
};

export const throttle = <Args extends unknown[], ReturnValue>(
    func: (...args: Args) => ReturnValue,
    delay: number,
): ((...args: Args) => void) => {
    let isThrottled: boolean = false;
    let savedArgs: Args | null = null;
    let savedThis: unknown = null;

    function wrapper(this: unknown, ...args: Args): void {
        if (isThrottled) {
            savedArgs = args;
            savedThis = this;

            return;
        }

        func.apply(this, args);

        isThrottled = true;

        setTimeout((): void => {
            isThrottled = false;

            if (savedArgs && savedThis) {
                wrapper.apply(savedThis, savedArgs);
                savedArgs = savedThis = null;
            }
        }, delay);
    }

    return wrapper;
};

export const scrollToTop = (anchor: string = ''): void => {
    const behavior = 'smooth';

    if (anchor) {
        findElem(anchor)?.scrollIntoView({
            behavior,
        });
    } else {
        scrollTo({
            top: 0,
            behavior,
        });
    }
};

export const scrollToBottom = (selector: string = ''): void => {
    const behavior: ScrollBehavior = 'smooth';

    if (selector) {
        const element = <HTMLElement | null>findElem(selector);

        if (element) {
            element?.scrollTo({
                top: element.scrollHeight,
                behavior,
            });
        }
    } else {
        window.scrollTo({
            top: document.body.scrollHeight,
            behavior,
        });
    }
};

export const debounce = <Args extends unknown[], ReturnValue>(
    func: (...args: Args) => ReturnValue,
    delay: number,
): ((...args: Args) => void) => {
    let timeout: ReturnType<typeof setTimeout>;

    return (...args: Args): void => {
        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout((): void => {
            func(...args);
        }, delay);
    };
};

/**
 * Check if browser supports specific image format.
 */
const checkImageFormatSupport = async (format: 'avif' | 'webp'): Promise<boolean> => {
    const testImages: Record<'avif' | 'webp', string> = {
        avif: 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgANogQEAwgMg8f8D///8WfhwB8+ErK42A=',
        webp: 'data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA=',
    };

    return new Promise<boolean>((resolve): void => {
        const img: HTMLImageElement = new Image();

        img.onload = (): void => resolve(true);
        img.onerror = (): void => resolve(false);

        img.src = testImages[format];
    });
};

/**
 * Get supported image formats by browser.
 */
const getSupportedImageFormats = async (): Promise<string[]> => {
    const CACHE_KEY: string = 'supported_image_formats';
    const cachedData: string | null = getLocalStorage(CACHE_KEY);

    if (cachedData) {
        return JSON.parse(cachedData);
    }

    // Run checks if no valid cache
    const formats: string[] = [];

    if (await checkImageFormatSupport('avif')) {
        formats.push('avif');
    }

    if (await checkImageFormatSupport('webp')) {
        formats.push('webp');
    }

    setLocalStorage(CACHE_KEY, JSON.stringify(formats));

    return formats;
};

/**
 * In-memory cache for supported formats (session-level).
 */
let cachedFormats: string[] | null = null;

/**
 * Get cached supported image formats (memory cache + localStorage).
 */
const getCachedSupportedFormats = async (): Promise<string[]> => {
    if (cachedFormats === null) {
        cachedFormats = await getSupportedImageFormats();
    }

    return cachedFormats;
};

export const fetchFunc = async <T = unknown>(
    url: string,
    data: FetchFuncOptions | FormData = {},
    method: string = 'POST',
): Promise<T> => {
    type TypeHeaders = {
        'X-Requested-With': string;
        contentType?: string;
        'X-CSRF-TOKEN'?: string;
        'X-Supported-Image-Formats'?: string;
    };

    type TypeOptions = {
        method?: string;
        headers: TypeHeaders;
        body?: FormData | string;
    };

    const supportedFormats: string[] = await getCachedSupportedFormats();
    const headers: TypeHeaders = {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': (<HTMLMetaElement | null>findElem('meta[name="csrf-token"]'))?.content || '',
        'X-Supported-Image-Formats': supportedFormats.join(','), // "avif,webp" or "webp" or ""
    };
    const contentType = 'application/json;charset=utf-8';
    let options: TypeOptions = {
        method,
        headers,
    };

    if (method.toUpperCase() === 'GET') {
        options = { headers };
    } else if (!(data instanceof FormData)) {
        options.headers.contentType = contentType;
        options.body = JSON.stringify(data);
    } else if (data instanceof FormData) {
        options.body = data;
    }

    const response: Response = await fetch(url, options);

    if (!response.ok) {
        console.error(`Network response was not ok: ${response.statusText}`);
    }

    return (await response.json()) as T;
};

export const scrollToAnchor = (selector: string): void => {
    findArrayElems(selector).forEach((link: HTMLElement): void => {
        link.addEventListener('click', function (e: Event): void {
            e.preventDefault();

            findElem((this as HTMLLinkElement).href.replace(/^(.*)(?=#)/, ''))?.scrollIntoView({
                behavior: 'smooth',
            });
        });
    });
};

export const toggleElement = <T extends HTMLElement>(element: T | null, isShow: boolean): void => {
    if (isShow) {
        removeClass(element, $HIDDEN_CLASS_NAME);
        addClass(element, $FLEX_CLASS_NAME);
    } else {
        removeClass(element, $FLEX_CLASS_NAME);
        addClass(element, $HIDDEN_CLASS_NAME);
    }
};

export const stripUnit = (value: number | string): number => {
    // Ensure the value is a string, then remove any non-digit characters except the decimal point
    const numericValue: number = parseFloat(value as string);

    // If the numeric conversion is unsuccessful, return the original value
    if (Number.isNaN(numericValue)) {
        return 0;
    }

    return numericValue;
};

export const getBaseDocumentFontSize = (): number => {
    const doc: HTMLElement = document.documentElement;
    const computedStyle = window.getComputedStyle(doc);

    return stripUnit(computedStyle.fontSize) || 16;
};

// Function to convert pixels to rems
export const toRem = (
    pxValue: number | string,
    baseUnitSize: null | number | string = null,
    sizeUnit: string = 'rem',
): string => {
    if (!baseUnitSize) {
        baseUnitSize = getBaseDocumentFontSize();
    }

    const numericPxValue: number = stripUnit(pxValue);
    const numericBaseUnitSize: number = stripUnit(baseUnitSize);

    // Calculate rem value
    return `${numericPxValue / numericBaseUnitSize}${sizeUnit}`;
};

export const normalizeNumber = (value: string | number): number => {
    if (typeof value === 'string') {
        value = value
            .replace(/[^\d.,]+/g, '')
            .replace(/,+/g, '.')
            .replace(/\.{2,}/g, '.');

        if (value.includes('.')) {
            value = parseFloat(value);
        } else {
            value = parseInt(value, 10);
        }
    }

    return value;
};

export const togglePage = <T extends HTMLElement>(loaderClassName: T | null, isBlockingPage: boolean): void => {
    blockBody(isBlockingPage);
    toggleElement(loaderClassName, isBlockingPage);
};
