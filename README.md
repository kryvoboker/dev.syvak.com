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
- **Quality tooling** with Larastan, Pint, PHPUnit, Biome, and TypeScript type checking.

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

| Guide                                                          | Description |
|----------------------------------------------------------------|-------------|
| [Getting Started](app-code/app/docs/getting-started.md)        | Installation, setup, and first run |
| [Architecture](app-code/app/docs/architecture.md)              | Modular monolith rules and boundaries |
| [Configuration](app-code/app/docs/configuration.md)            | Environment variables and config strategy |
| [Catalog Storefront](app-code/app/docs/catalog-storefront.md)  | Storefront routes, filters, sorting, product pages |
| [Admin Panel](app-code/app/docs/admin-panel.md)                | Filament resources and admin workflows |
| [Modules Guide](app-code/app/Modules/README.md)                | Module registration, loading, and singleton module rules |
| [OpenAPI Entry](app-code/app/docs/openapi/openapi.yaml)         | Root Swagger/OpenAPI entrypoint for module API docs |
| [Testing](app-code/app/docs/testing.md)                        | PHPUnit, Larastan, Pint, Biome, and TypeScript workflows |
| [Deployment](app-code/app/docs/deployment.md)                  | Docker-based deployment and production checklist |

## License

MIT
