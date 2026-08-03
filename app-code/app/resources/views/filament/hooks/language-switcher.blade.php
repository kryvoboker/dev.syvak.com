@php
    $current_route = request()->route()->getName();
    $route_params = request()->route()->parameters();
@endphp

<div class="fi-topbar-item">
    <x-filament::dropdown
        maxHeight="15.625rem"
        placement="bottom-start"
        teleport="true"
    >
        <x-slot name="trigger">
            <button
                type="button"
                class="fi-topbar-item fi-btn"
            >
                <x-filament::icon
                    icon="heroicon-m-language"
                    class="h-5 w-5"
                />

                <span class="font-medium">
                    {{ Str::upper($current_locale) }}
                </span>
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($languages as $language)
                <x-filament::dropdown.list.item
                    :color="$current_locale === $language->code ? 'primary' : null"
                    icon="heroicon-m-chevron-right"
                    :href="route($current_route, array_merge($route_params, [$locale_key => $language->code]))"
                    tag="a"
                >
                    {{ $language->name }}
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
