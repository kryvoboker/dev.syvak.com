[← Helpers Reference](helpers-reference.md) · [Back to README](../README.md) · [Testing →](testing.md)

# Services Reference

This page is the service catalog for `app/Services` and `app/Supports/Services`. It describes the intended entry point and the responsibility of each public method. Private methods are implementation details and should normally be changed through the public contract rather than called directly.

## Choosing a service

| Task | Start with |
|------|------------|
| Cart persistence and rendered cart payload | `CartService`, `CartSessionService`, `CartViewDataBuilderService` |
| Cart totals and discount/delivery adjustments | `CartTotalsPipelineService` and cart module callbacks |
| Promo-code configuration and checkout discounts | `PromoCodeAdminOptionsService`, `PromoCodePersistenceService`, `PromoCodeService` |
| Catalog filter setup/indexing | `CatalogFilterBootstrapService`, `CatalogFilterSetConfigurationService`, generator services, `CatalogFilterIndexRebuildDispatcherService` |
| Checkout city/branch UI state | `CheckoutCitySearchService`, `CheckoutBranchSearchService`, `CheckoutSelectionStateService` |
| Module discovery/runtime/admin settings | `ModuleDiscoveryService`, `ModuleRuntimeResolverService`, `ModuleInstanceService` |
| Order creation/editing/thank-you data | `OrderCreationService`, `OrderAdminPersistenceService`, `ThankYouOrderDataService` |
| Product/page defaults | `PageSettingsBootstrapService`, `ProductCategorySyncService` |
| Shared configuration, cache, images, translations | `GlobalConfigService`, `StorefrontCacheService`, `ImageUrlBuilderService`, translation services |

## Support services

### `App\Supports\Services\AppSettingsService`

Reads, stores, and clears cached application settings. `getSettings()` returns settings; `setSettings()` rebuilds/persists the settings cache; `removeSettings()` clears it. `resolveLanguageId()` is its internal locale-to-language resolver.

### `App\Supports\Services\GlobalConfigService`

The source of truth for `global_configs` CRUD and normalization.

| Method | Responsibility |
|--------|----------------|
| `getActiveGlobalConfigs()` / `getGlobalConfigs()` | Read active or all configuration records. |
| `getGlobalConfig()` / `getGlobalConfigsForForm()` | Read one value or return form-ready records. |
| `upsertGlobalConfig()` / `upsertGlobalConfigs()` | Create/update one or many values. |
| `syncGlobalConfigs()` | Synchronizes a submitted configuration set and removes missing keys where applicable. |
| `disableSelectedGlobalConfigs()` / `disableGlobalConfigsByIds()` | Disables selected records. |
| `deleteSelectedGlobalConfigs()` / `deleteGlobalConfigsByIds()` | Deletes selected records. |
| `deleteAllGlobalConfigs()` / `deleteGlobalConfig()` | Deletes all or selected keys. |
| `disableGlobalConfig()` | Disables selected keys. |
| `saveGlobalConfig()` | Persists a form payload for an optional existing record. |

Normalization, key selection, value serialization, and cache clearing are private implementation steps.

### `App\Supports\Services\CacheInvalidationService`

`flushAfterCommit()` schedules cache invalidation after a database commit; `flushNow()` performs the immediate internal invalidation. Use this service after mutations that affect cached runtime data.

### `App\Supports\Services\StorefrontCacheService`

`remember()` caches a storefront callback result for a configurable TTL. Use it for read-heavy public data and keep cache keys specific to locale/context.

### `App\Supports\Services\Images\ImageUrlBuilderService`

`url()` builds one responsive image URL and `multipleUrl()` builds all supported URL variants. The private pipeline validates the source, calculates aspect sizes, chooses output paths/formats, creates missing prototypes, and versions public assets.

### `App\Supports\Services\Currency\ConvertPrice`

`format()` formats a price for display; `convert()` converts between currency codes; `convertUsingExchangeRates()` converts with explicit exchange-rate data; `replaceCurrencySymbolToCode()` normalizes formatted currency text; `setDefaultCurrency()` changes the default currency context.

### `App\Supports\Services\Products\ProductSizeGuide` and `ProductsLimitService`

