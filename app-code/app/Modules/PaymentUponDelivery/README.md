# PaymentUponDelivery Module

The `PaymentUponDelivery` module adds **Payment upon delivery** as a checkout payment method.

This is a minimal singleton module. It does not connect to a payment gateway, create payment transactions, or expose its own HTTP endpoints. Its purpose is to expose an available payment option, persist the customer's selection, and pass the selected method to the order/payment flow.

## Module card

| Parameter | Value |
|---|---|
| Nwidart name | `PaymentUponDelivery` |
| Alias | `paymentupondelivery` |
| Payment method key | `payment_upon_delivery` |
| Type | Singleton |
| Global configuration | Not required |
| Module instances | Forbidden |
| Module-owned web/API routes | None |
| Module-owned controller | None |
| Admin page slug | `modules/payment-upon-delivery` |
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

- defining the canonical payment key `payment_upon_delivery`;
- checking whether the singleton module is enabled in `module_definitions`;
- providing the payment option to checkout;
- rendering a radio input inside `#checkout-payment-collapse`;
- persisting `payment_method` in checkout session state;
- validating that the selected method is available;
- passing the selected method to `OrderCreationService`;
- returning a pending payment intent without contacting an external provider;
- providing the required, intentionally empty Filament admin page;
- owning all module-specific translations.

The module is not responsible for:

- online acquiring or card payments;
- payment links, invoices, or gateway transactions;
- order or delivery price calculation;
- delivery method selection;
- cities, regions, post offices, or parcel lockers;
- global configuration records;
- `module_instances` records;
- module-owned HTTP endpoints.

## Singleton contract

The singleton behavior is declared in `config/config.php`:

```php
'admin' => [
    'can_create_instances' => false,
],
```

Do not create a `ModuleInstance` for this module. Runtime availability is determined by an enabled `ModuleDefinition` with:

- `nwidart_name = PaymentUponDelivery`;
- the module installed and enabled;
- `canCreateInstances()` returning `false`.

Runtime checks use the project helper:

```php
is_enabled_singleton_module('PaymentUponDelivery')
```

When the module is disabled, the payment radio is not rendered and a submitted `payment_upon_delivery` value is rejected by server-side validation.

## Module metadata and configuration

### `module.json`

The module registers only its main provider:

```json
{
    "name": "PaymentUponDelivery",
    "alias": "paymentupondelivery",
    "providers": [
        "Modules\\PaymentUponDelivery\\Providers\\PaymentUponDeliveryServiceProvider"
    ]
}
```

The module intentionally has no route provider, event provider, or route files.

### `config/config.php`

The configuration contains:

- module name and description;
- the payment method key;
- the singleton restriction;
- the admin page class used by the module list action;
- the `route_matched` provider-loading strategy.

The module has no `runtime.storefront` placement. The payment option is rendered directly by the shared checkout view rather than through the standard storefront placement resolver.

## Service provider

File: `app/Providers/PaymentUponDeliveryServiceProvider.php`

The provider:

- extends `Nwidart\Modules\Support\ModuleServiceProvider`;
- registers module translations from `resources/lang`;
- binds `PaymentUponDeliveryConfig` as a singleton;
- does not register routes, events, or additional providers.

Do not add custom lazy-loading logic to this provider. Loading strategy is controlled centrally by `ModuleProviderResolverService` and `config/config.php`.

## Main classes

| Class | Responsibility |
|---|---|
| `Support/PaymentUponDeliveryConfig.php` | Canonical payment key and translation key |
| `Services/Storefront/PaymentUponDeliveryModuleDataService.php` | Checkout option and availability payload |
| `Services/PaymentUponDeliveryPaymentModule.php` | Pending payment intent without an external gateway |
| `Filament/Pages/PaymentUponDeliverySettingsPage.php` | Empty singleton admin page |
| `Providers/PaymentUponDeliveryServiceProvider.php` | Translation registration and module binding |

## Payment data contract

`PaymentUponDeliveryModuleDataService::getCheckoutData()` in `Services/Storefront` returns:

```php
[
    'is_available' => bool,
    'payment_method' => 'payment_upon_delivery',
    'label_translation_key' => 'paymentupondelivery::storefront/checkout.payment_methods.payment_upon_delivery',
]
```

`is_available` is `true` only when the singleton module is enabled.

The checkout controller passes this payload to the view as:

```php
payment_upon_delivery_checkout_data
```

If the session has no `payment_method`, the first available payment option becomes the initial selected state. For the current module, that option is `payment_upon_delivery`.

## Admin page

The admin page is located at:

```text
app/Filament/Pages/PaymentUponDeliverySettingsPage.php
```

Its slug is:

```text
modules/payment-upon-delivery
```

The final URL is generated by Filament and depends on the locale and panel prefix, for example:

```text
/{locale}/alyo-admin/modules/payment-upon-delivery
```

The page is intentionally empty. It is required for the unified module-definition workflow: administrators can open the module from the modules list, but there are no settings to edit for this payment method.

Page discovery is registered in:

```text
app/app/Providers/Filament/AlyoAdminPanelProvider.php
```

When changing the namespace or slug, verify module-definition synchronization and admin page URL resolution.

## Checkout integration

Integration uses the application's shared checkout components:

| File | Responsibility |
|---|---|
| `app/Http/Controllers/Pages/CheckoutController.php` | Provides payment option data and initial state |
| `app/Http/Requests/Pages/CheckoutSelectionStoreRequest.php` | Normalizes and validates the selected method |
| `app/Services/Checkout/CheckoutSelectionStateService.php` | Stores `payment_method` in session state |
| `resources/views/catalog/pages/checkout.blade.php` | Renders the payment radio inside the accordion |
| `resources/assets/catalog/ts/features/pages/checkout/checkoutPage.ts` | Synchronizes the selection with the backend |

