import { $HIDDEN_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import { findArrayElems, findElem, toggleClass, toStringValue } from '@ts-shared/lib/helpers.ts';

const updatePaymentInformationVisibility = (): void => {
    const selectedPaymentMethod = (<HTMLInputElement | null>findElem('[data-checkout-payment-method-input]:checked'))
        ?.value;

    (<HTMLElement[] | []>findArrayElems('[data-checkout-payment-information]')).forEach(
        (element: HTMLElement): void => {
            const isVisible = element.dataset.checkoutPaymentInformation === selectedPaymentMethod;

            toggleClass(element, $HIDDEN_CLASS_NAME, !isVisible);
            element.hidden = !isVisible;
            element.setAttribute('aria-hidden', toStringValue(!isVisible));
        },
    );
};

const initializeBankTransferPaymentInformation = (): void => {
    document.addEventListener('change', (event: Event): void => {
        const target = event.target as HTMLElement | null;

        if (target?.matches('[data-checkout-payment-method-input]')) {
            updatePaymentInformationVisibility();
        }
    });

    updatePaymentInformationVisibility();
};

document.addEventListener('checkout:initialized', updatePaymentInformationVisibility);

initializeBankTransferPaymentInformation();
