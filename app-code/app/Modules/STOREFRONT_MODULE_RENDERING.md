# Storefront Module Rendering by Page and Placement

This document explains how modules are automatically connected to storefront pages based on:
- `page_type` (for example: `home`, `category`, `product`)
- `placement` (`top`, `bottom`)

## Why this exists

Previously, storefront pages had hardcoded module includes (for example, Carousel and ProductsCarousel in Home page templates/controllers). Any placement or page-type change required manual code updates.

Now, module output is resolved dynamically at runtime.

## Core flow

1. The layout defines explicit module anchors:
   - `$top_entrypoint_for_module`
   - `$bottom_entrypoint_for_module`
2. Each anchor calls a shared Blade entrypoint:
   - `resources/views/catalog/components/modules/placement.blade.php`
3. The entrypoint asks:
   - `App\Services\Modules\StorefrontModulePlacementResolverService`
4. The resolver uses:
   - `resolve_modules_for_context($placement)`
   - module runtime config metadata (`runtime.storefront.*`)
5. For each resolved module, the resolver:
   - resolves data service class,
   - calls `resolveForPlacement($placement, $page_type)`,
   - resolves module storefront view,
   - builds include-ready payload.
6. Blade entrypoint renders each module via dynamic `@include`.

## Required module metadata

Each module should provide these keys in `Modules/<Module>/config/config.php`:

```php
'runtime' => [
    'provider_loading_strategy' => 'route_matched',
    'storefront' => [
        'data_service'  => 'Services\\<ModuleName>ModuleDataService',
        'view'          => '<modulealias>::storefront.<view-name>',
        'view_data_key' => '<module_payload_key>',
    ],
],
```

Example:
- Carousel:
  - `data_service`: `Services\\CarouselModuleDataService`
  - `view`: `carousel::storefront.main-carousel`
  - `view_data_key`: `carousel_module_data`
- ProductsCarousel:
  - `data_service`: `Services\\ProductsCarouselModuleDataService`
  - `view`: `productscarousel::storefront.products-carousel`
  - `view_data_key`: `products_carousel_module_data`

## Provider loading lifecycle

Provider loading strategy is still managed by:
- `ModuleProvidersServiceProvider`
- `ModuleProviderResolverService`
- `ModuleProviderRegistrarService`

Allowed strategies:
- `eager`
- `route_matched`
- `middleware_after_session`

This lifecycle controls provider registration. Storefront rendering is an additional runtime layer on top of already loaded modules.

## How `page_type` and `placement` filtering works

- `placement` is passed by the Blade anchor (`top|bottom`).
- `page_type` is detected by `try_detect_page_type()` and passed to the resolver.
- `resolve_modules_for_context($placement)` returns active module definitions with enabled instances for that placement.
- Module instance data services apply module-specific filtering logic (including `settings.shared.page_types`).

## Onboarding a new module into dynamic storefront rendering

1. Implement module data resolver method:
   - `resolveForPlacement(string $placement, ?string $page_type = null): array`
2. Add `runtime.storefront` metadata in module config.
3. Ensure module view exists and accepts payload key from `view_data_key`.
4. Ensure module instances have proper `placement` and `settings.shared.page_types`.
5. Clear cache if needed:
   - `php artisan optimize:clear`

## Fail-soft behavior

If a module cannot be rendered dynamically, it is skipped and a warning is written to `stack` channel. Examples:
- missing data service class,
- missing `resolveForPlacement` method,
- invalid/missing view path.

This protects storefront rendering from full-page failures caused by one broken module.
