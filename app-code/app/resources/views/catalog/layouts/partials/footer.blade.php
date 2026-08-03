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

<footer class="footer border-t border-opacity-light-gray-40% max-md:overflow-hidden pt-4 pb-4 md:pt-7 md:pb-0 lg:pt-15 2xl:pt-20 2xl:mb-52px z-1">
    <div class="container">
        <div class="footer-main-grid grid grid-cols-1 gap-y-6 md:grid-cols-2 md:gap-x-12 md:gap-y-8 lg:grid-cols-3 lg:gap-x-10 lg:gap-y-0 2xl:grid-cols-4 2xl:gap-x-12">
            <section class="footer-subscribe flex flex-col gap-4" aria-label="{{ $subscription_data['title'] }}">
                <div class="footer-title">{{ $subscription_data['title'] }}</div>

                <x-catalog::common.telegram-link :telegram_data="$subscription_data"/>

                <p class="footer-support-text max-w-496px font-light text-white uppercase opacity-70 tracking-0.04em">
                    {{ $subscription_data['support_text'] }}
                </p>
            </section>

            <section class="footer-contacts" aria-label="{{ $contacts_data['title'] }}">
                <div class="footer-title">{{ $contacts_data['title'] }}</div>

                <ul class="footer-list">
                    @foreach($contacts_data['phones'] as $phone)
                        <li>
                            <a class="footer-link footer-phone text-xl 2xl:text-32px leading-none tracking-normal opacity-100"
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

                <div class="footer-socials footer-socials-inline md:flex 2xl:hidden">
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

        <div class="footer-brand-row hidden mt-5 md:flex md:items-end md:justify-center 2xl:justify-between md:w-full md:overflow-hidden 2xl:overflow-visible">
            <a class="hidden md:inline-block md:font-extrabold md:uppercase md:leading-none md:tracking-[28%] md:text-150px md:-mb-5
                      lg:text-[180px] lg:tracking-[35%] lg:ms-16 xl:text-[230px] xl:ms-20 2xl:text-82px 2xl:tracking-[25%]
                      2xl:m-0"
               href="{{ localized_route('catalog.home') }}"
               aria-label="{{ $footer_data['brand_large_text'] }}">
                {{ $footer_data['brand_large_text'] }}
            </a>

            <div class="footer-socials footer-socials-desktop hidden 2xl:flex">
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

<x-catalog::cart.modal/>
<x-catalog::cart.fast-order-modal/>
