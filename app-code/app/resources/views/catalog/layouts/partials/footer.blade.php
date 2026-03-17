@php
    /**
     * Footer template keeps only presentation-level mapping.
     * Data preparation stays in App\Services\FooterService.
     */

    $subscription_data = $footer_data['subscription_data'];
    $contacts_data = $footer_data['contacts_data'];
    $information_data = $footer_data['information_data'];
    $menu_items = collect($footer_data['menu_items']);
    $social_items = collect($footer_data['social_items']);
@endphp

<footer class="footer">
    <div class="container">
        <div class="footer-main-grid">
            <section class="footer-subscribe" aria-label="{{ $subscription_data['title'] }}">
                <div class="footer-title">{{ $subscription_data['title'] }}</div>

                <a class="footer-subscribe-btn dark-btn"
                   href="{{ $subscription_data['button_url'] }}"
                   aria-label="{{ $subscription_data['button_text'] }}">
                    <span>{{ $subscription_data['button_text'] }}</span>

                    <span class="footer-subscribe-btn-arrow">
                        <span class="icon-[quill--arrow-up] rotate-45"></span>
                    </span>
                </a>

                <p class="font-light text-white footer-support-text uppercase opacity-70 tracking-0.04em">
                    {{ $subscription_data['support_text'] }}
                </p>
            </section>

            <section class="footer-contacts" aria-label="{{ $contacts_data['title'] }}">
                <div class="footer-title">{{ $contacts_data['title'] }}</div>

                <ul class="footer-list">
                    @foreach($contacts_data['phones'] as $phone)
                        <li>
                            <a class="footer-link footer-phone"
                               href="tel:{{ clear_telephone($phone) }}">
                                {{ parse_telephone($phone) }}
                            </a>
                        </li>
                    @endforeach

                    <li>
                        <a class="footer-link" href="#">{{ $contacts_data['find_us_label'] }}</a>
                    </li>

                    <li>
                        <a class="footer-link" href="#">{{ $contacts_data['contacts_label'] }}</a>
                    </li>
                </ul>

                <div class="footer-socials footer-socials-inline">
                    @foreach($social_items as $item)
                        <a class="footer-social-link"
                           href="{{ $item['url'] }}"
                           aria-label="{{ $item['label'] }}">
                            {!! $item['svg_icon'] !!}
                        </a>
                    @endforeach
                </div>
            </section>

            <nav class="footer-menu md:order-4 lg:hidden bp1920px:flex bp1920px:order-3" aria-label="/ МЕНЮ /">
                <div class="footer-title">/ МЕНЮ /</div>

                <ul class="footer-list">
                    @foreach($menu_items as $item)
                        <li>
                            <a class="footer-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <nav class="footer-info md:order-3 lg:order-4" aria-label="{{ $information_data['title'] }}">
                <div class="footer-title">{{ $information_data['title'] }}</div>

                <ul class="footer-list">
                    @foreach($information_data['items'] as $item)
                        <li>
                            <a class="footer-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <div class="footer-brand-row md:overflow-hidden 2xl:overflow-visible">
            <a class="hidden md:inline-block md:font-extrabold md:uppercase md:leading-none md:tracking-[28%] md:text-150px md:-mb-5
                      lg:text-[180px] lg:tracking-[35%] lg:ms-16 xl:text-[230px] xl:ms-20 2xl:text-82px 2xl:tracking-[25%]
                      2xl:m-0"
               href="{{ localizedRoute('catalog.home') }}"
               aria-label="{{ $footer_data['brand_large_text'] }}">
                {{ $footer_data['brand_large_text'] }}
            </a>

            <div class="footer-socials footer-socials-desktop">
                @foreach($social_items as $item)
                    <a class="footer-social-link"
                       href="{{ $item['url'] }}"
                       aria-label="{{ $item['label'] }}">
                        {!! $item['svg_icon'] !!}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</footer>
