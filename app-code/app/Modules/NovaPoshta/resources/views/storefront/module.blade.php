@php
    $module_data = is_array($nova_poshta_module_data ?? null) ? $nova_poshta_module_data : [];
    $module_state = is_array($module_data['state'] ?? null) ? $module_data['state'] : [];
@endphp

<section class="nova-poshta-module" data-nova-poshta-module data-placement="{{ $module_data['placement'] ?? '' }}">
    <div class="space-y-4 rounded-lg border border-white/10 bg-black/20 p-4 text-white">
        <div class="space-y-1">
            <h2 class="text-base font-semibold">
                Nova Poshta
            </h2>

            <p class="text-sm text-white/70">
                {{ filled($module_state['delivery_method'] ?? null) ? 'State restored from session.' : 'No saved checkout selection yet.' }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <div class="text-sm font-medium">Regions</div>
                {!! $module_data['regions_html'] ?? '' !!}
            </div>

            <div class="space-y-2">
                <div class="text-sm font-medium">Cities</div>
                {!! $module_data['cities_html'] ?? '' !!}
            </div>

            <div class="space-y-2">
                <div class="text-sm font-medium">Post offices</div>
                {!! $module_data['post_offices_html'] ?? '' !!}
            </div>

            <div class="space-y-2">
                <div class="text-sm font-medium">Poshtomats</div>
                {!! $module_data['poshtomats_html'] ?? '' !!}
            </div>
        </div>
    </div>
</section>
