[← Architecture](architecture.md) · [Back to README](../../../README.md) · [Catalog Storefront →](catalog-storefront.md)

# Configuration

## Environment Strategy

- Source of truth for local setup: `.env.local`
- Runtime file used by Laravel: `.env`
- Config access in code: `config(...)` (not `env(...)` outside `config/*`)

## Important Areas

- **Localization**: locale prefix in routes, active languages may come from DB with config fallback
- **Catalog behavior**: stock thresholds, pagination, filters, sorting setup
- **Media settings**: image sizes, upload paths, and fallback image paths
- **App services**: AI/OpenAI settings, contact settings, user-facing app options

## Frontend Build Config

- `vite.config.ts` — Vite build pipeline
- `tailwind.config.js` — Tailwind config
- `package.json` scripts:
  - `npm run dev`
  - `npm run build`

## PHP/Laravel Config

- `config/*.php` — application configuration contracts
- `composer.json` scripts include setup/dev/test commands

## See Also

- [Project README](../../../README.md) — project landing page and navigation hub.
- [Getting Started](getting-started.md) — local setup commands.
- [Deployment](deployment.md) — production runtime notes.
- [Admin Panel](admin-panel.md) — where many settings are managed in UI.