### Markup contract

Payment options are rendered inside:

```html
#checkout-payment-collapse
```

Each payment method must use:

```html
input[data-checkout-payment-method-input][type="radio"][name="payment_method"]
```

The PaymentUponDelivery value is:

```text
payment_upon_delivery
```

The radio input is marked `required`. Native radio semantics ensure that only one payment method can be selected.

Do not create a separate accordion or a separate frontend entry point for this module.

### Selection endpoint

The selection is persisted through the shared endpoint:

```text
POST /{locale}/checkout/selection
```

Example payload:

```text
payment_method=payment_upon_delivery
delivery_method=nova_poshta
```

The response exposes the normalized value at:

```json
{
    "success": true,
    "state": {
        "payment_method": "payment_upon_delivery"
    }
}
```

The checkout selection request permits an intermediate payload without a payment method so the user can change delivery before completing the form. When a payment method is supplied, it must be currently available.

The user's required choice is enforced by:

- the `required` attribute on the checkout radio input;
- server-side validation at the order/payment boundary;
- rejection of unknown or disabled payment methods.

Changing the payment method does not clear delivery state. Clearing `city`, `delivery_point`, and `delivery_address` belongs only to delivery-method switching.

## Order/payment integration

`OrderCreationService` recognizes `payment_upon_delivery` and calls:

```php
Modules\PaymentUponDelivery\Services\PaymentUponDeliveryPaymentModule
```

The payment module:

- adds `payment_method` to the order payload;
- returns `is_success = true`;
- returns `status = pending`;
- returns `provider_code = payment_upon_delivery`;
- makes no external requests.

This does not mean that the order has already been paid. `pending` means that the order is waiting for payment upon receipt.

Example result:

```php
[
    'is_success' => true,
    'status' => 'pending',
    'provider_code' => 'payment_upon_delivery',
    'payload' => [
        'payment_method' => 'payment_upon_delivery',
    ],
]
```

If COD-specific business rules, order statuses, or delivery-service integrations are introduced later, implement them in the payment service and its tests rather than in Blade or the checkout controller.

## Validation rules

### Checkout selection

`CheckoutSelectionStoreRequest`:

- normalizes the payment method using lowercase and trim/squish;
- accepts a string up to 100 characters;
- rejects a supplied method when its payment module is unavailable;
- does not accept arbitrary payment keys;
- stores the normalized value in checkout session state.

### Order validation

`FastOrderValidateRequest` accepts `payment_upon_delivery` as a valid value and additionally checks that the singleton module is enabled.

Do not silently fall back to `cash_on_delivery` when a user explicitly submits a disabled or unknown payment method. Such a payload must produce a validation error.

## Translations

All module strings are stored inside the module:

```text
resources/lang/en/admin/modules/payment_upon_delivery.php
resources/lang/uk/admin/modules/payment_upon_delivery.php
resources/lang/en/storefront/checkout.php
resources/lang/uk/storefront/checkout.php
```

Translation namespace:

```text
paymentupondelivery::
```

Main keys:

```php
__('paymentupondelivery::admin/modules/payment_upon_delivery.title');
__('paymentupondelivery::admin/modules/payment_upon_delivery.description');
__('paymentupondelivery::storefront/checkout.payment_methods.payment_upon_delivery');
```

Do not move these strings to `resources/lang/catalog` or the shared application language tree without an explicit decision to create a shared contract.

## Routes and removed scaffold

The module has no module-owned routes. Its functionality uses existing application routes:

- checkout page: `GET /{locale}/checkout`;
- checkout selection: `POST /{locale}/checkout/selection`;
- application order confirmation and validation routes.

The following generated scaffold files are intentionally absent:

- `app/Http/Controllers/PaymentUponDeliveryController.php`;
- `routes/web.php`;
- `routes/api.php`;
- `app/Providers/RouteServiceProvider.php`;
- `app/Providers/EventServiceProvider.php`;
- placeholder storefront Blade views;
- module-specific Vite, JS, and Sass entry points.

Do not add these files merely to match the default Nwidart scaffold.

## Tests

The main test file is:

```text
Modules/PaymentUponDelivery/tests/Feature/PaymentUponDeliveryModuleTest.php
```

It covers:

- availability only when the singleton module is enabled;
- persistence through the checkout selection endpoint;
- rejection when the payment module is disabled;
- admin page registration and module translation;
- the pending payment intent.

Run the module tests with:

```bash
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm \
    php artisan test --compact Modules/PaymentUponDelivery/tests
```

When changing checkout integration, also run related Pickup/checkout tests and the quality checks:

```bash
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer pint
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer phpcs
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer phpstan
npm run ts:check
```

## Logging and error handling

The module must not log every successful availability check or normal radio selection.

Only important errors may be logged, including:

- failure to register or resolve the module provider or admin page;
- unexpected payment intent processing failures;
- unexpected validation or persistence failures.

Use the project's existing PHP logging channels:

```php
Log::channel('stack')->error('[PaymentUponDelivery...] message', $context);
```

Do not log complete customer payloads, phone numbers, addresses, or payment secrets.

## Rules for future changes

1. Keep `payment_upon_delivery` as the backward-compatible canonical key.
2. Keep the module singleton-based.
3. Do not add global configuration unless the payment method gains a real setting.
4. Do not add module-owned routes, controllers, or provider scaffold without a functional requirement.
5. Keep module-specific translations in the module.
6. Do not expose the payment option while the module definition is disabled.
7. Do not change delivery state when the payment method changes.
8. Every new payment method must use the same radio group and server-side validation contract.
9. Implement any external payment-provider integration as a dedicated payment service with explicit statuses and tests.
10. Update this README when module boundaries, contracts, or operational rules change.
