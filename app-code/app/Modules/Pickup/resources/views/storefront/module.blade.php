@php
    $pickup_checkout_data = is_array($pickup_checkout_data ?? null) ? $pickup_checkout_data : [];
    $pickup_store_address = (string) ($pickup_checkout_data['store_address'] ?? '');
    $pickup_map_iframe = (string) ($pickup_checkout_data['map_iframe'] ?? '');
@endphp

<label class="flex items-center gap-3 border-b border-opacity-light-gray-40% py-2 text-sm text-white md:text-lg"
       data-checkout-delivery-method-option="pickup_store">
    <input class="radio radio-sm border border-white rounded-none"
           data-checkout-delivery-method-input
           name="delivery_method"
           type="radio"
           value="pickup_store"
        @checked(($selected_delivery_method ?? '') === 'pickup_store')>

    <span>{{ __('pickup::storefront/checkout.delivery_method') }}</span>
</label>

<div class="hidden flex-col gap-4 pt-3" data-pickup-store-content>
    <div class="flex flex-col gap-2">
        <span class="text-sm text-white md:text-lg">{{ __('pickup::storefront/checkout.store_address') }}</span>
        <p class="alert rounded alert-info text-sm md:text-base" data-pickup-store-address>{{ $pickup_store_address }}</p>
    </div>

    @if($pickup_map_iframe !== '')
        <div class="accordion group" data-pickup-store-map-accordion>
            <div class="accordion-item border-b border-opacity-light-gray-40%">
                <button class="accordion-toggle flex w-full items-center justify-between gap-4 text-left ps-0 py-2"
                        type="button"
                        aria-expanded="false"
                        aria-controls="pickup-store-map-collapse">
                    <span class="text-sm text-white md:text-lg">{{ __('pickup::storefront/checkout.map_title') }}</span>
                    <span class="icon-[ep--arrow-down] size-5 shrink-0 transition-transform group-[.active]:rotate-180"></span>
                </button>

                <div class="accordion-content hidden overflow-hidden transition-[height]"
                     id="pickup-store-map-collapse"
                     role="region">
                    <div class="aspect-video w-full overflow-hidden pb-4 pt-2 [&>iframe]:size-full [&>iframe]:min-h-64 [&>iframe]:border-0">
                        {!! $pickup_map_iframe !!}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
