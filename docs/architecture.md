[← Getting Started](getting-started.md) · [Back to README](../README.md) · [Configuration →](configuration.md)

# Architecture

## Pattern

The project follows a **Modular Monolith** architecture.

## Main Modules

- `app/Models/Catalogs/*` — catalog domain (products, categories, filters, attributes)
- `app/Models/ApplicationSettings/*` — app-level settings/localization entities
- `app/Models/Users/*` — users and permissions
- `app/Models/Infos/*` — info/content entities
- `app/Modules/NovaPoshta/` — Nova Poshta singleton module for checkout data, sync, and admin control

## Application Layers

- `app/Http/*` — HTTP entrypoints and request contracts
- `app/Actions/*` — use-case orchestration
- `app/Services/*` and `app/Supports/*` — domain and shared services
- `app/Filament/*` — admin delivery layer
- `resources/views/*` — presentation only

## Dependency Rules

- Controllers/Filament pages call Actions/Services.
- Actions/Services work with Models and framework adapters.
- Business logic stays outside Blade templates.
- Cross-domain access should go through explicit service/action boundaries.
- Storefront TypeScript should stay compatible with `Biome` formatting/linting and `tsc --noEmit` type checking.

## Key Entry Points

- `README.md` — project landing page
- `routes/web.php` — storefront + AJAX routes
- `bootstrap/app.php` — app bootstrap, middleware, exception flow
- `.ai-factory/ARCHITECTURE.md` — AI workflow architecture rules

## See Also

- [Catalog Storefront](catalog-storefront.md) — runtime storefront flows.
- [Admin Panel](admin-panel.md) — Filament resource structure.
- [Configuration](configuration.md) — runtime/env contracts.
- [Testing](testing.md) — PHP and TypeScript quality workflows.
- [Modules Guide](../app-code/app/Modules/README.md) — module registration, loading, and singleton module rules.
- [NovaPoshta Module](../app-code/app/Modules/NovaPoshta/README.md) — module-specific runtime, sync, and maintenance notes.
- [UkrPoshta Module](../app-code/app/Modules/UkrPoshta/README.md) — module-specific runtime, sync, and maintenance notes.
