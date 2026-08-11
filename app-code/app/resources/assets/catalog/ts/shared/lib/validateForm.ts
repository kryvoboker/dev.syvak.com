import { $_WAS_VALIDATED_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import { addClass, findArrayElems } from '@ts-shared/lib/helpers.ts';

export const handleValidateForms = (forms: string = '._needs-validation'): void => {
    findArrayElems<HTMLFormElement>(forms).forEach((form: HTMLFormElement | HTMLElement): void => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        form.addEventListener(
            'submit',
            (e: SubmitEvent): void => {
                if (!(form as HTMLFormElement).checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                addClass(form, $_WAS_VALIDATED_CLASS_NAME);
            },
            false,
        );
    });
};
