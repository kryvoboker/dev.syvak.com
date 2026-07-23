[← Admin Panel](admin-panel.md) · [Back to README](../README.md) · [Services Reference →](services-reference.md)

# Helper Functions Reference

Global helpers are defined in [`app/Supports/helpers.php`](../app-code/app/app/Supports/helpers.php) and are loaded by Laravel. Use an existing helper before adding an equivalent function. Helpers should remain small, framework-friendly entry points; domain workflows belong in an Action or Service.

## How to choose a helper

| Need | Helper group |
|------|--------------|
| Localized URLs, locales, slugs | `localized_route()`, `normalize_locale()`, `resolve_language_by_locale()`, slug helpers |
| Product variant URLs | `localized_product_variant_route()` |
| Catalog filters | `prepare_product_attrs()`, `get_sorting_items()`, `resolve_sort_code()` |
| Prices and currencies | `convert_price()`, `format_price()`, `replace_currency_symbol_to_code()` |
| Images and uploads | `convert_img_and_get_url()`, `multiple_convert_img_and_get_url()`, upload path helpers |
| Global configuration | `get_global_config()`, `set_global_config()`, related CRUD helpers |
| Page/module context | `try_detect_page_type()`, `resolve_modules_for_context()`, `is_enabled_singleton_module()` |

## Localization and storefront URLs

| Function | Responsibility |
|----------|----------------|
| `localized_route(BackedEnum|string $route, array $parameters = [], bool $absolute = true): string` | Generates a route URL with the current/active locale. Use named routes and pass route parameters by name. |
| `localized_product_variant_route(string $product_slug, int $product_id, array $attribute_filters = [], bool $absolute = true): string` | Resolves the matching active-language variant from product attributes and returns the variant URL. Falls back to the product URL with normalized attribute query parameters when no variant slug exists. |
| `normalize_locale(?string $locale, bool $is_get_new_instance = false): string` | Normalizes a locale against allowed application languages. |
| `resolve_language_by_locale(string $locale, bool $is_get_new_instance = false): ?Language` | Returns the language model for a locale, using the request lookup context/cache when possible. |
| `get_allowed_locales(bool $is_get_new_instance = false): array` | Returns locales available for localized routes. |
| `breadcrumb(string $title, ?string $url = null): array` | Creates the standard breadcrumb item payload. |
| `get_slug_variants(?string $sluggable_type, ?string $slug_value = null, ?string $variant_slug_value = null, array $attribute_filters = []): array` | Builds localized slug variants for language switching. |
| `resolve_product_variant_slug_variants(string $slug_value, ?string $variant_slug_value, array $language_ids_by_code, array $attribute_filters, Slug $slug_instance): array` | Resolves product and variant slug pairs used by localized product links. |
| `resolve_product_variant_id_for_slug_variants(int $product_id, ?string $variant_slug_value, array $attribute_filters, Slug $slug_instance): int` | Finds the variant ID represented by a product/variant slug pair or attribute selection. |

### Product and variant URL contract

The public product routes are:

```php
localized.catalog.product.show          // /{locale}/product/{slug}
localized.catalog.product.variant.show  // /{locale}/product/{slug}/{variant_slug}
```

For a known variant, prefer the variant route and provide the product slug plus the variant's localized slug. Do not construct the path by concatenating IDs. `localized_product_variant_route()` is the preferred helper when the link starts from attribute selections rather than a known variant slug.

## Catalog, pages, and modules

