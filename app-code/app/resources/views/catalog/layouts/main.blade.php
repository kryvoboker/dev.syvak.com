@php
    $page_type = $page_type ?? try_detect_page_type();
    $top_entrypoint_for_module = 'top';
    $bottom_entrypoint_for_module = 'bottom';
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" data-theme="black">
<head>
    <meta name="robots" content="none">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $page_title ?? config('app.name') }}</title>

    <!-- Preconnect to speed up font handshake -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Load Google Fonts asynchronously: preload as style then switch to stylesheet onload -->
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" onload="this.rel='stylesheet'">

    @push('styles')
        @vite(['./resources/assets/catalog/css/app.css'])
    @endpush

    @stack('styles')
</head>

<body>
<script>
    window.app_params = {
        ...(window.app_params ?? {}),
        ...@js([
            'page_type' => $page_type,
            'cart_mode' => 'regular',
            'cart_modal_ajax_url' => localized_route('localized.catalog.cart-modal-ajax.index'),
            'cart_store_url' => localized_route('localized.catalog.cart.store'),
            'cart_update_url_pattern' => localized_route('localized.catalog.cart.update', ['cart_id' => '__cart_id__']),
            'cart_delete_url_pattern' => localized_route('localized.catalog.cart.delete', ['cart_id' => '__cart_id__']),
            'order_validate_url' => localized_route('localized.catalog.order-confirm.validate'),
            'order_store_url' => localized_route('localized.catalog.order-confirm.store')
        ])
    };
</script>

<div class="wrapper">
    @include('catalog.layouts.partials.header')

    <main class="main">
        @include('catalog.components.modules.placement', [
            'placement' => $top_entrypoint_for_module,
            'page_type' => $page_type,
            'entrypoint_name' => '$top_entrypoint_for_module',
        ])

        @yield('content')

        @include('catalog.components.modules.placement', [
            'placement' => $bottom_entrypoint_for_module,
            'page_type' => $page_type,
            'entrypoint_name' => '$bottom_entrypoint_for_module',
        ])
    </main>

    @include('catalog.layouts.partials.footer')
</div>

@vite(['./resources/assets/catalog/ts/index.ts'])
{{--@stack('scripts')--}}
</body>
</html>
