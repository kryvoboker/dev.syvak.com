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

## API and sync model

The Nova Poshta client talks to the public JSON API:

- base URL: `https://api.novaposhta.ua/v2.0/json/`
- transport: `POST`
- payload format: JSON

Every request sends:

- `apiKey`
- `modelName`
- `calledMethod`
- `methodProperties`

For directory sync the module uses:

- `modelName = AddressGeneral`
- `calledMethod = getSettlementAreas`
- `calledMethod = getSettlements`
- `calledMethod = getWarehouses`

Additional request properties:

- `Language = UA`
- `Page`
- `Limit`

The response is normalized from:

- `success`
- `data`
- `info`

Warnings and errors from the upstream payload are treated as failures and are not ignored.

Upstream integration documentation:

- `docs/openapi-upstream.yaml`

## Important module rules

1. Do not add module instances for NovaPoshta.
   - `can_create_instances` is `false`
   - the module is managed as a single global configuration source
2. The API key must be read from global configs.
   - key: `novaposhta.api_key`
   - there is no fallback key
   - missing key should be treated as a critical configuration error
3. Storefront views must stay in `resources/views/storefront/`.
4. Translations must stay in `resources/lang/`.
5. Tests must stay in `tests/`.
6. Admin-only page logic belongs in the module Filament namespace.
7. Keep data language in Ukrainian when reading, storing, and rendering Nova Poshta directory values.
8. Do not replace `delete()` with `truncate()` on synced tables.
   - the module has foreign key relations
   - `TRUNCATE` is unsafe here and can break the sync flow
   - the current implementation intentionally clears data with `delete()` before inserting fresh rows

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

The sync UI has two data sources:

- the live loader block reads the current queued sync state from cache and updates via polling
- the summary table reads the current database counts, so the displayed values are the actual rows stored in the database, not remote totals
- the sync service also keeps a cached last-summary snapshot in `nova_poshta.last_sync_summary`, but the admin summary table intentionally does not trust that cache for final counts

During sync, the loader block shows:

- current stage
- current phase
- processed rows in the current stage
- a spinner/loader so the admin can see that work is still running

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

Important behavior:

- the sync works in chunks so the PHP process does not keep all upstream data in memory at once
- recoverable stage errors are logged and the sync continues with the next chunk or the next dataset
- the queued state lives in cache and should be cleared when the run is stopped or completes
- the live counters in the admin loader are stage counters, not upstream total counters
- if the sync was interrupted previously, stale cache state can survive until cleanup, so the admin page should rely on the current database counts for final numbers
- `syncAll()` is resilient and keeps going even if one stage fails, so a single dataset error does not block the rest of the sync

## API integration

The API client is:

- `app/Services/NovaPoshtaApiService.php`

It calls the Nova Poshta JSON API and normalizes the response shape before the sync service processes it.

Key points:

- language is forced to Ukrainian
- the API key is read from global configs through `NovaPoshtaConfig`
- API warnings/errors should fail the request
- do not silently ignore invalid payloads
- the client sends JSON payloads and paginated requests for cities and warehouses

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

The module intentionally uses `delete()` before re-inserting rows because the tables are related through foreign keys and `TRUNCATE` would be unsafe.

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

Public storefront endpoints:

- `GET /novaposhta/state`
- `GET /novaposhta/regions`
- `GET /novaposhta/cities?region_ref=...`
- `GET /novaposhta/post-offices?city_ref=...`
- `GET /novaposhta/poshtomats?city_ref=...`
- `POST /novaposhta/selection`

All data endpoints return JSON with:

- `items`
- `html`

The selection endpoint stores the current checkout choice in the module checkout state service.

## Tests and translations

This module owns its tests and translations.

- tests:
  - `tests/Feature/NovaPoshtaModuleSyncServiceTest.php`
  - `tests/Unit/`
- translations:
  - `resources/lang/en/admin/modules/nova_poshta.php`
  - `resources/lang/uk/admin/modules/nova_poshta.php`

Keep module-specific tests and translation files inside the module instead of moving them back to the app root.

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
- `resources/lang/`
  - module translation files
- `tests/`
  - module test suite

## Maintenance checklist

Before changing the module, verify:

1. The API key still comes from global configs.
2. The module still stays singleton-only.
3. The sync page still uses dynamic Filament schema components.
4. The storefront views remain separate from admin views.
5. The sync service still rebuilds tables only after full stage collection.
6. New fields or sync stages are covered by module tests.

## Pitfalls to remember

- Do not add remote "total count" calls just to paint the admin progress UI. The current design uses the real loaded database counts and stage counters.
- Do not switch the sync to a single long-running request if the stage can be processed in chunks.
- Do not convert the queue state into an in-memory-only flow; the admin page depends on cached sync state for polling.
- Do not treat a single failed city/warehouse request as a global stop condition unless the sync service explicitly marks it as failed.
- Do not change the request language away from Ukrainian when reading or storing directory values.
- Keep the `AddressGeneral` calls paginated; cities and warehouse imports depend on `Page` and `Limit`.

## Related docs

- [Project architecture](../../docs/architecture.md)
- [Admin panel guide](../../docs/admin-panel.md)
- [Modules loading guide](../../Modules/README.md)
- [ architecture rules](../../../..//ARCHITECTURE.md)
