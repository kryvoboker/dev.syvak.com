@use(Illuminate\Support\Str;)

@props([
    'class' => '',
    'urls_data' => [
        'original_thumb' => '',
        'thumb_1x' => '',
        'thumb_2x' => '',
        'thumb_3x' => '',
        'thumb_4x' => '',
    ],
    'size' => 0,
    'max_density' => 4,
    'sizes' => '',
    'width' => '',
    'height' => '',
    'decoding' => 'async',
    'loading' => 'lazy',
    'alt' => '',
])

@php
    $normalized_size = max((int) $size, 0);
    $resolved_max_density = $max_density;

    if (
        blank($resolved_max_density)
        && $attributes->has('max_density')
    ) {
        $resolved_max_density = $attributes->get('max_density');
    }

    if (
        blank($resolved_max_density)
        && $attributes->has('max-density')
    ) {
        $resolved_max_density = $attributes->get('max-density');
    }

    $normalized_max_density = max(min((int) $resolved_max_density, 4), 1);

    $srcset = collect([
        ['key' => 'thumb_1x', 'density' => 1],
        ['key' => 'thumb_2x', 'density' => 2],
        ['key' => 'thumb_3x', 'density' => 3],
        ['key' => 'thumb_4x', 'density' => 4],
    ])
        ->filter(fn (array $candidate): bool => $candidate['density'] <= $normalized_max_density)
        ->map(function (array $candidate) use ($urls_data, $normalized_size): ?string {
            $candidate_url = (string) ($urls_data[$candidate['key']] ?? '');

            if (blank($candidate_url) || $normalized_size <= 0) {
                return null;
            }

            $candidate_width = $normalized_size * (int) $candidate['density'];

            return "$candidate_url {$candidate_width}w";
        })
        ->filter()
        ->implode(', ');

    $src = (string) ($urls_data['original_thumb'] ?: $urls_data['thumb_1x'] ?: $no_image_url);
@endphp

<img class="{{ Str::trim($class) }}"
     src="{{ $src }}"
     @if(filled($srcset)) srcset="{{ $srcset }}" @endif
     @if(!empty($sizes)) sizes="{{ Str::trim($sizes) }}" @endif
     @if(!empty($width)) width="{{ Str::trim($width) }}" @endif
     @if(!empty($height)) height="{{ Str::trim($height) }}" @endif
     decoding="{{ Str::trim($decoding) }}"
     loading="{{ Str::trim($loading) }}"
     alt="{{ Str::trim($alt) }}"/>
