# PaymentUponDelivery Module

Модуль `PaymentUponDelivery` добавляет способ оплаты **«Оплата после получения»** в checkout.

Это минимальный singleton-модуль: он не подключается к платёжному шлюзу, не создаёт платёжные транзакции и не имеет собственных HTTP endpoints. Его задача — показать доступный способ оплаты, сохранить выбор пользователя и передать идентификатор выбранного способа в order/payment flow.

## Краткая карточка модуля

| Параметр | Значение |
|---|---|
| Nwidart name | `PaymentUponDelivery` |
| Alias | `paymentupondelivery` |
| Payment method key | `payment_upon_delivery` |
| Тип | Singleton |
| Global configuration | Не требуется |
| Module instances | Запрещены |
| Собственные web/api routes | Нет |
| Собственный controller | Нет |
| Admin page | `modules/payment-upon-delivery` |
| Checkout accordion | `#checkout-payment-collapse` |
| Translations | Только внутри модуля |

## Документация проекта

- [Modules registration and loading guide](../README.md)
- [Checkout OpenAPI contract](../../docs/openapi/pages/checkout.yaml)
- [Project architecture](../../docs/architecture.md)
- [Admin panel guide](../../docs/admin-panel.md)
- [Testing guide](../../docs/testing.md)

## Ответственность модуля

Модуль отвечает за:

- идентификатор способа оплаты `payment_upon_delivery`;
- проверку того, включён ли singleton-модуль в `module_definitions`;
- передачу payment option в checkout;
- отображение radio input в `#checkout-payment-collapse`;
- сохранение `payment_method` в checkout session state;
- проверку доступности метода оплаты при выборе;
- передачу метода в `OrderCreationService`;
- возврат pending payment intent без обращения к внешнему провайдеру;
- пустую, но обязательную страницу модуля в Filament admin;
- module-owned переводы.

Модуль не отвечает за:

- онлайн-эквайринг или оплату картой;
- создание платёжных ссылок, invoices или транзакций;
- расчёт стоимости заказа или доставки;
- выбор доставки;
- города, регионы, почтовые отделения и почтоматы;
- хранение настроек в `global_configs`;
- создание строк в `module_instances`;
- собственные web/api endpoints.

## Singleton-контракт

Singleton определяется через `config/config.php`:

```php
'admin' => [
    'can_create_instances' => false,
],
```

Нельзя создавать для этого модуля `ModuleInstance`. Его доступность определяется исключительно записью `ModuleDefinition`:

- `nwidart_name = PaymentUponDelivery`;
- модуль установлен;
- модуль включён;
- `canCreateInstances()` возвращает `false`.

Проверка в runtime выполняется через:

```php
is_enabled_singleton_module('PaymentUponDelivery')
```

Если модуль выключен, payment radio не выводится, а попытка отправить `payment_upon_delivery` отклоняется серверной валидацией.

## Module metadata и configuration

### `module.json`

Файл содержит только основной provider:

```json
{
    "name": "PaymentUponDelivery",
    "alias": "paymentupondelivery",
    "providers": [
        "Modules\\PaymentUponDelivery\\Providers\\PaymentUponDeliveryServiceProvider"
    ]
}
```

В модуле намеренно отсутствуют route provider, event provider и route files.

### `config/config.php`

Конфигурация содержит:

- имя и описание модуля;
- ключ способа оплаты;
- запрет создания instances;
- класс страницы, используемой действием модуля в admin;
- `route_matched` provider-loading strategy.

Модуль не имеет `runtime.storefront` placement, потому что payment option выводится непосредственно в общем checkout view, а не через стандартный placement resolver.

## Service provider

Файл: `app/Providers/PaymentUponDeliveryServiceProvider.php`

Provider:

- наследуется от `Nwidart\Modules\Support\ModuleServiceProvider`;
- регистрирует module translations из `resources/lang`;
- регистрирует `PaymentUponDeliveryConfig` как singleton binding;
- не регистрирует routes, events или дополнительные providers.

Не следует добавлять в этот provider lazy-loading логику. Стратегия загрузки определяется общим `ModuleProviderResolverService` и `config/config.php`.

## Основные классы

