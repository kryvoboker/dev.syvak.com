# BankTransfer Module

The `BankTransfer` module adds **Bank transfer** as a checkout payment method.

It is a singleton payment module. It does not connect to a bank, payment gateway, acquiring provider, or external API. Its purpose is to expose a selectable payment method, optionally show merchant-provided payment instructions, and pass the selected method to the order/payment flow.

## Module card

| Parameter | Value |
|---|---|
| Nwidart name | `BankTransfer` |
| Alias | `banktransfer` |
| Payment method key | `bank_transfer` |
| Type | Singleton |
| Global configuration | Required for localized payment names; optional for payment information |
| Module instances | Forbidden |
| Module-owned web/API routes | None |
| Module-owned controller | None |
| Admin page slug | `modules/bank-transfer` |
| Checkout accordion | `#checkout-payment-collapse` |
| Documentation language | English |

## Project documentation

- [Module registration and loading guide](../README.md)
- [Checkout OpenAPI contract](../../openapi/pages/checkout.yaml)
- [Project architecture](../../../../docs/architecture.md)
- [Admin panel guide](../../../../docs/admin-panel.md)
- [Testing guide](../../../../docs/testing.md)

## Responsibilities

The module is responsible for:

- defining the canonical payment key `bank_transfer`;
- defining the singleton admin contract;
- storing localized payment names in global configs;
- storing optional localized plain-text payment information in global configs;
- exposing the payment method to checkout only after all active languages have a configured name;
- returning the payment name for the current checkout locale;
- rendering a required radio input inside `#checkout-payment-collapse`;
- rendering optional payment information only when the method is selected and information exists for the current locale;
- validating that the selected method is currently available;
- passing the selected method to `OrderCreationService`;
- returning a pending payment intent without contacting an external provider;
- providing a Filament settings page for the singleton module;
- owning all module-specific translations.

The module is not responsible for:

- communication with a bank or payment gateway;
- payment transaction creation or settlement;
- payment confirmation webhooks;
- invoices, bank reconciliation, or automatic payment matching;
- delivery methods, cities, regions, branches, or parcel lockers;
- order persistence beyond the shared order/payment contract;
- `module_instances` records;
- module-owned HTTP endpoints.

## Singleton contract

The singleton restriction is declared in `config/config.php`:

```php
'admin' => [
    'can_create_instances' => false,
],
```

Do not create a `ModuleInstance` for this module. Runtime availability depends on:

1. an enabled `ModuleDefinition` with `nwidart_name = BankTransfer`;
2. at least one active application language;
3. a non-empty payment name for every active language.

Runtime checks use:

```php
is_enabled_singleton_module('BankTransfer')
```

The final checkout availability is resolved by `Modules\\BankTransfer\\Services\\Storefront\\BankTransferModuleDataService`, which combines the singleton module status with `BankTransferConfig::isComplete(...)`.

If the module is disabled or any active language has no name, the payment radio is not rendered and a submitted `bank_transfer` value is rejected by server-side validation.

## Module metadata and configuration

### `module.json`

The module registers only its main provider:

```json
{
    "name": "BankTransfer",
    "alias": "banktransfer",
    "providers": [
        "Modules\\BankTransfer\\Providers\\BankTransferServiceProvider"
    ]
}
```

There is intentionally no route provider, event provider, controller, route file, database migration, seeder, or module-specific frontend entry point.

### `config/config.php`

The module config contains:

- module name and description;
- canonical payment key `bank_transfer`;
- singleton restriction;
- admin page class used by module-definition actions;
- `route_matched` provider-loading strategy.

The payment option is rendered by the shared checkout view, so the module does not use the generic storefront placement resolver.

## Service provider

File:

```text
app/Providers/BankTransferServiceProvider.php
```

The provider:

- extends `Nwidart\Modules\Support\ModuleServiceProvider`;
- loads module translations from `resources/lang` under the `banktransfer` namespace;
- binds `BankTransferConfig` as a singleton;
- does not register routes or event providers.

Provider loading remains controlled by the central module runtime. Do not add a second request-context loading guard to this provider.

## Global configuration

All persistent module settings use the shared global-config mechanism. No migration is required.

| Key | Value | Required |
|---|---|---|
| `bank_transfer.payment_names` | JSON/object map of language code to payment name | Yes, for every active language |
| `bank_transfer.payment_information` | JSON/object map of language code to plain-text instructions | No |

Example values:

```php
[
    'bank_transfer.payment_names' => [
        'uk' => 'Банківський переказ',
        'en' => 'Bank transfer',
    ],
    'bank_transfer.payment_information' => [
        'uk' => "Отримувач: Syvak\nIBAN: UA000000000000000000000000000",
        'en' => "Recipient: Syvak\nIBAN: UA000000000000000000000000000",
    ],
]
```

Use the project helpers or `GlobalConfigService` for reads and writes:

```php
get_global_config('bank_transfer.payment_names', []);
set_global_config('bank_transfer.payment_names', $payment_names);
```

