# NovaPoshta Module

This module provides Nova Poshta integration for the Syvak application.
It is intentionally implemented as a **singleton module**: there is one shared configuration set for the whole app, and it does not support creating module instances.

## Navigation

- [Back to project README](../../../../README.md)

## What the module does

- stores and reads Nova Poshta data in dedicated tables:
  - `nova_poshta_regions`
  - `nova_poshta_cities`
  - `nova_poshta_post_offices`
  - `nova_poshta_poshtomats`
- synchronizes that data from the Nova Poshta API in Ukrainian
- exposes admin tools for:
  - editing the API key
  - starting sync/import jobs
  - watching sync progress
- exposes storefront/AJAX endpoints for checkout selection
- renders customer-facing HTML fragments for regions, cities, post offices, and poshtomats

## Important module rules

1. Do not add module instances for NovaPoshta.
   - `can_create_instances` is `false`
   - the module is managed as a single global configuration source
2. The API key must be read from global configs.
   - key: `novaposhta.api_key`
   - there is no fallback key
   - missing key should be treated as a critical configuration error
3. Storefront views must stay in `resources/views/storefront/`.
4. Admin-only page logic belongs in the module Filament namespace.
5. Keep data language in Ukrainian when reading, storing, and rendering Nova Poshta directory values.

## Admin flow

The main admin page is:

- `app/Filament/Pages/NovaPoshtaSyncPage.php`

That page:

- shows the current API key input
- saves the API key to global configs
- starts the queued sync flow
- polls the current sync state and progress
- shows the current database counts and last sync summary

The page does not use a separate Blade layout for the admin screen. It is built through Filament schema components.

## Sync flow

The sync service is:

- `app/Services/NovaPoshtaSyncService.php`

The service supports two styles of import:

1. Full immediate import
   - `syncAll()`
   - used by tests and low-level service calls
2. Queued step-by-step import
   - `startQueuedSync()`
   - `processQueuedSyncStep()`
   - state is stored in cache
   - the page polls and continues until completion

The queued flow processes stages in this order:

1. regions
2. cities
3. post offices
4. poshtomats

Each stage can:

- fetch one API page at a time
- buffer rows in cache
- finalize the table rebuild only after all pages for that stage are received

## API integration

The API client is:

- `app/Services/NovaPoshtaApiService.php`

It calls the Nova Poshta JSON API and normalizes the response shape before the sync service processes it.

Key points:

- language is forced to Ukrainian
- the API key is read from global configs through `NovaPoshtaConfig`
- API warnings/errors should fail the request
- do not silently ignore invalid payloads

## Data model

The module uses these Eloquent models:

- `NovaPoshtaRegion`
- `NovaPoshtaCity`
- `NovaPoshtaPostOffice`
- `NovaPoshtaPoshtomat`

The sync logic rebuilds tables in full:

- regions are replaced after a successful regions fetch
- cities are replaced after all city pages are collected
- post offices are replaced after all warehouse pages are collected
- poshtomats are replaced after all warehouse pages are collected

## Storefront flow

Customer-facing data is handled by:

- `app/Services/NovaPoshtaCheckoutDataService.php`
- `app/Support/NovaPoshtaCheckoutStateService.php`
- `app/Services/NovaPoshtaModuleDataService.php`
- `app/Http/Controllers/NovaPoshtaController.php`

The storefront side:

- loads already saved checkout state from session/cache
- renders HTML fragments for AJAX responses
- returns regions, cities, post offices, and poshtomats as localized HTML

## Files to treat with care

- `config/config.php`
  - module runtime config
  - keep API settings sourced from global configs
- `app/Support/NovaPoshtaConfig.php`
  - central config reader
  - missing API key must remain a hard failure
- `app/Filament/Pages/NovaPoshtaSyncPage.php`
  - admin sync UI and API key input
- `app/Services/NovaPoshtaSyncService.php`
  - chunked sync state machine
- `routes/web.php`
  - storefront/AJAX endpoints

## Maintenance checklist

Before changing the module, verify:

1. The API key still comes from global configs.
2. The module still stays singleton-only.
3. The sync page still uses dynamic Filament schema components.
4. The storefront views remain separate from admin views.
5. The sync service still rebuilds tables only after full stage collection.
6. New fields or sync stages are covered by tests.

## Related docs

- [Project architecture](../../docs/architecture.md)
- [Admin panel guide](../../docs/admin-panel.md)
- [Modules loading guide](../../Modules/README.md)
- [ architecture rules](../../../..//ARCHITECTURE.md)
