import { initAccordion } from '@ts-shared/accordion/initAccordion.ts';
import { $_ERROR_CLASS_NAME, $DEBOUNCE_DELAY, $LOADER_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import {
    addClass,
    debounce,
    fetchFunc,
    findArrayElems,
    findElem,
    httpBuildQueryString,
    isEmpty,
    isNaNValue,
    redirect,
    sprintF,
    toggleElement,
    toNumber,
} from '@ts-shared/lib/helpers.ts';
import type { WindowAppParams } from '@ts-types/global';
import type { URLParamsType } from '@ts-types/httpQueryBuild.ts';
import type { API } from 'nouislider';
import noUiSlider from 'nouislider';
import wNumb from 'wnumb';

interface FireSearchProductsEventResponseType {
    success: boolean;
    total_products?: number;
    message?: string;
}

const CHECKED_INPUTS_SELECTOR: string = '[data-filter-item-code]:checked';
const CATEGORY_FILTER_DRAWER = <HTMLElement | null>findElem('#category-filter-drawer');
const WINDOW_APP_PARAMS: WindowAppParams = window.app_params ?? {};
let COUNT_UPDATE_NO_UI_SLIDER: number = 0;
let INPUT_PRICE_FROM: HTMLInputElement | null;
let INPUT_PRICE_TO: HTMLInputElement | null;
let FILTER_GROUPS_ELS: HTMLElement[] | [];
let RESULTS_EL: HTMLElement | null;
let CATEGORY_FILTER_CONTROLS_EL: HTMLElement | null;
let CLEAR_ALL_BTN_EL: HTMLButtonElement | null;
let APPLY_BTN_EL: HTMLButtonElement | null;
let LOADER_EL: HTMLElement | null;

const normalizePrice = (price: string): number => {
    return parseInt(price.replace(/\D/g, ''), 10);
};

function handleNoUiSlider(): void {
    const stepsSlider = <HTMLElement | null>findElem('#category-filter-steps-slider', CATEGORY_FILTER_DRAWER);

    if (stepsSlider === null || INPUT_PRICE_FROM === null || INPUT_PRICE_TO === null) {
        return;
    }

    const filterRangeData = WINDOW_APP_PARAMS?.catalog_filter_price_data?.range;

    const inputs: HTMLInputElement[] = [INPUT_PRICE_TO, INPUT_PRICE_FROM];
    const startMin: number = +(filterRangeData?.selected_from ?? filterRangeData?.min ?? 0);
    const startMax: number = +(filterRangeData?.selected_to ?? filterRangeData?.max ?? 0);
    const step: number = +(filterRangeData?.step ?? 1);

    const NO_UI_SLIDER_API: API = noUiSlider.create(stepsSlider, {
        start: [startMin, startMax],
        step: step,
        range: {
            min: [startMin],
            max: [startMax],
        },
        format: wNumb({
            decimals: 0,
            prefix: `${stepsSlider?.dataset.currencySign ?? ''} `,
        }),
    });

    NO_UI_SLIDER_API.on('update', (values: (string | number)[], handle: number): void => {
        const value: string = values[handle] as string;

        if (handle) {
            // @ts-expect-error
            INPUT_PRICE_FROM.value = value;
        } else {
            // @ts-expect-error
            INPUT_PRICE_TO.value = value;
        }

        if (COUNT_UPDATE_NO_UI_SLIDER <= 2) {
            COUNT_UPDATE_NO_UI_SLIDER++;
        } else {
            fireSearchProductsEventDebounce();
        }
    });

    inputs.forEach((input: HTMLInputElement, handle: number): void => {
        input.addEventListener('change', function () {
            NO_UI_SLIDER_API.setHandle(handle, this.value);
        });

        input.addEventListener('keydown', function (e: KeyboardEvent): void {
            const values = NO_UI_SLIDER_API.get();
            // @ts-expect-error
            const value: number = toNumber(values[handle], Number.NaN);

            // [[handle0_down, handle0_up], [handle1_down, handle1_up]]
            const steps = NO_UI_SLIDER_API.steps();

            // [down, up]
            const step = steps[handle];

            let position: number | false | null = null;

            // 13 is enter,
            // 38 is key up,
            // 40 is key down.
            switch (e.keyCode) {
                case 13:
                    NO_UI_SLIDER_API.setHandle(handle, this.value);

                    break;
                case 38:
                    // Get step to go increase slider value (up)
                    position = step[1];

                    // false = no step is set
                    if (position === false) {
                        position = 1;
                    }

                    // null = edge of slider
                    if (position !== null) {
                        NO_UI_SLIDER_API.setHandle(handle, value + position);
                    }

                    break;
                case 40:
                    position = step[0];

                    if (position === false) {
                        position = 1;
                    }

                    if (position !== null) {
                        NO_UI_SLIDER_API.setHandle(handle, value - position);
                    }

                    break;
            }
        });
    });
}

const processCollectUrlParams = (): URLParamsType => {
    const urlParams: URLParamsType = {};

    for (const filterGroupEl of FILTER_GROUPS_ELS) {
        const filterGroupKey: string = filterGroupEl.dataset.filterGroupGetKey ?? '';

        const checkedInputsEls = <HTMLInputElement[] | []>findArrayElems(CHECKED_INPUTS_SELECTOR, filterGroupEl);

        if (isEmpty(checkedInputsEls)) {
            continue;
        }

        urlParams[filterGroupKey] = checkedInputsEls
            .map((inputEl: HTMLInputElement): string => inputEl.dataset.filterItemCode ?? '')
            .filter((code: string): boolean => code.trim() !== '');
    }

    const priceFrom: number = normalizePrice(INPUT_PRICE_TO?.value ?? '');
    const priceTo: number = normalizePrice(INPUT_PRICE_FROM?.value ?? '');

    if (!isNaNValue(priceFrom) && !isNaNValue(priceTo)) {
        urlParams[WINDOW_APP_PARAMS?.catalog_filter_price_data?.get_extra?.from_key ?? 'price_from'] = priceFrom;
        urlParams[WINDOW_APP_PARAMS?.catalog_filter_price_data?.get_extra?.to_key ?? 'price_to'] = priceTo;
    }

    return urlParams;
};

const fireSearchProductsEvent = (): void => {
    const urlParams: URLParamsType = processCollectUrlParams();

    if (isEmpty(urlParams)) {
        toggleElement(CATEGORY_FILTER_CONTROLS_EL, false);
        toggleElement(RESULTS_EL, false);

        return;
    }

    const urlQueries: string = httpBuildQueryString(urlParams, true);

    toggleElement(LOADER_EL, true);

    fetchFunc<FireSearchProductsEventResponseType>(
        `${WINDOW_APP_PARAMS?.catalog_filter_ajax_url}?${urlQueries}`,
        {},
        'GET',
    )
        .then((json: FireSearchProductsEventResponseType): void => {
            if (json.success && json.total_products !== undefined) {
                const totalResults: number = json.total_products;

                if (RESULTS_EL) {
                    RESULTS_EL.textContent = sprintF(RESULTS_EL.dataset.template ?? '%d products found', totalResults);

                    if (json.total_products > 0) {
                        APPLY_BTN_EL?.addEventListener('click', (): void => {
                            redirect(`${location.origin}${location.pathname}?${urlQueries}`);
                        });

                        toggleElement(APPLY_BTN_EL, true);
                    } else {
                        toggleElement(APPLY_BTN_EL, false);
                    }

                    toggleElement(RESULTS_EL, true);
                }

                toggleElement(CATEGORY_FILTER_CONTROLS_EL, true);
            } else if (json.message) {
                console.error('Error: ', json.message);

                if (RESULTS_EL) {
                    RESULTS_EL.textContent = json.message;

                    addClass(RESULTS_EL, $_ERROR_CLASS_NAME);
                    toggleElement(RESULTS_EL, true);
                }
            }
        })
        .catch((err) => console.error('err: ', err))
        .finally((): void => toggleElement(LOADER_EL, false));
};

const fireSearchProductsEventDebounce = debounce(fireSearchProductsEvent, $DEBOUNCE_DELAY);

function handleFilters(): void {
    for (const filterGroupEl of FILTER_GROUPS_ELS) {
        const filterInputsEls = <HTMLInputElement[] | []>findArrayElems('[data-filter-item-code]', filterGroupEl);
        const filterGroupKey: string = filterGroupEl.dataset.filterGroupGetKey ?? '';

        if (isEmpty(filterGroupKey)) {
            filterGroupEl.remove();

            continue;
        }

        filterInputsEls.forEach((filterInputEl: HTMLInputElement): void => {
            filterInputEl.addEventListener('change', (): void => {
                fireSearchProductsEventDebounce();
            });
        });
    }
}

const handleClearAllChoosenFilters = (): void => {
    CLEAR_ALL_BTN_EL?.addEventListener('click', (): void => {
        const checkedInputsEls = <HTMLInputElement[] | []>(
            findArrayElems(CHECKED_INPUTS_SELECTOR, CATEGORY_FILTER_DRAWER)
        );

        checkedInputsEls.forEach((input: HTMLInputElement): void => {
            input.checked = false;
        });

        toggleElement(CATEGORY_FILTER_CONTROLS_EL, false);
        toggleElement(RESULTS_EL, false);
    });
};

export const handleProductsFilter = (): void => {
    if (isEmpty(CATEGORY_FILTER_DRAWER)) {
        return;
    }

    INPUT_PRICE_FROM = <HTMLInputElement | null>findElem('#category-filter-steps-input-from');
    INPUT_PRICE_TO = <HTMLInputElement | null>findElem('#category-filter-steps-input-to');
    FILTER_GROUPS_ELS = <HTMLElement[] | []>findArrayElems('[data-filter-group-get-key]', CATEGORY_FILTER_DRAWER);
    RESULTS_EL = <HTMLElement | null>findElem('#category-filter-total-results', CATEGORY_FILTER_DRAWER);
    CLEAR_ALL_BTN_EL = <HTMLButtonElement | null>findElem('#category-filter-clear-all-btn', CATEGORY_FILTER_DRAWER);
    APPLY_BTN_EL = <HTMLButtonElement | null>findElem('#category-filter-apply-btn', CATEGORY_FILTER_DRAWER);
    CATEGORY_FILTER_CONTROLS_EL = <HTMLElement | null>findElem('#category-filter-controls', CATEGORY_FILTER_DRAWER);
    LOADER_EL = <HTMLElement | null>findElem(`.${$LOADER_CLASS_NAME}`, CATEGORY_FILTER_DRAWER);

    handleNoUiSlider();

    const filterAccordionsEls = <HTMLElement[] | []>findArrayElems('.accordion-item');

    filterAccordionsEls.forEach((accordionEl: HTMLElement): void => {
        initAccordion(accordionEl);
    });

    handleFilters();
    handleClearAllChoosenFilters();
};
