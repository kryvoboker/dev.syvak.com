import { $HIDDEN_CLASS_NAME } from "@ts-shared/lib/constants.ts";

export const findElem             = <T extends HTMLElement>(searchVal: string, context: T | Document | null = document): T | HTMLElement | null => context ? context.querySelector(searchVal) : null;
export const findElems            = <T extends HTMLElement>(searchVal: string, context: T | Document = document): NodeListOf<T> | NodeListOf<HTMLElement> | null => context.querySelectorAll(searchVal);
export const arrayFrom            = <T>(pseudoArray: ArrayLike<T> | null): T[] => pseudoArray ? Array.from(pseudoArray) : [];
export const findArrayElems       = <T extends HTMLElement>(searchVal: string, context: T | Document = document): (T | HTMLElement)[] | [] => arrayFrom(findElems(searchVal, context));
export const isArray              = (value: any): value is any[] => Array.isArray(value);
export const addClass             = <T extends HTMLElement>(element: T | null, selector: string | string[]): void | null => element ? (isArray(selector) ? element.classList.add(... selector) : element.classList.add(selector)) : null;
export const removeClass          = <T extends HTMLElement>(element: T | null, selector: string | string[]): void | null => element ? (isArray(selector) ? element.classList.remove(... selector) : element.classList.remove(selector)) : null;
export const toggleClass          = <T extends HTMLElement>(element: T | null, selector: string): boolean => element ? element.classList.toggle(selector) : false;
export const toggleActive         = (DOMElements: NodeListOf<HTMLElement> | HTMLElement[] = []): void => arrayFrom(DOMElements).forEach((element: HTMLElement) => toggleClass(element, 'active'));
export const setLocalStorage      = (key: string, value: string): void => localStorage.setItem(key, value);
export const getLocalStorage      = (key: string, defaultValue: any = null): string | null => (localStorage.getItem(key) ?? defaultValue);
export const removeLocalStorage   = (key: string): void => localStorage.removeItem(key);
export const setSessionStorage    = (key: string, value: string): void => sessionStorage.setItem(key, value);
export const getSessionStorage    = (key: string, defaultValue: any = null): string | null => (sessionStorage.getItem(key) ?? defaultValue);
export const removeSessionStorage = (key: string): void => sessionStorage.removeItem(key);
export const isContainsClass      = <T extends HTMLElement>(element: T | null, className: string): boolean => element ? element.classList.contains(className) : false;
export const redirect             = (url: string): string => location.href = url;
export const removeElement        = <T extends HTMLElement>(selector: string, context: T | Document = document): void => findElem(selector, context)?.remove();
export const getSpinnerHtml       = (selector: string = ''): string => `<div class="spinner-border ${selector}" role="status"></div>`;
export const blockBody            = (isBlock: boolean = true): string => document.body.style.overflow = isBlock ? $HIDDEN_CLASS_NAME : '';
export const showErrorInConsole   = (errorMessage: string): void => console.error(new Error(errorMessage));
export const getFormDataInstance  = <T extends HTMLFormElement>(form: T | null = null): FormData => form ? new FormData(form) : new FormData();
export const getRandomNums        = (): string => Math.random().toString(36).substring(2, 9);
export const windowMatchMedia     = (query: string): boolean => matchMedia(`(${query.replace(/^\(+/, '').replace(/\)+$/, '')})`).matches;
export const getClosestParentEl   = <T extends HTMLElement>(selector: string, childEl: T | null): T | null => childEl ? childEl.closest(selector) : null;
export const isClosestClass       = <T extends HTMLElement>(selector: string, context: T | null): boolean => getClosestParentEl(selector, context) !== null;

const isEmpty = <T extends Object>(value: string | number | null | undefined | any[] | T): boolean => {
    if (typeof value === 'number') {
        return isNaN(value) ? false : value === 0;
    } else if (isArray(value) || typeof value === 'string') {
        return value.length === 0;
    } else if (value === null) {
        return true;
    } else if (value === undefined) {
        return false;
    } else {
        return Object.keys(value).length === 0;
    }
};

const throttle = (func: Function, delay: number): Function => {
    let isThrottled: boolean = false,
        savedArgs: IArguments | null,
        savedThis: Function | null;

    function wrapper(this: Function): void {
        if (isThrottled) {
            savedArgs = arguments;
            savedThis = this;
            return;
        }

        func.apply(this, arguments);

        isThrottled = true;

        setTimeout((): void => {
            isThrottled = false;

            if (savedArgs && savedThis) {
                wrapper.apply(savedThis, savedArgs as any);
                savedArgs = savedThis = null;
            }
        }, delay);
    }

    return wrapper;
};

const scrollToTop = (anchor: string = ''): void => {
    const behavior = 'smooth';

    if (anchor) {
        findElem(anchor)?.scrollIntoView({
            behavior
        })
    } else {
        scrollTo({
            top: 0,
            behavior
        });
    }
};

const scrollToBottom = (selector: string = ''): void => {
    const behavior: ScrollBehavior = 'smooth';

    if (selector) {
        const element = <HTMLElement | null>findElem(selector);

        if (element) {
            element?.scrollTo({
                top: element.scrollHeight,
                behavior
            });
        }
    } else {
        window.scrollTo({
            top: document.body.scrollHeight,
            behavior
        });
    }
};

