import { addClass, debounce, fetchFunc, findElem, removeClass }  from "@ts-shared/lib/helpers.ts";
import HSOverlay                                                 from "flyonui/src/js/plugins/overlay/index";
import { $DEBOUNCE_DELAY, $FLEX_CLASS_NAME, $HIDDEN_CLASS_NAME } from "@ts-shared/lib/constants.ts";

interface ProcessSearchProdsJsonResponse {
    success?: boolean;
    html?: string;
}

const fireSearch = async (
    resultContainerEl: HTMLElement | null,
    loaderEl: HTMLElement | null,
    action: string,
    keyword: string
): Promise<void> => {
    removeClass(loaderEl, $HIDDEN_CLASS_NAME);
    addClass(loaderEl, $FLEX_CLASS_NAME);

    await fetchFunc(`${action}?keyword=` + decodeURIComponent(keyword), {}, 'GET')
        .then((json: ProcessSearchProdsJsonResponse): void => {
            if (resultContainerEl && json.html) {
                resultContainerEl.innerHTML = json.html;
            }
        })
        .finally((): void => {
            removeClass(loaderEl, $FLEX_CLASS_NAME);
            addClass(loaderEl, $HIDDEN_CLASS_NAME);
        });
};

const processSearchProds = async (): Promise<void> => {
    const mobSearchFormEl = <HTMLFormElement | null>findElem('.mob-search-form');

    if (!mobSearchFormEl) {
        return;
    }

    const inputEl            = <HTMLInputElement | null>findElem('.mob-search-input');
    const loaderEl           = <HTMLElement | null>findElem('.mob-search .loader');
    const resultContainerEl  = <HTMLElement | null>findElem('.mob-search-results');
    const fireSearchDebounce = debounce(fireSearch, $DEBOUNCE_DELAY);

    inputEl?.addEventListener('input', function (this: HTMLInputElement): void {
        if (this.value.trim().length >= this.minLength) {
            fireSearchDebounce(
                resultContainerEl,
                loaderEl,
                mobSearchFormEl.action,
                inputEl?.value ?? ''
            );
        }
    });
};

export const handleMobSearch = (): void => {
    const openMenuBtn     = <HTMLButtonElement | null>findElem('.open-mob-search-btn');
    const menuContainerEl = <HTMLElement | null>findElem('.mob-search');

    if (!menuContainerEl) {
        return;
    }

    const modalInstance = new HSOverlay(menuContainerEl);

    openMenuBtn?.addEventListener('click', (): void => {
        modalInstance.open();

        processSearchProds();
    });
};
