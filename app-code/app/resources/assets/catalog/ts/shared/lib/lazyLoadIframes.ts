import { $LAZY_LOAD_CLASS_NAME } from '@ts-shared/lib/constants.ts';
import {
    addClass,
    createHtmlElement,
    findArrayElems,
    findElem,
    getDataset,
    removeClass,
} from '@ts-shared/lib/helpers.ts';

const processLoad = (input: HTMLElement): void => {
    const parentBlock: HTMLElement | null = input.parentElement as HTMLElement | null;

    if (!parentBlock) {
        return;
    }

    const divEl: HTMLDivElement = <HTMLDivElement>createHtmlElement('div');
    const iframeEl: HTMLIFrameElement = <HTMLIFrameElement>createHtmlElement('iframe');
    const commonFullPageLoaderEl: HTMLElement | null = findElem('.common-full-page-loader');

    if (commonFullPageLoaderEl) {
        divEl.insertAdjacentElement('afterbegin', commonFullPageLoaderEl);
    }

    addClass(parentBlock, $LAZY_LOAD_CLASS_NAME);

    divEl.className = 'relative';
    divEl.style.paddingTop = '55%';

    iframeEl.className = 'absolute inset-0 w-full h-full rounded-3xl';
    iframeEl.src = getDataset(input, 'ajaxIframe') ?? '';
    iframeEl.width = '560';
    iframeEl.height = '314';
    iframeEl.allowFullscreen = true;

    divEl.insertAdjacentElement('beforeend', iframeEl);
    parentBlock.insertAdjacentElement('beforeend', divEl);

    iframeEl.addEventListener('load', (): void => {
        removeClass(parentBlock, $LAZY_LOAD_CLASS_NAME);
    });

    commonFullPageLoaderEl?.remove();
    input.remove();
};

export const handleLazyLoadIframes = (): void => {
    const observer = new IntersectionObserver((entries, obs): void => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                processLoad(entry.target as HTMLElement);
                obs.unobserve(entry.target);
            }
        });
    });

    findArrayElems('[data-ajax-iframe]').forEach((input: HTMLElement): void => {
        observer.observe(input);
    });
};
