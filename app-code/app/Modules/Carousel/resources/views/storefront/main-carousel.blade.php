@php
    $carousel_dom_id = 'main-carousel-' . $carousel_module_data['instance_id'];
    $open_in_new_tab = $carousel_module_data['open_links_in_new_tab'] ?? true;
    $target = $open_in_new_tab ? '_blank' : '_self';
    $rel = $open_in_new_tab ? 'noopener noreferrer' : null;
@endphp

<section class="home-main-carousel-section" aria-label="Main carousel">
    <div id="{{ $carousel_dom_id }}"
         class="home-main-carousel"
         data-main-carousel
         data-carousel='{"loadingClasses": "opacity-0, opacity-100 transition-opacity duration-500", "isAutoHeight": true, "isInfiniteLoop": true}'>
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
                    <article class="carousel-slide home-main-carousel-slide">
                        <div class="home-main-carousel-media">
                            @if($has_image_link)
                                <a class="home-main-carousel-media-link"
                                   href="{{ $slide['image_url'] }}"
                                   target="{{ $target }}"
                                   @if($rel) rel="{{ $rel }}" @endif
                                   aria-label="{{ $slide['title'] ?: $carousel_module_data['name'] }}">
                            @endif
                                <picture>
                                    <source media="(min-width: 48rem)"
                                            srcset="{{ $slide['desktop_image']['urls']['thumb_1x'] ?? $slide['desktop_image']['urls']['original_thumb'] }}">
                                    <img class="home-main-carousel-image"
                                         src="{{ $slide['mobile_image']['urls']['thumb_1x'] ?? $slide['mobile_image']['urls']['original_thumb'] }}"
                                         alt="{{ $slide['title'] ?: $carousel_module_data['name'] }}"
                                         width="{{ $slide['mobile_image']['width'] }}"
                                         height="{{ $slide['mobile_image']['height'] }}"
                                         loading="eager"
                                         decoding="async">
                                </picture>
                            @if($has_image_link)
                                </a>
                            @endif
                        </div>

                        <div class="container home-main-carousel-content-wrap">
                            <div class="home-main-carousel-content">
                                @if($has_copy)
                                    <div class="home-main-carousel-copy">
                                        @if($has_title)
                                            <h1 class="home-main-carousel-title">{{ $slide['title'] }}</h1>
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
                    </article>
                @endforeach
            </div>
        </div>

        @if(count($carousel_module_data['slides']) > 1)
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
