@props([
    'telegram_data' => [
        'url' => '',
        'text' => '',
    ],
])

<a {{ $attributes->merge(['class' => 'footer-subscribe-btn dark-btn inline-flex items-center justify-between gap-3 border border-opacity-light-gray-40% uppercase bg-white px-4 py-2 text-black md:px-4 md:py-3']) }}
   href="{{ $telegram_data['url'] }}"
   aria-label="{{ $telegram_data['text'] }}">
    <span>{{ $telegram_data['text'] }}</span>

    <span class="footer-subscribe-btn-arrow inline-flex size-12 items-center justify-center border border-black text-32px leading-none">
        <span class="icon-[quill--arrow-up] rotate-45"></span>
    </span>
</a>
