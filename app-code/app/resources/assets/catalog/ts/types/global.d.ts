import { HSDropdown, HSOverlay } from "flyonui/flyonui"

declare global {
    interface Window {
        HSDropdown: typeof HSDropdown;
        HSOverlay: typeof HSOverlay;
    }
}

export {};
