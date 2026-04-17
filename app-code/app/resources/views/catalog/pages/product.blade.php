@extends('catalog.layouts.main')

@php
    $aa = [
            'size_help'     => __('catalog/default.product.labels.size_help'),
            'buy_one_click' => __('catalog/default.product.labels.buy_one_click'),
            'add_to_cart'   => __('catalog/default.product.labels.add_to_cart'),
            'notify'        => __('catalog/default.product.labels.notify'),
            'telegram'      => __('catalog/default.product.labels.telegram'),
        ];
@endphp

@section('content')
    <x-catalog::common.breadcrumbs :breadcrumbs="$breadcrumbs"/>

    <section class="product" id="product">
        <div class="container">
            <div class="product-content grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
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

                                <div class="flex">
                                    @foreach($option_group['value_links'] as $value_data)
                                        <a class="{{ $value_data['is_selected'] === true ? 'selected' : '' }}"
                                           href="{{ $value_data['url'] }}">
                                            {{ $value_data['value'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif


                <div class="product-carousel relative w-full" id="vertical-thumbnails" data-carousel='{ "loadingClasses": "opacity-0", "isDraggable": true, "isInfiniteLoop":true }'>
                    <div class="carousel flex space-x-2 rounded-none">
                        <div class="relative grow overflow-hidden">
                            <div class="carousel-body h-80 carousel-dragging:transition-none carousel-dragging:cursor-grabbing cursor-grab opacity-0">
                                <!-- Slide 1 -->
                                <div class="carousel-slide">
                                    <div class="flex size-full justify-center">
                                        <img src="https://cdn.flyonui.com/fy-assets/components/carousel/image-21.png" class="size-full object-cover" alt="mountain"/>
                                    </div>
                                </div>
                                <!-- Slide 2 -->
                                <div class="carousel-slide">
                                    <div class="flex size-full justify-center">
                                        <img src="https://cdn.flyonui.com/fy-assets/components/carousel/image-14.png" class="size-full object-cover" alt="sand"/>
                                    </div>
                                </div>
                                <!-- Slide 3 -->
                                <div class="carousel-slide">
                                    <div class="flex size-full justify-center">
                                        <img
                                            src="https://cdn.flyonui.com/fy-assets/components/carousel/image-7.png"
                                            class="size-full object-cover"
                                            alt="cloud"
                                        />
                                    </div>
                                </div>
                            </div>
                            <!-- Previous Slide -->
                            <button type="button" class="carousel-prev start-5 max-sm:start-3 carousel-disabled:opacity-50 size-9.5 bg-base-100 flex items-center justify-center rounded-full shadow-base-300/20 shadow-sm">
                                <span class="icon-[tabler--chevron-left] size-5 cursor-pointer"></span>
                                <span class="sr-only">Previous</span>
                            </button>
                            <!-- Next Slide -->
                            <button type="button" class="carousel-next end-5 max-sm:end-3 carousel-disabled:opacity-50 size-9.5 bg-base-100 flex items-center justify-center rounded-full shadow-base-300/20 shadow-sm">
                                <span class="icon-[tabler--chevron-right] size-5"></span>
                                <span class="sr-only">Next</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex-none ms-2">
                    <div class="product-carousel__pagination carousel-pagination h-full max-sm:w-8 w-[200px] flex justify-between flex-col gap-y-2 overflow-hidden">
                        <img src="https://cdn.flyonui.com/fy-assets/components/carousel/image-21.png" class="carousel-pagination-item grow object-cover active" alt="mountain"/>
                        <img src="https://cdn.flyonui.com/fy-assets/components/carousel/image-14.png" class="carousel-pagination-item grow object-cover" alt="sand"/>
                        <img src="https://cdn.flyonui.com/fy-assets/components/carousel/image-7.png" class="carousel-pagination-item grow object-cover" alt="cloud"/>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


{{--<div data-block="top">
                    <x-catalog::common.img
                        class="object-cover"
                        :urls_data="$product_view_data['main_image']['urls']"
                        :size="$product_view_data['main_image']['width']"
                        :max-density="3"
                        sizes="100vw"
                        width="{{ $product_view_data['main_image']['width'] }}"
                        height="{{ $product_view_data['main_image']['height'] }}"
                        alt="{{ strip_tags($product_view_data['title']) }}"
                    />

                    <div data-block="gallery-thumbnails">
                        @foreach($product_view_data['gallery_images_data'] as $gallery_image_data)
                            <x-catalog::common.img
                                class="object-cover"
                                :urls_data="$gallery_image_data['urls']"
                                :size="$gallery_image_data['width']"
                                :max-density="3"
                                sizes="100vw"
                                width="{{ $gallery_image_data['width'] }}"
                                height="{{ $gallery_image_data['height'] }}"
                                alt="{{ strip_tags($product_view_data['title']) }}"
                            />
                        @endforeach
                    </div>
                </div>--}}
