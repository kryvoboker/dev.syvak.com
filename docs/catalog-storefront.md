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
GET /{locale}/contacts
GET /{locale}/{contacts_slug}
POST /{locale}/contacts/contact
POST /{locale}/{contacts_slug}/contact
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

## Contacts Page

The public Contacts page is backed by the singleton `PageSetting` with
`page_type=contacts` and is rendered by `ContactsController`.

- `/{locale}/contacts` is the static fallback URL.
- `/{locale}/{contacts_slug}` is the localized SEO URL when a slug is configured.
- If a localized slug is configured, the static URL redirects to it.
- The page resolves localized title, working hours, addresses, phones, emails,
  images, map settings, and contact-form settings from the Contacts page setting.
- The form action follows the URL that rendered the page:
  `POST /{locale}/contacts/contact` for the static URL or
  `POST /{locale}/{contacts_slug}/contact` for a slug URL.
- Form fields are dynamically validated from admin settings. Disabled fields are
  prohibited, required fields are enforced, and file type/size/regex/length
  restrictions are applied server-side.
- Successful submissions redirect back with a success flash message. Validation
  or delivery failures redirect back with validation errors and preserved input;
  the uploaded file is excluded from preserved input.
- Delivery is queued by `DeliverContactsFormJob`. Email and Telegram are
  independent destinations. When enabled, the configured file can be attached
  to email or sent as a separate Telegram document; a downloadable file URL is
  also available to the configured message template through `{file}`.

See [OpenAPI contacts form contract](../app-code/app/openapi/pages/contacts.yaml)
for the endpoint-level request and response documentation.

## See Also

- [Project README](../README.md) — project landing page and navigation hub.
- [Admin Panel](admin-panel.md) — where filters/sort/page settings are managed.
- [Architecture](architecture.md) — separation of storefront vs domain logic.
- [Testing](testing.md) — how to validate storefront changes.
