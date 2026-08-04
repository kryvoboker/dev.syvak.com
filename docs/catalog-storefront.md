[← Configuration](configuration.md) · [Back to README](../README.md) · [Catalog Filtering →](catalog-filtering.md)

# Catalog Storefront

## Main Routes

```php
GET /{locale}/
GET /{locale}/category/{slug}
GET /{locale}/category/{slug}/filters
GET /{locale}/category/{slug}/load-more
GET /{locale}/product/{slug}
GET /{locale}/product/{slug}/{variant_slug}
GET /{locale}/product/static/{product_id}/{variant_id}
GET /{locale}/search
GET /{locale}/live-search
```

## Category Flow

- `CategoryController` prepares category page data.
- Filter/sort options are normalized from GET parameters.
- Product list may be extended via AJAX load-more endpoint.

## Product Flow

- Product is resolved by product slug + language.
- Variant can be resolved by:
  - `variant_slug`, or
  - GET attribute params (for variant selection behavior).
- The language switcher uses the localized parent product slug for a default variant when the variant has no own slug.
- If a localized SEO URL cannot identify the requested variant, it uses the validated static product-variant route.
- Breadcrumbs can include category chain before product title.

## Filtering and Sorting

- Catalog filters use normalized GET contract.
- Sorting options are configured from page settings and validated server-side.
- Filter index table is used to optimize product filtering.
- Storefront TypeScript that powers catalog interactions is validated with `npm run ts:check` and fixed with `npm run ts:fix`.

## See Also

- [Project README](../README.md) — project landing page and navigation hub.
- [Admin Panel](admin-panel.md) — where filters/sort/page settings are managed.
- [Architecture](architecture.md) — separation of storefront vs domain logic.
- [Testing](testing.md) — how to validate storefront changes.
