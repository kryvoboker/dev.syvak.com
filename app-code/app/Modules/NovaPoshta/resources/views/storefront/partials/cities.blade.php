@php
    $cities = is_iterable($cities ?? null) ? $cities : [];
@endphp

<ul class="space-y-2" data-nova-poshta-cities-list>
    @forelse($cities as $city)
        <li class="rounded border border-white/10 px-3 py-2 text-sm" data-nova-poshta-city-item data-city-ref="{{ $city->ref }}">
            <span class="font-medium">{{ $city->city_name ?: $city->description }}</span>
            @if(filled($city->region_description))
                <span class="text-white/60">({{ $city->region_description }})</span>
            @endif
        </li>
    @empty
        <li class="text-sm text-white/60">No cities available.</li>
    @endforelse
</ul>