| Класс | Назначение |
|---|---|
| `Support/PaymentUponDeliveryConfig.php` | Канонический payment key и translation key |
| `Services/PaymentUponDeliveryModuleDataService.php` | Формирует checkout option и availability |
| `Services/PaymentUponDeliveryPaymentModule.php` | Возвращает pending payment intent без внешнего gateway |
| `Filament/Pages/PaymentUponDeliverySettingsPage.php` | Пустая admin page singleton-модуля |
| `Providers/PaymentUponDeliveryServiceProvider.php` | Translations и module binding |

## Payment data contract

`PaymentUponDeliveryModuleDataService::getCheckoutData()` возвращает:

```php
[
    'is_available' => bool,
    'payment_method' => 'payment_upon_delivery',
    'label_translation_key' => 'paymentupondelivery::storefront/checkout.payment_methods.payment_upon_delivery',
]
```

`is_available` равен `true` только при включённом singleton-модуле.

Checkout controller передаёт эти данные в view под ключом:

```php
payment_upon_delivery_checkout_data
```

Если ранее в session отсутствовал `payment_method`, первый доступный payment option используется как initial selected state. Для текущего модуля это `payment_upon_delivery`.

## Admin page

Admin page находится в:

```text
app/Filament/Pages/PaymentUponDeliverySettingsPage.php
```

Её slug:

```text
modules/payment-upon-delivery
```

Фактический URL формируется Filament и зависит от локали и panel prefix, например:

```text
/{locale}/alyo-admin/modules/payment-upon-delivery
```

Страница намеренно пустая. Она необходима для единого module-definition workflow: администратор может открыть страницу модуля из списка modules, но не должен видеть форму настроек, поскольку этот payment method не требует конфигурации.

Page discovery зарегистрирован в:

```text
app/app/Providers/Filament/AlyoAdminPanelProvider.php
```

При изменении namespace или slug необходимо проверить синхронизацию module definitions и разрешение admin page URL.

## Checkout integration

Интеграция выполняется через общие checkout-компоненты приложения:

| Файл | Роль |
|---|---|
| `app/Http/Controllers/Pages/CheckoutController.php` | Передаёт payment option и initial state в view |
| `app/Http/Requests/Pages/CheckoutSelectionStoreRequest.php` | Нормализует и проверяет выбранный payment method |
| `app/Services/Checkout/CheckoutSelectionStateService.php` | Хранит `payment_method` в session state |
| `resources/views/catalog/pages/checkout.blade.php` | Выводит payment radio в accordion |
| `resources/assets/catalog/ts/features/pages/checkout/checkoutPage.ts` | Синхронизирует выбор с backend |

### Markup contract

Payment options находятся внутри:

```html
#checkout-payment-collapse
```

Каждый метод должен использовать:

```html
input[data-checkout-payment-method-input][type="radio"][name="payment_method"]
```

Для PaymentUponDelivery значение input:

```text
payment_upon_delivery
```

Radio input помечен `required`. Native radio semantics гарантирует, что пользователь может выбрать только один payment method.

Не следует создавать отдельный accordion для PaymentUponDelivery или отдельный frontend entry point.

### Selection endpoint

Выбор сохраняется через общий endpoint:

```text
POST /{locale}/checkout/selection
```

Пример payload:

```text
payment_method=payment_upon_delivery
delivery_method=nova_poshta
```

В ответе значение доступно по пути:

```json
{
    "success": true,
    "state": {
        "payment_method": "payment_upon_delivery"
    }
}
```

Checkout selection request разрешает промежуточный payload без payment method, чтобы пользователь мог менять доставку до завершения заполнения формы. Если payment method передан, он обязан быть доступным.

Обязательность выбора для пользователя обеспечивается:

- `required` на checkout radio input;
- серверной проверкой order/payment request boundary;
- запретом неизвестных или выключенных payment methods.

При изменении способа оплаты данные доставки не очищаются. Очистка `city`, `delivery_point` и `delivery_address` относится только к переключению способа доставки.

## Order/payment integration

`OrderCreationService` распознаёт `payment_upon_delivery` и вызывает:

```php
Modules\PaymentUponDelivery\Services\PaymentUponDeliveryPaymentModule
```

Payment module:

- добавляет `payment_method` в order payload;
- возвращает `is_success = true`;
- возвращает `status = pending`;
- возвращает `provider_code = payment_upon_delivery`;
- не отправляет запросы во внешние системы.

Это не означает, что заказ уже оплачен. `pending` означает, что заказ ожидает оплаты после получения.

