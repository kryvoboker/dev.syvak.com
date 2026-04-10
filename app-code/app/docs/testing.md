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
vendor/bin/phpstan analyse --memory-limit=1G

# Formatting
vendor/bin/pint --dirty --format=agent
```

## Recommended Change Validation

1. Run focused tests for the changed feature.
2. Run `phpstan` for touched areas/files.
3. Run `pint` before commit.
4. Optionally run full suite before release.

## See Also

- [Getting Started](getting-started.md) — local environment setup.
- [Catalog Storefront](catalog-storefront.md) — storefront behavior to verify.
- [Admin Panel](admin-panel.md) — admin scenarios to cover.
