# Syvak

> Laravel 13 storefront + Filament 5 admin for multilingual product catalog management.

Syvak is an e-commerce-oriented application with two main surfaces: a public catalog (categories, filters, search, product pages) and an admin panel for managing products, categories, attributes, page settings, and localization.

## Quick Start

```bash
cd app-code/app
composer install
npm install
cp .env.local .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

## Key Features

- **Catalog storefront** with category pages, filtering, sorting, and product detail pages.
- **Filament admin** for products, categories, filters, settings, and localized content.
- **Multilingual content** with dynamic locale handling and DB-backed language data.
- **SEO-aware routing** for category/product slugs and variant slugs.
- **Quality tooling** with Larastan, Pint, and PHPUnit.

## Example

```bash
# Category page (localized)
GET /uk/category/{slug}

# Product page
GET /uk/product/{product-slug}
GET /uk/product/{product-slug}/{variant-slug}

# Category AJAX helpers
GET /uk/category/{slug}/filters
GET /uk/category/{slug}/load-more
```

---

## Documentation

| Guide | Description |
|-------|-------------|
| [Getting Started](docs/getting-started.md) | Installation, setup, and first run |
| [Architecture](docs/architecture.md) | Modular monolith rules and boundaries |
| [Configuration](docs/configuration.md) | Environment variables and config strategy |
| [Catalog Storefront](docs/catalog-storefront.md) | Storefront routes, filters, sorting, product pages |
| [Admin Panel](docs/admin-panel.md) | Filament resources and admin workflows |
| [Testing](docs/testing.md) | PHPUnit, Larastan, Pint workflows |
| [Deployment](docs/deployment.md) | Docker-based deployment and production checklist |

## License

MIT
