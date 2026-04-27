@php
    $cart_data = is_array($cart_data ?? null) ? $cart_data : [];
    $items = $cart_data['items'] ?? [];
    $totals = $cart_data['totals'] ?? [];
@endphp

<div class="cart-page-content" data-cart-page-content>
    @if(($cart_data['is_empty'] ?? true) === true)
        <p class="text-light-gray">{{ __('catalog/default.cart.labels.empty') }}</p>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-6">
            <div class="flex flex-col gap-3">
                @foreach($items as $cart_item)
                    @include('catalog.partials.cart.modal-item', ['cart_item' => $cart_item])
                @endforeach
            </div>

            <div class="flex flex-col gap-3 min-w-72">
                @foreach(($totals['lines'] ?? []) as $line_data)
                    @continue(($line_data['is_visible'] ?? false) === false)

                    <div class="flex items-center justify-between gap-2">
                        <span class="text-light-gray">{{ $line_data['label'] }}</span>
                        <span>{{ $line_data['formatted'] }}</span>
                    </div>
                @endforeach

                <div class="flex items-center justify-between gap-2 font-bold text-lg border-t border-t-opacity-light-gray-40% pt-3">
                    <span>{{ __('catalog/default.cart.totals.grand_total') }}</span>
                    <span>{{ $totals['grand_total_formatted'] ?? '' }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
