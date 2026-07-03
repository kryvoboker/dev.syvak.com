import {
    $DEBOUNCE_DELAY,
    $FLEX_CLASS_NAME,
    $GRID_COLS_1_CLASS_NAME,
    $GRID_COLS_2_CLASS_NAME,
    $HIDDEN_CLASS_NAME,
    $LOADER_CLASS_NAME,
} from '@ts-shared/lib/constants.ts';
import { addClass, debounce, fetchFunc, findElem, removeClass } from '@ts-shared/lib/helpers.ts';
import HSOverlay from 'flyonui/src/js/plugins/overlay/index';

interface HandleMobSearch {
    openSearchBtn: string;
    searchContainer: string;
    searchInput: string;
    searchResults: string;
    searchForm: string;
}

interface FireSearch extends Omit<HandleMobSearch, 'openSearchBtn'> {}

interface ProcessSearchProdsJsonResponse {
    success?: boolean;
    html?: string;
    total_products: number;
}

const processSearchProds = async (
    resultContainerEl: HTMLElement | null,
    loaderEl: HTMLElement | null,
    action: string,
    keyword: string,
): Promise<void> => {
    removeClass(loaderEl, $HIDDEN_CLASS_NAME);
    addClass(loaderEl, $FLEX_CLASS_NAME);

    await fetchFunc<ProcessSearchProdsJsonResponse>(`${action}?keyword=${decodeURIComponent(keyword)}`, {}, 'GET')
        .then((json: ProcessSearchProdsJsonResponse): void => {
            if (resultContainerEl && json.html) {
                if (json.total_products > 1) {
                    removeClass(resultContainerEl, $GRID_COLS_1_CLASS_NAME);
                    addClass(resultContainerEl, $GRID_COLS_2_CLASS_NAME);
                } else if (json.total_products === 1) {
                    removeClass(resultContainerEl, $GRID_COLS_2_CLASS_NAME);
                    addClass(resultContainerEl, $GRID_COLS_1_CLASS_NAME);
                }

                resultContainerEl.innerHTML = json.html;
            }
        })
        .finally((): void => {
            removeClass(loaderEl, $FLEX_CLASS_NAME);
            addClass(loaderEl, $HIDDEN_CLASS_NAME);
        });
};

const fireSearch = async (params: FireSearch): Promise<void> => {
    const mobSearchFormEl = <HTMLFormElement | null>findElem(params.searchForm);

    if (!mobSearchFormEl) {
        return;
    }

    const inputEl = <HTMLInputElement | null>findElem(params.searchInput);
    const loaderEl = <HTMLElement | null>findElem(`${params.searchContainer} .${$LOADER_CLASS_NAME}`);
    const resultContainerEl = <HTMLElement | null>findElem(params.searchResults);
    const processSearchProdsDebounce = debounce(processSearchProds, $DEBOUNCE_DELAY);

    inputEl?.addEventListener('input', function (this: HTMLInputElement): void {
        if (this.value.trim().length >= this.minLength) {
            processSearchProdsDebounce(
                resultContainerEl,
                loaderEl,
                mobSearchFormEl.dataset.ajaxSearchUrl ?? '',
                inputEl?.value ?? '',
            );
        }
    });
};

export const handleMobSearch = (params: HandleMobSearch): void => {
    const openSearchBtn = <HTMLButtonElement | null>findElem(params.openSearchBtn);
    const menuContainerEl = <HTMLElement | null>findElem(params.searchContainer);

    if (!menuContainerEl) {
        return;
    }

    const modalInstance = new HSOverlay(menuContainerEl);

    openSearchBtn?.addEventListener('click', (): void => {
        modalInstance.open();

        fireSearch(params);
    });
};
