@php
    $regions = is_iterable($regions ?? null) ? $regions : [];
@endphp

<ul class="space-y-2" data-nova-poshta-regions-list>
    @forelse($regions as $region)
        <li class="rounded border border-white/10 px-3 py-2 text-sm" data-nova-poshta-region-item data-region-ref="{{ $region->ref }}">
            <span class="font-medium">{{ $region->description }}</span>
            @if(filled($region->regions_center))
                <span class="text-white/60">({{ $region->regions_center }})</span>
            @endif
        </li>
    @empty
        <li class="text-sm text-white/60">No regions available.</li>
    @endforelse
</ul>
