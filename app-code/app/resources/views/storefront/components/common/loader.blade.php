@props(['variant' => 'page'])

@php
    $default_classes = $variant === 'container'
        ? 'loader absolute inset-0 hidden items-center justify-center size-full bg-black/90 z-10'
        : 'loader fixed top-0 left-0 hidden items-center justify-center size-full bg-black/90 z-10';
@endphp

<div {{ $attributes->merge(['class' => $default_classes]) }}>
    <span class="loading loading-spinner loading-xl"></span>
</div>
