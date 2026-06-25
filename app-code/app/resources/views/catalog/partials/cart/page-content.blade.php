@php
    $cart_data = is_array($cart_data ?? null) ? $cart_data : [];
@endphp

<div class="cart-page-content" data-cart-page-content>
    @include('catalog.partials.cart.modal-items', [
        'cart_data' => $cart_data,
        'cart_mode' => $cart_data['mode'] ?? 'regular',
        'show_checkout_button' => false,
    ])
</div>
