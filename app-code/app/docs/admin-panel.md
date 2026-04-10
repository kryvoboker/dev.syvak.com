[← Catalog Storefront](catalog-storefront.md) · [Back to README](../README.md) · [Testing →](testing.md)

# Admin Panel

## Stack

- Filament `v5`
- Livewire `v4`
- Spatie Permission + Filament Shield

## Main Admin Areas

- `Filament/Resources/Catalogs/*` — products, categories, attributes, catalog filters
- `Filament/Resources/PageSettings/*` — category/product/search page settings
- `Filament/Resources/ApplicationSettings/*` — global app settings, languages, etc.

## Resource Guidelines

- Keep complex mutation/aggregation logic in Actions/Services.
- Keep form/table schemas focused on UI and validation.
- Reuse existing helpers/services where possible.

## Important Workflows

- Product and variant management (including localized content)
- Catalog filter groups/values and product index refresh
- Page settings for storefront behavior
- Localization and language-dependent content

## See Also

- [Catalog Storefront](catalog-storefront.md) — where admin-managed data is rendered.
- [Architecture](architecture.md) — module boundaries for admin code.
- [Configuration](configuration.md) — settings and fallback behavior.
