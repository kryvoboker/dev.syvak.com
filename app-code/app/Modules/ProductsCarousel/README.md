# ProductsCarousel module

The `ProductsCarousel` module renders a localized product carousel on the storefront. It resolves enabled module instances for the current placement and page type, selects products according to each instance's settings, and passes a storefront-ready product-card payload to the module view.

## Module type

`ProductsCarousel` is an **instance-based module, not a singleton**.

- Administrators can create multiple module instances.
- Each instance can have its own name, placement, page visibility, product source, filters, sorting, and presentation settings.
- Multiple instances may be rendered on the same page when their placement and page-type settings match.
- The module does not use one global configuration record as its storefront content source.

The module config does not set `admin.can_create_instances` to `false`. The default module-definition behavior therefore allows the standard Filament create/edit instance flow.

## Storefront integration

| Concern | Implementation |
|---|---|
| Storefront service | `Services\\Storefront\\ProductsCarouselStorefrontService` |
| Storefront view | `productscarousel::storefront.products-carousel` |
| View data key | `products_carousel_module_data` |
| Public entry point | `resolveForPlacement(string $placement, ?string $page_type = null)` |
| Settings form | `Filament\\ModuleInstanceFormSchema` |
| Settings normalization | `Services\\Filament\\ModuleSettingsNormalizerService` |

The service receives the current placement and optional page type. It resolves the enabled module definitions, filters their instances, and omits instances whose final product list is empty.

## Common instance settings

These settings are available in the shared part of the Filament form:

| Setting | Meaning | Default |
|---|---|---:|
| `name` | Internal administrator-facing instance name. | — |
| `settings.shared.page_types` | Page types where the instance may render. At least one is required in the form. | `home` |
| `placement` | Registered storefront placement where the instance is rendered. | — |
| `sort_order` | Order of instances within a placement. | `1` |
| `is_enabled` | Enables or disables the instance. | `true` |
| `settings.shared.translations` | Localized storefront title and short description. | empty |
| `settings.shared.min_quantity` | Minimum product stock quantity. Products below it are excluded. | `1` |
| `settings.shared.products_limit` | Maximum number of products returned after filtering and sorting. | `15` |
| `settings.shared.product_image_width` | Requested product image conversion width. | `420` |
| `settings.shared.product_image_height` | Requested product image conversion height. | `420` |
| `settings.shared.sort_mode` | `custom` or `random`. | `custom` |

The available page types come from `config/page-settings.php`, not from a hardcoded ProductsCarousel list. The current application defines `home`, `product`, `category`, `search`, `cart`, `checkout`, `order`, `thankyou`, and `failure`.

The title and short description are entered per active language. Storefront resolution first tries the current locale, then the default active language, then the first translation containing content, and finally legacy shared scalar values when present.

## Product source modes

The `settings.source_mode` field controls how an instance builds its product set.

### Products from categories: `category_based`

This is the default source mode.

1. Select one or more active categories in the category tree.
2. Products must be active and belong to at least one selected category.
3. Products must have `quantity >= settings.shared.min_quantity`.
4. Optionally enable **Show only specific products**.
5. When that option is enabled, select active product variants from the selected categories. Every selected variant is a separate carousel card, so several variants of one product can be displayed.
6. When it is disabled, all matching products are sorted according to the sorting settings and use their default variant in the card.

The category and product selectors remove inactive records while settings are normalized. Clearing all categories also clears the category-scoped selected products.

### Only selected products: `manual_only`

This mode searches the complete active catalog and lets the administrator select product variants explicitly.

- Only active variants of active products are searchable and selectable.
- Search results identify the product, variant number, default status, regular price, and active discount price when available.
- The minimum quantity filter still applies at storefront resolution time.
- The final list preserves the selected variant ID order.
- The result is limited by `settings.shared.products_limit`.

Search supports parts of a product name, SKU, model, or EAN. Append `== price` to match an exact regular or currently active discount price, for example `Garden Guardian == 1499.00`. The category-based search is restricted to the selected categories; manual mode searches all active variants.

The normalized settings use `selected_variant_ids`. Existing instances that still contain `selected_product_ids` are converted to their active default variants during normalization; an explicitly empty `selected_variant_ids` remains empty.

## Sorting

### Custom sorting

The form exposes one direction for each field. `Do not use` excludes the field from the sorting sequence. The fields are:

- price: low to high or high to low;
- name: A to Z or Z to A;
- date added: oldest first or newest first;
- stock quantity: low to high or high to low.

The selected fields are applied in the order stored in the normalized custom-sort map. Opposite directions for the same field are removed. If no valid option remains, the runtime fallback is stock descending followed by date added descending.

Sorting is currently performed on the base `products` table. The carousel card itself displays the default variant's price and localized name when that variant exists, so an administrator should account for this distinction when configuring price or name sorting.

### Random sorting

Random mode generates a runtime sort sequence from the four supported fields and directions. The sequence may contain one or more fields and can change when the module data is resolved again.

## Default variant data

The module eager-loads the active default variant and its current-locale description and slug. Product cards use the selected active variant when the instance is configured with explicit variants; otherwise they use the default variant for:

- localized product name;
- price;
- image when the variant has one;
- variant URL when both product and variant slugs are available.

If variant presentation data is unavailable, the resolver falls back to the base product description, price, image, or product URL. Product activation, stock filtering, category membership, and the current sort query remain based on the base product record.

## Rendering lifecycle

```text
placement + page type
        ↓
enabled ProductsCarousel module definitions
        ↓
matching module instances
        ↓
source mode and stock/category/product filters
        ↓
custom or random sorting
        ↓
products_limit
        ↓
default-variant product cards
        ↓
productscarousel::storefront.products-carousel
```

An instance is eligible when its placement is resolved for the current request and its configured page types contain the current page type. If no page type is supplied, the service does not apply page-type filtering. Legacy instances with no page types are also treated as eligible by the storefront service, while the current form requires at least one page type for new or normalized settings.

## Main files

| Path | Responsibility |
|---|---|
| `config/config.php` | Runtime integration and allowed/default settings. |
| `app/Filament/ModuleInstanceFormSchema.php` | Admin instance form and dynamic selectors. |
| `app/Services/Filament/ModuleSettingsNormalizerService.php` | Validation, cleanup, defaults, and legacy settings normalization. |
| `app/Services/Filament/ProductsCarouselCategoryTreeService.php` | Active category tree for the admin form. |
| `app/Services/Filament/ProductsCarouselProductSearchService.php` | Active-product search and selector labels. |
| `app/Services/ProductsCarouselProductFilterService.php` | Shared active product/category filtering. |
| `app/Services/Storefront/ProductsCarouselStorefrontService.php` | Storefront instance resolution and product-card mapping. |
| `resources/views/storefront/products-carousel.blade.php` | Carousel markup and product-card rendering. |
| `tests/Feature/ProductsCarouselModuleServicesTest.php` | Service and settings behavior coverage. |

## See also

- [Module registration and loading guide](../README.md) — shared module architecture and instance rules.
- [Storefront module rendering guide](../STOREFRONT_MODULE_RENDERING.md) — placement, page-type filtering, and runtime rendering.
- [ProductsCarousel configuration](config/config.php) — defaults and allowed values used by the module.
