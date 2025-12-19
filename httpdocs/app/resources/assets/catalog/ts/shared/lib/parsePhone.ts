import { findArrayElems } from "@ts-shared/lib/helpers.ts";

const findPositionSelection = (string : string) : number => {
    let indexLetter : number = 0;

    while (typeof string[indexLetter] != 'undefined' && string[indexLetter] != '_' && string.length > indexLetter) {
        indexLetter++;
    }

    return indexLetter;
};

const replaceTempValue = (tempValue : string, pattern : string) : string => {
    const regExp : RegExp = new RegExp(pattern);

    return tempValue.replace(regExp, '');
};

const checkTempValue = (tempValue : string, pattern : string) : boolean => {
    const regExp : RegExp = new RegExp(pattern);

    return regExp.test(tempValue);
};

const processTempValue = (phone : HTMLInputElement, oldStartPosition : number) : string => {
    let tempValue : string = phone.value.replace(/\D+/g, '');

    if (oldStartPosition < 6) {
        tempValue = replaceTempValue(tempValue, '^[^0]+');
    }

    if (checkTempValue(tempValue, '^380')) {
        tempValue = replaceTempValue(tempValue, '^380');
    } else if (checkTempValue(tempValue, '^38')) {
        tempValue = replaceTempValue(tempValue, '^38');
    } else if (checkTempValue(tempValue, '^80')) {
        tempValue = replaceTempValue(tempValue, '^80');
    } else if (checkTempValue(tempValue, '^3')) {
        tempValue = replaceTempValue(tempValue, '^3');
    } else if (checkTempValue(tempValue, '^8')) {
        tempValue = replaceTempValue(tempValue, '^8');
    } else if (checkTempValue(tempValue, '^0')) {
        tempValue = replaceTempValue(tempValue, '^0');
    }

    return tempValue;
};

const parsePhone = (_e : InputEvent, phone : HTMLInputElement) : void => {
    let startPosition : number    = phone.selectionStart ?? 0,
        oldStartPosition : number = startPosition,
        pattern                   = '+38 (0__) ___-__-__',
        tempValue                 = processTempValue(phone, oldStartPosition);

    for (let numberIndex = 0 ; numberIndex < tempValue.length ; numberIndex++) {
        pattern = pattern.replace(/_/, tempValue[numberIndex]);
    }

    if (startPosition < 6) {
        startPosition = 6;
    }

    phone.value = pattern;

    if (_e.inputType == 'deleteContentBackward') {
        phone.selectionStart = phone.selectionEnd = startPosition;
    } else {
        phone.selectionStart = phone.selectionEnd = findPositionSelection(pattern);
    }
};

const checkClickStartPosition = (phone : HTMLInputElement) : void => {
    const startPosition : number = phone.selectionStart ?? 0;

    if (startPosition < 6) {
        phone.selectionStart = phone.selectionEnd = 6;
    }
};

export const handleParsePhone = () : void => {
    const inputFields = <HTMLInputElement[] | []>findArrayElems('input[type=tel]');

    inputFields.forEach((phone : HTMLInputElement) : void => {
        phone.addEventListener('click', () : void => checkClickStartPosition(phone));
        phone.addEventListener('input', (e : Event) : void => parsePhone(e as InputEvent, phone));
    });
};
