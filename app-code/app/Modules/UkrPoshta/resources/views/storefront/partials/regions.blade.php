@if ($regions->isNotEmpty())
    <div class="flex flex-col gap-2">
        @foreach ($regions as $region)
            <button class="rounded-md border border-white/10 px-3 py-2 text-left text-sm hover:bg-white/10"
                    type="button"
                    data-ukr-poshta-select
                    data-ukr-poshta-field="region"
                    data-ukr-poshta-value="{{ $region->region_id }}"
                    aria-pressed="{{ (int) $selected_region_id === (int) $region->region_id ? 'true' : 'false' }}">
                {{ $region->region_ua }}
            </button>
        @endforeach
    </div>
@else
    <div class="rounded-md border border-dashed border-white/10 px-3 py-2 text-sm text-white/70">
        No regions available.
    </div>
@endif
