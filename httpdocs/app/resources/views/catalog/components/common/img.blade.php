@props([
    'urls_data' => [
        'original_thumb' => '',
        'thumb_1x' => '',
        'thumb_2x' => '',
        'thumb_3x' => '',
        'thumb_4x' => '',
],
    'size' => 0,
    'sizes' => '',
    'width' => '',
    'height' => '',
    'decoding' => 'async',
    'loading' => 'lazy',
    'alt' => '',
])

<img src="{{ $urls_data['original_thumb'] ?: $no_image_url }}"
     srcset="
        @if($urls_data['thumb_1x']) {{ $urls_data['thumb_1x'] }} {{ $size }}w, @endif
        @if($urls_data['thumb_2x']) {{ $urls_data['thumb_2x'] }} {{ $size * 2 }}w, @endif
        @if($urls_data['thumb_3x']) {{ $urls_data['thumb_3x'] }} {{ $size * 3 }}w, @endif
        @if($urls_data['thumb_4x']) {{ $urls_data['thumb_4x'] }} {{ $size * 4 }}w @endif
     "
     @if(!empty($sizes)) sizes="{{ trim($sizes) }}" @endif
     @if(!empty($width)) width="{{ trim($width) }}" @endif
     @if(!empty($height)) height="{{ trim($height) }}" @endif
     decoding="{{ trim($decoding) }}"
     loading="{{ trim($loading) }}"
     alt="{{ trim($alt) }}"/>
