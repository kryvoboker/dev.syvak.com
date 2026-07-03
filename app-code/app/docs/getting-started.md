[Back to README](../../../README.md) · [Architecture →](architecture.md)

# Getting Started

## Prerequisites

- PHP `8.5+`
- Composer `2+`
- Node.js `25.6.1+`
- npm `10+`
- MariaDB `10.11+` (or Docker dev stack)

## Installation

```bash
cd app-code/app
composer install
npm install
cp .env.local .env
php artisan key:generate
php artisan migrate
```

## Run Development Environment

```bash
# terminal 1
php artisan serve

# terminal 2
npm run dev
```

Alternative via Docker from project root:

```bash
make up-dev
```

## First Verification

```bash
php artisan test --compact
vendor/bin/pint --dirty --format=agent
npm run ts:check
```

Expected: tests run successfully, Pint returns pass/fixed output, and TypeScript checks pass.

## See Also

- [Project README](../../../README.md) — project landing page and navigation hub.
- [Architecture](architecture.md) — module boundaries and dependency rules.
- [Configuration](configuration.md) — env and config details.
- [Testing](testing.md) — test/static analysis workflow.
