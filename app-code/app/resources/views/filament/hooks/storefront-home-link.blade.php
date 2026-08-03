<div class="fi-topbar-item">
    <a
        class="fi-topbar-item fi-ac-btn-action fi-btn fi-color fi-color-success fi-bg-color-600 hover:fi-bg-color-500 dark:fi-bg-color-600 dark:hover:fi-bg-color-500
               fi-text-color-0 hover:fi-text-color-0 dark:fi-text-color-0 dark:hover:fi-text-color-0"
        target="_blank"
        href="{{ localized_route('catalog.home') }}"
    >
        <x-filament::icon
            icon="heroicon-m-home"
            class="h-5 w-5"
        />

        {{ __('actions.open_website') }}
    </a>
</div>
