@php
    $poshtomats = is_iterable($poshtomats ?? null) ? $poshtomats : [];
@endphp

<ul class="space-y-2" data-nova-poshta-poshtomats-list>
    @forelse($poshtomats as $poshtomat)
        <li class="rounded border border-white/10 px-3 py-2 text-sm" data-nova-poshta-poshtomat-item data-delivery-point-ref="{{ $poshtomat->ref }}">
            <span class="font-medium">{{ $poshtomat->description }}</span>
            @if(filled($poshtomat->number))
                <span class="text-white/60">#{{ $poshtomat->number }}</span>
            @endif
        </li>
    @empty
        <li class="text-sm text-white/60">No poshtomats available.</li>
    @endforelse
</ul>
