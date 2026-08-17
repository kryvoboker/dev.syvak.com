<div class="overlay overlay-open:translate-x-0 drawer drawer-start hidden main-mob-menu bg-black"
     id="main-mob-menu"
     role="dialog"
     tabindex="-1">
    <div class="drawer-header flex-col items-stretch pt-38px px-4 pb-6">
        <button type="button" class="size-8 btn btn-text btn-circle ms-auto mb-4"
                aria-expanded="true"
                aria-controls="{{ __('storefront/default.aria_labels.close_mob_main_menu') }}"
                data-overlay="#main-mob-menu">
            <span class="icon-[iconamoon--close] custom-icon size-8"></span>
        </button>

        <div class="flex md:hidden items-center justify-between gap-x-2 border-y border-y-opacity-light-gray-40% py-18px">
            <x-storefront::common.language-swithcer class="inline-flex"
                                                 :languages="$header_data['languages']"
                                                 :sluggable_type="$header_data['sluggable_type']"
                                                 :slug="$header_data['slug']"
                                                 :variant_slug="$header_data['variant_slug']"
                                                 :attribute_filters="$header_data['attribute_filters']" />

            <button class="open-mob-search-btn btn btn-text btn-circle"
                    type="button"
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="{{ __('storefront/default.placeholders.search') }}">
                <span class="icon-[si--search-line] custom-icon"></span>
            </button>
        </div>
    </div>

    <div class="drawer-body px-4">
        @foreach($header_data['categories'] as $category_data)
            <a class="dark-btn flex items-center justify-between gap-x-2 font-cormorant-garamond
                      font-bold text-lg leading-none tracking-normal uppercase border-b
                      border-b-opacity-light-gray-40% py-4"
               href="{{ localized_route('localized.catalog.category.show', ['slug' => $category_data['slug']]) }}">
                <span>
                    {{ $category_data['descriptions']['name'] }}
                </span>

                <span class="icon-[ep--arrow-right] custom-icon"></span>
            </a>
        @endforeach
    </div>

    <div class="drawer-footer gap-x-6 px-4">
        @foreach($header_data['socials'] as $social_data)
            <a class="custom-icon size-10" href="{{ $social_data['url'] }}">
                {!! $social_data['svg_icon'] !!}
            </a>
        @endforeach
    </div>
</div>
