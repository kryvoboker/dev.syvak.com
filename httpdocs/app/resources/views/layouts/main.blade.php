<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="robots" content="none">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $page_title ?? config('app.name') }}</title>

    <!-- Preconnect to speed up font handshake -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Load Google Fonts asynchronously: preload as style then switch to stylesheet onload -->
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" onload="this.rel='stylesheet'">

    @vite(['resources/assets/catalog/css/app.css'])
</head>

<body>
<div class="wrapper">
    @include('layouts.partials.header')

    <main class="main">
        @yield('content')
    </main>

    @include('layouts.partials.footer')
</div>

@vite(['resources/assets/catalog/ts/index.ts'])
</body>
</html>
