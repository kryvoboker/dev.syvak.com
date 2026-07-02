@if ($cities->isNotEmpty())
    <div class="flex flex-col gap-2">
        @foreach ($cities as $city)
            <button class="rounded-md border border-white/10 px-3 py-2 text-left text-sm hover:bg-white/10"
                    type="button"
                    data-ukr-poshta-select
                    data-ukr-poshta-field="city"
                    data-ukr-poshta-value="{{ $city->city_id }}"
                    aria-pressed="{{ (int) $selected_city_id === (int) $city->city_id ? 'true' : 'false' }}">
                {{ $city->description }}
            </button>
        @endforeach
    </div>
@else
    <div class="rounded-md border border-dashed border-white/10 px-3 py-2 text-sm text-white/70">
        No cities available.
    </div>
@endif
