import { addClass, findArrayElems }   from "@ts-shared/lib/helpers.ts";
import { $_WAS_VALIDATED_CLASS_NAME } from "@ts-shared/lib/constants.ts";

export const handleValidateForms = (forms : string = '._needs-validation') : void => {
    findArrayElems(forms).forEach((form : HTMLElement | HTMLFormElement) : void => {
        (form as HTMLFormElement).addEventListener('submit', (e : SubmitEvent) : void => {
            if (!(form as HTMLFormElement).checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }

            addClass(form, $_WAS_VALIDATED_CLASS_NAME);
        }, false);
    })
};