`ProductSizeGuide::parseSizeGuideTableRowsFromString()` parses tab- or semicolon-separated size-guide text into rows. `ProductsLimitService::getProductsCategoryLimit()` resolves the category product pagination limit with a configured fallback.

### `App\Supports\Services\RequestLookupContext`

Caches request-scoped lookup collections. `getActiveLanguages()`, `getLanguageByCode()`, and `getDefaultLanguage()` resolve languages; `getEnabledModuleDefinitions()` and `isEnabledSingletonModule()` resolve enabled modules; the `forget*()` methods clear those request caches.

### AI, translation, and SEO support

| Service | Public contract |
|---------|-----------------|
| `AiTranslationService` | `productName()`, `productDescription()`, and `productAttributeText()` translate product content through the configured AI/cache flow. |
| `OpenAiRateLimiterService` | `throttle()` applies the OpenAI request rate limit. |
| `ProductNameAiTranslatorService` | Finds/stores cached product-name translations through the shared translator base. |
| `ProductDescriptionAiTranslatorService` | Finds/stores cached product-description translations. |
| `ProductAttributeTextAiTranslatorService` | Finds/stores cached product-attribute translations. |
| `AiPromptHasherService`, `EnSeoSlugService`, `UaSeoSlugService` | Shared prompt hashing and language-specific slug-generation support; inspect their inherited/invokable contract before use. |

### `App\Services\Ai\OpenAiTranslatorService`

`translate()` sends a prompt through the configured OpenAI client with retry/rate-limit handling. `extractRetryAfterSeconds()` is private response parsing used by the retry policy.

## Cart services

### `App\Services\Cart\CartService`

Application facade for cart operations. `getSnapshot()` returns a complete cart payload; `addItem()`, `updateItem()`, `removeItem()`, and `clearCart()` mutate the cart; `getTotalProducts()` returns the item count. Availability is checked internally before adding/updating variants.

### `App\Services\Cart\CartSessionService`

Persists cart items for authenticated users and guests. `getItems()`, `addItem()`, `updateItem()`, `removeItem()`, `clearCart()`, and `getTotalProducts()` are the persistence API. Owner resolution, guest-to-user migration, attribute normalization, and signatures are private internals.

### `App\Services\Cart\CartViewDataBuilderService`

Transforms stored cart items into view/AJAX data. `build()` is the main entry point; it resolves names, attributes, images, variant/product URLs, totals, and mode-specific payloads. `collectCartData()` is the protected extension point for final aggregation.

### `App\Services\Cart\CartTotalsPipelineService` and cart modules

`calculate()` applies the configured totals callbacks and returns normalized totals. Delivery modules (`NovaPoshtaDeliveryModule`, `UkrPoshtaDeliveryModule`) and discount modules (`GiftCertificateModule`, `PromoCodeModule`) expose `resolveCallback()` and provide pipeline callbacks.

### `App\Services\Marketing\PromoCodeService`

`resolve()` loads a promo code by its normalized Unicode-safe code. `applyToTotals()` validates the active period, identity/usage limits, minimum order, product/category scope, discount mode, and currency fallback, then adds the discount line and metadata to cart totals. `validateAndCalculate()` exposes the calculation contract for server-side revalidation. `consume()` records one successful order usage after payment confirmation.

### `App\Services\Marketing\PromoCodePersistenceService`

`prepareForSave()` normalizes codes, validates uniqueness and the required default-currency discount, and separates Filament relationship data. `hydrateFormData()` maps relationships back into form state. `syncRelations()` persists selected users, groups, products, categories, currency discounts, and per-language error messages.

### `App\Services\Marketing\PromoCodeAdminOptionsService`

`currencyOptions()`, `userOptions()`, `userLabelById()`, and `userGroupOptions()` provide localized admin choices. `productSearchOptions()` and `categorySearchOptions()` search active catalog records across descriptions and return labels in the current admin language; the corresponding `*LabelById()` methods hydrate selected repeater values.

## Catalog and checkout services

### Catalog filters