Do not read the `global_configs` table directly from controllers, views, or Filament pages.

### Normalization

`BankTransferConfig` and `BankTransferSettingsService`:

- trim language keys and values;
- normalize language keys to lowercase;
- remove empty language keys and empty values;
- preserve line breaks in payment information;
- never treat payment information as HTML.

The admin page performs an additional completeness check against all currently active languages before saving. Payment names are required; payment information is not.

## Main classes

| Class | Responsibility |
|---|---|
| `Support/BankTransferConfig.php` | Canonical key, global-config keys, localized values, and completeness check |
| `Services/Filament/BankTransferSettingsService.php` | Normalizes and persists both settings maps for the admin page |
| `Services/Storefront/BankTransferModuleDataService.php` | Builds the localized checkout payment payload |
| `Services/BankTransferPaymentModule.php` | Returns the pending bank-transfer payment intent |
| `Filament/Pages/BankTransferSettingsPage.php` | Localized singleton admin settings page |
| `Providers/BankTransferServiceProvider.php` | Translation registration and config binding |

## Admin settings page

The page class is:

```text
app/Filament/Pages/BankTransferSettingsPage.php
```

Its slug is:

```text
modules/bank-transfer
```

The final URL is generated by Filament and depends on the locale and panel prefix:

```text
/{locale}/alyo-admin/modules/bank-transfer
```

Page discovery is registered in:

```text
app/app/Providers/Filament/AlyoAdminPanelProvider.php
```

This registration is required so the module-definition synchronization workflow can resolve the page URL.

### Form contract

The page stores form state under `settings_form` and creates one language tab per active language. Each tab contains:

1. `payment_names.{language_code}` — required `TextInput`;
2. `payment_information.{language_code}` — optional `Textarea`.

The tab label is the active language name and its badge is the language code.

The page validates every active language explicitly before calling `BankTransferSettingsService`. An empty name causes a validation error on:

```text
settings_form.payment_names.{language_code}
```

Payment information may remain empty and does not prevent saving or checkout availability.

## Checkout data contract

`BankTransferModuleDataService::getCheckoutData(string $locale)` in `Services/Storefront` returns:

```php
[
    'is_available' => bool,
    'payment_method' => 'bank_transfer',
    'label_translation_key' => 'banktransfer::storefront/checkout.payment_methods.bank_transfer',
    'payment_name' => 'Bank transfer',
    'payment_information' => 'Recipient: Syvak\nIBAN: ...',
]
```

The controller passes the payload to the view as:

```php
bank_transfer_checkout_data
```

When the module is disabled or incomplete, `is_available` is `false`, `payment_name` is empty, and `payment_information` is empty.

`label_translation_key` remains in the payload as the module translation fallback/contract field. The checkout view uses the configured `payment_name` when available rather than the static translation.

## Checkout integration

The shared application files integrate BankTransfer with checkout:

| File | Responsibility |
|---|---|
| `app/Http/Controllers/Pages/CheckoutController.php` | Provides localized BankTransfer data and contributes it to default payment selection |
| `app/Http/Requests/Pages/CheckoutSelectionStoreRequest.php` | Validates the selected payment method against available modules |
| `app/Http/Requests/Order/FastOrderValidateRequest.php` | Requires and validates the final payment method |
| `app/Services/Checkout/CheckoutSelectionStateService.php` | Persists `payment_method` in checkout session state |
| `app/Services/Order/OrderCreationService.php` | Dispatches `bank_transfer` to the module payment service |
| `resources/views/catalog/pages/checkout.blade.php` | Renders the radio and optional payment information |
| `resources/assets/catalog/ts/features/pages/checkout/checkoutPage.ts` | Synchronizes payment selection and toggles optional information |

### Markup contract

The payment radio is rendered inside:

```html
#checkout-payment-collapse
```

Each payment option uses:

```html
input[data-checkout-payment-method-input][type="radio"][name="payment_method"]
```

The BankTransfer value is:

```text
bank_transfer
```

The radio is `required`, and all payment methods share the same radio group so only one can be selected.

The optional information block uses:

```html
[data-checkout-payment-information="bank_transfer"]
```

It is rendered only when the current locale has non-empty configured information. TypeScript shows it only while `bank_transfer` is the selected payment method.

The value is rendered with escaped Blade output and `whitespace-pre-line`. HTML entered in the admin textarea is displayed as text, not interpreted as markup.

### Selection endpoint

Payment selection is persisted through the shared endpoint:

```text
POST /{locale}/checkout/selection
```

Example payload:

```text
payment_method=bank_transfer
delivery_method=nova_poshta
```

Example response:

```json
{
    "success": true,
    "state": {
        "payment_method": "bank_transfer",
        "delivery_method": "nova_poshta"
    }
}
```

The selection request allows an intermediate payload without a payment method while the checkout UI is synchronizing delivery state. If a payment method is supplied, it must be available and recognized by the shared payment validation contract.

Final order validation requires `payment_method`; it does not silently convert an omitted value to `cash_on_delivery`.

