@extends('catalog.layouts.main')

@section('content')
    <script>
        window.app_params = {
            ...(window.app_params ?? {}),
            ...@js([
                'cart_mode' => 'regular',
                'cart_modal_ajax_url' => localized_route('localized.catalog.cart-modal-ajax.index'),
                'cart_store_url' => localized_route('localized.catalog.cart.store'),
                'cart_update_url_pattern' => localized_route('localized.catalog.cart.update', ['cart_id' => '__cart_id__']),
                'cart_delete_url_pattern' => localized_route('localized.catalog.cart.delete', ['cart_id' => '__cart_id__']),
                'order_validate_url' => localized_route('localized.catalog.order-confirm.validate'),
                'order_store_url' => localized_route('localized.catalog.order-confirm.store'),
            ])
        };
    </script>

    <x-catalog::common.breadcrumbs
        :breadcrumbs="$breadcrumbs"
    />

    <section class="cart section" id="cart">
        <div class="container flex flex-col gap-4" id="cart-page-root">
            @include('catalog.partials.cart.page-content', [
                'cart_data' => $cart_data ?? [],
                'show_checkout_button' => $show_checkout_button,
            ])
        </div>

        <x-catalog::common.loader class="cart-loader z-20"/>
    </section>
@endsection
