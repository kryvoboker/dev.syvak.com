<section class="category-products-list-section border-b border-opacity-light-gray-40% py-10 md:py-12 lg:py-14 2xl:py-16"
         aria-label="Category products list">
    <div class="container">
        <div class="mb-6 flex items-end justify-between gap-4 md:mb-7 md:gap-6 lg:mb-8">
            <h1 class="section-title">
                КОЛЕКЦІЇ
            </h1>

            <div class="flex items-center gap-x-1 md:gap-x-2 lg:gap-x-3">
                <button class="open-category-filter-drawer-btn default-btn border border-opacity-light-gray-40% px-3 py-1.5 text-11px
                               uppercase tracking-0.04em duration-200 ease-in-out hover:border-white md:text-sm lg:text-base"
                        type="button"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        aria-controls="category-filter-drawer">
                    ФІЛЬТР
                </button>

                <div class="dropdown category-sort-dropdown relative">
                    <button class="dropdown-toggle default-btn flex items-center gap-x-2 border border-opacity-light-gray-40% px-3 py-1.5 text-11px
                                   uppercase tracking-0.04em duration-200 ease-in-out hover:border-white md:text-sm lg:text-base"
                            type="button"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            aria-label="Sort products dropdown">
                        <span>СОРТУВАННЯ</span>
                        <span class="icon-[solar--alt-arrow-down-line-duotone] dropdown-open:rotate-180 size-19px text-white duration-100 ease-in-out"></span>
                    </button>

                    <ul class="dropdown-menu dropdown-open:opacity-100 hidden min-w-52 border border-opacity-light-gray-40% bg-black p-3 md:p-4"
                        role="menu"
                        aria-orientation="vertical">
                        <button class="close-category-sort-dropdown-btn mb-1 ms-auto block"
                                type="button"
                                aria-label="Close sort dropdown">
                            <span class="icon-[ic--baseline-close] size-5 text-white"></span>
                        </button>

                        @foreach($category_page_settings['sort_options'] as $sort_option)
                            @php
                                $sort_code = (string) data_get($sort_option, 'code', '');
                                $sort_value = (string) data_get($sort_option, 'value', '');
                                $sort_label = (string) data_get($sort_option, 'label', '');
                                $is_current_sort = $sort_code === (string) $active_sort_code;
                            @endphp

                            <li class="border-b border-opacity-light-gray-40% last:border-b-0">
                                <button class="w-full p-2 text-left text-11px uppercase tracking-0.04em duration-200 ease-in-out hover:text-white md:text-sm lg:text-base
                                              {{ $is_current_sort ? 'text-white' : 'text-light-gray' }}"
                                        type="button"
                                        data-sort-code="{{ $sort_code }}"
                                        data-sort-value="{{ $sort_value }}"
                                        aria-current="{{ $is_current_sort ? 'true' : 'false' }}">
                                    {{ $sort_label }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        @if(filled($products))
            <div class="grid grid-cols-2 gap-x-2 gap-y-3 md:grid-cols-3 md:gap-x-3 md:gap-y-4 lg:gap-x-4 lg:gap-y-5 bp1440px:grid-cols-4">
                @foreach($products as $product_index => $product)
                    @php
                        $url = (string) data_get($product, 'url', '');
                        $name = (string) data_get($product, 'name', '');
                        $image_urls_data = (array) data_get($product, 'image_data.urls', []);
                        $image_width = (int) data_get($product, 'image_data.width', 420);
                        $image_height = (int) data_get($product, 'image_data.height', 420);

                        $price = (string) data_get($product, 'price.formatted', '');
                        $old_price = (string) data_get($product, 'price.base_formatted', '');
                        $has_discount = filled(data_get($product, 'price.discount_value'))
                            && $old_price !== ''
                            && $old_price !== $price;

                        $quantity = (int) data_get($product, 'quantity', 0);
                        $stock_text = $quantity > 0
                            ? 'В наявності: ' . $quantity
                            : '';

                        $is_mixed_layout = (($product_index + 1) % 7 === 0);
                    @endphp

                    <div class="category-card flex h-full flex-col gap-2 md:gap-3 lg:gap-4 {{ $is_mixed_layout ? 'col-span-2 md:col-span-1 bp1920px:col-span-2' : '' }}">
                        <a class="category-img-container flex items-center justify-center overflow-hidden"
                           href="{{ $url }}"
                           aria-label="{{ $name }}">
                            <x-catalog::common.img
                                class="object-cover w-full transition-transform duration-500 ease-in-out hover:scale-105"
                                :urls_data="$image_urls_data"
                                :size="$image_width"
                                :max-density="3"
                                sizes="100vw"
                                width="{{ $image_width }}"
                                height="{{ $image_height }}"
                                alt="{{ $name }}"
                            />
                        </a>

                        <div class="category-info flex min-h-20 flex-col gap-2">
                            <a class="category-card-title prod-list__name"
                               href="{{ $url }}"
                               aria-label="{{ $name }}">
                                {{ $name }}
                            </a>

                            <div class="flex items-center justify-between gap-x-3">
                                <div class="category-card-price text-11px uppercase tracking-0.04em text-light-gray md:text-sm lg:text-base 2xl:text-lg">
                                    {{ $price }}
                                    @if($has_discount)
                                        <span class="text-light-gray/80 line-through">
                                            {{ $old_price }}
                                        </span>
                                    @endif
                                </div>

                                <button class="add-to-cart"
                                        type="button"
                                        aria-label="Add product to cart">
                                    <span class="icon-[solar--cart-5-linear] custom-icon"></span>
                                </button>
                            </div>

                            @if(filled($stock_text))
                                <div class="text-11px uppercase tracking-0.04em text-light-gray md:text-sm">
                                    {{ $stock_text }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-10 text-center text-light-gray uppercase tracking-0.04em">
                Товари для цієї категорії не знайдені
            </div>
        @endif

        @if($category_page_settings['is_ajax_products_loading_enabled'])
            <div class="mt-5 flex justify-center md:mt-6 lg:mt-7">
                <button class="default-btn uppercase tracking-0.04em text-light-gray duration-200 ease-in-out hover:text-white"
                        type="button">
                    ПОКАЗАТИ ЩЕ
                    <span class="icon-[solar--alt-arrow-down-line-duotone] size-4 md:size-5"></span>
                </button>
            </div>
        @endif
    </div>
</section>

<div class="overlay overlay-open:translate-x-0 drawer drawer-start hidden category-filter-drawer bg-black"
     id="category-filter-drawer"
     role="dialog"
     tabindex="-1">
    <div class="drawer-header flex-col items-stretch px-4 pt-38px pb-6">
        <button type="button"
                class="btn btn-text btn-circle ms-auto mb-4 size-8"
                aria-expanded="true"
                aria-controls="Close category filter drawer"
                data-overlay="#category-filter-drawer">
            <span class="icon-[iconamoon--close] custom-icon size-8"></span>
        </button>

        <h4>Фільтр</h4>
    </div>

    <div class="drawer-body px-4">
        <p class="text-light-gray text-sm uppercase tracking-0.04em md:text-base">
            Фільтр товарів буде тут
        </p>
    </div>
</div>
