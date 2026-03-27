import { findArrayElems } from "@ts-shared/lib/helpers.ts";
import { initAccordion }  from "@ts-shared/accordion/initAccordion.ts";
import { findElem }       from "@ts-shared/lib/helpers.ts";
import noUiSlider         from "nouislider";
import type { API }       from "nouislider";
import wNumb              from "wnumb";

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

export const handleProductsFilter = (): void => {
    handleNoUiSlider();

    const filterAccordionsEls = <HTMLElement[] | []>findArrayElems('.accordion-item');

    filterAccordionsEls.forEach((accordionEl: HTMLElement): void => {
        initAccordion(accordionEl);
    });
};
