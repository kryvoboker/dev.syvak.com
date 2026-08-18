[← Services Reference](services-reference.md) · [Back to README](../README.md) · [Deployment →](deployment.md)

# Testing

## Test Stack

- PHPUnit `v12` (feature and unit tests)
- Larastan `v3` (static analysis)
- Pint `v1` (formatting)
- Biome `v2` (TypeScript formatting and linting)
- TypeScript `v6` (`tsc --noEmit` type checking)
- Stylelint `v17` (CSS quality and custom-property validation)

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

# Psalm production checks
composer psalm:production:storefront
composer psalm:production:admin
composer psalm:production

# Diagnostic strict audit (not a production gate)
composer psalm:production:strict

# TypeScript quality
npm run ts:check
npm run ts:fix
npm run ts:format
npm run ts:lint
npm run ts:typecheck

# CSS quality
npm run css:lint
npm run css:lint-fix
```

## Recommended Change Validation

1. Run focused tests for the changed feature.
2. Run `composer phpstan` for touched areas/files.
3. Run `composer pint` before commit.
4. Run `composer phpcs` when you need PHPCS warnings checked.
5. Run `composer phpmd` when you need PHPMD violations checked.
6. Run `npm run ts:check` for the storefront TypeScript and module TypeScript tree.
7. Run `npm run css:lint` for storefront and module CSS files.
8. Use `npm run css:lint-fix` to apply Stylelint's safe CSS fixes, then review the diff.
9. Optionally run the full suite before release.

## Module Test Placement

- Tests for a module should live inside that module, next to the module code.
- Use the module test tree for module-specific coverage, for example:
  - `Modules/UkrPoshta/tests/Feature/...`
  - `Modules/NovaPoshta/tests/Feature/...`
- Keep shared application tests in the main app test tree only when the behavior is truly shared across modules or the core app.
- When you add or change module behavior, update the module test files in the module itself.

## Tooling Notes

- The Laravel app root is `app-code/app`; run Composer quality scripts from there.
- The Docker app root is `/var/webroot/sites/syvak.com/app` when you execute commands inside the PHP container.
- The fixer script is `composer phpcs:fix`; `phpcs:fx` is not defined in this project.
- PHPCS checks `PSR12`, `Generic.Files.LineLength`, and `phpcs/ProjectStandard`.
- PHPMD uses `rulesets/unusedcode.xml`.
- PHPStan uses Larastan with `level: 5` and scans `app`, `Modules`, `routes`, `config`, `database`, and `tests`.
- Psalm production checks are split between `psalm-production.xml` for storefront/backend code and `psalm-production.admin.xml` for Filament/Livewire code. The strict Psalm configuration is an audit tool, not a required production gate, because Laravel's dynamic APIs produce framework-related diagnostics.
- TypeScript quality uses `Biome` for format/lint and `tsc --noEmit` for type checking.
- The `ts:check` script runs `biome check .` and `tsc --noEmit -p tsconfig.json` together.
- The `ts:fix` script runs `biome check --write .` to auto-fix TypeScript formatting and safe lint issues.
- CSS quality uses `Stylelint` with `stylelint-config-standard` for style consistency and `no-unknown-custom-properties` for CSS variable validation.
- The `css:lint` script checks CSS in `resources/assets/catalog/css/**/*.css` and `Modules/**/resources/assets/**/*.css`.
- The `css:lint-fix` script runs the same checks with `--fix`; review its changes before committing.
- CSS variables declared in project CSS are loaded through `referenceFiles`; variables created only in TypeScript, JavaScript, or HTML are not statically discoverable by Stylelint.
- Recommended cleanup order is `composer pint` -> `composer phpcs` -> `composer phpcs:fix` if needed -> `composer phpmd` -> `composer phpstan`.
- Recommended frontend cleanup order is `npm run ts:fix` -> `npm run ts:check` -> `npm run css:lint` -> `npm run css:lint-fix` when fixes are needed -> `npm run ts:typecheck` when you need to isolate type-only failures.

## See Also

- [Project README](../README.md) — project landing page and navigation hub.
- [Getting Started](getting-started.md) — local environment setup.
- [Catalog Storefront](catalog-storefront.md) — storefront behavior to verify.
- [Admin Panel](admin-panel.md) — admin scenarios to cover.
- [Modules Guide](../app-code/app/Modules/README.md) — module loading and singleton-module rules to validate.
- [NovaPoshta Module](../app-code/app/Modules/NovaPoshta/README.md) — module-specific sync and API-key flows to test.
- [UkrPoshta Module](../app-code/app/Modules/UkrPoshta/README.md) — module-specific sync and API-key flows to test.
