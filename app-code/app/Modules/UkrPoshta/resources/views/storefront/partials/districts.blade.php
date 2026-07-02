@if ($districts->isNotEmpty())
    <div class="flex flex-col gap-2">
        @foreach ($districts as $district)
            <button class="rounded-md border border-white/10 px-3 py-2 text-left text-sm hover:bg-white/10"
                    type="button"
                    data-ukr-poshta-select
                    data-ukr-poshta-field="district"
                    data-ukr-poshta-value="{{ $district->district_id }}"
                    aria-pressed="{{ (int) $selected_district_id === (int) $district->district_id ? 'true' : 'false' }}">
                {{ $district->district_ua }}
            </button>
        @endforeach
    </div>
@else
    <div class="rounded-md border border-dashed border-white/10 px-3 py-2 text-sm text-white/70">
        No districts available.
    </div>
@endif
