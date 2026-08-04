[← Catalog Filtering](catalog-filtering.md) · [Back to README](../README.md) · [Category Sorting →](category-sorting.md)

# Catalog Filter Administration

This document describes how the catalog filter configuration is created, edited, synchronized, and indexed from the Filament admin panel.

## Architecture

The application uses one canonical filter set for the category catalog:

```text
CatalogFilterSet
    ↓
CatalogFilterGroup records
    ↓
CatalogFilterValue records
    ↓
CatalogFilterProductIndex rows
```

The admin page is a settings page for the `default_category` set. It is not a CRUD interface for creating multiple independent filter sets.

| Layer | Model/table | Purpose |
|---|---|---|
| Filter set | `CatalogFilterSet` / `catalog_filter_sets` | Global behavior and context configuration |
| Filter group | `CatalogFilterGroup` / `catalog_filter_groups` | Price or product-attribute filter definition |
| Filter value | `CatalogFilterValue` / `catalog_filter_values` | Selectable value belonging to an attribute group |
| Product index | `CatalogFilterProductIndex` / `catalog_filter_product_index` | Precomputed product-to-filter lookup rows |
| Index metadata | `CatalogFilterIndexMeta` / `catalog_filter_index_meta` | Version, status, lock, and rebuild statistics |

## Source of truth

| Responsibility | Location |
|---|---|
| Filament resource | `app/Filament/Resources/Catalogs/CatalogFilter/CatalogFilterSetResource.php` |
| Admin edit page | `app/Filament/Resources/Catalogs/CatalogFilter/Pages/EditCatalogFilterSet.php` |
| Admin form schema | `app/Filament/Resources/Catalogs/CatalogFilter/Schemas/CatalogFilterSetForm.php` |
| Canonical set bootstrap | `app/Services/Catalogs/CatalogFilter/CatalogFilterBootstrapService.php` |
| Group synchronization | `app/Services/Catalogs/CatalogFilter/FilterGroupGeneratorService.php` |
| Value synchronization | `app/Services/Catalogs/CatalogFilter/FilterValueGeneratorService.php` |
| Product index rebuild | `app/Services/Catalogs/CatalogFilter/CatalogFilterIndexRebuildService.php` |
| Configuration defaults | `config/catalog-filter.php` |

## Canonical filter set creation

`CatalogFilterBootstrapService::bootstrapDefaultCategorySet()` is the entry point used by both the admin page and storefront filter flow.

When called, it:

1. Reads defaults from `config/catalog-filter.php`.
2. Normalizes the configured contexts and uses `category` as the fallback context.
3. Creates the `default_category` record if it does not exist.
4. Creates the related index metadata row if it does not exist.
5. Loads the set with its groups, values, translations, and index metadata.

The resource disables create and delete operations. The edit page also resolves the canonical set during `mount()`, so a record ID from the URL does not select another filter set.

## Editing the filter set

The `Core` tab stores the following settings on `CatalogFilterSet`:

- enabled state;
- supported contexts;
- price-filter switch;
- attribute-filter switch;
- price source mode;
- discount-only fallback policy;
- facet strategy;
- minimum stock quantity;
- index rebuild settings in the `settings` JSON column.

Before saving, the page normalizes `context_types`, removes duplicate values, and mirrors the first context into the legacy-compatible `context_type` column.

The `Filter options` tab displays generated groups in a read-only-structure repeater. The repeater cannot add, delete, or reorder items. Administrators can update:

- localized group labels;
- enabled state;
- sort order;
- GET key and auxiliary GET data;
- filter mode;
- price range boundaries and step for the price group.

The technical `stock` group is excluded from this repeater.

### Group update flow

`EditCatalogFilterSet::mutateFormDataBeforeSave()` extracts `filter_items` and passes them to `syncFilterItems()` before the normal Filament record save.

For each submitted group, the page:

1. Finds an existing group by `catalog_filter_set_id + code`.
2. Creates the group if it is missing.
3. Preserves unrelated existing JSON configuration keys.
4. Updates source information, enabled state, sort order, GET settings, and filter mode.
5. Creates or updates a translation row for every active language.

Translations use the language code in the form and the corresponding `language_id` in the database.

Groups missing from the submitted repeater are not deleted or automatically disabled. In normal operation the repeater contains all groups except the technical stock group, but stale groups can remain after the source attribute disappears.

## Generating filter groups

The `Sync groups` action calls `FilterGroupGeneratorService::sync()`.

It creates or updates:

1. The system price group.
2. One group for every active attribute used by an active product.

Groups are matched by their stable code. New groups receive generated defaults for `get_key`, `sort_order`, and configuration. Existing enabled state, order, and custom configuration are preserved where possible. Localized group labels are synchronized for all active languages.

This action does not remove groups that are no longer produced by the current attribute query.

## Generating filter values

The `Sync values` action calls `FilterValueGeneratorService::sync()`.

For every enabled attribute group it:

1. Reads values from active product variants.
2. Normalizes whitespace and case for grouping.
3. Resolves one canonical value and localized labels.
4. Creates or updates `CatalogFilterValue` records.
5. Creates or updates translations for active languages.
6. Deletes values whose codes are no longer present for that group.

Values are generated from product data and are not manually added in the Filament form. Values belonging to disabled or obsolete groups can remain in the database until the group is synchronized or removed by a separate maintenance operation.

## Rebuilding the product index

The `Refresh index status` action calls `CatalogFilterIndexRebuildService::rebuild()`. Despite its label, it performs a full rebuild.

The rebuild service:

- acquires a configurable rebuild lock;
- creates a new index version;
- indexes enabled price and attribute groups;
- uses only enabled filter values;
- indexes active products and their default active variants;
- calculates base, discount, and effective prices;
- applies the minimum stock rule;
- removes rows from old index versions;
- switches the metadata to the new active version;
- records status, timestamps, progress, and row totals.

The `Sync all` action runs the complete sequence:

```text
sync groups → sync values → rebuild product index
```

## Save and synchronization behavior

Saving the form updates the filter set and group configuration, but it does not automatically regenerate values or rebuild the product index. After changing a group or a setting that affects indexed results, use `Sync all` to make the storefront index consistent with the admin configuration.

The current save flow does not wrap the filter-set save, group updates, and translation updates in one database transaction. If a later operation fails, partially persisted configuration is possible and should be checked through the index metadata and application logs.

## Operational checklist

When product attributes or filter settings change:

1. Open the catalog filter admin page.
2. Update the core or filter-group settings.
3. Save the form.
4. Run `Sync all`.
5. Confirm the index status is `ok` and the active index version changed.
6. Verify a category page and its AJAX filter count.

## See Also

- [Catalog Filtering](catalog-filtering.md) — storefront request and query flow.
- [Category Sorting](category-sorting.md) — category sorting configuration and URL behavior.
- [Admin Panel](admin-panel.md) — general Filament resource conventions.
