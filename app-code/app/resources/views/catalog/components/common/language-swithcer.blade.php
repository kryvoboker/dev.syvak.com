@props([
    'languages' => [],
    'sluggable_type' => null,
    'slug' => null,
    'variant_slug' => null,
    'attribute_filters' => [],
    'current_locale' => app()->getLocale(),
])

@php
    $current_route = request()->route()->getName();
    $route_params = request()->route()->parameters();
    $slug_variants = get_slug_variants(
        $sluggable_type ?? null,
        $slug ?? null,
        $variant_slug ?? null,
        is_array($attribute_filters) ? $attribute_filters : [],
    );
@endphp

<div {{ $attributes->merge(['class' => 'dropdown dropdown-lang-menu relative']) }}>
    <button type="button" class="dropdown-toggle dropdown-lang-menu-btn flex items-center uppercase"
            id="dropdown-lang-menu-btn"
            aria-haspopup="menu"
            aria-expanded="false"
            aria-label="Dropdown">
        {{ $current_locale }}

        <div class="flex items-center justify-center p-2">
            <span class="icon-[solar--alt-arrow-down-line-duotone] dropdown-open:rotate-180 size-19px text-white duration-100 ease-in-out"></span>
        </div>
    </button>

    <ul class="dropdown-menu dropdown-open:opacity-100 hidden max-w-36 w-full bg-black border border-opacity-light-gray-40% rounded-none p-4"
        role="menu"
        aria-orientation="vertical"
        aria-labelledby="dropdown-lang-menu-btn">
        <button class="close-dropdown-lang-menu-btn block ms-auto">
            <span class="icon-[ic--baseline-close] size-5 text-white"></span>
        </button>

        @foreach ($languages as $language)
            <li class="flex items-center gap-x-2 w-full border-b border-b-opacity-light-gray-40% dark-btn">
                @if ($language->code == $current_locale)
                    <span class="flex items-center gap-x-2">
                        <span class="icon-[material-symbols--square] shrink-0 size-2 text-white"></span>

                        <span
                            class="block w-full text-white p-3"
                            aria-current="true">
                            {{ $language->name }}
                        </span>
                    </span>
                @else
                    @php
                        $next_route_params = array_merge($route_params, [
                            $locale_key => $language->code,
                        ]);
                        $next_route_name = $current_route;
                        $localized_slug_data = $slug_variants[$language->code] ?? null;

                        if (is_array($localized_slug_data)) {
                            $next_route_params = array_merge($next_route_params, $localized_slug_data);

                            if (filled((string) ($localized_slug_data['variant_slug'] ?? ''))) {
                                $next_route_name = 'localized.catalog.product.variant.show';
                            }
                        } elseif (is_string($localized_slug_data) && isset($slug)) {
                            $next_route_params = array_merge($next_route_params, [
                                'slug' => $localized_slug_data,
                            ]);
                        }
                    @endphp

                    <span class="flex items-center gap-x-2 w-full">
                        <span class="material-symbols--square shrink-0 size-2 bg-transparent"></span>

                        <a class="block w-full p-3"
                           href="{{ route($next_route_name, $next_route_params) }}">
                            {{ $language->name }}
                        </a>
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
