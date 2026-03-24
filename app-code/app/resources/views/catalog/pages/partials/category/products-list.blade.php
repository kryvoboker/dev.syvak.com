@if(filled($products))
    <div class="grid grid-cols-2 gap-x-2 gap-y-3 md:grid-cols-3 md:gap-x-3 md:gap-y-4 lg:gap-x-4 lg:gap-y-5 bp1440px:grid-cols-4">
        @foreach($products as $product)
            <div class="category-card flex h-full flex-col gap-2 md:gap-3 lg:gap-4 {{ $product['is_mixed_layout'] ? 'col-span-2 md:col-span-1 bp1920px:col-span-2' : '' }}">
                <a class="category-img-container flex items-center justify-center overflow-hidden"
                   href="{{ $product['url'] }}"
                   aria-label="{{ $product['name'] }}">
                    <x-catalog::common.img
                        class="object-cover w-full transition-transform duration-500 ease-in-out hover:scale-105"
                        :urls_data="$product['image_urls_data']"
                        :size="$product['image_width']"
                        :max-density="3"
                        sizes="100vw"
                        width="{{ $product['image_width'] }}"
                        height="{{ $product['image_height'] }}"
                        alt="{{ $product['name'] }}"
                    />
                </a>

                <div class="category-info flex min-h-20 flex-col gap-2">
                    <a class="category-card-title prod-list__name"
                       href="{{ $product['url'] }}"
                       aria-label="{{ $product['name'] }}">
                        {{ $product['name'] }}
                    </a>

                    <div class="flex items-center justify-between gap-x-3">
                        <div class="category-card-price text-11px uppercase tracking-0.04em text-light-gray md:text-sm lg:text-base 2xl:text-lg">
                            {{ $product['price'] }}
                            @if($product['has_discount'])
                                <span class="text-light-gray/80 line-through">
                                    {{ $product['old_price'] }}
                                </span>
                            @endif
                        </div>

                        <button class="add-to-cart"
                                type="button"
                                aria-label="{{ __('catalog/default.aria_labels.add_product_to_cart') }}">
                            <span class="icon-[solar--cart-5-linear] custom-icon"></span>
                        </button>
                    </div>

                    @if(filled($product['stock_text']))
                        <div class="text-11px uppercase tracking-0.04em text-light-gray md:text-sm">
                            {{ $product['stock_text'] }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="py-10 text-center text-light-gray uppercase tracking-0.04em">
        {{ __('catalog/default.texts.category_products_not_found') }}
    </div>
@endif

@if($category_page_settings['is_ajax_products_loading_enabled'])
    <div class="mt-5 flex justify-center md:mt-6 lg:mt-7">
        <button class="default-btn uppercase tracking-0.04em text-light-gray duration-200 ease-in-out hover:text-white"
                type="button">
            {{ __('catalog/default.buttons.show_more') }}
            <span class="icon-[solar--alt-arrow-down-line-duotone] size-4 md:size-5"></span>
        </button>
    </div>
@endif
