@php
    use App\Services\Modules\StorefrontModulePlacementResolverService;

    /**
     * This entrypoint is intentionally explicit: each page/layout decides where
     * modules are mounted by placement anchors (top|bottom), while actual
     * module selection is resolved dynamically from DB settings and page type.
     */
    $resolved_storefront_modules = app(StorefrontModulePlacementResolverService::class)
        ->resolveForPlacement((string) ($placement ?? ''), $page_type ?? null);
@endphp

@if($resolved_storefront_modules !== [])
    <div class="module-entrypoint module-entrypoint-{{ $placement }}" data-module-entrypoint="{{ $entrypoint_name ?? $placement }}">
        @foreach($resolved_storefront_modules as $resolved_storefront_module)
            @include($resolved_storefront_module['view'], $resolved_storefront_module['view_data'])
        @endforeach
    </div>
@endif
