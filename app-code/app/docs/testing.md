[← Admin Panel](admin-panel.md) · [Back to README](../README.md) · [Deployment →](deployment.md)

# Testing

## Test Stack

- PHPUnit `v12` (feature and unit tests)
- Larastan `v3` (static analysis)
- Pint `v1` (formatting)

## Common Commands

```bash
# Full tests
php artisan test --compact

# Single file
php artisan test --compact tests/Feature/ExampleTest.php

# Filter by test name
php artisan test --compact --filter=testName

# Static analysis
composer phpstan

# Formatting
composer pint

# Code style checks
composer phpcs

# Code style fixer
composer phpcs:fix

# Design smell checks
composer phpmd
```

## Recommended Change Validation

1. Run focused tests for the changed feature.
2. Run `composer phpstan` for touched areas/files.
3. Run `composer pint` before commit.
4. Run `composer phpcs` when you need PHPCS warnings checked.
5. Run `composer phpmd` when you need PHPMD violations checked.
6. Optionally run full suite before release.

## Tooling Notes

- The Laravel app root is `app-code/app`; run Composer quality scripts from there.
- The Docker app root is `/var/webroot/sites/syvak.com/app` when you execute commands inside the PHP container.
- The fixer script is `composer phpcs:fix`; `phpcs:fx` is not defined in this project.
- PHPCS checks `PSR12`, `Generic.Files.LineLength`, and `phpcs/ProjectStandard`.
- PHPMD uses `rulesets/unusedcode.xml`.
- PHPStan uses Larastan with `level: 5` and scans `app`, `Modules`, `routes`, `config`, `database`, and `tests`.
- Recommended cleanup order is `composer pint` -> `composer phpcs` -> `composer phpcs:fix` if needed -> `composer phpmd` -> `composer phpstan`.

## See Also

- [Getting Started](getting-started.md) — local environment setup.
- [Catalog Storefront](catalog-storefront.md) — storefront behavior to verify.
- [Admin Panel](admin-panel.md) — admin scenarios to cover.
