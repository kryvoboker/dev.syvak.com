import { findArrayElems, getClosestParentEl, getDataset } from '@ts-shared/lib/helpers.ts';

document.addEventListener('DOMContentLoaded', (): void => {
    (<HTMLElement[] | []>findArrayElems('[data-ukr-poshta-module]')).forEach((moduleElement: HTMLElement): void => {
        moduleElement.addEventListener('click', (event: Event): void => {
            const target = event.target as HTMLElement | null;
            const button = getClosestParentEl('[data-ukr-poshta-select]', target);

            if (!button) {
                return;
            }

            const field = getDataset(button, 'ukrPoshtaField') ?? '';
            const value = getDataset(button, 'ukrPoshtaValue') ?? '';

            moduleElement.dispatchEvent(
                new CustomEvent('ukr-poshta:selection-changed', {
                    detail: {
                        field,
                        value,
                    },
                    bubbles: true,
                }),
            );
        });
    });
});
