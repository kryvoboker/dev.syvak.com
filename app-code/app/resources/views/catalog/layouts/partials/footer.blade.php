@php
    /**
     * Footer rendering intentionally contains only presentation-level preparation.
     * All heavy data preparation is delegated to App\Services\FooterService.
     */

    $subscription_data = $footer_data['subscription_data'];
    $contacts_data = $footer_data['contacts_data'];
    $information_data = $footer_data['information_data'];
    $menu_items = collect($footer_data['menu_items']);
    $social_items = collect($footer_data['social_items']);
@endphp

<footer class="footer">
    <div class="container">
        {{--
            Main grid follows Figma behavior:
            - Mobile: subscription -> contacts -> menu -> info
            - Tablet: 2x2 blocks
            - Desktop: 4 columns (subscription/contacts/menu/info)
        --}}
        <div class="footer-main-grid">
            <section class="footer-subscribe" aria-label="{{ $subscription_data['title'] }}">
                <p class="footer-title">{{ $subscription_data['title'] }}</p>

                {{-- Telegram CTA as in Figma (input-like dark/light block with arrow action). --}}
                <a class="footer-subscribe-btn dark-btn"
                   href="{{ $subscription_data['button_url'] }}"
                   aria-label="{{ $subscription_data['button_text'] }}">
                    <span>{{ $subscription_data['button_text'] }}</span>
                    <span class="footer-subscribe-btn-arrow" aria-hidden="true">{!! $subscription_data['arrow_icon_svg'] !!}</span>
                </a>

                <p class="text-accent max-w-[31rem] uppercase">
                    {{ $subscription_data['support_text'] }}
                </p>
            </section>

            <section class="footer-contacts" aria-label="{{ $contacts_data['title'] }}">
                <h4 class="footer-title">{{ $contacts_data['title'] }}</h4>

                <ul class="footer-list">
                    @foreach($contacts_data['phones'] as $phone)
                        <li>
                            <a class="footer-link footer-phone"
                               href="tel:{{ clear_telephone($phone) }}">
                                {{ $phone }}
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

                {{-- In mobile/tablet, social icons are inside contacts block per Figma. --}}
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

            <nav class="footer-menu md:order-4 lg:order-3" aria-label="/ МЕНЮ /">
                <h4 class="footer-title">/ МЕНЮ /</h4>

                <ul class="footer-list">
                    @foreach($menu_items as $item)
                        <li>
                            <a class="footer-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <nav class="footer-info md:order-3 lg:order-4" aria-label="{{ $information_data['title'] }}">
                <h4 class="footer-title">{{ $information_data['title'] }}</h4>

                <ul class="footer-list">
                    @foreach($information_data['items'] as $item)
                        <li>
                            <a class="footer-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        {{--
            Bottom row follows desktop/tablet mockups:
            - Tablet: large brand text only
            - Desktop: large brand text + social icons aligned to the right
        --}}
        <div class="footer-brand-row">
            <a class="footer-brand-link"
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
