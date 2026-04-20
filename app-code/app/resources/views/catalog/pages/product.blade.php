@extends('catalog.layouts.main')

@use(Illuminate\Support\Str;)

@push('styles')
    @vite(['./node_modules/@fancyapps/ui/dist/fancybox/fancybox.css'])
@endpush

@php
    $aa = [
            'size_help'     => __('catalog/default.product.labels.size_help'),
            'buy_one_click' => __('catalog/default.product.labels.buy_one_click'),
            'add_to_cart'   => __('catalog/default.product.labels.add_to_cart'),
            'notify'        => __('catalog/default.product.labels.notify'),
            'telegram'      => __('catalog/default.product.labels.telegram'),
        ];

    $product_title = Str::trim(strip_tags($product_view_data['title']));
@endphp

@section('content')
    <x-catalog::common.breadcrumbs :breadcrumbs="$breadcrumbs"/>

    <section class="product" id="product">
        <div class="container">
            <div class="product-content grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
                <div class="product-carousel relative w-full {{ !empty($product_view_data['gallery_images_data']) ? 'product-carousel--init' : '' }}"
                     id="vertical-thumbnails"
                     @if(!empty($product_view_data['gallery_images_data']))
                         data-carousel='{ "loadingClasses": "opacity-0", "isDraggable": true, "isInfiniteLoop":true }'
                    @endif
                >
                    <div class="carousel flex space-x-2 rounded-none">
                        <div class="relative grow overflow-hidden">
                            <div class="carousel-body {{ !empty($product_view_data['gallery_images_data']) ? 'carousel-dragging:transition-none carousel-dragging:cursor-grabbing cursor-grab opacity-0' : '' }}">
                                @forelse($product_view_data['gallery_images_data'] as $gallery_image_data)
                                    <div class="carousel-slide">
                                        <a class="flex size-full justify-center"
                                           href="{{ $gallery_image_data['urls']['original_thumb'] }}"
                                           data-fancybox="gallery">
                                            <x-catalog::common.img
                                                class="size-full object-contain {{ $loop->first ? 'active' : '' }}"
                                                :urls_data="$gallery_image_data['urls']"
                                                :size="$gallery_image_data['width']"
                                                :max-density="3"
                                                sizes="100vw"
                                                width="{{ $gallery_image_data['width'] }}"
                                                height="{{ $gallery_image_data['height'] }}"
                                                alt="{{ $product_title }}"
                                            />
                                        </a>
                                    </div>
                                @empty
                                    <a class="flex size-full justify-center"
                                       href="{{ $product_view_data['main_image']['urls']['original_thumb'] }}"
                                       data-fancybox="gallery">
                                        <x-catalog::common.img
                                            class="size-full object-contain"
                                            :urls_data="$product_view_data['main_image']['urls']"
                                            :size="$product_view_data['main_image']['width']"
                                            :max-density="3"
                                            sizes="100vw"
                                            width="{{ $product_view_data['main_image']['width'] }}"
                                            height="{{ $product_view_data['main_image']['height'] }}"
                                            alt="{{ $product_title }}"
                                        />
                                    </a>
                                @endforelse
                            </div>

                            @if(!empty($product_view_data['gallery_images_data']))
                                <button class="carousel-prev start-5 max-sm:start-3 carousel-nav"
                                        type="button"
                                        aria-label="Previous product">
                                    <span class="icon-[mynaui--arrow-left] size-6"></span>
                                </button>

                                <button class="carousel-next end-5 max-sm:end-3 carousel-nav"
                                        type="button"
                                        aria-label="Next product">
                                    <span class="icon-[mynaui--arrow-right] size-6"></span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($product_view_data['gallery_images_data']))
                    <div class="product-carousel__pagination carousel-pagination hidden md:flex justify-between gap-x-4 size-full max-h-28 overflow-hidden">
                        @foreach($product_view_data['gallery_images_data'] as $gallery_image_data)
                            <x-catalog::common.img
                                class="carousel-pagination-item grow object-contain {{ $loop->first ? 'active' : '' }}"
                                :urls_data="$gallery_image_data['urls']"
                                :size="$gallery_image_data['width']"
                                :max-density="3"
                                sizes="100vw"
                                width="{{ $gallery_image_data['width'] }}"
                                height="{{ $gallery_image_data['height'] }}"
                                alt="{{ $product_title }}"
                            />
                        @endforeach
                    </div>
                @endif

                <h1 class="product-name">
                    {{ $product_view_data['title'] }}
                </h1>

                <div class="flex gap-x-2 items-center justify-between">
                    <div>
                        {{ $product_view_data['sku'] }}
                    </div>
                    <div>
                        {{ $product_view_data['price_formatted'] }}
                    </div>
                </div>

                <div class="flex flex-col gap-y-2">
                    <button class="white-btn" type="button">
                        {{ __('catalog/default.product.labels.buy_one_click') }}
                    </button>

                    <button class="black-btn" type="button">
                        {{ __('catalog/default.product.labels.add_to_cart') }}
                    </button>
                </div>

                @if(filled($product_view_data['option_groups']))
                    <div class="flex gap-x-2 items-center justify-between">
                        @foreach($product_view_data['option_groups'] as $option_group)
                            <div class="flex gap-x-2 items-center justify-between">
                                <div>
                                    {{ $option_group['name'] }}:
                                </div>

                                <div class="flex items-center gap-3">
                                    @foreach($option_group['value_links'] as $value_data)
                                        @if(isset($value_data['is_selected']) && $value_data['is_selected'] === true)
                                            <div class="selected text-black bg-white border border-opacity-light-gray-40% px-2 py-1">
                                                {{ $value_data['value'] }}
                                            </div>
                                        @else
                                            <a class="border border-opacity-light-gray-40% px-2 py-1"
                                               href="{{ $value_data['url'] }}">
                                                {{ $value_data['value'] }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