Changing the payment method does not change `delivery_method`, `city`, `delivery_point`, or `delivery_address`. Delivery state is managed only by delivery-method logic.

## Order and payment flow

`OrderCreationService` recognizes `bank_transfer` and calls:

```php
Modules\BankTransfer\Services\BankTransferPaymentModule
```

The payment module:

- forces `payment_method = bank_transfer` into the payment payload;
- returns `is_success = true`;
- returns `status = pending`;
- returns `provider_code = bank_transfer`;
- performs no external request.

Example result:

```php
[
    'is_success' => true,
    'status' => 'pending',
    'provider_code' => 'bank_transfer',
    'payload' => [
        'payment_method' => 'bank_transfer',
    ],
]
```

`pending` means the order is awaiting a manual bank transfer or a future payment-processing integration. It does not mean that the payment has been confirmed.

If bank API integration is added later, keep it inside a dedicated payment service, define explicit statuses, and add failure/retry tests. Do not place gateway logic in Blade, the checkout controller, or the Filament page.

## Validation rules

### Admin settings

- Every active language must have a non-empty payment name.
- Payment information is optional for every language.
- Values are trimmed before persistence.
- Empty payment information values are omitted from the stored map.

### Checkout selection

`CheckoutSelectionStoreRequest`:

- normalizes the payment method to lowercase and trimmed text;
- accepts a string up to 100 characters;
- checks BankTransfer availability only when `bank_transfer` is submitted;
- rejects `bank_transfer` when the singleton is disabled or names are incomplete;
- rejects unknown payment keys through the shared payment availability check.

### Final order

`FastOrderValidateRequest` and its store-request subclass:

- require `payment_method`;
- allow `bank_transfer` alongside existing payment keys;
- reject BankTransfer when the module is unavailable;
- do not silently fall back to another method when the submitted value is invalid.

## Translations

All module-specific strings are inside the module:

```text
resources/lang/en/admin/modules/bank_transfer.php
resources/lang/uk/admin/modules/bank_transfer.php
resources/lang/en/storefront/checkout.php
resources/lang/uk/storefront/checkout.php
```

Translation namespace:

```text
banktransfer::
```

Examples:

```php
__('banktransfer::admin/modules/bank_transfer.title');
__('banktransfer::admin/modules/bank_transfer.labels.payment_name');
__('banktransfer::storefront/checkout.labels.payment_information');
```

The configured payment name is content, not a translation key. It is selected by locale from `bank_transfer.payment_names` and rendered as escaped text.

## Routes and removed scaffold

The module has no module-owned routes. It uses shared application routes:

- checkout page: `GET /{locale}/checkout`;
- checkout selection: `POST /{locale}/checkout/selection`;
- shared order confirmation and validation routes.

The generated scaffold intentionally does not contain:

- `app/Http/Controllers/BankTransferController.php`;
- `routes/web.php`;
- `routes/api.php`;
- `app/Providers/RouteServiceProvider.php`;
- `app/Providers/EventServiceProvider.php`;
- demo Blade layouts/views;
- module-specific JS or Sass entry points;
- database migrations or seeders.

Do not recreate these files without a concrete functional requirement.

## Tests

The module test files are:

```text
Modules/BankTransfer/tests/TestCase.php
Modules/BankTransfer/tests/Feature/BankTransferModuleTest.php
```

The tests cover:

- disabled module availability;
- incomplete required localized names;
- localized payment-name and optional-information payloads;
- enabled checkout selection and disabled-method rejection;
- admin page URL, title, and translations;
- pending bank-transfer payment intent;
- canonical payment method/provider values.

Run the module tests with:

```bash
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm \
    php artisan test --compact Modules/BankTransfer/tests
```

When changing checkout or PHP integration, run:

```bash
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer pint
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer phpcs
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer phpstan
npm run ts:check
```

## Logging and error handling

Normal availability checks, config reads, radio changes, and successful payment-intent creation must not produce logs.

The module logs only unexpected settings persistence errors through the `stack` channel:

```php
Log::channel('stack')->error('[BankTransferSettingsService.save] settings save failed', $context);
```

Do not log:

- payment names or payment instructions;
- complete customer/order payloads;
- phone numbers, addresses, or account/payment secrets;
- every checkout selection.

## Rules for future changes

1. Keep `bank_transfer` as the canonical payment key.
2. Keep the module singleton-based.
3. Keep payment names required for every active language.
4. Keep payment information optional and plain text unless a new security-reviewed content contract is introduced.
5. Keep availability hidden until all active-language names are configured.
6. Keep module-specific translations inside `Modules/BankTransfer/resources/lang`.
7. Do not add module-owned routes, controllers, or generated scaffold files without a functional requirement.
8. Use the shared checkout radio group and server-side payment validation contract.
9. Keep external bank/payment integration in a dedicated service with explicit statuses and tests.
10. Update this README when module boundaries, global-config keys, checkout contracts, or operational rules change.
