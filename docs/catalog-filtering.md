[← Catalog Storefront](catalog-storefront.md) · [Back to README](../README.md) · [Category Sorting →](category-sorting.md)

# Catalog Product Filtering

This document describes how category product filters are configured in Filament, normalized from the request, applied to the Eloquent query, and exposed to the storefront.

## Source of truth

| Responsibility | Location |
|---|---|
| Filter orchestration and product query | `app/Actions/FilterProductsAction.php` |
| AJAX result count endpoint | `app/Http/Controllers/Ajax/CatalogFilterAjaxController.php` |
| Category page endpoint | `app/Http/Controllers/Pages/CategoryController.php` |
| Request normalization and validation | `app/Http/Requests/Ajax/CatalogFilterAjaxIndexRequest.php` |
| Admin filter-set form | `app/Filament/Resources/Catalogs/CatalogFilter/Schemas/CatalogFilterSetForm.php` |
| Storefront filter UI | `resources/views/catalog/pages/partials/category/filter-content.blade.php` |
| Storefront filter behavior | `resources/assets/catalog/ts/features/products/productsFilter.ts` |
| Filter configuration defaults | `config/catalog-filter.php` |

`FilterProductsAction` is the shared application entry point. The category page and the AJAX controller must use the same action so that the displayed product list and the previewed result count follow the same rules.

The action selects the first enabled filter set for the category context, ordered by filter-set ID. If no such set exists, filtering is disabled and the category query still returns products using the base catalog rules.

## Admin configuration

The resource under `app/Filament/Resources/Catalogs/CatalogFilter/` manages a filter set, its groups, and its values.

### Filter-set settings

- `is_enabled` enables the filter set.
- `context_types` determines where the set can be used. Category filtering requires the `category` context.
- `is_price_filter_enabled` enables price filtering.
- `is_attribute_filtering_enabled` enables attribute filtering.
- `price_source_mode` chooses the effective price source:
  - `base_only` — regular product price;
  - `discount_only` — active discount price;
  - `both` — active discount price with regular price fallback.
- `discount_only_policy` controls products without an active discount in discount-only mode:
  - `exclude_without_discount` — remove them from the result;
  - `fallback_to_base` — use the regular price.
- `min_stock_quantity` sets the minimum default-variant stock required for a product to appear.
- `facet_strategy`, index settings, and rebuild settings control filter metadata and index maintenance.

### Filter groups and values

Groups are ordered by `sort_order` and must be enabled. A group identifies its source through `source_type` and `source_id`:

- `price` represents the price range;
- `attribute` represents a product attribute;
- other source types are not applied by the current product action.

Each group can define:

- a stable `code`;
- a GET key in `get.key`;
- an optional fallback value in `config.get.value`;
- extra GET keys in `config.get.extra`;
- a filter mode (`range`, `boolean`, or `multiple`);
- localized labels;
- enabled state and order.

Attribute values use stable `code` values in URLs. Their labels are localized in the admin configuration and are mapped to the canonical value plus all translations when querying products.

## Request contract

`CatalogFilterAjaxIndexRequest` accepts and validates the following normalized fields:

```text
page                 integer >= 1
sort                 nullable string, max 120 characters
price_from           nullable numeric >= 0
price_to             nullable numeric >= 0
attributes           array
attributes.{id}      array of string codes
```

The request reads values from the query string using each group's configured GET key. It supports both array-style and comma-separated values, for example:

```text
?attributes[color][]=black&attributes[color][]=white
?color=black,white
?price_from=100&price_to=500
```

If `price_from` is greater than `price_to`, validation fails. Empty or unsupported groups are ignored during normalization.

## Query behavior

The base query selects products whose:

- product is active;
- default variant is active;
- default variant quantity is at least `min_stock_quantity`;
- product belongs to the requested category;
- discount conditions match the configured price policy.

Attribute filtering uses the default variant's attribute values:

- selected values within one attribute group use `OR`;
- different attribute groups use `AND`;
- unknown filter codes produce no matching products for that group.

The price range is applied to the effective price expression selected by `price_source_mode` and `discount_only_policy`. Active discounts are resolved for the current application's `user_group_id` and the current date/time.

## Category-page flow

`CategoryController::show()` calls the action with `is_get_filters_data: true`. The action returns:

- paginated products;
- applied filter state;
- active filter and sort values;
- filter metadata and item counts;
- clear-filter state.

The Blade partial renders the filter drawer from `filters_data`. The TypeScript module collects checked item codes and the price range, then calls the AJAX endpoint to show the number of matching products. The user is redirected to the category URL with the selected GET parameters after pressing Apply.

`CatalogFilterAjaxController::index()` calls the same action with `is_get_filters_data: false` and returns only the matching product total. Exceptions are reported and converted to the localized filtering error response.

## Extension rules

When adding a filter:

1. Add its stable configuration contract to `config/catalog-filter.php` or the corresponding database-backed Filament settings.
2. Keep request normalization in `CatalogFilterAjaxIndexRequest`.
3. Keep query changes in `FilterProductsAction` so category rendering and AJAX counts remain consistent.
4. Preserve localized labels and stable URL codes.
5. Update the Blade payload and `productsFilter.ts` only when the browser needs a new interaction.
6. Add tests for normal, empty, invalid, and discount-price cases.

## See Also

- [Category Sorting](category-sorting.md) — category sort configuration and query behavior.
- [Catalog Storefront](catalog-storefront.md) — category routes and storefront rendering.
- [Admin Panel](admin-panel.md) — Filament resource conventions and workflows.