Пример результата:

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

Если позже понадобится отдельная бизнес-логика COD, статусы заказа или интеграция службы доставки, её нужно добавлять в module payment service и тесты, а не в Blade или checkout controller.

## Validation rules

### Checkout selection

`CheckoutSelectionStoreRequest`:

- нормализует payment method через lowercase и trim/squish;
- принимает строку длиной до 100 символов;
- отклоняет переданный method, если соответствующий payment module недоступен;
- не принимает произвольный payment key;
- сохраняет normalized value в checkout session state.

### Order validation

`FastOrderValidateRequest` признаёт `payment_upon_delivery` допустимым значением и дополнительно проверяет, включён ли singleton-модуль.

Не следует возвращать silent fallback на `cash_on_delivery`, если пользователь явно передал выключенный или неизвестный payment method. Такой payload должен завершаться validation error.

## Translations

Все строки модуля находятся внутри него:

```text
resources/lang/en/admin/modules/payment_upon_delivery.php
resources/lang/uk/admin/modules/payment_upon_delivery.php
resources/lang/en/storefront/checkout.php
resources/lang/uk/storefront/checkout.php
```

Namespace:

```text
paymentupondelivery::
```

Основные ключи:

```php
__('paymentupondelivery::admin/modules/payment_upon_delivery.title');
__('paymentupondelivery::admin/modules/payment_upon_delivery.description');
__('paymentupondelivery::storefront/checkout.payment_methods.payment_upon_delivery');
```

Не переносите эти строки в `resources/lang/catalog` или общий application language tree без отдельного решения о создании shared contract.

## Routes and removed scaffold

У модуля нет собственных routes. Его функциональность работает через существующие application routes:

- checkout page: `GET /{locale}/checkout`;
- checkout selection: `POST /{locale}/checkout/selection`;
- order confirmation/validation routes приложения.

Внутри модуля намеренно отсутствуют:

- `app/Http/Controllers/PaymentUponDeliveryController.php`;
- `routes/web.php`;
- `routes/api.php`;
- `app/Providers/RouteServiceProvider.php`;
- `app/Providers/EventServiceProvider.php`;
- placeholder storefront Blade view;
- module-specific Vite/JS/Sass entry points.

Не добавляйте эти файлы только ради соответствия стандартному scaffold Nwidart.

## Tests

Основной тест:

```text
Modules/PaymentUponDelivery/tests/Feature/PaymentUponDeliveryModuleTest.php
```

Он проверяет:

- доступность только включённого singleton-модуля;
- сохранение `payment_method` через checkout selection endpoint;
- отказ при выключенном payment module;
- наличие admin page и module translation;
- pending payment intent.

Запуск:

```bash
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm \
    php artisan test --compact Modules/PaymentUponDelivery/tests
```

После изменения checkout integration также запускайте связанные тесты Pickup/checkout и проверки качества:

```bash
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer pint
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer phpcs
docker compose -f .docker/dev/docker-compose.yml exec -T dev-syvak-php-fpm composer phpstan
npm run ts:check
```

## Logging and error handling

Модуль не должен писать logs на каждый успешный availability check или обычный выбор radio.

Разрешены только важные ошибки:

- невозможность зарегистрировать/разрешить module provider или admin page;
- unexpected failure при обработке payment intent;
- unexpected validation/persistence failure.

Для PHP используйте существующие project channels:

```php
Log::channel('stack')->error('[PaymentUponDelivery...] message', $context);
```

Не записывайте в logs полные customer payload, телефоны, адреса или платёжные секреты.

## Rules for future changes

1. Сохраняйте `payment_upon_delivery` как backward-compatible canonical key.
2. Не превращайте модуль в instance-based модуль.
3. Не создавайте global config, если для метода оплаты не появилась реальная настройка.
4. Не добавляйте собственные routes/controller/provider scaffold без отдельной функциональной необходимости.
5. Не размещайте module-specific translations в shared language directories.
6. Не делайте payment option доступным, если module definition выключен.
7. Не меняйте delivery state при выборе payment method.
8. Любой новый payment method должен использовать тот же radio group и server-side validation contract.
9. Любую интеграцию с внешним платёжным провайдером реализуйте отдельным payment service с тестами и явными статусами.
10. После изменений обновляйте этот README, если изменились module boundaries, contracts или operational rules.
