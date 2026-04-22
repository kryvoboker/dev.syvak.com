@extends('catalog.layouts.main')

@push('styles')
    @vite(['./node_modules/@fancyapps/ui/dist/fancybox/fancybox.css'])
@endpush

@section('content')
    <x-catalog::common.breadcrumbs :breadcrumbs="$breadcrumbs" class="product-breadcrumbs"/>

    <section class="product section pt-0 md:pt-0 lg:pt-0" id="product">
        <div class="container">
            <div class="product-content grid gap-y-4 md:gap-y-6">
                <div class="relative overflow-hidden border-y border-opacity-light-gray-40%">
                    <div class="product-main-grid grid grid-cols-1 gap-y-4 md:grid-cols-2 md:gap-x-6 md:gap-y-0 lg:gap-x-8">
                        <div class="product-carousel relative w-full {{ !empty($product_view_data['gallery_images_data']) ? 'product-carousel--init' : '' }}"
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
                                                        <x-catalog::common.img
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
                                                <x-catalog::common.img
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

                        <div class="flex flex-col gap-y-3 pb-4 md:py-4 lg:py-6">
                            @if(!empty($product_view_data['gallery_images_data']))
                                <div class="product-carousel__pagination carousel-pagination hidden md:flex justify-end gap-x-3 lg:gap-x-4 size-full max-h-28 overflow-hidden">
                                    @foreach($product_view_data['gallery_images_data'] as $gallery_image_data)
                                        <button class="carousel-pagination-item relative w-20 lg:w-24 overflow-hidden {{ $loop->first ? 'active' : '' }}"
                                                type="button"
                                                aria-label="{{ __('catalog/default.links.go_to_page', ['page' => $loop->iteration]) }}">
                                            <x-catalog::common.img
                                                class="size-full grow object-cover"
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

                            <h1 class="product-name">
                                {{ $product_view_data['title'] }}
                            </h1>

                            <div class="flex items-end justify-between gap-x-4">
                                <div class="text-light-gray">
                                    {{ __('catalog/default.texts.sku', ['sku' => $product_view_data['sku']]) }}
                                </div>

                                <div class="text-xl md:text-32px leading-1.4em">
                                    {{ $product_view_data['price_formatted'] }}
                                </div>
                            </div>

                            @if(filled($product_view_data['option_groups']))
                                <div class="mt-1 flex flex-col gap-y-2 md:mt-3">
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

                                    @if(!empty($product_view_data['size_guide']))
                                        <button class="inline-flex items-center justify-end gap-x-2 text-sm md:text-lg"
                                                type="button">
                                            <span class="icon-[mdi--tag-outline]"></span>
                                            {{ __('catalog/default.product.labels.size_help') }}
                                        </button>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-2 flex flex-col gap-y-2 md:mt-4">
                                @if(!empty($product_view_data['is_in_stock']))
                                    <button class="white-btn default-btn w-full" type="button">
                                        {{ __('catalog/default.product.labels.buy_one_click') }}
                                    </button>

                                    <button class="black-btn default-btn w-full" type="button">
                                        {{ __('catalog/default.product.labels.add_to_cart') }}
                                    </button>
                                @else
                                    <div class="default-btn text-sm md:text-lg">
                                        {{ __('catalog/default.product.labels.notify') }}
                                    </div>

                                    <a class="white-btn default-btn flex w-full items-center justify-between"
                                       href="#">
                                        <span>{{ __('catalog/default.product.labels.telegram') }}</span>
                                        <span class="icon-[mynaui--arrow-up-right-square]"></span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if(collect($product_view_data['details_sections'] ?? [])->contains(fn (array $section): bool => !empty($section['items'])))
                    <div class="grid grid-cols-1 gap-y-6 bg-black/50 px-4 py-6 md:grid-cols-2 md:gap-x-8 md:gap-y-0 md:px-8">
                        @foreach(collect($product_view_data['details_sections'])->filter(fn (array $section): bool => !empty($section['items'])) as $details_section)
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
        </div>
    </section>
@endsection
