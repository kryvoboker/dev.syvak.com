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
    <div id="{{ $carousel_dom_id }}"
         class="home-main-carousel --prevent-on-load-init"
         @if($is_interactive_carousel)
             data-main-carousel
         data-slides-count="{{ $slides_count }}"
         data-page-types='@json($page_types)'
         data-carousel='{ "isAutoHeight": true, "loadingClasses": "opacity-0", "isDraggable": true, "dotsItemClasses": "carousel-dot carousel-active:bg-primary" }'
        @endif>
        <div class="carousel home-main-carousel-track">
            <div class="carousel-body home-main-carousel-body">
                @foreach($carousel_module_data['slides'] as $slide)
                    @php
                        $has_image_link = filled($slide['image_url']);
                        $has_title = filled($slide['title']);
                        $has_description = filled($slide['description']);
                        $has_copy = $has_title || $has_description;
                        $has_button = filled($slide['button_text']) && filled($slide['button_url']);
                    @endphp
                    <div class="carousel-slide home-main-carousel-slide">
                        <div class="home-main-carousel-media">
                            @if($has_image_link)
                                <a class="home-main-carousel-media-link"
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
                                    <div class="home-main-carousel-copy">
                                        @if($has_title)
                                            <div class="home-main-carousel-title">{{ $slide['title'] }}</div>
                                        @endif

                                        @if($has_description)
                                            <p class="home-main-carousel-description">{{ $slide['description'] }}</p>
                                        @endif
                                    </div>
                                @endif

                                @if($has_button)
                                    <a class="home-main-carousel-cta"
                                       href="{{ $slide['button_url'] }}"
                                       target="{{ $target }}"
                                       @if($rel) rel="{{ $rel }}" @endif>
                                        <span>{{ $slide['button_text'] }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($is_interactive_carousel)
            <div class="home-main-carousel-controls">
                <button class="carousel-prev home-main-carousel-nav" type="button" aria-label="Previous slide">
                    <span class="icon-[material-symbols-light--arrow-back-rounded] home-main-carousel-nav-icon"></span>
                </button>

                <div class="carousel-pagination home-main-carousel-pagination"></div>

                <button class="carousel-next home-main-carousel-nav" type="button" aria-label="Next slide">
                    <span class="icon-[material-symbols-light--arrow-forward-rounded] home-main-carousel-nav-icon"></span>
                </button>
            </div>
        @endif
    </div>
</section>
