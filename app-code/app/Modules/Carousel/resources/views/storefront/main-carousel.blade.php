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
@endphp

<section class="home-main-carousel-section" aria-label="Main carousel">
    <div class="container">
        <div id="{{ $carousel_dom_id }}"
             class="home-main-carousel --prevent-on-load-init"
             @if($is_interactive_carousel)
                 data-main-carousel
             data-slides-count="{{ $slides_count }}"
             data-page-types='@json($page_types)'
             data-carousel='{ "isAutoHeight": true, "loadingClasses": "opacity-0", "dotsItemClasses": "carousel-dot size-1.5 bg-light-gray carousel-active:size-2.5 carousel-active:ease-in-out carousel-active:duration-200", "isDraggable": true, "isInfiniteLoop": true, "isAutoPlay": true }'
            @endif>
            <div class="carousel home-main-carousel-track rounded-none">
                <div class="carousel-body home-main-carousel-body">
                    @foreach($carousel_module_data['slides'] as $slide)
                        @php
                            $has_image_link = filled($slide['image_url']);
                            $has_title = filled($slide['title']);
                            $has_description = filled($slide['description']);
                            $has_copy = $has_title || $has_description;
                            $has_button = filled($slide['button_text']) && filled($slide['button_url']);
                        @endphp
                        <div class="carousel-slide relative flex flex-col items-center home-main-carousel-slide">
                            <div class="home-main-carousel-media justify-center">
                                @if($has_image_link)
                                    <a class="home-main-carousel-media-link flex items-center justify-center w-full h-full"
                                       href="{{ $slide['image_url'] }}"
                                       target="{{ $target }}"
                                       @if($rel) rel="{{ $rel }}" @endif
                                       aria-label="{{ $slide['title'] ?: $carousel_module_data['name'] }}">
                                        <picture>
                                            @if($slide['desktop_image']['urls']['thumb_4x'])
                                                <source media="(min-width: 80rem)"
                                                        srcset="{{ $slide['desktop_image']['urls']['thumb_4x'] }}">
                                            @endif

                                            @if($slide['desktop_image']['urls']['thumb_3x'])
                                                <source media="(min-width: 64rem)"
                                                        srcset="{{ $slide['desktop_image']['urls']['thumb_3x'] }}">
                                            @endif

                                            @if($slide['desktop_image']['urls']['thumb_2x'])
                                                <source media="(min-width: 48rem)"
                                                        srcset="{{ $slide['desktop_image']['urls']['thumb_2x'] }}">
                                            @endif

                                            <x-catalog::common.img
                                                class="object-contain"
                                                :urls_data="$slide['mobile_image']['urls']"
                                                :size="$slide['mobile_image']['width']"
                                                :max-density="2"
                                                sizes="100vw"
                                                width="{{ $slide['mobile_image']['width'] * 4 }}"
                                                height="{{ $slide['mobile_image']['height'] * 4 }}"
                                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                                decoding="async"
                                                alt="{{ $slide['title'] ?: $carousel_module_data['name'] }}"
                                            />
                                        </picture>
                                    </a>
                                @endif
                            </div>

                            <div class="container home-main-carousel-content-wrap">
                                <div class="home-main-carousel-content">
                                    @if($has_copy)
                                        @if($has_title)
                                            <div class="home-main-carousel-title absolute top-2/12 left-10 font-cormorant-garamond
                                                        font-semibold text-122px leading-0.8em -tracking-0.05em">
                                                {{ $slide['title'] }}
                                            </div>
                                        @endif

                                        @if($has_description)
                                            <p class="home-main-carousel-description absolute bottom-20 left-1/3 w-full max-w-463px text-light-gray font-light tracking-0.04em">
                                                {{ $slide['description'] }}
                                            </p>
                                        @endif
                                    @endif

                                    @if($has_button)
                                        <a class="home-main-carousel-cta"
                                           href="{{ $slide['button_url'] }}"
                                           target="{{ $target }}"
                                           @if($rel) rel="{{ $rel }}" @endif>
                                            <span>
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
                <button class="carousel-next start-5 max-sm:end-3 carousel-disabled:opacity-50 size-14
                           bg-black/20 flex items-center justify-center border border-light-gray rounded-full shadow-base-300/20
                           shadow-sm"
                        type="button">
                    <span class="icon-[mynaui--arrow-left] size-6"></span>
                </button>

                <div class="carousel-pagination flex items-center justify-center gap-x-2"></div>

                <!-- Previous Slide -->
                <button class="carousel-prev end-5 max-sm:start-3 carousel-disabled:opacity-50 size-14
                           bg-black/20 flex items-center justify-center border border-light-gray rounded-full shadow-base-300/20
                           shadow-sm"
                        type="button">
                    <span class="icon-[mynaui--arrow-right] size-6"></span>
                </button>
            @endif
        </div>
    </div>
</section>
