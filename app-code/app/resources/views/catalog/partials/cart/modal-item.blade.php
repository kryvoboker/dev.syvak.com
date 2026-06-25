@php
    $image_data = $cart_item['image_data'] ?? null;
    $item_attributes = is_array($cart_item['attributes'] ?? null) ? $cart_item['attributes'] : [];
    $display_price_formatted = $display_price_formatted ?? ($cart_item['unit_price_formatted'] ?? $cart_item['line_total_formatted'] ?? '');
    $minimum_quantity = max(1, (int) ($cart_item['minimum_quantity'] ?? 1));
    $available_quantity = max(0, (int) ($cart_item['available_quantity'] ?? 0));
@endphp

<div class="flex items-start gap-2 md:gap-4 border-b border-b-opacity-light-gray-40% py-3 md:py-4 cart-item-row"
     data-cart-item-row
     data-cart-id="{{ $cart_item['cart_id'] }}"
     data-variant-id="{{ $cart_item['variant_id'] }}">
    <label class="mt-2 md:mt-0 inline-flex shrink-0 cursor-pointer">
        <input class="checkbox checkbox-xs border border-light-gray rounded-none cursor-pointer"
               data-cart-item-select
               data-cart-id="{{ $cart_item['cart_id'] }}"
               type="checkbox"/>
    </label>

    <a class="w-26.5 md:w-35 shrink-0 overflow-hidden"
       href="{{ $cart_item['url'] }}">
        <x-catalog::common.img
            class="object-cover size-full aspect-square"
            :urls_data="$image_data['urls'] ?? []"
            :size="$image_data['width'] ?? 220"
            sizes="(max-width: 767px) 106px, 140px"
            width="{{ $image_data['width'] ?? 220 }}"
            height="{{ $image_data['height'] ?? 220 }}"
            alt="{{ $cart_item['name'] }}"
        />
    </a>

    <div class="grow min-w-0 flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4">
        <div class="min-w-0 flex flex-col gap-2 md:gap-2.5 md:max-w-[14.7rem] lg:max-w-none">
            <a class="font-cormorant-garamond text-lg md:text-24px 2xl:text-4xl leading-none uppercase block"
               href="{{ $cart_item['url'] }}">
                {{ $cart_item['name'] }}
            </a>

            <p class="text-light-gray text-11px md:text-sm lg:text-base 2xl:text-lg tracking-0.04em">
                {{ __('catalog/default.texts.sku', ['sku' => $cart_item['sku'] ?? '']) }}
            </p>

            @if($item_attributes !== [])
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($item_attributes as $attribute)
                        @php
                            $attribute_text = is_array($attribute)
                                ? (string) ($attribute['value'] ?? $attribute['label'] ?? '')
                                : (string) $attribute;
                        @endphp

                        @continue(trim($attribute_text) === '')

                        <span class="inline-flex items-center justify-center min-h-8 px-3 py-1 border border-opacity-light-gray-40% text-sm md:text-sm lg:text-base 2xl:text-lg">
                            {{ $attribute_text }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between md:justify-end gap-3 md:gap-4 lg:gap-6 2xl:gap-8 w-full md:w-auto">
            <div class="inline-flex items-center gap-2 md:gap-4" data-cart-qty-stepper>
                <button class="inline-flex size-6 md:size-9 items-center justify-center border border-white/80 text-white/60"
                        type="button"
                        aria-label="{{ __('catalog/default.cart.labels.quantity') }} -"
                        onclick="const stepper=this.closest('[data-cart-qty-stepper]'); const input=stepper?.querySelector('[data-cart-item-quantity]'); if(!input){return;} const min=Number(input.min||1); const next=Math.max(min, Number(input.value||min)-1); input.value=String(next); input.dispatchEvent(new Event('change',{bubbles:true}));">
                    <span class="icon-[mdi--minus] size-4"></span>
                </button>

                <input class="w-8 md:w-10 border-0 bg-transparent p-0 text-center text-sm md:text-base 2xl:text-lg"
                       id="cart-item-qty-{{ $cart_item['cart_id'] }}"
                       data-cart-item-quantity
                       data-cart-id="{{ $cart_item['cart_id'] }}"
                       type="number"
                       min="{{ $minimum_quantity }}"
                       max="{{ max($minimum_quantity, $available_quantity) }}"
                       value="{{ $cart_item['quantity'] }}"/>

                <button class="inline-flex size-6 md:size-9 items-center justify-center border border-white/80"
                        type="button"
                        aria-label="{{ __('catalog/default.cart.labels.quantity') }} +"
                        onclick="const stepper=this.closest('[data-cart-qty-stepper]'); const input=stepper?.querySelector('[data-cart-item-quantity]'); if(!input){return;} const max=Number(input.max||9999); const min=Number(input.min||1); const next=Math.min(max, Math.max(min, Number(input.value||min)+1)); input.value=String(next); input.dispatchEvent(new Event('change',{bubbles:true}));">
                    <span class="icon-[mdi--plus] size-4"></span>
                </button>
            </div>

            <p class="text-sm md:text-base lg:text-2xl whitespace-nowrap">
                {{ $display_price_formatted }}
            </p>

            <button class="btn btn-text p-0"
                    data-remove-cart-item
                    data-cart-id="{{ $cart_item['cart_id'] }}"
                    type="button"
                    aria-label="{{ __('catalog/default.cart.messages.item_removed') }}">
                <span class="icon-[iconamoon--trash-light] custom-icon size-6 md:size-6"></span>
            </button>
        </div>
    </div>
</div>
