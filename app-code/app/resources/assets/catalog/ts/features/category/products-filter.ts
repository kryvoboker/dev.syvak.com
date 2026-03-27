import {
    debounce, fetchFunc, findArrayElems, findElem,
    httpBuildQueryString, isEmpty, removeClass, sprintF
} from "@ts-shared/lib/helpers.ts";
import { initAccordion }      from "@ts-shared/accordion/initAccordion.ts";
import type { API }           from "nouislider";
import noUiSlider             from "nouislider";
import wNumb                  from "wnumb";
import { $HIDDEN_CLASS_NAME } from "@ts-shared/lib/constants.ts";
import type { URLParamsType } from "@ts-types/httpQueryBuild.ts";

const CATEGORY_FILTER_DRAWER = <HTMLElement | null>findElem('#category-filter-drawer');
let FILTER_GROUPS_ELS: HTMLElement[] | [];
let RESULTS_EL: HTMLElement | null;
let CLEAR_ALL_BTN_EL: HTMLButtonElement | null;
let APPLY_BTN_EL: HTMLButtonElement | null;

function handleNoUiSlider(): void {
    const stepsSlider = <HTMLElement | null>findElem('#category-filter-steps-slider');
    const inputFrom   = <HTMLInputElement | null>findElem('#category-filter-steps-input-from');
    const inputTo     = <HTMLInputElement | null>findElem('#category-filter-steps-input-to');

    if (stepsSlider === null || inputFrom === null || inputTo === null) {
        return;
    }

    const inputs: HTMLInputElement[] = [inputTo, inputFrom];
    const startMin: number           = +(inputFrom?.dataset.startMin ?? 0);
    const startMax: number           = +(inputTo?.dataset.startMax ?? 0);

    const NO_UI_SLIDER_API: API = noUiSlider.create(stepsSlider, {
        start:  [startMin, startMax],
        step:   1,
        range:  {
            'min': [startMin],
            'max': [startMax]
        },
        format: wNumb({
            decimals: 0,
            prefix:   (stepsSlider?.dataset.currencySign ?? '') + ' ',
        })
    });

    NO_UI_SLIDER_API.on('update', function (values: (string | number)[], handle: number): void {
        if (handle) {
            inputFrom.value = values[handle] as string;
        } else {
            inputTo.value = values[handle] as string;
        }
    });

    inputs.forEach(function (input: HTMLInputElement, handle: number) {
        input.addEventListener('change', function () {
            NO_UI_SLIDER_API.setHandle(handle, this.value);
        });

        input.addEventListener('keydown', function (e: KeyboardEvent): void {
            const values        = NO_UI_SLIDER_API.get();
            // @ts-ignore
            const value: number = Number(values[handle]);

            // [[handle0_down, handle0_up], [handle1_down, handle1_up]]
            const steps = NO_UI_SLIDER_API.steps();

            // [down, up]
            const step = steps[handle];

            let position;

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

const fireSearchProductsEvent = (): void => {
    const urlParams: URLParamsType = {};

    for (const filterGroupEl of FILTER_GROUPS_ELS) {
        const filterGroupKey: string = filterGroupEl.dataset.filterGroupGetKey ?? '';

        const checkedInputsEls    = <HTMLInputElement[] | []>findArrayElems('[data-filter-item-code]:checked', filterGroupEl);
        urlParams[filterGroupKey] = checkedInputsEls
            .map((inputEl: HTMLInputElement): string => inputEl.dataset.filterItemCode ?? '')
            .filter((code: string): boolean => code.trim() !== '');
    }

    const url: string = httpBuildQueryString(urlParams);

    console.log('url: ', url);

    // TODO: change hardcoded URL to dynamic one
    // TODO: need dev prepare API response
    fetchFunc('/en/category/t-shirts/filters?' + url, {}, 'GET')
        .then(res => {
            console.log('res: ', res);

            if (res['success'] === true) {
                const totalResults: number = res['total_products'];

                if (RESULTS_EL !== null) {
                    RESULTS_EL.textContent = sprintF(
                        RESULTS_EL.dataset.template ?? '%d products found',
                        totalResults
                    );

                    removeClass(RESULTS_EL, $HIDDEN_CLASS_NAME);
                }
            }
        })
        .catch(err => console.error('err: ', err));
};

function handleFilters(): void {
    if (isEmpty(CATEGORY_FILTER_DRAWER)) {
        return;
    }

    FILTER_GROUPS_ELS = <HTMLElement[] | []>findArrayElems('[data-filter-group-get-key]', CATEGORY_FILTER_DRAWER);
    RESULTS_EL        = <HTMLElement | null>findElem('#category-filter-total-results', CATEGORY_FILTER_DRAWER);
    CLEAR_ALL_BTN_EL  = <HTMLButtonElement | null>findElem('#category-filter-clear-all-btn', CATEGORY_FILTER_DRAWER);
    APPLY_BTN_EL      = <HTMLButtonElement | null>findElem('#category-filter-apply-btn', CATEGORY_FILTER_DRAWER);

    for (const filterGroupEl of FILTER_GROUPS_ELS) {
        const filterInputsEls        = <HTMLInputElement[] | []>findArrayElems('[data-filter-item-code]', filterGroupEl);
        const filterGroupKey: string = filterGroupEl.dataset.filterGroupGetKey ?? '';

        if (isEmpty(filterGroupKey)) {
            filterGroupEl.remove();

            continue;
        }

        const fireSearchProductsEventDebounce = debounce(fireSearchProductsEvent, 1000);

        filterInputsEls.forEach((filterInputEl: HTMLInputElement): void => {
            filterInputEl.addEventListener('change', function (): void {
                fireSearchProductsEventDebounce();
            });
        });
    }
}

export const handleProductsFilter = (): void => {
    handleNoUiSlider();

    const filterAccordionsEls = <HTMLElement[] | []>findArrayElems('.accordion-item');

    filterAccordionsEls.forEach((accordionEl: HTMLElement): void => {
        initAccordion(accordionEl);
    });

    // TODO: add event listener to clear all button
    // TODO: add event listener to apply button
    handleFilters();
};
