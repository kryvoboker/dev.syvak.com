[← Category Sorting](category-sorting.md) · [Back to README](../README.md) · [Helpers Reference →](helpers-reference.md)

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

## Contacts Page Settings

The Contacts page is configured in
`Filament/Resources/PageSettings/Contacts/` as a singleton edit page. Its form
contains localized general content, images, SEO slugs, contact-form rules,
email and Telegram destinations/templates, map settings, phones, emails, and
localized addresses.

- Active application languages are rendered as localized tabs.
- Phone, email, image, and address collections support ordered repeaters.
- Each form field can be enabled, marked required, constrained by length or a
  regular-expression mask, and validated on the backend.
- File uploads define allowed extensions, maximum size, and an upload path.
- Email and Telegram delivery can be enabled independently. Each destination
  can be configured to include an uploaded file; Telegram requires its page
  bot token and chat ID, while email requires a recipient address.
- Settings changes are normalized by `PageSettingsBootstrapService`; localized
  slugs are persisted in the shared `slugs` table.

## See Also

- [Project README](../README.md) — project landing page and navigation hub.
- [Catalog Storefront](catalog-storefront.md) — where admin-managed data is rendered.
- [Architecture](architecture.md) — module boundaries for admin code.
- [Configuration](configuration.md) — settings and fallback behavior.
- [Modules Guide](../app-code/app/Modules/README.md) — module registration/loading rules and singleton module guidance.
- [UkrPoshta Module](../app-code/app/Modules/UkrPoshta/README.md) — module-specific admin sync page and API key handling.
