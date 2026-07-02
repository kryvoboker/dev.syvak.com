document.addEventListener('DOMContentLoaded', (): void => {
    document.querySelectorAll<HTMLElement>('[data-ukr-poshta-module]').forEach((moduleElement: HTMLElement): void => {
        moduleElement.addEventListener('click', (event: Event): void => {
            const target = event.target as HTMLElement | null;
            const button = target?.closest<HTMLElement>('[data-ukr-poshta-select]');

            if (!button) {
                return;
            }

            const field = button.dataset.ukrPoshtaField ?? '';
            const value = button.dataset.ukrPoshtaValue ?? '';

            moduleElement.dispatchEvent(new CustomEvent('ukr-poshta:selection-changed', {
                detail: {
                    field,
                    value,
                },
                bubbles: true,
            }));
        });
    });
});
