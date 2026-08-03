import { addClass, findArrayElems, isContainsClass } from '@ts-shared/lib/helpers.ts';
import type { IHTMLElementFloatingUI } from 'flyonui/flyonui';
import HSDropdown from 'flyonui/src/js/plugins/dropdown/index';

interface InitDropdownOptions {
    dropdownSelector: string;
    closeButtonSelector?: string;
    initializedClassName?: string;
}

/**
 * Initializes FlyonUI dropdowns with safe one-time binding per node.
 * Reusable for language switcher, sorting dropdown, and future dropdown UI.
 */
export const initDropdown = ({
    dropdownSelector,
    closeButtonSelector = '',
    initializedClassName = 'is-dropdown-initialized',
}: InitDropdownOptions): void => {
    const dropdownElements = <IHTMLElementFloatingUI[] | []>findArrayElems(dropdownSelector);

    dropdownElements.forEach((dropdownElement: IHTMLElementFloatingUI): void => {
        if (isContainsClass(dropdownElement, initializedClassName)) {
            return;
        }

        addClass(dropdownElement, initializedClassName);

        const dropdownInstance = new HSDropdown(dropdownElement);

        if (!closeButtonSelector) {
            return;
        }

        const closeButtons = <HTMLElement[] | []>findArrayElems(closeButtonSelector, dropdownElement);

        closeButtons.forEach((closeButton: HTMLElement): void => {
            closeButton.addEventListener('click', (): void => {
                dropdownInstance.close();
            });
        });
    });
};