| Service | Method | Responsibility |
|---------|--------|----------------|
| `CatalogFilterBootstrapService` | `bootstrapDefaultCategorySet()` | Loads or creates the default category filter set. |
| `CatalogFilterSetConfigurationService` | `update()` | Atomically normalizes and persists the canonical filter set, groups, and active-language translations. |
| `CatalogFilterIndexFreshnessService` | `markStale()` | Marks the active index as requiring a rebuild after an index-affecting mutation. |
| `CatalogFilterIndexRebuildService` | `rebuild()` | Rebuilds the filter index under a lock using configured price/discount policies. |
| `CatalogFilterIndexRebuildDispatcherService` | `dispatch()` | Runs a synchronous rebuild or dispatches the unique rebuild job according to configuration. |
| `FilterGroupGeneratorService` | `sync()` | Synchronizes system and attribute filter groups and translations. |
| `FilterValueGeneratorService` | `sync()` | Synchronizes attribute filter values, labels, and stable codes. |
| `PriceSourceResolverService` | `resolveEffectivePrice()` | Resolves effective product price according to the filter-set pricing mode. |
| `ProductCategorySyncService` | `syncWithRetry()` / `normalizeCategoryIds()` | Normalizes product categories and persists them with lock-retry handling. |

### Page settings

| Service | Public methods | Responsibility |
|---------|----------------|----------------|
| `CategoryPageFilterSyncService` | `sync()` | Synchronizes category page filter items and their payloads. |
| `PageSettingsBootstrapService` | `getCategorySettings()`, `getCategoryProductImageSize()`, `getCategoryAdminImageSettings()`, `getProductSettings()`, `getProductMinimumStockQuantity()`, `getProductEanMaxLength()`, `getProductUploadMaxSizeKb()`, `getProductImageUploadDirectory()`, `getProductNoImagePath()`, `getProductPreviewInListSize()`, `getProductPreviewInPageSize()`, `getSearchSettings()`, `getSearchProductsPerPageLimit()`, `getSearchProductImageSize()`, `getSearchNotFoundImageData()`, `bootstrapCategoryPageSetting()`, `bootstrapProductPageSetting()`, `bootstrapSearchPageSetting()` | Reads normalized category/product/search settings and creates/synchronizes missing default settings contracts. |

### Checkout

| Service | Public methods | Responsibility |
|---------|----------------|----------------|
| `CheckoutCitySearchService` | `searchCities()` | Searches, normalizes, merges, and sorts delivery cities. |
| `CheckoutBranchSearchService` | `loadBranches()` | Loads and normalizes branches/poshtomats for Nova Poshta or UkrPoshta. |
| `CheckoutSelectionStateService` | `getState()`, `replaceState()`, `setCity()`, `setDeliveryMethod()`, `setDeliveryAddress()`, `setDeliveryPoint()`, `clearDeliveryPoint()`, `clearDeliveryAddress()`, `clear()` | Owns normalized checkout delivery selection state. |
| `UpdateRatesService` | `handle()` | Updates configured currency exchange rates. |

## Storefront chrome

`HeaderService::__invoke()` builds header navigation, language, cart, and module context data. `FooterService::__invoke()` builds subscription, contacts, information, menu, and social data. Keep data assembly in these services rather than duplicating it in Blade layouts.

## Module services

| Service | Responsibility |
|---------|----------------|
| `ModuleCacheService` | `remember()` caches module data; `flush()` invalidates it; `buildKey()` and `getVersion()` support stable cache keys/versioning. |
| `ModuleDiscoveryService` | `discover()` finds module definitions. |
| `ModuleDefinitionSyncService` | `sync()` synchronizes discovered module definitions. |
| `ModuleClassResolverService` | `resolve()` maps a module definition and relative class name to a concrete class. |
| `ModuleInstanceFormSchemaResolverService` | `resolve()` returns the Filament schema for a module definition/instance. |
| `ModuleInstanceSettingsNormalizerService` | `normalizeForDefinition()` and `normalizeForInstance()` normalize settings before persistence. |
| `ModuleInstanceService` | `setGlobalState()`, `createFromDefinition()`, `duplicate()`, `update()`, `setInstanceState()`, `delete()` | Creates, duplicates, updates, enables/disables, and deletes module instances. |
| `ModuleProviderResolverService` | `resolveForStrategy()` selects providers for storefront/admin strategy and request context. |
| `ModuleProviderRegistrarService` | `register()` registers resolved provider classes. |
| `ModuleRuntimeResolverService` | `resolve()` returns runtime modules for placement/context. |
| `StorefrontModulePlacementResolverService` | `resolveForPlacement()` resolves storefront module definitions, settings, and data services for a placement. |

