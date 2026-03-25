@if(filled($products))
    <div class="grid grid-cols-2 gap-x-2 gap-y-3 md:grid-cols-3 md:gap-x-3 md:gap-y-4 lg:gap-x-4 lg:gap-y-5 bp1440px:grid-cols-4">
        @foreach($products as $product)
            <div class="category-card flex h-full flex-col gap-2 md:gap-3 lg:gap-4">
                <a class="category-img-container flex items-center justify-center overflow-hidden"
                   href="{{ $product['url'] }}"
                   aria-label="{{ $product['name'] }}">
                    <x-catalog::common.img
                        class="object-cover w-full transition-transform duration-500 ease-in-out hover:scale-105"
                        :urls_data="$product['image_data']['urls']"
                        :size="$product['image_data']['width']"
                        :max-density="3"
                        sizes="100vw"
                        width="{{ $product['image_data']['width'] }}"
                        height="{{ $product['image_data']['height'] }}"
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
                            {{ $product['price']['formatted'] }}
                            @if($product['price']['discount_value'] !== null)
                                <span class="text-light-gray/80 line-through">
                                    {{ $product['price']['discount_formatted'] }}
                                </span>
                            @endif
                        </div>

                        <button class="add-to-cart"
                                type="button"
                                aria-label="{{ __('catalog/default.aria_labels.add_product_to_cart') }}">
                            <span class="icon-[solar--cart-5-linear] custom-icon"></span>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="py-10 text-center text-light-gray uppercase tracking-0.04em">
        {{ __('catalog/default.texts.category_products_not_found') }}
    </div>
@endif

@include('catalog.pages.partials.category.load-more-btn')
