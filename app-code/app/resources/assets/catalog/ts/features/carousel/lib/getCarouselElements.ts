import { findArrayElems } from "@ts-shared/lib/helpers.ts";

export function getCarouselElements(): HTMLElement[] {
    return findArrayElems('[data-main-carousel]');
}
