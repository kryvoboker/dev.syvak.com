@php
    $image_data = $cart_item['image_data'] ?? null;
    $item_attributes = is_array($cart_item['attributes'] ?? null) ? $cart_item['attributes'] : [];
@endphp

<div class="flex items-start gap-2 md:gap-4 border-b border-b-opacity-light-gray-40% pb-3 cart-item-row"
     data-cart-item-row
     data-variant-id="{{ $cart_item['variant_id'] }}">
    <span class="mt-1 inline-block size-4 border border-light-gray/80 shrink-0"></span>

    <a class="w-[6.625rem] md:w-[8.75rem] shrink-0 overflow-hidden"
       href="{{ $cart_item['url'] }}">
        <x-catalog::common.img
            class="object-cover size-full"
            :urls_data="$image_data['urls'] ?? []"
            :size="$image_data['width'] ?? 220"
            sizes="(max-width: 767px) 106px, 140px"
            width="{{ $image_data['width'] ?? 220 }}"
            height="{{ $image_data['height'] ?? 220 }}"
            alt="{{ $cart_item['name'] }}"
        />
    </a>

    <div class="grow min-w-0 flex flex-col gap-2">
        <a class="font-cormorant-garamond text-lg md:text-24px leading-[1] uppercase"
           href="{{ $cart_item['url'] }}">
            {{ $cart_item['name'] }}
        </a>

        <p class="text-light-gray text-11px md:text-sm tracking-0.04em">
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

                    <span class="inline-flex items-center justify-center min-h-8 px-2 border border-opacity-light-gray-40% text-sm">
                        {{ $attribute_text }}
                    </span>
                @endforeach
            </div>
        @endif

        <p class="text-sm md:text-base">
            {{ $cart_item['unit_price_formatted'] ?? $cart_item['line_total_formatted'] ?? '' }}
        </p>

        <div class="flex items-center justify-between gap-2">
            <div class="inline-flex items-center gap-2" data-cart-qty-stepper>
                <button class="inline-flex size-6 items-center justify-center border border-white/80 text-white/60"
                        type="button"
                        aria-label="{{ __('catalog/default.cart.labels.quantity') }} -"
                        onclick="const stepper=this.closest('[data-cart-qty-stepper]'); const input=stepper?.querySelector('[data-cart-item-quantity]'); if(!input){return;} const min=Number(input.min||1); const next=Math.max(min, Number(input.value||min)-1); input.value=String(next); input.dispatchEvent(new Event('change',{bubbles:true}));">
                    <span class="icon-[mdi--minus] size-4"></span>
                </button>

                <input class="w-10 border-0 bg-transparent p-0 text-center text-sm md:text-base"
                       id="cart-item-qty-{{ $cart_item['variant_id'] }}"
                       data-cart-item-quantity
                       data-variant-id="{{ $cart_item['variant_id'] }}"
                       type="number"
                       min="{{ $cart_item['minimum_quantity'] }}"
                       max="{{ max($cart_item['minimum_quantity'], $cart_item['available_quantity']) }}"
                       value="{{ $cart_item['quantity'] }}"/>

                <button class="inline-flex size-6 items-center justify-center border border-white/80"
                        type="button"
                        aria-label="{{ __('catalog/default.cart.labels.quantity') }} +"
                        onclick="const stepper=this.closest('[data-cart-qty-stepper]'); const input=stepper?.querySelector('[data-cart-item-quantity]'); if(!input){return;} const max=Number(input.max||9999); const min=Number(input.min||1); const next=Math.min(max, Math.max(min, Number(input.value||min)+1)); input.value=String(next); input.dispatchEvent(new Event('change',{bubbles:true}));">
                    <span class="icon-[mdi--plus] size-4"></span>
                </button>
            </div>

            <button class="btn btn-text p-0"
                    data-remove-cart-item
                    data-variant-id="{{ $cart_item['variant_id'] }}"
                    type="button"
                    aria-label="{{ __('catalog/default.cart.messages.item_removed') }}">
                <span class="icon-[iconamoon--trash-light] custom-icon size-6 md:size-6"></span>
            </button>
        </div>

        @if((int) ($cart_item['quantity'] ?? 1) > 1)
            <p class="text-light-gray text-sm">
                {{ __('catalog/default.cart.labels.total') }}: {{ $cart_item['line_total_formatted'] ?? '' }}
            </p>
        @endif
    </div>
</div>
