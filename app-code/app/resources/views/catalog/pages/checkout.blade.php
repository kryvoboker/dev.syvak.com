@extends('catalog.layouts.main')

@section('content')
    @php
        $checkout_data = is_array($checkout_data ?? null) ? $checkout_data : [];
        $visible_items = is_array($checkout_data['visible_items'] ?? null) ? $checkout_data['visible_items'] : [];
        $hidden_items = is_array($checkout_data['hidden_items'] ?? null) ? $checkout_data['hidden_items'] : [];
        $items_count = (int) ($checkout_data['items_count'] ?? 0);
        $continue_shopping_url = (string) ($checkout_data['continue_shopping_url'] ?? localized_route('localized.catalog.home'));
        $edit_items_url = (string) ($checkout_data['edit_items_url'] ?? localized_route('localized.catalog.cart.index'));
    @endphp

    <section class="checkout section" id="checkout" data-checkout-page>
        <div class="container flex flex-col gap-4 md:gap-6">
            <a class="go-to-previous-page__btn inline-flex items-center gap-2 border-y border-opacity-light-gray-40% px-0 py-3 text-sm md:text-base lg:px-8 lg:py-4"
               href="{{ $continue_shopping_url }}">
                <span class="icon-[mdi-light--arrow-left] size-4 md:size-5 shrink-0"></span>
                <span>{{ __('catalog/default.cart.buttons.continue_shopping') }}</span>
            </a>

            <div class="flex flex-col gap-6 pb-10 md:gap-8 lg:pb-16">
                <h1 class="font-cormorant-garamond text-32px md:text-44px lg:text-6xl 2xl:text-90px leading-0.9em uppercase">
                    ОФОРМЛЕННЯ ЗАМОВЛЕННЯ
                </h1>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_21.5rem] 2xl:grid-cols-[minmax(0,1fr)_54.1875rem] 2xl:gap-10">
                    <div class="flex flex-col gap-6 md:gap-8">
                        <form class="flex flex-col gap-6 md:gap-8" action="#" method="post" novalidate>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-x-6 md:gap-y-5">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm md:text-lg text-white" for="checkout-first-name">
                                        {{ __('catalog/default.cart.labels.first_name') }}*
                                    </label>
                                    <input class="border-0 border-b border-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm text-white placeholder:text-light-gray md:text-lg"
                                           id="checkout-first-name"
                                           name="first_name"
                                           type="text"
                                           placeholder="Леся"/>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm md:text-lg text-white" for="checkout-last-name">
                                        {{ __('catalog/default.cart.labels.last_name') }}*
                                    </label>
                                    <input class="border-0 border-b border-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm text-white placeholder:text-light-gray md:text-lg"
                                           id="checkout-last-name"
                                           name="last_name"
                                           type="text"
                                           placeholder="Українка"/>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm md:text-lg text-white" for="checkout-phone">
                                        {{ __('catalog/default.cart.labels.phone') }}*
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
                                        E-mail
                                    </label>
                                    <input class="border-0 border-b border-opacity-light-gray-40% bg-transparent px-0 py-2 text-sm text-white placeholder:text-light-gray md:text-lg"
                                           id="checkout-email"
                                           name="email"
                                           type="email"
                                           placeholder="yourname@gmail.com"/>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 md:gap-5">
                                <div class="accordion group" data-checkout-accordion>
                                    <div class="accordion-item border-y border-opacity-light-gray-40%">
                                        <button class="accordion-toggle flex w-full items-center justify-between gap-4 py-3 text-left md:py-4"
                                                type="button"
                                                aria-expanded="false"
                                                aria-controls="checkout-delivery-collapse">
                                            <span class="text-sm md:text-lg text-white">
                                                Способи доставки
                                            </span>

                                            <span class="icon-[ep--arrow-down] size-5 shrink-0 transition-transform group-[.active]:rotate-180"></span>
                                        </button>

                                        <div class="accordion-content hidden overflow-hidden transition-[height] md:block"
                                             id="checkout-delivery-collapse"
                                             role="region">
                                            <div class="flex flex-col gap-4 pb-4 md:pb-6">
                                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div class="flex flex-col gap-2">
                                                        <label class="text-sm text-white md:text-lg" for="checkout-city">
                                                            Місто*
                                                        </label>

                                                        <div class="flex items-center gap-2 border-b border-opacity-light-gray-40% py-2 text-sm text-light-gray md:text-lg">
                                                            <span class="icon-[tabler--search] size-4 shrink-0 md:size-5"></span>
                                                            <span>TODO: choices.js city search</span>
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-col gap-2">
                                                        <label class="text-sm text-white md:text-lg" for="checkout-delivery-method">
                                                            Спосіб доставки*
                                                        </label>

                                                        <button class="flex items-center justify-between border-b border-opacity-light-gray-40% py-2 text-left text-sm text-light-gray md:text-lg"
                                                                id="checkout-delivery-method"
                                                                type="button">
                                                            <span>TODO: delivery methods</span>
                                                            <span class="icon-[ep--arrow-down] size-5 shrink-0"></span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div class="flex flex-col gap-2">
                                                        <label class="text-sm text-white md:text-lg" for="checkout-branch">
                                                            Відділення*
                                                        </label>

                                                        <div class="flex items-center gap-2 border-b border-opacity-light-gray-40% py-2 text-sm text-light-gray md:text-lg">
                                                            <span class="icon-[tabler--search] size-4 shrink-0 md:size-5"></span>
                                                            <span>TODO: choices.js branch search</span>
                                                        </div>
                                                    </div>

                                                    <button class="inline-flex items-center justify-between gap-4 border border-white px-4 py-2 text-sm text-white md:justify-center md:text-lg"
                                                            type="button">
                                                        <span>ЗНАЙТИ НА КАРТІ</span>
                                                        <span class="icon-[solar--map-point-linear] size-5 shrink-0"></span>
                                                    </button>
                                                </div>

                                                <div class="flex min-h-48 items-center justify-center border border-dashed border-opacity-light-gray-40% bg-black/30 px-4 text-center text-sm text-light-gray md:min-h-56 md:text-base">
                                                    TODO: Leaflet map with branch markers
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion group" data-checkout-accordion>
                                    <div class="accordion-item border-y border-opacity-light-gray-40%">
                                        <button class="accordion-toggle flex w-full items-center justify-between gap-4 py-3 text-left md:py-4"
                                                type="button"
                                                aria-expanded="false"
                                                aria-controls="checkout-payment-collapse">
                                            <span class="text-sm md:text-lg text-white">
                                                Способи оплати
                                            </span>

                                            <span class="icon-[ep--arrow-down] size-5 shrink-0 transition-transform group-[.active]:rotate-180"></span>
                                        </button>

                                        <div class="accordion-content hidden overflow-hidden transition-[height] md:block"
                                             id="checkout-payment-collapse"
                                             role="region">
                                            <div class="flex flex-col gap-4 pb-4 md:pb-6">
                                                <div class="flex items-center justify-between border-b border-opacity-light-gray-40% py-2 text-sm text-light-gray md:text-lg">
                                                    <span>TODO: payment methods</span>
                                                    <span class="icon-[ep--arrow-down] size-5 shrink-0"></span>
                                                </div>

                                                <div class="rounded-sm border border-opacity-light-gray-40% px-4 py-3 text-sm text-light-gray md:text-base">
                                                    TODO: payment module placeholder
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <label class="inline-flex items-center gap-2 text-sm text-white md:text-lg">
                                    <span class="inline-flex size-4 shrink-0 border border-white"></span>
                                    <span>Не дзвонити для підтвердження замовлення</span>
                                </label>

                                <button class="inline-flex items-center gap-2 text-sm text-white md:text-lg"
                                        type="button">
                                    <span class="icon-[mdi--plus] size-4 shrink-0"></span>
                                    <span>Додати коментар до замовлення</span>
                                </button>

                                <button class="inline-flex items-center gap-2 text-sm text-white md:text-lg"
                                        type="button">
                                    <span class="icon-[mdi--plus] size-4 shrink-0"></span>
                                    <span>Є промокод / сертифікат</span>
                                </button>

                                <p class="max-w-[40rem] text-sm text-light-gray md:text-base">
                                    Оформлюючи замовлення, ви підтверджуєте свою згоду з умовами обслуговування та політикою конфіденційності.
                                </p>

                                <button class="white-btn default-btn w-full md:max-w-sm text-sm md:text-lg"
                                        type="button">
                                    ОФОРМИТИ ЗАМОВЛЕННЯ
                                </button>
                            </div>
                        </form>
                    </div>

                    <aside class="border border-opacity-light-gray-40% p-4 md:p-6 lg:sticky lg:top-[calc(var(--site-header-height)+1rem)] lg:self-start">
                        <div class="flex flex-col gap-4 md:gap-5">
                            <div class="flex items-center justify-between gap-4 border-b border-opacity-light-gray-40% pb-3 text-sm md:text-lg">
                                <h2 class="font-inter text-sm uppercase leading-1.4em tracking-0.04em md:text-lg">
                                    Ваше замовлення
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

                                        <button class="accordion-toggle inline-flex items-center gap-2 text-sm md:text-base"
                                                type="button"
                                                aria-expanded="false"
                                                aria-controls="checkout-hidden-items-collapse">
                                            <span>
                                                Показати всі товари ({{ $items_count }})
                                            </span>

                                            <span class="icon-[ep--arrow-down] size-5 shrink-0 transition-transform group-[.active]:rotate-180"></span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <a class="inline-flex w-full items-center justify-end border-b border-opacity-light-gray-40% pb-2 text-sm uppercase tracking-0.04em underline decoration-1 underline-offset-4 md:text-base"
                               href="{{ $edit_items_url }}">
                                Редагувати товари
                            </a>

                            <div class="flex flex-col gap-3 border-t border-opacity-light-gray-40% pt-4">
                                <div class="flex items-center justify-between gap-4 text-sm md:text-base">
                                    <span>Загальна сума</span>
                                    <span>{{ $checkout_data['subtotal_formatted'] ?? '' }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-4 text-sm md:text-base">
                                    <span>Доставка</span>
                                    <span>{{ $checkout_data['delivery_formatted'] ?? '—' }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-4 text-base font-bold uppercase md:text-lg">
                                    <span>ПІДСУМОК</span>
                                    <span>{{ $checkout_data['grand_total_formatted'] ?? '' }}</span>
                                </div>
                            </div>

                            <p class="text-sm text-light-gray md:text-base">
                                Менеджер зв&apos;яжеться з вами найближчим часом для уточнення деталей замовлення.
                            </p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>

@endsection
