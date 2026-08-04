[← Filter Administration](catalog-filter-admin.md) · [Back to README](../README.md) · [Admin Panel →](admin-panel.md)

# Category Product Sorting

This document describes how category sorting options are configured in the admin panel, localized, converted into storefront links, validated, and applied to the product query.

## Source of truth

| Responsibility | Location |
|---|---|
| Category page and sort links | `app/Http/Controllers/Pages/CategoryController.php` |
| Sorting query application | `app/Actions/FilterProductsAction.php` |
| Admin settings form | `app/Filament/Resources/PageSettings/Category/Schemas/CategoryPageSettingsForm.php` |
| Settings persistence and normalization | `app/Filament/Resources/PageSettings/Category/Pages/EditCategoryPageSettings.php` |
| Runtime settings bootstrap | `app/Services/PageSettings/PageSettingsBootstrapService.php` |
| Shared sorting helpers | `app/Supports/helpers.php` |
| Sort configuration values | `config/page-settings.php` |
| Storefront sort menu | `resources/views/catalog/pages/partials/category/sort.blade.php` |

## Admin settings

The category page settings resource contains a `Sorting` tab with:

- `is_sorting_enabled` — global switch for category sorting;
- `sorting_items` — the ordered list of available options;
- localized labels for each option;
- `code` — internal canonical sort code;
- `get.key` — query-string key;
- `get.value` — value placed into the URL;
- `sort_order` — display and processing order;
- `is_enabled` — option-level switch.

The form restricts fixed fields to the values allowed by `config/page-settings.php`. The persistence page keeps the default sorting contract synchronized while retaining administrator changes such as order, enabled state, GET payload, and labels.

The `is_sorting_enabled` value is persisted in the category settings contract and is enforced by `get_sorting_items()`. When it is disabled, no sorting options are built and the category view does not render the sort control. When it is enabled, only sorting items with `is_enabled` set to `true` are shown. The administrator controls the order and localized labels of the options; the actual ordering rules remain in `FilterProductsAction::applySorting()`.

## Supported sort codes

The current query implementation recognizes these codes:

| Code | Behavior |
|---|---|
| `default` | Newest products: `date_added DESC`, then `id DESC` |
| `newest` | Explicit alias for the same newest-first behavior |
| `bestsellers` | `products.viewed DESC`, then `id DESC` |
| `price-asc` | Effective price ascending, then `id DESC` |
| `price-desc` | Effective price descending, then `id DESC` |

The configured GET value is mapped to the internal code. Unknown or empty values resolve to `default`.

## Runtime flow

1. `CategoryController::show()` normalizes the requested `sort` value.
2. `resolve_sort_code()` maps the requested GET value to a configured item code.
3. `buildSortOptions()` creates the menu payload:
   - code;
   - GET value;
   - localized label;
   - URL preserving the current query parameters.
4. `FilterProductsAction::handle()` resolves the same sort value and applies it to the product query.
5. The category view renders the resulting options through `sort.blade.php`.

Sorting is therefore applied server-side. The sort menu only creates links; it does not reorder products in the browser. `default` and `newest` intentionally produce the same newest-first order: the application always needs a deterministic ordering, and the default policy is newest products first.

## Localized labels

Labels are resolved in this order:

1. label from the database settings for the current language ID;
2. first non-empty configured label;
3. translation key `catalog/default.sort.{code}`;
4. a generated headline based on the sort code.

This fallback chain keeps the menu usable when an administrator has not filled every language tab.

## URL behavior

The sort link builder:

- starts with the current request URL and query string;
- removes all configured sorting keys;
- writes the selected key/value pair;
- preserves active filter parameters and unrelated query parameters;
- supports both flat keys (`sort`) and nested keys (`filter[sort]`).

Example:

```text
/en/category/t-shirts?attributes[color][]=black&sort=price-desc
```

When another sorting option is selected, the previous sort value is replaced while the attribute filters remain intact.

Changing sorting resets pagination to the first page by removing the old `page` parameter before adding the selected sort value. This prevents a late page from producing an empty result after the new ordering changes the available page count. Applying a new filter state follows the same first-page rule in `productsFilter.ts`.

## Price sorting

`price-asc` and `price-desc` use the same effective price expression as price filtering and the same default-variant price source. Depending on the active catalog filter set, this can mean:

- regular price only;
- active discount price only;
- active discount price with regular-price fallback.

The discount lookup uses the current user group and the active date interval, so the sort order can differ for different customer groups.

## Extension rules

When adding a sorting option:

1. Add the canonical code and GET value to `config/page-settings.php` if it is a supported platform option.
2. Add the query behavior to `FilterProductsAction::applySorting()`.
3. Add or update localized labels and admin options.
4. Keep GET-key replacement logic intact so sorting does not remove active filters.
5. Add tests for the new mapping, unknown values, URL generation, and query order.

## See Also

- [Catalog Filtering](catalog-filtering.md) — filter configuration and effective-price rules.
- [Catalog Storefront](catalog-storefront.md) — category routes and rendering.
- [Admin Panel](admin-panel.md) — Filament page-setting workflows.
