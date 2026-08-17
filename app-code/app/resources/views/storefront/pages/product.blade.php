@extends('storefront.layouts.main')

@push('styles')
    @vite(['node_modules/@fancyapps/ui/dist/fancybox/fancybox.css'])
@endpush

@section('content')
    <x-storefront::common.breadcrumbs :breadcrumbs="$breadcrumbs" class="product-breadcrumbs"/>

    <section class="product section" id="product">
        <div class="container relative">
            <div class="product-content grid grid-cols-1 lg:grid-cols-3 lg:grid-rows-[repeat(20,auto)] gap-y-4 gap-x-6 z-1">
                <div class="product-carousel w-full lg:col-start-2 lg:col-end-3 lg:row-start-1 lg:row-end-21 {{ !empty($product_view_data['gallery_images_data']) ? 'product-carousel--init' : '' }}"
                     id="vertical-thumbnails"
                     @if(!empty($product_view_data['gallery_images_data']))
                         data-carousel='{ "loadingClasses": "opacity-0", "isDraggable": true, "isInfiniteLoop":true }'
                    @endif
                >
                    <div class="carousel flex space-x-2 rounded-none">
                        <div class="relative grow overflow-hidden">
                            <div class="carousel-body {{ !empty($product_view_data['gallery_images_data']) ? 'carousel-dragging:transition-none carousel-dragging:cursor-grabbing cursor-grab opacity-0' : '' }}">
                                @if(!empty($product_view_data['gallery_images_data']))
                                    @foreach($product_view_data['gallery_images_data'] as $gallery_image_data)
                                        <div class="carousel-slide">
                                            <a class="flex size-full justify-center"
                                               href="{{ $gallery_image_data['urls']['original_thumb'] }}"
                                               data-fancybox="gallery">
                                                <x-storefront::common.img
                                                    class="size-full object-contain {{ $loop->first ? 'active' : '' }}"
                                                    :urls_data="$gallery_image_data['urls']"
                                                    :size="$gallery_image_data['width']"
                                                    :max-density="3"
                                                    sizes="100vw"
                                                    width="{{ $gallery_image_data['width'] }}"
                                                    height="{{ $gallery_image_data['height'] }}"
                                                    alt="{{ strip_tags((string) $product_view_data['title']) }}"
                                                />
                                            </a>
                                        </div>
                                    @endforeach
                                @else
                                    <a class="flex size-full justify-center"
                                       href="{{ $product_view_data['main_image']['urls']['original_thumb'] ?? '' }}"
                                       data-fancybox="gallery">
                                        <x-storefront::common.img
                                            class="size-full object-contain"
                                            :urls_data="$product_view_data['main_image']['urls']"
                                            :size="$product_view_data['main_image']['width']"
                                            :max-density="3"
                                            sizes="100vw"
                                            width="{{ $product_view_data['main_image']['width'] }}"
                                            height="{{ $product_view_data['main_image']['height'] }}"
                                            alt="{{ strip_tags((string) $product_view_data['title']) }}"
                                        />
                                    </a>
                                @endif
                            </div>

                            @if(!empty($product_view_data['gallery_images_data']))
                                <button class="carousel-prev inset-s-5 max-sm:inset-s-3 carousel-nav"
                                        type="button"
                                        aria-label="Previous product">
                                    <span class="icon-[mynaui--arrow-left] size-6"></span>
                                </button>

                                <button class="carousel-next inset-e-5 max-sm:inset-e-3 carousel-nav"
                                        type="button"
                                        aria-label="Next product">
                                    <span class="icon-[mynaui--arrow-right] size-6"></span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($product_view_data['gallery_images_data']))
                    <div class="lg:col-start-3 lg:col-end-4 lg:row-start-1 lg:row-end-10 product-carousel__pagination carousel-pagination
                                hidden md:flex gap-x-3 lg:gap-x-4 size-full max-h-42 overflow-x-auto">
                        @foreach($product_view_data['gallery_images_data'] as $gallery_image_data)
                            <button class="shrink-0 carousel-pagination-item relative max-w-31 w-full {{ $loop->first ? 'active' : '' }}"
                                    type="button"
                                    aria-label="{{ __('storefront/default.links.go_to_page', ['page' => $loop->iteration]) }}">
                                <x-storefront::common.img
                                    class="size-full grow object-contain"
                                    :urls_data="$gallery_image_data['urls']"
                                    :size="$gallery_image_data['width']"
                                    :max-density="3"
                                    sizes="100vw"
                                    width="{{ $gallery_image_data['width'] }}"
                                    height="{{ $gallery_image_data['height'] }}"
                                    alt="{{ strip_tags((string) $product_view_data['title']) }}"
                                />
                            </button>
                        @endforeach
                    </div>
                @endif

                <h1 class="product-name lg:col-start-1 lg:col-end-2 lg:row-start-1 lg:row-end-10">
                    {{ $product_view_data['title'] }}
                </h1>

                <div class="lg:col-start-1 lg:col-end-2 lg:row-start-10 lg:row-end-12 flex flex-col lg:flex-col xl:flex-row justify-between gap-4">
                    <div class="text-light-gray">
                        {{ __('storefront/default.texts.sku', ['sku' => $product_view_data['sku']]) }}
                    </div>

                    <div class="text-xl md:text-32px leading-1.4em">
                        {{ $product_view_data['price_formatted'] }}
                        @if($product_view_data['is_discounted'] ?? false)
                            <del class="ml-2 text-light-gray/80">
                                {{ $product_view_data['rrc_price_formatted'] }}
                            </del>
                        @endif
                    </div>
                </div>

                @if(filled($product_view_data['option_groups']))
                    <div class="lg:col-start-3 lg:col-end-4 lg:row-start-10 lg:row-end-18 flex flex-col gap-y-2 mt-1 md:mt-3">
                        @foreach($product_view_data['option_groups'] as $option_group)
                            <div class="border-b border-opacity-light-gray-40% pb-2">
                                <div class="flex items-center justify-between gap-x-2">
                                    <div class="shrink-0 text-sm md:text-lg uppercase">
                                        {{ $option_group['name'] }}
                                    </div>

                                    <div class="flex items-center gap-2 overflow-x-auto">
                                        @foreach($option_group['value_links'] as $value_data)
                                            @if(!empty($value_data['is_selected']))
                                                <div class="inline-flex items-center border border-opacity-light-gray-40% bg-white px-2 py-1 text-sm md:text-lg text-black">
                                                    {{ $value_data['value'] }}
                                                </div>
                                            @elseif(filled((string) $value_data['url']))
                                                <a class="inline-flex items-center border border-opacity-light-gray-40% px-2 py-1 text-sm md:text-lg"
                                                   href="{{ $value_data['url'] }}">
                                                    {{ $value_data['value'] }}
                                                </a>
                                            @else
                                                <div class="inline-flex items-center border border-opacity-light-gray-40% px-2 py-1 text-sm md:text-lg text-light-gray">
                                                    {{ $value_data['value'] }}
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <button class="product-size-guide__open-btn lg:col-start-3 lg:col-end-4 lg:row-start-18 lg:row-end-19 flex items-center gap-x-2 ms-auto">
                    @if(filled($product_view_data['size_guide']))
                        <span class="custom-icon icon-[mingcute--tag-2-line]"></span>

                        <div>
                            {{ __('storefront/pages/product/show.buttons.check_your_size') }}
                        </div>
                    @endif
                </button>

                <div class="lg:col-start-3 lg:col-end-4 lg:row-start-19 lg:row-end-21 flex flex-col gap-y-2 mt-2 md:mt-4">
                    @if(isset($product_view_data['is_in_stock']) && $product_view_data['is_in_stock'] === true)
                                <button class="btn default-btn w-full"
                                        type="button"
                                        data-fast-order="{{ (int) ($variant?->id ?? $product?->default_variant_id ?? 0) }}">
                            {{ __('storefront/default.product.labels.buy_one_click') }}
                        </button>

                        <button class="btn btn-black default-btn w-full"
                                type="button"
                                data-add-to-cart="{{ (int) ($variant?->id ?? $product?->default_variant_id ?? 0) }}">
                            {{ __('storefront/default.product.labels.add_to_cart') }}
                        </button>
                    @else
                        <div class="default-btn text-sm md:text-lg">
                            {{ __('storefront/default.product.labels.notify') }}
                        </div>

                        <x-storefront::common.telegram-link :telegram_data="$telegram_data"/>
                    @endif
                </div>

                @if(filled($product_view_data['details_sections']))
                    <div class="lg:col-start-1 lg:col-end-2 lg:row-start-12 lg:row-end-21 flex flex-col md:flex-row lg:flex-col gap-y-6 md:gap-x-6 md:gap-y-0 lg:gap-y-6">
                        @foreach($product_view_data['details_sections'] as $details_section)
                            <div class="flex flex-col gap-y-2">
                                <h6 class="font-bold">
                                    {{ $details_section['label'] }}
                                </h6>

                                <div class="flex flex-col gap-y-2 text-light-gray">
                                    @foreach($details_section['items'] as $item)
                                        <p>{{ $item }}</p>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if(filled($product_view_data['description']))
                <div class="mt-4 md:mt-6 lg:mt-12">
                    {!! $product_view_data['description'] !!}
                </div>
            @endif
        </div>
    </section>

    @include('storefront.pages.partials.product.product-size-guide-modal')
@endsection
