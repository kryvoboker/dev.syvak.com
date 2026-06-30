@php
    $post_offices = is_iterable($post_offices ?? null) ? $post_offices : [];
@endphp

<ul class="space-y-2" data-nova-poshta-post-offices-list>
    @forelse($post_offices as $post_office)
        <li class="rounded border border-white/10 px-3 py-2 text-sm" data-nova-poshta-post-office-item data-delivery-point-ref="{{ $post_office->ref }}">
            <span class="font-medium">{{ $post_office->description }}</span>
            @if(filled($post_office->number))
                <span class="text-white/60">#{{ $post_office->number }}</span>
            @endif
        </li>
    @empty
        <li class="text-sm text-white/60">No post offices available.</li>
    @endforelse
</ul>
