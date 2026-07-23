# WayForPay Module

The `WayForPay` module integrates the WayForPay payment service with the regular storefront checkout.
It is a singleton payment module: administrators configure one merchant account, and customers select
the resulting `wayforpay` payment method in `#checkout-payment-collapse`.

## Module card

| Item | Value |
|---|---|
| Nwidart name | `WayForPay` |
| Alias | `wayforpay` |
| Payment method | `wayforpay` |
| Type | Singleton |
| Admin slug | `modules/wayforpay` |
| Checkout flow | `SimpleOrder` validation and creation |
| Payment endpoint | `https://secure.wayforpay.com/pay` |
| Widget script | `https://secure.wayforpay.com/server/pay-widget.js` |
| Module OpenAPI | [`docs/openapi.yaml`](docs/openapi.yaml) |

## Responsibilities

The module owns:

- WayForPay merchant settings and localized checkout names;
- the Filament settings page;
- the `wayforpay` checkout payment option;
- creation of signed WayForPay request data through the official PHP SDK;
- the official widget integration and POST redirect fallback;
- validation of WayForPay callback and return signatures;
- module-specific translations and frontend code.

Shared application code owns the checkout form, order endpoints, cart snapshot, order number,
delivery selection, session state, and final success/failure pages.

The module does not persist payment transactions or order entities itself. `OrderCreationService`
currently builds the order payload and delegates payment preparation to this module.

## Singleton and availability

Instances must not be created. The restriction is declared in `config/config.php`:

```php
'admin' => [
    'can_create_instances' => false,
],
```

The payment option is available only when all conditions are true:

1. the `WayForPay` singleton module is enabled;
2. `merchant_account`, `secret_key`, and `merchant_domain_name` are configured;
3. every active application language has a non-empty localized payment name.

The check is implemented by `WayForPayModuleDataService` and `WayForPayConfig::isComplete()`.
Incomplete configuration intentionally hides the payment option instead of showing a broken method.

## Configuration storage

Persistent settings use the global-config mechanism. No migration or module instance is required.

| Global config key | Shape | Required |
|---|---|---|
| `wayforpay.settings` | object with merchant and request settings | Yes |
| `wayforpay.payment_names` | language-code to payment-name map | Yes for every active language |

Example:

```php
[
    'wayforpay.settings' => [
        'merchant_account' => 'merchant_account',
        'secret_key' => 'secret',
        'merchant_domain_name' => 'shop.example.test',
        'checkout_widget_enabled' => '1',
        'payment_systems' => 'card;googlePay;applePay',
    ],
    'wayforpay.payment_names' => [
        'uk' => 'Оплата карткою WayForPay',
        'en' => 'WayForPay card payment',
    ],
]
```

Use `WayForPayConfig`, `WayForPaySettingsService`, or the project global-config helpers. Do not
read `global_configs` directly from controllers or views.

## Admin settings

The page class is `app/Filament/Pages/WayForPaySettingsPage.php`; its Filament slug is
`modules/wayforpay`. The module list resolves the final localized admin URL.

The page has language tabs for the required payment name and fields for:

- merchant account;
- secret key;
- merchant domain name;
- whether to prefer the payment widget on checkout;
- merchant auth type;
- merchant transaction type;
- secure transaction type;
- API version;
- WayForPay language;
- optional payment systems;
- a read-only callback handler method.

Values that are protocol defaults or option lists are defined in `config/config.php`, not in the
Filament page. Current defaults are `SimpleSignature`, `AUTO`, `AUTO`, API version `1`, language
`UA`, and widget mode enabled. The callback handler is read-only because it is an application
contract, not an administrator-defined PHP method.

## Checkout integration

The shared `CheckoutController` supplies `wayforpay_checkout_data`, the widget script URL, the
payment method key, and the redirect method to `checkout.blade.php`. The view renders the module's
radio input only when the module is available:

```html
<input data-checkout-payment-method-input
       name="payment_method"
       type="radio"
       value="wayforpay"
       required>
```

The radio is inside `#checkout-payment-collapse`; payment selection is mandatory and only one
payment method can be selected. Selection state is saved through the shared
`POST /{locale}/checkout/selection` endpoint.

WayForPay is processed only by the regular checkout endpoints:

```text
POST /{locale}/order-validate/simple
POST /{locale}/order-confirm/simple
```

The frontend does not load the external widget during initial page load. After successful order
creation, the module's `wayforpayCheckout.ts` dynamically loads the widget script only when the
response requests widget mode. If loading or opening the widget fails, it submits the returned
`redirect_data` as a hidden POST form to `https://secure.wayforpay.com/pay`.

## Payment preparation contract

`WayForPayPaymentModule::prepare()` receives an order payload from `OrderCreationService`:

```php
[
    'order_number' => 'ORD-...',
    'customer' => [
        'first_name' => 'Lesia',
        'last_name' => 'Ukrainka',
        'email' => 'customer@example.test',
        'phone' => '+380...',
    ],
    'delivery' => [
        'method' => 'pickup_store',
        'address' => '...',
    ],
    'cart' => [...],
    'locale' => 'uk',
    'payment_method' => 'wayforpay',
    'return_url' => 'https://shop.example.test/uk/wayforpay/return',
    'service_url' => 'https://shop.example.test/uk/wayforpay/callback',
]
```

The returned shape is:

```php
[
    'success' => true,
    'payment_method' => 'wayforpay',
    'use_widget' => true,
    'widget_data' => [...],
    'redirect_data' => [
        'action' => 'https://secure.wayforpay.com/pay',
        'method' => 'POST',
        'fields' => [...],
    ],
    'errors' => [],
]
```

The SDK creates the signed request. The module adds `apiVersion`, removes empty optional timeout
fields, and filters empty values before returning the payload. Exceptions are logged to the stack
channel with the order reference and returned as a generic payment error.

## Callback and return flow

The application routes are localized and intentionally bypass CSRF middleware because the requests
come from the payment provider or its browser return flow:

| Route | Method | Purpose |
|---|---|---|
| `/{locale}/wayforpay/callback` | POST | Server-to-server WayForPay status callback |
| `/{locale}/wayforpay/return` | GET, POST | Browser return after widget/payment-page flow |

`WayForPayCallbackController` parses and validates the provider response with
`ServiceUrlHandler`, logs only the order reference/status, and returns the SDK success response.
Invalid callbacks return JSON `{ "status": "reject" }` with HTTP 400.

`WayForPayReturnController` accepts GET or POST data. An approved response with a valid signature
redirects to the localized `thank-you` page. Declined, incomplete, unsigned, or invalid responses
redirect to the localized failure page. The controller logs validation failures and non-approved
statuses without logging credentials or full payment payloads.

## Important implementation boundaries

- Keep merchant credentials in global configs and never expose the secret key to the browser.
- Keep the official widget URL and payment endpoint in `config/config.php`.
- Keep protocol defaults and allowed option values in the module config.
- Do not move WayForPay translations into application-level language files.
- Do not add a second order flow: WayForPay must use `storeSimpleOrder()` and
  `validateSimpleOrder()`.
- Do not treat a browser return as proof of payment without signature validation.
- Do not log full request payloads, signatures, credentials, or card data.

## Related documentation

- [Module OpenAPI contract](docs/openapi.yaml)
- [Checkout OpenAPI contract](../../openapi/pages/checkout.yaml)
- [Project OpenAPI entrypoint](../../openapi/openapi.yaml)
- [Project architecture](../../../../docs/architecture.md)
- [Testing guide](../../../../docs/testing.md)
