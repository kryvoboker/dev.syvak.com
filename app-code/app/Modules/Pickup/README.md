# Pickup Module

The Pickup module provides the **Pickup from store** delivery method for checkout. It is intended for a customer who collects an order from the merchant's physical store.

The module is a **singleton module**. It has one global configuration for the whole application, does not create module instances, and is available only on the checkout page.

## Documentation links

- [Modules loading guide](../README.md)
- [Pickup OpenAPI contract](docs/openapi.yaml)
- [Checkout OpenAPI contract](../../openapi/pages/checkout.yaml)
- [Root OpenAPI entrypoint](../../openapi/openapi.yaml)
- [Project architecture](../../../../docs/architecture.md)
- [Admin panel guide](../../../../docs/admin-panel.md)

## Responsibilities

The module:

- adds `pickup_store` to the checkout delivery-method list when configured and enabled;
- stores one localized store address for every active storefront language;
- optionally stores a safe Google Maps embed iframe;
- passes the localized address and optional map iframe to checkout;
- fills `delivery_address` with the selected-language store address;
- keeps `city` and `delivery_point` empty for pickup;
- renders the map accordion only when a safe iframe exists;
- hides the city and delivery-point controls while pickup is selected.

The module does not calculate delivery prices, query regions or cities, search branches, or store pickup locations in database tables.

## Singleton and runtime configuration

The module definition is in `module.json`, and runtime behavior is in `config/config.php`.

Important invariants:

- `admin.can_create_instances` must remain `false`;
- the module uses the `route_matched` provider-loading strategy;
- its storefront page type is `checkout`;
- module-wide settings are stored in global configs, not `module_instances`;
- the module must remain hidden until every active language has a non-empty address;
- the map iframe is optional and does not affect module availability.

Global config keys:

| Key | Value | Required |
|---|---|---|
| `pickup_store.addresses` | Associative array keyed by normalized locale, for example `{'uk': '...', 'en': '...'}` | Yes, one non-empty address per active language |
| `pickup_store.map_iframe` | Sanitized Google Maps `<iframe>` HTML | No |

The global configuration is accessed through `Modules\\Pickup\\Support\\PickupConfig` and written by `Modules\\Pickup\\Services\\Filament\\PickupSettingsService`.

## Admin settings

The admin page is:

- `Modules/Pickup/app/Filament/Pages/PickupSettingsPage.php`
- route slug: `modules/pickup-store`

The page contains:

1. language tabs for all active languages;
2. one required textarea per active language for the store address;
3. one optional textarea named `map_iframe` for Google Maps embed HTML;
4. a save action that writes the singleton configuration.

Address validation is performed explicitly in `saveSettings()`, so every active language remains required even though the map field is optional.

## Safe iframe contract

The admin accepts HTML input but only persists a safe iframe produced by `PickupIframeSanitizer`.

The sanitizer requires:

- exactly one top-level `<iframe>`;
- an HTTPS `src`;
- an allowed host: `google.com`, `www.google.com`, or `maps.google.com`;
- a path beginning with `/maps/embed`.

Unsafe attributes are discarded. The persisted iframe is normalized with safe defaults, including lazy loading, a referrer policy, and fullscreen support. Scripts, event-handler attributes, additional HTML, and non-Google sources are rejected.

An empty string is valid and means that the map is not configured. Empty map input is saved as an empty global config value.

## Availability and checkout data

`PickupCheckoutDataService::getCheckoutData(string $locale)` returns this shape:

```php
[
    'is_available' => bool,
    'delivery_method' => 'pickup_store',
    'store_address' => string,
    'map_iframe' => string,
]
```

`is_available` is `true` only when:

- the `Pickup` singleton module is enabled;
- at least one active language exists;
- every active language has a non-empty localized address.

The iframe is not part of the availability condition. When it is absent, `map_iframe` is an empty string and the address remains available to checkout.

`PickupStorefrontService::resolveForPlacement()` returns data only for the checkout page and is the configured storefront entrypoint.

## Checkout behavior

The application checkout controller loads the module data and passes it to the module view as `pickup_checkout_data`.

When pickup is selected:

- the delivery method is `pickup_store`;
- `delivery_address` becomes the localized store address;
- `city` is cleared;
- `delivery_point` is cleared;
- city and branch search controls are hidden and are not required;
- the map accordion is shown only when `map_iframe` is non-empty.

When the customer switches from pickup to another delivery method, the frontend clears `delivery_address`, `city`, and `delivery_point` before continuing with the other method's flow.

The module view is `resources/views/storefront/module.blade.php`.

The map contract must remain stable:

- wrapper: `[data-pickup-store-map-accordion]`;
- collapse element: `#pickup-store-map-collapse`;
- iframe is rendered only after server-side sanitization;
- the iframe container uses an aspect-ratio layout and responsive full-size iframe classes.

The checkout TypeScript initializes the root `.accordion` exactly once. Do not initialize the Pickup accordion separately from the checkout accordion bootstrap.

## Translation ownership

All Pickup translations belong to the module and are registered by `PickupServiceProvider`:

- `resources/lang/en/admin/modules/pickup.php`
- `resources/lang/uk/admin/modules/pickup.php`
- `resources/lang/en/storefront/checkout.php`
- `resources/lang/uk/storefront/checkout.php`

Use the `pickup::` namespace for module translations. Do not move Pickup strings into the shared application language tree unless the string becomes a deliberately shared application contract.

## Important files

| File | Purpose |
|---|---|
| `module.json` | Nwidart module metadata and provider registration |
| `config/config.php` | Singleton, runtime, placement, and admin configuration |
| `app/Providers/PickupServiceProvider.php` | Registers views, translations, config, and module services |
| `app/Support/PickupConfig.php` | Reads and validates global Pickup configuration |
| `app/Services/Filament/PickupSettingsService.php` | Sanitizes and persists settings |
| `app/Services/Filament/PickupIframeSanitizer.php` | Enforces the safe iframe allowlist |
| `app/Services/Storefront/PickupCheckoutDataService.php` | Builds localized checkout data and availability |
| `app/Services/Storefront/PickupStorefrontService.php` | Resolves storefront placement data |
| `app/Filament/Pages/PickupSettingsPage.php` | Admin settings page |
| `resources/views/storefront/module.blade.php` | Checkout delivery option, address, and optional map accordion |
| `resources/lang/` | Module-owned admin and storefront translations |
| `tests/Feature/` | Configuration, checkout data, and selection regression tests |

## Testing and maintenance

Run the module tests from the application container:

```bash
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm \
    php artisan test --compact Modules/Pickup/tests
```

Changes to the module should cover at least:

- all active-language address validation;
- optional empty iframe behavior;
- rejection and sanitization of unsafe iframe HTML;
- module availability when the map is absent;
- localized checkout data;
- clearing pickup state when another delivery method is selected;
- conditional rendering of `#pickup-store-map-collapse`.

Do not introduce database tables or module instances for this module without changing the singleton contract and updating this document and its OpenAPI documentation.
