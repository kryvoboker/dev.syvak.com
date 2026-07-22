@php
    $attributes = $product['attributes'] ?? [];
@endphp

<article class="flex flex-col gap-4 border-b border-light-gray/40 py-5 first:pt-0 md:flex-row md:items-center md:gap-6 md:py-6">
    <div class="flex h-36 w-full shrink-0 items-center justify-center overflow-hidden bg-light-black md:h-44 md:w-32">
        <img
            class="h-full w-full object-cover opacity-80"
            src="{{ $product['image_url'] ?? $no_image_url }}"
            alt="{{ $product['name'] ?? '' }}"
            loading="lazy"
        >
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-2">
        <h3 class="break-words text-xl leading-tight text-white md:text-2xl">
            {{ $product['name'] ?? __('catalog/pages/thank-you.fallbacks.product_name') }}
        </h3>

        <p class="text-sm font-light tracking-0.04em text-light-gray">
            {{ __('catalog/default.texts.sku', ['sku' => $product['sku'] ?? '—']) }}
        </p>

        @if($attributes !== [])
            <dl class="flex flex-wrap gap-2">
                @foreach($attributes as $attribute_name => $attribute_value)
                    <div class="flex max-w-full flex-wrap border border-light-gray/40 px-3 py-1.5 text-sm">
                        <dt class="break-words text-light-gray">{{ $attribute_name }}:&nbsp;</dt>
                        <dd class="break-words text-white">{{ $attribute_value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 text-base text-white">
            <span>{{ __('catalog/pages/thank-you.labels.quantity') }}: {{ $product['quantity'] ?? 0 }}</span>
            <span class="whitespace-nowrap">{{ $product['price_formatted'] ?? '—' }}</span>
        </div>
    </div>
</article>
