@if ($post_offices->isNotEmpty())
    <div class="flex flex-col gap-2">
        @foreach ($post_offices as $post_office)
            <button class="rounded-md border border-white/10 px-3 py-2 text-left text-sm hover:bg-white/10"
                    type="button"
                    data-ukr-poshta-select
                    data-ukr-poshta-field="delivery_point"
                    data-ukr-poshta-value="{{ $post_office->postcode }}"
                    aria-pressed="{{ (int) $selected_delivery_point_postcode === (int) $post_office->postcode ? 'true' : 'false' }}">
                {{ $post_office->description }}
            </button>
        @endforeach
    </div>
@else
    <div class="rounded-md border border-dashed border-white/10 px-3 py-2 text-sm text-white/70">
        No post offices available.
    </div>
@endif
