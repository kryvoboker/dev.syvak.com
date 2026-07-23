@include('catalog.pages.partials.common.main-mob-menu')
@include('catalog.pages.partials.search.mob-search')
@include('catalog.pages.partials.search.pc-search')

<header class="header sticky top-0 border-b border-b-opacity-light-gray-40% backdrop-blur-[6px] z-10">
    <nav>
        <div class="container">
            <div class="flex items-center justify-between gap-x-1">
                <button class="burger-menu open-main-mob-menu-btn lg:hidden"
                        type="button"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="{{ __('catalog/default.labels.toggle_main_menu') }}">
                    <span class="icon-[qlementine-icons--menu-burger-16] custom-icon"></span>
                </button>

                @if(isset($page_type) && $page_type == config('page-settings.page_type.home'))
                    <div>
                        <x-catalog::common.img
                                class="object-contain"
                                :urls_data="$header_data['logo_data']['urls']"
                                :size="$header_data['logo_data']['width']"
                                sizes="(max-width: {{ $max_viewport_width }}px) {{ $header_data['logo_data']['width'] * 2 }}px,
                               {{ $header_data['logo_data']['width'] * 2 }}px"
                                width="{{ $header_data['logo_data']['width'] }}"
                                height="{{ $header_data['logo_data']['height'] }}"
                                alt="SYVAK"
                        />
                    </div>
                @else
                    <a href="{{ localized_route('catalog.home') }}">
                        <x-catalog::common.img
                                class="object-contain"
                                :urls_data="$header_data['logo_data']['urls']"
                                :size="$header_data['logo_data']['width']"
                                sizes="(max-width: {{ $max_viewport_width }}px) {{ $header_data['logo_data']['width'] * 2 }}px,
                               {{ $header_data['logo_data']['width'] * 2 }}px"
                                width="{{ $header_data['logo_data']['width'] }}"
                                height="{{ $header_data['logo_data']['height'] }}"
                                alt="SYVAK"
                        />
                    </a>
                @endif

                <div class="hidden lg:flex lg:items-center lg:justify-between lg:gap-x-8 uppercase">
                    <button class=""
                            type="button"
                            aria-haspopup="dialog"
                            aria-expanded="false"
                            aria-controls="{{ __('catalog/default.labels.toggle_catalog_menu') }}">
                        {{ __('catalog/default.buttons.catalog') }}
                    </button>

                    <a href="{{ localized_route('localized.catalog.category.show', ['slug' => $header_data['hoodie_category']['slug']]) }}">
                        {{ $header_data['hoodie_category']['descriptions']['name'] }}
                    </a>

                    <a href="{{ localized_route('localized.catalog.category.show', ['slug' => $header_data['exclusive_gifts_category']['slug']]) }}">
                        {{ $header_data['exclusive_gifts_category']['descriptions']['name'] }}
                    </a>
                </div>

                <div class="flex items-center justify-between gap-x-7">
                    <x-catalog::common.language-swithcer class="hidden md:inline-flex"
                                                         :languages="$header_data['languages']"
                                                         :sluggable_type="$header_data['sluggable_type']"
                                                         :slug="$header_data['slug']"
                                                         :variant_slug="$header_data['variant_slug']"
                                                         :attribute_filters="$header_data['attribute_filters']" />

                    <button class="hidden md:block open-pc-search-btn"
                            type="button"
                            aria-haspopup="dialog"
                            aria-expanded="false"
                            aria-controls="{{ __('catalog/default.placeholders.search') }}">
                        <span class="icon-[si--search-line] custom-icon"></span>
                    </button>

                    <button class="open-cart-modal-btn relative" id="open-cart-modal-btn" type="button">
                        <span class="custom-icon icon-[material-symbols-light--shopping-bag-outline]"></span>
                        <span class="badge badge-xs absolute -end-2 -top-2 min-w-5 border border-white/20 bg-white/15 px-1 text-xs text-white"
                              data-cart-badge
                              aria-live="polite"
                              aria-atomic="true"
                              @class(['hidden' => (int) ($cart_total_products ?? 0) <= 0])>
                            {{ (int) ($cart_total_products ?? 0) }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </nav>
</header>
