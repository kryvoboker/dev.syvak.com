# Modules: Registration and Loading Guide

This document explains how module providers are registered in the project (`nwidart/laravel-modules`), which loading strategies exist, and what rules should be followed when adding a new module.

It also covers two module shapes used in the project:

- **instance-based modules** like `Carousel` and `ProductsCarousel`
- **singleton modules** like `NovaPoshta`, where the whole module has one shared configuration and creating instances is forbidden

## Navigation

- [Back to project README](../../../README.md)

## 1. Who controls module loading

Main flow:

1. `App\Providers\ModuleProvidersServiceProvider`
2. `App\Services\Modules\ModuleProviderResolverService`
3. `App\Services\Modules\ModuleProviderRegistrarService`

Core idea: a module `ServiceProvider` must not decide on its own whether it should be loaded. The decision is centralized in `ModuleProviderResolverService`.

## 2. Where the module list comes from

Source of truth:

- DB table `module_definitions` (module activity, `nwidart_name`, path, and metadata)
- DB table `module_instances` (instance settings, `is_enabled`, `placement`, `settings.shared.page_types`)
- Module config: `Modules/<ModuleName>/config/config.php`

`ModuleProviderResolverService` only considers active modules (`enabled()`) and filters them by the current request context.

## 2.1 Singleton modules

A singleton module is a module that:

- has exactly one global configuration set for the whole application;
- does not allow module instances;
- stores its runtime secrets or credentials outside instance settings, usually in global configs;
- usually exposes a dedicated admin page instead of create/edit instance flows.

Typical example:

- `NovaPoshta`

For singleton modules:

1. Set `admin.can_create_instances` to `false` in the module config.
2. Do not create `ModuleInstance` rows for the module.
3. Keep module-wide settings in module services or global configs.
4. Expose admin actions/pages directly from the module Filament namespace.
5. If the module also renders storefront content, follow [the storefront rendering guide](STOREFRONT_MODULE_RENDERING.md).

## 3. Provider loading strategies

Allowed strategies are defined in `config/modules-runtime.php`:

- `eager`
- `route_matched`
- `middleware_after_session`

### `eager`

Loaded immediately in `ModuleProvidersServiceProvider::boot()`.

Use when:
- the module is needed early in the lifecycle;
- the module does not depend on `request()->route()` or session.

### `route_matched`

Loaded on the `Illuminate\Routing\Events\RouteMatched` event.

Use when:
- the module depends on route name/page type;
- the route must already be matched.

### `middleware_after_session`

Loaded by middleware `App\Http\Middleware\Modules\RegisterModuleProvidersAfterSession`.

Use when:
- the module depends on session data or user preferences;
- the module must start after `StartSession`.

## 4. How the resolver selects a specific provider

For each relevant module, the resolver builds the FQCN using this pattern:

`Modules\\<NwidartName>\\Providers\\<NwidartName>ServiceProvider`

Example:

- `nwidart_name = ProductsCarousel`
- provider: `Modules\\ProductsCarousel\\Providers\\ProductsCarouselServiceProvider`

If the class does not exist, the provider is not registered, and a warning is written to the `stack` log channel.

## 5. Rules for module service providers

1. Do not duplicate lazy-loading logic in `boot()`.
2. If a provider is registered, it must correctly finish `registerViews()`, `registerConfig()`, and similar setup steps.
3. Do not hardcode a foreign namespace.
4. `name` and `nameLower` must match the module.

## 6. What belongs inside a module

As a default rule, module-owned code and assets should live inside the module directory:

- controllers
- models
- tests
- routes
- translations
- providers
- middlewares
- views
- JS
- TS
- CSS
- services
- support classes
- module-specific config
- module-specific database migrations, factories, and seeders

The main exception is shared code or shared resources that are intentionally reused by multiple modules or the core app. In that case, place the code outside the module and document the shared contract clearly.

If a file is only used by one module, it should normally stay in that module.

## 7. Rules for adding a new module

Minimum checklist:

1. Create the module in `Modules/<ModuleName>/`.
2. Check `module.json`:
   - `name` = `<ModuleName>`
   - `providers` contains the correct FQCN for that module only
3. Check the module `composer.json`:
   - PSR-4: `"Modules\\<ModuleName>\\": "app/"`
4. Add a loading strategy in `Modules/<ModuleName>/config/config.php`:

```php
'runtime' => [
    'provider_loading_strategy' => 'route_matched',
],
```

5. Synchronize module definitions using the existing sync mechanism.
6. If the module is instance-based, ensure it is active in `module_definitions` and the required `module_instances` are enabled.
7. If the module is singleton-based, ensure `can_create_instances` is `false` and do not create module instances.
8. Clear cache after changing providers or configs:

```bash
php artisan optimize:clear
```

## 8. Common mistakes

1. Incorrect provider namespace in `module.json`.
2. Duplicating lazy conditions in both the module provider and the resolver.
3. Expecting `page_types` when the field is empty/null, without fallback logic.
4. Not clearing cache after changing provider/config data.
5. Treating a singleton module as an instance-based module and wiring create/edit instance screens for it.

## 9. Quick diagnostics

1. Check that the provider class exists at the expected PSR-4 path.
2. Check the strategy in the module `config/config.php`.
3. Check the module record in the database (`module_definitions.is_enabled`, `is_installed`, `is_enabled_in_filesystem`).
4. Check the relevant `module_instances` (`is_enabled`, `placement`, `settings.shared.page_types`).
5. Check the `stack` logs and run `php artisan optimize:clear`.

---

If a module should work like `Carousel` or `ProductsCarousel`, follow the current chain:

- `ModuleProvidersServiceProvider`
- `ModuleProviderResolverService`
- `ModuleProviderRegistrarService`

and do not add a second independent lazy-loading system inside the module.

If a module should work like `NovaPoshta`, keep it singleton-based and route its admin behavior through dedicated Filament pages and global configs instead of module instances.

## Related docs

- [Storefront module rendering guide](STOREFRONT_MODULE_RENDERING.md)
