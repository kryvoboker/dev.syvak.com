@php
    $carousel_dom_id = 'main-carousel-' . $carousel_module_data['instance_id'];
    $open_in_new_tab = $carousel_module_data['open_links_in_new_tab'] ?? true;
    $target = $open_in_new_tab ? '_blank' : '_self';
    $rel = $open_in_new_tab ? 'noopener noreferrer' : null;
    $page_types = collect($carousel_module_data['page_types'] ?? [])
        ->filter(fn (mixed $page_type): bool => is_string($page_type) && filled($page_type))
        ->values()
        ->all();
    $slides_count = count($carousel_module_data['slides'] ?? []);
    $is_interactive_carousel = $slides_count > 1;
    $is_not_current_device_desktop = $current_device_type !== config('devices.types.desktop');
    $max_width = $is_not_current_device_desktop
        ? (int) ($carousel_module_data['mobile_max_width'] ?? 0)
        : (int) ($carousel_module_data['desctop_max_width'] ?? 0);
    $max_height = $is_not_current_device_desktop
    ? (int) ($carousel_module_data['mobile_max_height'] ?? 0)
    : (int) ($carousel_module_data['desctop_max_height'] ?? 0);
@endphp

<section class="main-carousel-section carousel-section" aria-label="{{ __('carousel::admin/modules/carousel.aria.main_carousel') }}">
    <div class="container">
        <div id="{{ $carousel_dom_id }}"
             class="main-carousel carousel --prevent-on-load-init relative"
             @if($is_interactive_carousel)
                 data-main-carousel
             data-slides-count="{{ $slides_count }}"
             data-page-types='@js($page_types)'
             data-carousel='{ "isAutoHeight": true, "loadingClasses": "opacity-0, opacity-100", "isDraggable": true, "isInfiniteLoop": true, "isAutoPlay": true }'
            @endif>
            <div class="carousel main-carousel-track rounded-none">
                <div class="main-carousel-body carousel-body
                            {{ $is_interactive_carousel ? ' carousel-dragging:transition-none carousel-dragging:cursor-grabbing cursor-grab' : '' }}">
                    @foreach($carousel_module_data['slides'] as $slide)
                        @php
                            $has_image_link = filled($slide['image_url']);
                            $has_title = filled($slide['title']);
                            $has_description = filled($slide['description']);
                            $has_copy = $has_title || $has_description;
                            $has_button = filled($slide['button_text']) && filled($slide['button_url']);
                            $desktop_image_urls = is_array($slide['desktop_image']['urls'] ?? null) ? $slide['desktop_image']['urls'] : [];
                            $mobile_image_urls = is_array($slide['mobile_image']['urls'] ?? null) ? $slide['mobile_image']['urls'] : [];
                            $has_any_desktop_image = filled($desktop_image_urls['thumb_2x'] ?? '')
                                || filled($desktop_image_urls['thumb_3x'] ?? '')
                                || filled($desktop_image_urls['thumb_4x'] ?? '')
                                || filled($desktop_image_urls['thumb_1x'] ?? $desktop_image_urls['original_thumb'] ?? '');
                            $has_any_mobile_image = filled($mobile_image_urls['thumb_2x'] ?? '')
                                || filled($mobile_image_urls['thumb_3x'] ?? '')
                                || filled($mobile_image_urls['thumb_4x'] ?? '')
                                || filled($mobile_image_urls['thumb_1x'] ?? $mobile_image_urls['original_thumb'] ?? '');

                            $has_slide_image = $has_any_mobile_image || $has_any_desktop_image;
                            $has_slide_image_for_current_device = $is_not_current_device_desktop ? $has_any_mobile_image : $has_any_desktop_image;
                            $image_urls_data = $has_any_mobile_image ? $mobile_image_urls : $desktop_image_urls;
                            $image_width = $is_not_current_device_desktop
                                ? (int) ($slide['mobile_image']['width'] ?? 0)
                                : (int) ($slide['desktop_image']['width'] ?? 0);
                            $image_height = $is_not_current_device_desktop
                                ? (int) ($slide['mobile_image']['height'] ?? 0)
                                : (int) ($slide['desktop_image']['height'] ?? 0);
                        @endphp

                        <div class="main-carousel-slide carousel-slide relative flex flex-col items-center w-full h-full">
                            <div class="main-carousel-media w-full h-full"
                                @if($is_not_current_device_desktop && $has_slide_image_for_current_device)
                                @style("max-height: calc($max_height / var(--base-font-size) * 1rem)")
                                @else
                                @style("height: calc($max_height / var(--base-font-size) * 1rem)")
                                @endif
                            >
                                @if($has_image_link)
                                    <a class="main-carousel-media-link flex items-center justify-center w-full h-full"
                                       href="{{ $slide['image_url'] }}"
                                       target="{{ $target }}"
                                       @if($rel) rel="{{ $rel }}" @endif
                                       aria-label="{{ $slide['title'] ?: $carousel_module_data['name'] }}">
                                        @if($has_slide_image)
                                            <picture>
                                                @if(filled($desktop_image_urls['thumb_4x'] ?? ''))
                                                    <source media="(min-width: 80rem)"
                                                            srcset="{{ $desktop_image_urls['thumb_4x'] }}">
                                                @endif

                                                @if(filled($desktop_image_urls['thumb_3x'] ?? ''))
                                                    <source media="(min-width: 64rem)"
                                                            srcset="{{ $desktop_image_urls['thumb_3x'] }}">
                                                @endif

                                                @if(filled($desktop_image_urls['thumb_2x'] ?? ''))
                                                    <source media="(min-width: 48rem)"
                                                            srcset="{{ $desktop_image_urls['thumb_2x'] }}">
                                                @endif

                                                <x-storefront::common.img
                                                    class="object-contain"
                                                    :urls_data="$image_urls_data"
                                                    :size="$image_width"
                                                    :max-density="2"
                                                    sizes="100vw"
                                                    width="{{ $image_width }}"
                                                    height="{{ $image_height }}"
                                                    loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                                    alt="{{ $slide['title'] ?: $carousel_module_data['name'] }}"
                                                />
                                            </picture>
                                        @else
                                            <div class="w-full h-full">

                                            </div>
                                        @endif
                                    </a>
                                @else
                                    <div class="main-carousel-media flex items-center justify-center w-full h-full">
                                        @if($has_slide_image)
                                            <picture>
                                                @if(filled($desktop_image_urls['thumb_4x'] ?? ''))
                                                    <source media="(min-width: 80rem)"
                                                            srcset="{{ $desktop_image_urls['thumb_4x'] }}">
                                                @endif

                                                @if(filled($desktop_image_urls['thumb_3x'] ?? ''))
                                                    <source media="(min-width: 64rem)"
                                                            srcset="{{ $desktop_image_urls['thumb_3x'] }}">
                                                @endif

                                                @if(filled($desktop_image_urls['thumb_2x'] ?? ''))
                                                    <source media="(min-width: 48rem)"
                                                            srcset="{{ $desktop_image_urls['thumb_2x'] }}">
                                                @endif

                                                <x-storefront::common.img
                                                    class="object-contain"
                                                    :urls_data="$image_urls_data"
                                                    :size="$image_width"
                                                    :max-density="2"
                                                    sizes="100vw"
                                                    width="{{ $image_width }}"
                                                    height="{{ $image_height }}"
                                                    loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                                    decoding="async"
                                                    alt="{{ $slide['title'] ?: $carousel_module_data['name'] }}"
                                                />
                                            </picture>
                                        @else
                                            <div class="w-full h-full">

                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="main-carousel-content-wrap">
                                <div class="main-carousel-content carousel-content">
                                    @if($has_copy)
                                        @if($has_title)
                                            <div class="main-carousel-title absolute top-10 left-0 bp425px:left-1/2 bp425px:-translate-x-1/3 md:translate-x-0
                                                        md:top-2/12 md:left-10 font-cormorant-garamond xl:max-w-1/2 md:max-w-3/4 font-semibold text-42px
                                                        md:text-64px lg:text-82px 2xl:text-122px leading-none -tracking-0.05em uppercase">
                                                {{ $slide['title'] }}
                                            </div>
                                        @endif

                                        @if($has_description)
                                            <p class="main-carousel-description absolute bottom-20 left-0 md:left-1/12 lg:left-1/3 w-full md:max-w-2/5
                                                      lg:max-w-1/3 text-light-gray font-light tracking-0.04em uppercase">
                                                {{ $slide['description'] }}
                                            </p>
                                        @endif
                                    @endif

                                    @if($has_button)
                                        <a class="main-carousel-btn absolute bottom-1/3 md:bottom-24 right-1/2 max-md:translate-x-1/2 md:right-6 flex items-center
                                                  justify-center text-white text-sm lg:text-base xl:text-xl hover:bg-white hover:text-black duration-200
                                                  ease-in uppercase border border-white rounded-full px-50px py-72px md:px-58px md:py-121px lg:px-48px
                                                  lg:py-117px xl:px-70px xl:py-133px"
                                           href="{{ $slide['button_url'] }}"
                                           target="{{ $target }}"
                                           @if($rel) rel="{{ $rel }}" @endif>
                                            <span class="block max-w-44 w-full text-center">
                                                {{ $slide['button_text'] }}
                                            </span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($is_interactive_carousel)
                <!-- Next Slide -->
                <button class="main-carousel-next carousel-next start-0 carousel-nav"
                        type="button">
                    <span class="icon-[mynaui--arrow-left] size-6"></span>
                </button>

                <div class="main-carousel-pagination carousel-pagination"></div>

                <!-- Previous Slide -->
                <button class="main-carousel-prev carousel-prev end-0 carousel-nav"
                        type="button">
                    <span class="icon-[mynaui--arrow-right] size-6"></span>
                </button>
            @endif
        </div>
    </div>
</section>