Module-specific delivery code belongs in `Modules/<Name>/Services/Filament` or `Modules/<Name>/Services/Storefront`; these application services coordinate discovery and runtime resolution.

## Order and payment services

### Creation and persistence

| Service | Public methods | Responsibility |
|---------|----------------|----------------|
| `OrderCreationService` | `validateFastOrderData()`, `createFastOrder()`, `validateOrderData()`, `createOrder()`, `validateSimpleOrderData()`, `createSimpleOrder()` | Validates checkout payloads and orchestrates fast, regular, and simple order creation. |
| `OrderAggregatePersistenceService` | `createSimpleOrder()` | Persists the order aggregate, customer, products, totals, shipping, and localized names for simple orders. |
| `OrderAdminPersistenceService` | `update()` | Updates an admin order and its customer, shipping, products, payments, totals, and audit history. |
| `OrderAdminDeliveryService` | `getDefaultDeliveryCost()`, `getCapabilities()`, `searchCities()`, `searchDeliveryPoints()`, `findCity()`, `findDeliveryPoint()` | Supplies delivery options and lookup data to admin order forms. |
| `OrderAdminOptionsService` | `getOrderStatusOptions()`, `getPaymentStatusOptions()`, `getStatusLabel()`, `getOrderTypeOptions()`, `getOrderTypeLabel()`, `getHistoryEventLabel()`, `getDeliveryMethodOptions()`, `getPaymentMethodOptions()`, `getUserGroupOptions()`, `getCurrencyOptions()`, `getCurrentLanguageId()` | Provides localized Filament options and labels. |

### Lifecycle, payment, and thank-you page

| Service | Public methods | Responsibility |
|---------|----------------|----------------|
| `OrderLifecycleService` | `getDefaultOrderStatus()`, `getDefaultPaymentStatus()`, `getPaymentStatusByCode()`, `getOrderStatusByCode()`, `transitionOrderForPayment()`, `transitionOrder()`, `transitionPayment()` | Applies validated order/payment state transitions and records history. |
| `OrderStatusManagementService` | `create()`, `update()`, `activate()`, `deactivate()`, `delete()` | CRUD and activation state for order statuses. |
| `PaymentStatusManagementService` | `create()`, `update()`, `activate()`, `deactivate()`, `delete()` | CRUD and activation state for payment statuses. |
| `CashOnDeliveryPaymentModule` / `WayForPayPaymentModule` | `process()` | Processes the corresponding payment payload and returns a normalized result. |
| `ThankYouOrderDataService` | `getByOrderNumber()` | Loads the current public order by number and maps products, attributes, delivery, and totals for the thank-you page. |

## Traits

`CartTrait` exposes `store()`, `update()`, and `delete()` controller actions backed by cart services; it builds mutation responses and resolves cart mode internally. `SocialServiceTrait::normalizeSocialUrl()` normalizes social links for the requested locale.

## Extension rules

- Inject services into controllers, Livewire components, or Filament pages instead of resolving them repeatedly inside views.
- Keep public methods focused on one application responsibility and return typed payloads.
- Reuse `Supports` services only for stable cross-domain behavior; domain-specific workflows belong in `Services` or `Actions`.
- For module code, keep admin and storefront entry points separated under `Services/Filament` and `Services/Storefront`.
- Before adding a new helper or service, search this reference and the source directory for an existing contract.

## See Also

- [Helpers Reference](helpers-reference.md) — global helper contracts and URL generation.
- [Architecture](architecture.md) — service boundaries and application flow.
- [Admin Panel](admin-panel.md) — Filament entry points that consume services.
- [Catalog Storefront](catalog-storefront.md) — storefront controllers and routes.
