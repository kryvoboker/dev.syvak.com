@php
    use Illuminate\Support\Str;

    $carousel_dom_id = 'products-carousel-' . ($products_carousel_module_data['instance_id'] ?? Str::random(6));
    $section_title = (string) ($products_carousel_module_data['module_name_for_user'] ?? '');
    $section_description = (string) ($products_carousel_module_data['short_description_for_user'] ?? '');
    $current_page_type = (string) ($page_type ?? '');
@endphp

<section class="products-carousel-section relative overflow-hidden border-b border-opacity-light-gray-40% pt-12 pb-10 md:pt-16 md:pb-12
                lg:pt-20 lg:pb-14 2xl:pt-24 2xl:pb-16"
         aria-label="Products carousel section">
    <div class="container">
        <div class="flex gap-10 justify-between mb-8">
            @if(filled($section_title))
                <h2 class="section-title shrink-0">
                    {{ $section_title }}
                </h2>
            @endif
            @if(filled($section_description))
                <p class="products-carousel-description hidden md:block max-w-860px font-light uppercase tracking-0.04em text-light-gray
                          md:text-sm lg:text-base 2xl:text-lg">
                    {{ $section_description }}
                </p>
            @endif
        </div>

        <div id="{{ $carousel_dom_id }}"
             class="products-carousel-root --prevent-on-load-init relative"
             data-products-carousel
             data-page-types='@js($products_carousel_module_data['page_types'])'
             data-current-page-type="{{ $current_page_type }}"
             data-carousel='{"loadingClasses":"opacity-0, opacity-100 transition-opacity easy duration-200","isAutoHeight":true,"isInfiniteLoop":true, "isDraggable": true, "slidesQty":{"xs":1,"sm":1,"md":2,"lg":3,"2xl":5}, "isAutoPlay": true}'>
            <div class="carousel products-carousel-track rounded-none overflow-hidden border border-light-black bg-black">
                <div class="carousel-body products-carousel-body h-full carousel-dragging:transition-none carousel-dragging:cursor-grabbing cursor-grab
                            -mx-2 md:-mx-2.5 lg:-mx-3">
                    @foreach($products_carousel_module_data['products'] as $product)
                        @php
                            $name = (string) ($product['name'] ?? 'Product name');
                            $price = (string) ($product['price'] ?? '0 UAH');
                            $rrc_price = (string) ($product['rrc_price'] ?? '');
                            $url = (string) ($product['url'] ?? '#');
                            $image_urls_data = $product['image_data']['urls'] ?? [];
                            $image_width = (int) ($product['image_data']['width'] ?? 420);
                            $image_height = (int) ($product['image_data']['height'] ?? 420);
                        @endphp

                        <div class="carousel-slide products-carousel-slide h-full px-2 pb-1 md:px-2.5 lg:px-3">
                            <div class="font-light text-light-gray mb-2">
                                @if($loop->iteration < 10)
                                    / 0{{ $loop->iteration }}
                                @else
                                    / {{ $loop->iteration }}
                                @endif
                            </div>

                            <div class="products-carousel-card card border-none">
                                <a class="products-carousel-img-container flex items-center justify-center overflow-hidden"
                                   href="{{ $url }}"
                                   aria-label="{{ $name }}">
                                    <x-storefront::common.img
                                        class="object-contain transition-transform hover:scale-105 duration-500 ease-in-out"
                                        :urls_data="$image_urls_data"
                                        :size="$image_width"
                                        :max-density="3"
                                        sizes="100vw"
                                        width="{{ $image_width }}"
                                        height="{{ $image_height }}"
                                        alt="{{ $name }}"
                                    />
                                </a>

                                <div class="products-carousel-info card-body">
                                    <a class="products-carousel-card-title card-title"
                                       href="{{ $url }}"
                                       aria-label="{{ $name }}">
                                        {{ $name }}
                                    </a>
                                    <div class="flex items-center justify-between gap-x-3">
                                        <div class="products-carousel-card-price text-sm uppercase tracking-0.04em text-light-gray md:text-base 2xl:text-lg">
                                            {{ $price }}
                                            @if($product['is_discounted'] ?? false)
                                                <del class="ml-2 text-light-gray/80">{{ $rrc_price }}</del>
                                            @endif
                                        </div>

                                        <button class="add-to-cart"
                                                type="button"
                                                data-add-to-cart="{{ (int) ($product['variant_id'] ?? 0) }}"
                                                aria-label="{{ __('storefront/default.aria_labels.add_product_to_cart') }}">
                                            <span class="icon-[solar--cart-5-linear] custom-icon"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if(count($products_carousel_module_data['products'] ?? []) > 1)
                <button class="carousel-prev inset-s-0 carousel-nav"
                        @style("top: calc(($image_height / 2) / var(--base-font-size) * 1rem * -1); transform: translateY(calc(($image_height * 0.1) / var(--base-font-size) * 1rem));")
                        type="button"
                        aria-label="Previous product">
                    <span class="icon-[mynaui--arrow-left] size-6"></span>
                </button>

                <button class="carousel-next inset-e-0 carousel-nav"
                        @style("top: calc(($image_height / 2) / var(--base-font-size) * 1rem * -1); transform: translateY(calc(($image_height * 0.1) / var(--base-font-size) * 1rem));")
                        type="button"
                        aria-label="Next product">
                    <span class="icon-[mynaui--arrow-right] size-6"></span>
                </button>

                <div class="carousel-pagination mt-5"></div>
            @endif
        </div>
    </div>
</section>
