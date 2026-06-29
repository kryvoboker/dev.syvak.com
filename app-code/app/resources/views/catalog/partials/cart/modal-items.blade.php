@php
    use App\Enums\CartModeEnum;

    $cart_data = is_array($cart_data ?? null) ? $cart_data : [];
    $first_item = $cart_data['first_item'] ?? null;
    $hidden_items = $cart_data['hidden_items'] ?? [];
    $totals = $cart_data['totals'] ?? [];
    $total_lines = $totals['lines'] ?? [];
    $cart_mode = $cart_mode ?? ($cart_data['mode'] ?? CartModeEnum::Regular->value);
    $show_checkout_button = $show_checkout_button ?? true;
@endphp

<div class="flex flex-col h-full" data-cart-root data-cart-mode="{{ $cart_mode }}">
    <div class="flex-1 px-4 md:px-8 lg:px-6 2xl:px-6 py-4 md:py-6 overflow-y-auto">
        <div class="hidden mb-4 rounded border border-light-red/40 bg-light-red/10 px-3 py-2 text-sm text-light-red"
             aria-live="polite"
             data-cart-general-error
             role="alert"></div>

        @if(($cart_data['is_empty'] ?? true) === true)
            <p class="text-light-gray text-sm md:text-base">
                {{ __('catalog/pages/category/show.texts.empty_cart') }}
            </p>
        @else
            <div class="mb-4 md:mb-6 flex items-center justify-between gap-3 text-sm md:text-base lg:text-base 2xl:text-lg">
                <label class="inline-flex items-center gap-2 text-light-gray cursor-pointer" data-cart-select-all-label>
                    <input class="checkbox checkbox-xs border border-light-gray rounded-none cursor-pointer"
                           data-cart-select-all
                           type="checkbox"/>
                    <span data-cart-selected-summary
                          data-template="{{ __('catalog/default.cart.labels.selected_items_in_modal') }}">
                            {{ __('catalog/default.cart.labels.selected_items', ['selected' => 0, 'total' => $cart_data['items_count'] ?? 0]) }}
                        </span>
                </label>

                <button class="btn btn-text p-0 hidden"
                        data-remove-selected-cart-items
                        type="button"
                        aria-label="{{ __('catalog/default.cart.messages.item_removed') }}">
                    <span class="icon-[iconamoon--trash-light] custom-icon size-6 md:size-6"></span>
                </button>
            </div>

            <div class="flex flex-col gap-y-6.5" data-cart-items-list>
                @if(is_array($first_item))
                    @include('catalog.partials.cart.modal-item', [
                        'cart_item' => $first_item,
                        'display_price_formatted' => $first_item['line_total_formatted'] ?? $first_item['unit_price_formatted'] ?? '',
                    ])
                @endif

                @if($hidden_items !== [])
                    <div class="accordion group" data-cart-extra-items-accordion>
                        <div class="accordion-item">
                            <div class="accordion-content hidden overflow-hidden transition-[height]"
                                 id="cart-extra-items-collapse"
                                 role="region">
                                <div class="flex flex-col gap-0 pb-3">
                                    @foreach($hidden_items as $cart_item)
                                        @include('catalog.partials.cart.modal-item', [
                                            'cart_item' => $cart_item,
                                            'display_price_formatted' => $cart_item['line_total_formatted'] ?? $cart_item['unit_price_formatted'] ?? '',
                                        ])
                                    @endforeach
                                </div>
                            </div>

                            <button class="accordion-toggle inline-flex items-center justify-start gap-2 w-full p-0"
                                    aria-expanded="false"
                                    aria-controls="cart-extra-items-collapse">
                                <span class="text-sm md:text-base group-[.active]:hidden">
                                    {{ __('catalog/default.cart.buttons.show_more_items', ['count' => count($hidden_items)]) }}
                                </span>

                                <span class="hidden text-sm md:text-base group-[.active]:inline">
                                    {{ __('catalog/default.cart.buttons.hide_more_items') }} ({{ count($hidden_items) }})
                                </span>

                                <span class="icon-[ep--arrow-down] custom-icon transition-transform size-5 md:size-6
                                             group-[.active]:rotate-180"></span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="px-4 pb-4 md:pb-6 md:px-6">
        @if(($cart_data['is_empty'] ?? true) === false)
            <div class="mt-3 flex flex-col gap-2" data-cart-totals>
                @foreach($total_lines as $line_data)
                    @continue(($line_data['is_visible'] ?? false) === false)

                    <div class="flex items-center justify-between gap-2 text-sm md:text-base">
                        <span class="text-light-gray">{{ $line_data['label'] }}</span>
                        <span>{{ $line_data['formatted'] }}</span>
                    </div>
                @endforeach

                <div class="flex items-center justify-between gap-2 font-bold text-lg md:text-lg 2xl:text-2xl uppercase">
                    <span>{{ __('catalog/default.cart.totals.grand_total') }}</span>
                    <span>{{ $totals['grand_total_formatted'] ?? '' }}</span>
                </div>
            </div>
        @endif

        @if(($cart_data['is_empty'] ?? true) === false && $cart_mode === CartModeEnum::Regular->value && $show_checkout_button === true)
            <a class="white-btn default-btn w-full md:max-w-85.75 lg:max-w-91.75 2xl:max-w-md text-lg mt-3 mx-auto"
               href="{{ localized_route('localized.catalog.checkout.index') }}">
                {{ __('catalog/default.cart.buttons.checkout') }}
            </a>
        @endif
    </div>
</div>
