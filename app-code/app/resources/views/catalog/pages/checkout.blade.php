@extends('catalog.layouts.main')

@prepend('styles')
    @vite([
        'node_modules/choices.js/src/styles/choices.scss',
        'resources/assets/catalog/css/libs/leaflet.css',
    ])
@endprepend

@section('content')
    @php
        $checkout_data = is_array($checkout_data ?? null) ? $checkout_data : [];
        $visible_items = is_array($checkout_data['visible_items'] ?? null) ? $checkout_data['visible_items'] : [];
        $hidden_items = is_array($checkout_data['hidden_items'] ?? null) ? $checkout_data['hidden_items'] : [];
        $items_count = (int) ($checkout_data['items_count'] ?? 0);
        $continue_shopping_url = (string) ($checkout_data['continue_shopping_url'] ?? localized_route('localized.catalog.home'));
        $edit_items_url = (string) ($checkout_data['edit_items_url'] ?? localized_route('localized.catalog.cart.index'));
        $checkout_selection_state = is_array($checkout_selection_state ?? null) ? $checkout_selection_state : [];
        $selected_city = is_array($checkout_selection_state['city'] ?? null) ? $checkout_selection_state['city'] : [];
        $selected_city_description = (string) ($selected_city['city_description'] ?? '');
        $selected_city_nova_poshta_city_id = (string) ($selected_city['nova_poshta_city_id'] ?? '');
        $selected_city_ukr_poshta_city_id = (int) ($selected_city['ukr_poshta_city_id'] ?? 0);
        $selected_city_lat = $selected_city['city_lat'] ?? null;
        $selected_city_lng = $selected_city['city_lng'] ?? null;
        $selected_delivery_method = (string) ($checkout_selection_state['delivery_method'] ?? '');
        $selected_delivery_point = is_array($checkout_selection_state['delivery_point'] ?? null) ? $checkout_selection_state['delivery_point'] : [];
        $selected_delivery_address = (string) ($checkout_selection_state['delivery_address'] ?? '');
        $selected_delivery_point_description = (string) ($selected_delivery_point['description'] ?? '');
        $selected_delivery_point_value = (string) (
            $selected_delivery_point['id']
            ?? $selected_delivery_point['ref']
            ?? $selected_delivery_point['postcode']
            ?? ''
        );
    @endphp

    <script>
        window.app_params = {
            ...(window.app_params ?? {}),
            ...@js([
                    'checkout_city_search_url' => $checkout_city_search_url ?? localized_route('localized.catalog.checkout.cities'),
                    'checkout_branch_search_url' => $checkout_branch_search_url ?? localized_route('localized.catalog.checkout.branches'),
                    'checkout_selection_save_url' => $checkout_selection_save_url ?? localized_route('localized.catalog.checkout.selection.store'),
                    'checkout_selection_state' => $checkout_selection_state,
                    'checkout_map_data' => $checkout_map_data ?? [],
                    'checkout_choose_city_first_text' => __('catalog/pages/checkout.warnings.choose_city_first'),
                    'checkout_no_delivery_methods_text' => __('catalog/pages/checkout.warnings.no_delivery_methods'),
                    'checkout_no_cities_text' => __('catalog/pages/checkout.warnings.no_cities_found'),
                    'checkout_branch_label_text' => __('catalog/pages/checkout.labels.branch'),
                    'checkout_poshtomat_label_text' => __('catalog/pages/checkout.labels.poshtomat'),
                ])
        };
    </script>

    <div class="container border-b border-b-opacity-light-gray-40%">
        <button class="go-to-previous-page__btn inline-flex items-center gap-2 border-y border-opacity-light-gray-40% px-0 py-3 text-sm md:text-base lg:px-8 lg:py-4"
                type="button">
            <span class="icon-[mdi-light--arrow-left] size-4 md:size-5 shrink-0"></span>
            <span>{{ __('catalog/pages/checkout.continue_shopping') }}</span>
        </button>
    </div>

    <section class="checkout section" id="checkout" data-checkout-page>
        <div class="container flex flex-col gap-4 md:gap-6">
            <div class="flex flex-col gap-6 pb-10 md:gap-8 lg:pb-16">
                <h1 class="font-cormorant-garamond text-32px md:text-44px lg:text-6xl 2xl:text-90px leading-0.9em uppercase">
                    {{ __('catalog/pages/checkout.title') }}
                </h1>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-[minmax(0,1fr)_1fr] lg:grid-cols-[minmax(0,1fr)_0.8fr] xl:grid-cols-[minmax(0,1fr)_0.7fr] 2xl:gap-10">
                    <div class="flex flex-col gap-1">
                        <form class="flex flex-col gap-1" action="#" method="post" novalidate>
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-x-6 lg:gap-y-5">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm md:text-lg text-white" for="checkout-first-name">
                                        {{ __('catalog/pages/checkout.labels.first_name') }}
                                    </label>
                                    <input class="border-0 border-b border-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm text-white placeholder:text-light-gray md:text-lg"
                                           id="checkout-first-name"
                                           name="first_name"
                                           type="text"
                                           placeholder="Леся"/>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm md:text-lg text-white" for="checkout-last-name">
                                        {{ __('catalog/pages/checkout.labels.last_name') }}
                                    </label>
                                    <input class="border-0 border-b border-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm text-white placeholder:text-light-gray md:text-lg"
                                           id="checkout-last-name"
                                           name="last_name"
                                           type="text"
                                           placeholder="Українка"/>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm md:text-lg text-white" for="checkout-phone">
                                        {{ __('catalog/pages/checkout.labels.phone') }}
                                    </label>
                                    <input class="border-0 border-b border-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm text-white placeholder:text-light-gray md:text-lg"
                                           id="checkout-phone"
                                           name="phone"
                                           type="tel"
                                           inputmode="tel"
                                           placeholder="+38 000 000 0000"/>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm md:text-lg text-white" for="checkout-email">
                                        {{ __('catalog/pages/checkout.labels.email') }}
                                    </label>
                                    <input class="border-0 border-b border-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm text-white placeholder:text-light-gray md:text-lg"
                                           id="checkout-email"
                                           name="email"
                                           type="email"
                                           placeholder="yourname@gmail.com"/>
                                </div>
                            </div>

                            <div class="flex flex-col gap-y-1">
                                <div class="accordion group" data-checkout-accordion>
                                    <div class="accordion-item border-b border-opacity-light-gray-40%">
                                        <button class="accordion-toggle flex w-full items-center justify-between gap-4 text-left ps-0 pt-2 md:pb-4"
                                                type="button"
                                                aria-expanded="false"
                                                aria-controls="checkout-delivery-collapse">
                                            <span class="text-sm md:text-lg text-white">
                                                {{ __('catalog/pages/checkout.delivery_section_title') }}
                                            </span>

                                            <span class="icon-[ep--arrow-down] size-5 shrink-0 transition-transform group-[.active]:rotate-180"></span>
                                        </button>

                                        <div class="accordion-content hidden overflow-hidden transition-[height]"
                                             id="checkout-delivery-collapse"
                                             role="region">
                                            <div class="flex flex-col gap-4 pb-4 md:pb-6">
                                                <div class="flex flex-col gap-2">
                                                    <label class="text-sm text-white md:text-lg" for="checkout-city">
                                                        {{ __('catalog/pages/checkout.labels.city') }}
                                                    </label>

                                                    <select class="border-0 border-b border-opacity-light-gray-40% bg-transparent py-2 pl-0 pr-8 text-sm text-white md:text-lg"
                                                            id="checkout-city"
                                                            name="city"
                                                            data-placeholder="{{ __('catalog/pages/checkout.placeholders.city_search') }}">
                                                        <option value="">
                                                            {{ __('catalog/pages/checkout.placeholders.city_search') }}
                                                        </option>

                                                        @if(filled($selected_city_description))
                                                            <option value="{{ $selected_city_description }}"
                                                                    selected
                                                                    data-nova-poshta-city-id="{{ $selected_city_nova_poshta_city_id }}"
                                                                    data-ukr-poshta-city-id="{{ $selected_city_ukr_poshta_city_id }}"
                                                                    data-city-lat="{{ $selected_city_lat }}"
                                                                    data-city-lng="{{ $selected_city_lng }}">
                                                                {{ $selected_city_description }}
                                                            </option>
                                                        @endif
                                                    </select>

                                                    <p class="hidden text-sm text-light-red md:text-base"
                                                       data-checkout-city-warning
                                                       aria-hidden="true"></p>
                                                </div>

                                                <div class="flex flex-col gap-2">
                                                    <span class="text-sm text-white md:text-lg">
                                                        {{ __('catalog/pages/checkout.labels.delivery_method') }}
                                                    </span>

                                                    <div class="flex flex-col gap-3">
                                                        <label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
                                                               data-checkout-delivery-method-option="nova_poshta">
                                                            <input class="radio radio-sm border border-white rounded-none"
                                                                   data-checkout-delivery-method-input
                                                                   name="delivery_method"
                                                                   type="radio"
                                                                   value="nova_poshta"
                                                                @checked($selected_delivery_method === 'nova_poshta')>

                                                            <span>{{ __('catalog/pages/checkout.delivery_methods.nova_poshta') }}</span>
                                                        </label>

                                                        <label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
                                                               data-checkout-delivery-method-option="nova_poshta_courier">
                                                            <input class="radio radio-sm border border-white rounded-none"
                                                                   data-checkout-delivery-method-input
                                                                   name="delivery_method"
                                                                   type="radio"
                                                                   value="nova_poshta_courier"
                                                                @checked($selected_delivery_method === 'nova_poshta_courier')>

                                                            <span>{{ __('catalog/pages/checkout.delivery_methods.nova_poshta_courier') }}</span>
                                                        </label>

                                                        <label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
                                                               data-checkout-delivery-method-option="nova_poshta_poshtomat">
                                                            <input class="radio radio-sm border border-white rounded-none"
                                                                   data-checkout-delivery-method-input
                                                                   name="delivery_method"
                                                                   type="radio"
                                                                   value="nova_poshta_poshtomat"
                                                                @checked($selected_delivery_method === 'nova_poshta_poshtomat')>

                                                            <span>{{ __('catalog/pages/checkout.delivery_methods.nova_poshta_poshtomat') }}</span>
                                                        </label>

                                                        <label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
                                                               data-checkout-delivery-method-option="ukr_poshta">
                                                            <input class="radio radio-sm border border-white rounded-none"
                                                                   data-checkout-delivery-method-input
                                                                   name="delivery_method"
                                                                   type="radio"
                                                                   value="ukr_poshta"
                                                                @checked($selected_delivery_method === 'ukr_poshta')>

                                                            <span>{{ __('catalog/pages/checkout.delivery_methods.ukr_poshta') }}</span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div @class([
                                                    'flex flex-col gap-2',
                                                    'hidden' => $selected_delivery_method !== 'nova_poshta_courier',
                                                ])
                                                     data-checkout-delivery-address-wrapper>
                                                    <label class="text-sm text-white md:text-lg" for="checkout-delivery-address">
                                                        {{ __('catalog/pages/checkout.labels.delivery_address') }}
                                                    </label>

                                                    <input class="border-0 border-b border-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm text-white placeholder:text-light-gray md:text-lg"
                                                           id="checkout-delivery-address"
                                                           name="delivery_address"
                                                           type="text"
                                                           maxlength="255"
                                                           value="{{ $selected_delivery_address }}"
                                                           placeholder="{{ __('catalog/pages/checkout.placeholders.delivery_address') }}"
                                                           @if($selected_delivery_method === 'nova_poshta_courier') required @endif>
                                                </div>

                                                <div @class([
                                                    'flex flex-col gap-2',
                                                    'hidden' => $selected_delivery_method === '' || $selected_delivery_method === 'nova_poshta_courier',
                                                ])
                                                     data-checkout-branch-wrapper>
                                                    <label class="text-sm text-white md:text-lg" for="checkout-branch" data-checkout-branch-label>
                                                        @if($selected_delivery_method === 'nova_poshta_poshtomat')
                                                            {{ __('catalog/pages/checkout.labels.poshtomat') }}
                                                        @else
                                                            {{ __('catalog/pages/checkout.labels.branch') }}
                                                        @endif
                                                    </label>

                                                    <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                                                        <div class="relative flex min-w-0 max-lg:w-full flex-1 items-center gap-2 pt-1 text-sm md:text-lg">
                                                            <span class="absolute bottom-1 left-0 icon-[tabler--search] custom-icon md:bottom-2"></span>
                                                            <select class="w-full border-0 border-b border-opacity-light-gray-40% bg-transparent py-1 pe-0 ps-7 text-sm text-white placeholder:text-light-gray md:py-2 md:ps-9 md:text-lg"
                                                                    id="checkout-branch"
                                                                    name="branch"
                                                                    data-placeholder="{{ __('catalog/pages/checkout.placeholders.branch_search') }}">
                                                                <option value="">
                                                                    {{ __('catalog/pages/checkout.placeholders.branch_search') }}
                                                                </option>

                                                                @if(filled($selected_delivery_point_description))
                                                                    <option value="{{ $selected_delivery_point_value }}"
                                                                            selected
                                                                            data-custom-properties="{{ e(json_encode($selected_delivery_point, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}">
                                                                        {{ $selected_delivery_point_description }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                        </div>

                                                        <button class="dark-btn inline-flex shrink-0 items-center justify-between gap-4 max-lg:w-full border border-white px-4 py-2 text-left text-sm uppercase tracking-[0.02em] text-white md:text-base"
                                                                id="find-on-map-btn"
                                                                data-checkout-map-button
                                                                type="button">
                                                            <span>{{ __('catalog/pages/checkout.buttons.find_on_map') }}</span>
                                                            <span class="icon-[tabler--map-pin] custom-icon shrink-0"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion group" data-checkout-accordion>
                                    <div class="accordion-item border-b border-opacity-light-gray-40%">
                                        <button class="accordion-toggle flex w-full items-center justify-between gap-4 text-left ps-0 pt-2 md:pb-4"
                                                type="button"
                                                aria-expanded="false"
                                                aria-controls="checkout-payment-collapse">
                                            <span class="text-sm md:text-lg text-white">
                                                {{ __('catalog/pages/checkout.payment_section_title') }}
                                            </span>

                                            <span class="icon-[ep--arrow-down] size-5 shrink-0 transition-transform group-[.active]:rotate-180"></span>
                                        </button>

                                        <div class="accordion-content hidden overflow-hidden transition-[height]"
                                             id="checkout-payment-collapse"
                                             role="region">
                                            <div class="flex flex-col gap-4 pb-4 md:pb-6">
                                                <div class="flex items-center justify-between border-b border-opacity-light-gray-40% py-2 text-sm text-light-gray md:text-lg">
                                                    <span>{{ __('catalog/pages/checkout.placeholders.payment_methods') }}</span>
                                                    <span class="icon-[ep--arrow-down] size-5 shrink-0"></span>
                                                </div>

                                                <div class="rounded-sm border border-opacity-light-gray-40% px-4 py-3 text-sm text-light-gray md:text-base">
                                                    {{ __('catalog/pages/checkout.placeholders.payment_placeholder') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 flex flex-col gap-y-4">
                                    <div class="accordion group" data-checkout-accordion>
                                        <div class="accordion-item">
                                            <button class="accordion-toggle flex w-full items-center justify-start gap-2 text-left p-0"
                                                    type="button"
                                                    aria-expanded="false"
                                                    aria-controls="checkout-comment-collapse">
                                                <span class="icon-[mdi--plus] size-4 shrink-0"></span>
                                                <span class="text-sm text-white md:text-lg">{{ __('catalog/pages/checkout.labels.comment') }}</span>
                                            </button>

                                            <div class="accordion-content hidden overflow-hidden transition-[height]"
                                                 id="checkout-comment-collapse"
                                                 role="region">
                                                <div class="flex flex-col gap-2 pb-4 md:pb-6">
                                                    <label class="sr-only" for="checkout-comment">
                                                        {{ __('catalog/pages/checkout.labels.comment') }}
                                                    </label>

                                                    <textarea class="min-h-28 border border-opacity-light-gray-40% bg-transparent px-4 py-3 text-sm text-white placeholder:text-light-gray md:text-base"
                                                              id="checkout-comment"
                                                              name="comment"
                                                              placeholder="{{ __('catalog/pages/checkout.labels.comment_field') }}"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion group" data-checkout-accordion>
                                        <div class="accordion-item">
                                            <button class="accordion-toggle flex w-full items-center justify-start gap-2 text-left p-0"
                                                    type="button"
                                                    aria-expanded="false"
                                                    aria-controls="checkout-promo-collapse">
                                                <span class="icon-[mdi--plus] size-4 shrink-0"></span>
                                                <span class="text-sm text-white md:text-lg">{{ __('catalog/pages/checkout.labels.promo') }}</span>
                                            </button>

                                            <div class="accordion-content hidden overflow-hidden transition-[height]"
                                                 id="checkout-promo-collapse"
                                                 role="region">
                                                <div class="flex flex-col gap-2 pb-4 md:pb-6">
                                                    <label class="sr-only" for="checkout-promo-code">
                                                        {{ __('catalog/pages/checkout.labels.promo') }}
                                                    </label>

                                                    <input class="border border-opacity-light-gray-40% bg-transparent px-4 py-3 text-sm text-white placeholder:text-light-gray md:text-base"
                                                           id="checkout-promo-code"
                                                           name="promo_code"
                                                           type="text"
                                                           placeholder="{{ __('catalog/pages/checkout.labels.promo_field') }}"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <label class="inline-flex items-center gap-3 text-sm text-white md:text-lg">
                                        <input class="checkbox checkbox-sm border border-white rounded-none"
                                               id="checkout-no-call"
                                               name="no_call"
                                               type="checkbox"
                                               value="1"/>

                                        <span>{{ __('catalog/pages/checkout.labels.no_call') }}</span>
                                    </label>

                                    <p class="max-w-160 text-sm text-light-gray md:text-base">
                                        {{ __('catalog/pages/checkout.texts.consent') }}
                                    </p>

                                    <button class="white-btn default-btn w-full md:max-w-sm text-sm md:text-lg"
                                            type="button">
                                        {{ __('catalog/pages/checkout.buttons.submit') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <aside class="border border-opacity-light-gray-40% p-4 md:p-6 lg:sticky lg:top-[calc(var(--site-header-height)+1rem)] lg:self-start">
                        <div class="flex flex-col gap-4 md:gap-5">
                            <div class="flex items-center justify-between gap-4 border-b border-opacity-light-gray-40% pb-3 text-sm md:text-lg">
                                <h2 class="font-inter text-sm uppercase leading-1.4em tracking-0.04em md:text-lg">
                                    {{ __('catalog/pages/checkout.summary_title') }}
                                </h2>

                                <p class="font-bold text-white">
                                    {{ $checkout_data['subtotal_formatted'] ?? '' }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-4 md:gap-5">
                                @foreach($visible_items as $checkout_item)
                                    @php
                                        $item_attributes = is_array($checkout_item['selected_attributes'] ?? null)
                                            ? $checkout_item['selected_attributes']
                                            : [];
                                        $item_image_data = is_array($checkout_item['image_data'] ?? null)
                                            ? $checkout_item['image_data']
                                            : [];
                                    @endphp

                                    <div class="flex items-start gap-4 border-b border-opacity-light-gray-40% pb-4 md:gap-6">
                                        <a class="shrink-0 w-20 overflow-hidden md:w-26.5 xl:w-35" href="{{ $checkout_item['url'] }}">
                                            <x-catalog::common.img
                                                class="aspect-square size-full object-contain"
                                                :urls_data="$item_image_data['urls'] ?? []"
                                                :size="$item_image_data['width'] ?? 220"
                                                sizes="(max-width: 767px) 80px, (max-width: 1023px) 106px, 140px"
                                                width="{{ $item_image_data['width'] ?? 220 }}"
                                                height="{{ $item_image_data['height'] ?? 220 }}"
                                                alt="{{ $checkout_item['name'] }}"
                                            />
                                        </a>

                                        <div class="min-w-0 flex flex-1 flex-col gap-2 md:gap-2.5">
                                            <a class="font-cormorant-garamond text-lg leading-none uppercase md:text-24px 2xl:text-4xl"
                                               href="{{ $checkout_item['url'] }}">
                                                {{ $checkout_item['name'] }}
                                            </a>

                                            <p class="text-11px text-light-gray tracking-0.04em md:text-sm lg:text-base 2xl:text-lg">
                                                {{ __('catalog/default.texts.sku', ['sku' => $checkout_item['sku'] ?? '']) }}
                                            </p>

                                            @if($item_attributes !== [])
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($item_attributes as $attribute)
                                                        @php
                                                            $attribute_label = is_array($attribute) ? (string) ($attribute['value'] ?? '') : (string) $attribute;
                                                        @endphp

                                                        @continue(trim($attribute_label) === '')

                                                        <span class="inline-flex items-center justify-center border border-opacity-light-gray-40% px-3 py-1 text-sm md:text-sm lg:text-base 2xl:text-lg">
                                                            {{ $attribute_label }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <p class="text-sm uppercase md:text-base lg:text-2xl">
                                                {{ $checkout_item['line_total_formatted'] ?? $checkout_item['unit_price_formatted'] ?? '' }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($hidden_items !== [])
                                <div class="accordion group" data-checkout-accordion>
                                    <div class="accordion-item">
                                        <div class="accordion-content hidden overflow-hidden transition-[height]" id="checkout-hidden-items-collapse" role="region">
                                            <div class="flex flex-col gap-4 pb-4 pt-4">
                                                @foreach($hidden_items as $checkout_item)
                                                    @php
                                                        $item_attributes = is_array($checkout_item['selected_attributes'] ?? null)
                                                            ? $checkout_item['selected_attributes']
                                                            : [];
                                                        $item_image_data = is_array($checkout_item['image_data'] ?? null)
                                                            ? $checkout_item['image_data']
                                                            : [];
                                                    @endphp

                                                    <article class="flex items-start gap-4 border-b border-opacity-light-gray-40% pb-4 md:gap-6">
                                                        <a class="shrink-0 w-20 overflow-hidden md:w-26.5 lg:w-35" href="{{ $checkout_item['url'] }}">
                                                            <x-catalog::common.img
                                                                class="aspect-square size-full object-contain"
                                                                :urls_data="$item_image_data['urls'] ?? []"
                                                                :size="$item_image_data['width'] ?? 220"
                                                                sizes="(max-width: 767px) 80px, (max-width: 1023px) 106px, 140px"
                                                                width="{{ $item_image_data['width'] ?? 220 }}"
                                                                height="{{ $item_image_data['height'] ?? 220 }}"
                                                                alt="{{ $checkout_item['name'] }}"
                                                            />
                                                        </a>

                                                        <div class="min-w-0 flex flex-1 flex-col gap-2 md:gap-2.5">
                                                            <a class="font-cormorant-garamond text-lg leading-none uppercase md:text-24px 2xl:text-4xl"
                                                               href="{{ $checkout_item['url'] }}">
                                                                {{ $checkout_item['name'] }}
                                                            </a>

                                                            <p class="text-11px text-light-gray tracking-0.04em md:text-sm lg:text-base 2xl:text-lg">
                                                                {{ __('catalog/default.texts.sku', ['sku' => $checkout_item['sku'] ?? '']) }}
                                                            </p>

                                                            @if($item_attributes !== [])
                                                                <div class="flex flex-wrap gap-2">
                                                                    @foreach($item_attributes as $attribute)
                                                                        @php
                                                                            $attribute_label = is_array($attribute) ? (string) ($attribute['value'] ?? '') : (string) $attribute;
                                                                        @endphp

                                                                        @continue(trim($attribute_label) === '')

                                                                        <span class="inline-flex items-center justify-center border border-opacity-light-gray-40% px-3 py-1 text-sm md:text-sm lg:text-base 2xl:text-lg">
                                                                            {{ $attribute_label }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            @endif

                                                            <p class="text-sm uppercase md:text-base lg:text-2xl">
                                                                {{ $checkout_item['line_total_formatted'] ?? $checkout_item['unit_price_formatted'] ?? '' }}
                                                            </p>
                                                        </div>
                                                    </article>
                                                @endforeach
                                            </div>
                                        </div>

                                        <button class="accordion-toggle inline-flex items-center gap-2 text-sm md:text-base p-0"
                                                type="button"
                                                aria-expanded="false"
                                                aria-controls="checkout-hidden-items-collapse">
                                            <span>
                                                {{ __('catalog/pages/checkout.buttons.show_all_items', ['count' => $items_count]) }}
                                            </span>

                                            <span class="icon-[ep--arrow-down] size-5 shrink-0 transition-transform group-[.active]:rotate-180"></span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <a class="inline-flex w-full items-center justify-end text-sm uppercase tracking-0.04em underline decoration-1 underline-offset-4 md:text-base"
                               href="{{ $edit_items_url }}">
                                {{ __('catalog/pages/checkout.buttons.edit_items') }}
                            </a>

                            <div class="flex flex-col gap-3 border-t border-opacity-light-gray-40% pt-4">
                                <div class="flex items-center justify-between gap-4 text-sm md:text-base">
                                    <span>{{ __('catalog/pages/checkout.texts.subtotal') }}</span>
                                    <span>{{ $checkout_data['subtotal_formatted'] ?? '' }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-4 text-sm md:text-base">
                                    <span>{{ __('catalog/pages/checkout.texts.delivery') }}</span>
                                    <span>{{ $checkout_data['delivery_formatted'] ?? '—' }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-4 text-base font-bold uppercase md:text-lg">
                                    <span>{{ __('catalog/pages/checkout.texts.total') }}</span>
                                    <span>{{ $checkout_data['grand_total_formatted'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>

@endsection