const debounce = <F extends (... args: any[]) => any>(func: F, delay: number): (this: ThisParameterType<F>, ... args: Parameters<F>) => void => {
    let timeout: ReturnType<typeof setTimeout>;

    return function (this: ThisParameterType<F>, ... args: Parameters<F>): void {
        const context: ThisParameterType<F> = this;

        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout((): void => {
            func.apply(context, args);
        }, delay);
    };
};

/**
 * Check if browser supports specific image format.
 */
const checkImageFormatSupport = async (format: 'avif' | 'webp'): Promise<boolean> => {
    const testImages: Record<'avif' | 'webp', string> = {
        avif: 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgANogQEAwgMg8f8D///8WfhwB8+ErK42A=',
        webp: 'data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA='
    };

    return new Promise<boolean>((resolve): void => {
        const img: HTMLImageElement = new Image();

        img.onload  = (): void => resolve(true);
        img.onerror = (): void => resolve(false);

        img.src = testImages[format];
    });
};

/**
 * Get supported image formats by browser.
 */
const getSupportedImageFormats = async (): Promise<string[]> => {
    const CACHE_KEY: string         = 'supported_image_formats';
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

type FetchFuncOptions = Record<string | number, string | number>;

const fetchFunc = async (url: string, data: FetchFuncOptions | FormData = {}, method: string = 'POST'): Promise<any> => {
    type TypeHeaders = {
        "X-Requested-With": string;
        contentType?: string;
        "X-CSRF-TOKEN"?: string;
        "X-Supported-Image-Formats"?: string;
    };

    type TypeOptions = {
        method?: string,
        headers: TypeHeaders,
        body?: FormData | string
    };

    const supportedFormats: string[] = await getCachedSupportedFormats();
    const headers: TypeHeaders       = {
              'X-Requested-With':          'XMLHttpRequest',
              'X-CSRF-TOKEN':              (<HTMLMetaElement | null>findElem('meta[name="csrf-token"]'))?.content || '',
              'X-Supported-Image-Formats': supportedFormats.join(','), // "avif,webp" or "webp" or ""
          },
          contentType                = 'application/json;charset=utf-8';
    let options: TypeOptions         = {
        method,
        headers
    };

    if (method.toUpperCase() === 'GET') {
        options = { headers };
    } else if (!(data instanceof FormData)) {
        options.headers.contentType = contentType;
        options.body                = JSON.stringify(data);
    } else if (data instanceof FormData) {
        options.body = data;
    }

    const response: Response = await fetch(url, options);

    if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.statusText}`);
    }

    return await response.json();
};

const scrollToAnchor = (selector: string): void => {
    findArrayElems(selector).forEach((link: HTMLElement): void => {
        link.addEventListener('click', function (e: Event): void {
            e.preventDefault();

            findElem((this as HTMLLinkElement).href.replace(/^(.*)(?=#)/, ''))?.scrollIntoView({
                behavior: 'smooth'
            })
        })
    })
};

const toggleLoader = (classNameLoader: string, isShowLoader: boolean): void => {
    const loaderContainer = <HTMLElement | null>findElem(classNameLoader);

    if (isShowLoader) {
        removeClass(loaderContainer, 'invisible');
        addClass(document.body, 'overflow-hidden');
    } else {
        addClass(loaderContainer, 'invisible');
        removeClass(document.body, 'overflow-hidden');
    }
};

const stripUnit = (value: number | string): number => {
    // Ensure the value is a string, then remove any non-digit characters except the decimal point
    const numericValue: number = parseFloat(value as string);

    // If the numeric conversion is unsuccessful, return the original value
    if (isNaN(numericValue)) {
        return 0;
    }

    return numericValue;
};

const getBaseDocumentFontSize = (): number => {
    const doc: HTMLElement = document.documentElement;
    const computedStyle    = window.getComputedStyle(doc);

    return stripUnit(computedStyle.fontSize) || 16;
};

// Function to convert pixels to rems
const toRem = (pxValue: number | string, baseUnitSize: null | number | string = null, sizeUnit: string = 'rem'): string => {
    if (!baseUnitSize) {
        baseUnitSize = getBaseDocumentFontSize();
    }

    const numericPxValue: number      = stripUnit(pxValue);
    const numericBaseUnitSize: number = stripUnit(baseUnitSize);

    // Calculate rem value
    return `${numericPxValue / numericBaseUnitSize}${sizeUnit}`;
};

const normalizeNumber = (value: string | number): number => {
    if (typeof value == 'string') {
        value = parseFloat(value);
    } else {
        value = Math.abs(value);
    }

    if (!value || value < 1) {
        value = 0;
    }

    return value;
};

const togglePage = (loaderClassName: string, isBlockingPage: boolean): void => {
    blockBody(isBlockingPage);
    toggleLoader(loaderClassName, isBlockingPage);
};

export {
    throttle, scrollToTop, debounce, fetchFunc, scrollToAnchor,
    toggleLoader, isEmpty, scrollToBottom, stripUnit, toRem,
    getBaseDocumentFontSize, normalizeNumber, togglePage
};
