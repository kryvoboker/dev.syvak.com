import { $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import {
    addClass,
    fetchFunc,
    findElem,
    httpBuildQueryString,
    isEmpty,
    scrollToTop,
    setHistoryState,
    toggleElement,
} from '@ts-shared/lib/helpers.ts';
import { reportCriticalFrontendError } from '@ts-shared/lib/reportCriticalError.ts';
import type { WindowAppParams } from '@ts-types/global';
import type { URLParamsType } from '@ts-types/httpQueryBuild.ts';

interface ProcessLoadMoreProductsResponse {
    html?: string;
    message?: string;
    success?: boolean;
    is_has_more_pages?: boolean;
    next_page?: number | null;
}

const WINDOW_APP_PARAMS: WindowAppParams = window.app_params ?? {};
const CATEGORY_CONTENT_CONTAINER_EL = <HTMLElement | null>findElem('.category__content-container');
const LOAD_MORE_PRODUCTS_AJAX_URL: string = WINDOW_APP_PARAMS.load_more_products_ajax_url ?? '';
let IS_HAS_MORE_PAGES: boolean = Boolean(WINDOW_APP_PARAMS.is_has_more_pages);

const processLoadMoreProducts = (): void => {
    const loadMoreProductsBtnEl = <HTMLButtonElement | null>findElem('.load-more-prods-btn');

    loadMoreProductsBtnEl?.addEventListener('click', function (this: HTMLButtonElement): void {
        const urlParams: URLParamsType = {};
        const nextPage: number = WINDOW_APP_PARAMS.next_page ?? 0;

        if (isEmpty(nextPage)) {
            return;
        }

        const arrowDownIconEl = <HTMLElement | null>findElem('.arrow-down-icon', this);
        const roundedArrowIconEl = <HTMLElement | null>findElem('.rounded-arrow-icon', this);

        toggleElement(arrowDownIconEl, false);
        toggleElement(roundedArrowIconEl, true);

        this.disabled = true;

        urlParams.page = nextPage;
        urlParams.page_type = WINDOW_APP_PARAMS.page_type ?? '';

        const urlQueries: string = httpBuildQueryString(urlParams, true);
        const url: string = `${location.origin}${location.pathname}?${urlQueries}`;

        fetchFunc<ProcessLoadMoreProductsResponse>(`${LOAD_MORE_PRODUCTS_AJAX_URL}?${urlQueries}`, {}, 'GET')
            .then((json: ProcessLoadMoreProductsResponse): void => {
                if (json.success && json.html && CATEGORY_CONTENT_CONTAINER_EL) {
                    CATEGORY_CONTENT_CONTAINER_EL.innerHTML = json.html;

                    IS_HAS_MORE_PAGES = Boolean(json.is_has_more_pages);
                    WINDOW_APP_PARAMS.next_page = json.next_page;

                    setHistoryState(url);
                    scrollToTop('#category');
                    handleLoadMoreProducts();
                } else if (json.message) {
                    console.error('Error: ', json.message);

                    IS_HAS_MORE_PAGES = false;
                    WINDOW_APP_PARAMS.next_page = null;
                }

                if (!IS_HAS_MORE_PAGES) {
                    addClass(this, $HIDDEN_CLASS_NAME);
                }
            })
            .catch((err) => {
                reportCriticalFrontendError(err);
                console.error('err: ', err);
            })
            .finally((): void => {
                toggleElement(arrowDownIconEl, true);
                toggleElement(roundedArrowIconEl, false);
                this.disabled = false;
            });
    });
};

export const handleLoadMoreProducts = (): void => {
    if (IS_HAS_MORE_PAGES && !isEmpty(CATEGORY_CONTENT_CONTAINER_EL) && !isEmpty(LOAD_MORE_PRODUCTS_AJAX_URL)) {
        processLoadMoreProducts();
    }
};
