@php
    $cart_data = is_array($cart_data ?? null) ? $cart_data : [];
    $first_item = $cart_data['first_item'] ?? null;
    $hidden_items = $cart_data['hidden_items'] ?? [];
    $totals = $cart_data['totals'] ?? [];
    $total_lines = $totals['lines'] ?? [];
    $cart_mode = $cart_mode ?? ($cart_data['mode'] ?? 'regular');
@endphp

<div class="flex flex-col h-full" data-cart-root data-cart-mode="{{ $cart_mode }}">
    <div class="flex-1 px-4 md:px-8 py-4 overflow-y-auto">
        @if(($cart_data['is_empty'] ?? true) === true)
            <p class="text-light-gray text-sm md:text-base">
                {{ __('catalog/default.cart.labels.empty') }}
            </p>
        @else
            <div class="mb-4 flex items-center justify-between gap-3 text-sm md:text-base">
                <div class="inline-flex items-center gap-2 text-light-gray">
                    <span class="inline-block size-4 border border-light-gray/80"></span>
                    <span>{{ __('catalog/default.cart.labels.quantity') }}: {{ $cart_data['items_count'] ?? 0 }}</span>
                </div>
            </div>

            <div class="flex flex-col gap-3" data-cart-items-list>
                @if(is_array($first_item))
                    @include('catalog.partials.cart.modal-item', ['cart_item' => $first_item])
                @endif

                @if($hidden_items !== [])
                    <div class="accordion" data-cart-extra-items-accordion>
                        <div class="accordion-item border-b border-b-opacity-light-gray-40%">
                            <button class="accordion-toggle py-2 inline-flex items-center justify-between gap-2 w-full"
                                    aria-expanded="false"
                                    aria-controls="cart-extra-items-collapse">
                            <span class="text-sm md:text-base">
                                {{ __('catalog/default.cart.buttons.show_more_items', ['count' => count($hidden_items)]) }}
                            </span>

                                <span class="icon-[solar--alt-arrow-right-linear] accordion-item-active:-rotate-90 custom-icon transition-transform duration-300 size-5 md:size-6"></span>
                            </button>

                            <div class="accordion-content hidden overflow-hidden transition-[height] duration-300"
                                 id="cart-extra-items-collapse"
                                 role="region">
                                <div class="flex flex-col gap-3 pb-3">
                                    @foreach($hidden_items as $cart_item)
                                        @include('catalog.partials.cart.modal-item', ['cart_item' => $cart_item])
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="border-t border-t-opacity-light-gray-40% px-4 md:px-8 pt-3 pb-4 md:pb-6">
        @if($cart_mode === 'fast_order')
            <form class="flex flex-col gap-2 md:gap-3" data-fast-order-form>
                <label class="flex flex-col gap-1 text-sm text-white">
                    <span>{{ __('catalog/default.cart.labels.first_name') }}*</span>
                    <input class="border-0 border-b border-b-opacity-light-gray-40% px-0 py-2 bg-transparent text-sm md:text-base"
                           data-order-first-name
                           name="first_name"
                           placeholder="{{ __('catalog/default.cart.labels.first_name') }}"
                           type="text"/>
                </label>

                <label class="flex flex-col gap-1 text-sm text-white">
                    <span>{{ __('catalog/default.cart.labels.last_name') }}*</span>
                    <input class="border-0 border-b border-b-opacity-light-gray-40% px-0 py-2 bg-transparent text-sm md:text-base"
                           data-order-last-name
                           name="last_name"
                           placeholder="{{ __('catalog/default.cart.labels.last_name') }}"
                           type="text"/>
                </label>

                <label class="flex flex-col gap-1 text-sm text-white">
                    <span>{{ __('catalog/default.cart.labels.phone') }}*</span>
                    <input class="border-0 border-b border-b-opacity-light-gray-40% px-0 py-2 bg-transparent text-sm md:text-base"
                           data-order-phone
                           name="phone"
                           placeholder="{{ __('catalog/default.cart.labels.phone') }}"
                           type="tel"/>
                </label>
                @if(($cart_data['is_empty'] ?? true) === false)
                    <div class="mt-3 flex flex-col gap-2" data-cart-totals>
                        @foreach($total_lines as $line_data)
                            @continue(($line_data['is_visible'] ?? false) === false)

                            <div class="flex items-center justify-between gap-2 text-sm md:text-base">
                                <span class="text-light-gray">{{ $line_data['label'] }}</span>
                                <span>{{ $line_data['formatted'] }}</span>
                            </div>
                        @endforeach

                        <div class="flex items-center justify-between gap-2 font-bold text-lg md:text-xl uppercase">
                            <span>{{ __('catalog/default.cart.totals.grand_total') }}</span>
                            <span>{{ $totals['grand_total_formatted'] ?? '' }}</span>
                        </div>
                    </div>
                @endif

                <button class="white-btn default-btn w-full mt-3 text-lg"
                        data-submit-fast-order
                        type="submit">
                    {{ __('catalog/default.cart.buttons.submit_fast_order') }}
                </button>
            </form>
        @else
            @if(($cart_data['is_empty'] ?? true) === false)
                <div class="mt-3 flex flex-col gap-2" data-cart-totals>
                    @foreach($total_lines as $line_data)
                        @continue(($line_data['is_visible'] ?? false) === false)

                        <div class="flex items-center justify-between gap-2 text-sm md:text-base">
                            <span class="text-light-gray">{{ $line_data['label'] }}</span>
                            <span>{{ $line_data['formatted'] }}</span>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between gap-2 font-bold text-lg md:text-xl uppercase">
                        <span>{{ __('catalog/default.cart.totals.grand_total') }}</span>
                        <span>{{ $totals['grand_total_formatted'] ?? '' }}</span>
                    </div>
                </div>
            @endif

            <a class="white-btn default-btn w-full mt-3 text-lg"
               href="{{ localized_route('localized.catalog.cart.index') }}">
                {{ __('catalog/default.cart.buttons.checkout') }}
            </a>
        @endif
    </div>
</div>