| Function | Responsibility |
|----------|----------------|
| `try_detect_page_type(?Request $request = null): ?string` | Detects the current page type from the request/route context. |
| `prepare_product_attrs(array $attribute_filters): array` | Normalizes product attribute filter input into attribute IDs and value ID arrays. |
| `get_page_settings(PageSetting $page_setting): array` | Converts a page setting model into the normalized settings payload used by views/services. |
| `get_sorting_items(PageSetting $page_setting): Collection` | Returns configured sorting options for a page setting. |
| `resolve_sort_code(PageSetting $page_setting, string $sort_value): string` | Resolves and validates a requested sorting value against page settings. |
| `resolve_modules_for_context(?string $placement = null, ?string $context_key = null): Collection` | Returns runtime modules for a placement/context. |
| `is_enabled_singleton_module(string $module_name): bool` | Checks whether a singleton module is enabled. |
| `get_app_settings(): ?AppSettingsData` | Returns cached application settings data. |
| `get_global_config(string $key, mixed $default = null): mixed` | Reads one active global configuration value. |
| `get_global_configs(): Collection` | Returns global configuration records. |
| `set_global_config(array|string $key, mixed $value = null, bool $is_active = true): mixed` | Creates or updates one or more global configuration values. |
| `delete_global_config(array|string $key): int` | Deletes selected global configuration keys. |
| `disable_global_config(array|string $key): int` | Disables selected global configuration keys without deleting them. |

Global configuration helpers delegate to `GlobalConfigService`; use the service when a workflow needs several operations or explicit dependency injection.

## Images and uploads

| Function | Responsibility |
|----------|----------------|
| `convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): string` | Converts/resizes one image and returns its public URL. |
| `multiple_convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): array` | Returns the generated image URL variants for supported client formats. |
| `sanitaze_url(?string $url): string` | Normalizes/sanitizes a URL before it is stored or rendered. |
| `normalize_upload_path_template(?string $path): string` | Normalizes an upload path template. |
| `resolve_upload_path_placeholders(?string $path): string` | Replaces supported upload path placeholders with runtime values. |

## Text, HTML, and scalar values

| Function | Responsibility |
|----------|----------------|
| `clear_telephone(?string $telephone, bool $is_delete_first_nums = false): string` | Removes unsupported telephone characters and optionally strips leading digits. |
| `parse_telephone(string $telephone): string` | Parses a telephone value into the application's display/storage format. |
| `trim_strs_in_arr(array $arr): array` | Trims string values in an array while preserving non-string values. |
| `sanitaze_str(?string $string): string` | Normalizes a string for safe application use. |
| `decode_html_entities(?string $string): string` | Decodes HTML entities using the application's safe encoding settings. |
| `escape_special_html(?string $html_string): string` | Escapes special HTML content for output. |
| `str_more_or_equal_length(?string $string, ?int $length): bool` | Checks whether a string reaches a minimum length. |
| `num_more_or_equal_num(mixed $num, ?int $num_for_comparison): bool` | Checks whether a numeric value reaches a minimum value. |
| `get_now_date(?string $time_zone = null): Carbon\|CarbonInterface` | Returns the current date/time for the requested or application timezone. |

## Prices and currency

| Function | Responsibility |
|----------|----------------|
| `convert_price(float $price, string $code_from, string $code_to): float` | Converts a price between configured currencies. |
| `format_price(float|int $price, ?string $currency_code = null, float|int $exchange_rate = 0, bool $is_formatting = true): string|float` | Applies currency conversion/formatting for storefront or admin display. |
| `replace_currency_symbol_to_code(string $price_string, ?string $currency_symbol = null, ?string $currency_code = null): string` | Replaces a currency symbol in formatted text with a currency code. |

## Product-specific support helpers

| Class/method | Responsibility |
|--------------|----------------|
| `ProductSizeGuide::parseSizeGuideTableRowsFromString(string $table_raw): array` | Parses tab- or semicolon-separated size-guide text into rows and trimmed cells. |
| `ProductsLimitService::getProductsCategoryLimit(array $page_setting_settings): int` | Reads the category pagination limit from page settings with a configured fallback and minimum of one. |

## Notes for AI and developers

- PHP helper variables follow the project snake_case convention.
- Use `Str::trim()`, `Str::ltrim()`, and `Str::rtrim()` in new code, as required by project conventions.
- Keep database reads and cache behavior inside the existing helper/service boundary; do not duplicate slug, locale, URL, or image logic in controllers and Blade views.
- When a helper delegates to a service, inspect the service contract before changing the helper behavior.

## See Also

- [Services Reference](services-reference.md) — application and support service contracts.
- [Catalog Storefront](catalog-storefront.md) — routes and storefront data flow.
- [Architecture](architecture.md) — layer boundaries and dependency rules.
