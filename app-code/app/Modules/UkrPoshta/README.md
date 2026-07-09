# UkrPoshta Module

This module provides Ukr Poshta integration for the Syvak application.
It is intentionally implemented as a **singleton module**: there is one shared configuration set for the whole app, and it does not support creating module instances.

## Navigation

- [Back to project README](../../../../README.md)

## What the module does

- stores and reads Ukr Poshta data in dedicated tables:
  - `ukr_poshta_regions`
  - `ukr_poshta_districts`
  - `ukr_poshta_cities`
  - `ukr_poshta_post_offices`
- synchronizes that data from the Ukr Poshta API
- exposes admin tools for:
  - editing the API key
  - editing delivery cost settings
  - starting sync/import jobs
  - watching sync progress
- exposes storefront/AJAX endpoints for checkout selection
- renders customer-facing HTML fragments for regions, districts, cities, and post offices

## API and sync model

The Ukr Poshta client talks to the public address-classifier service:

- base URL: `https://www.ukrposhta.ua/address-classifier-ws/`
- transport: `GET`
- auth: bearer token in the `Authorization` header
- request delay: the client intentionally waits before every request to avoid hammering the upstream API

The sync service calls these endpoints:

- `get_regions_by_region_ua`
- `get_districts_by_region_id_and_district_ua`
- `get_city_by_region_id_and_district_id_and_city_ua`
- `get_postoffices_by_postindex`

Request data that is actually sent:

- `region_id` for district lookups
- `district_id` for city lookups
- `pdDistrictId` for post office lookups

The response is normalized from:

- `Entries.Entry`
- fallback `data`

Warnings and errors from the upstream payload are treated as failures and are not ignored.

Upstream integration documentation:

- `docs/openapi-upstream.yaml`

## Important module rules

1. Do not add module instances for UkrPoshta.
   - `can_create_instances` is `false`
   - the module is managed as a single global configuration source
2. The API key must be read from global configs.
   - key: `ukr_poshta.api_key`
   - there is no fallback key
   - missing key should be treated as a critical configuration error
3. Storefront views must stay in `resources/views/storefront/`.
4. Admin-only page logic belongs in the module Filament namespace.
5. Keep data language in Ukrainian when reading, storing, and rendering Ukr Poshta directory values.
6. Tests, language files, module services, and sync logic should stay inside the module structure.
   - tests live in `Modules/UkrPoshta/tests/`
   - translations live in `Modules/UkrPoshta/resources/lang/`
   - `Modules/UkrPoshta/app/Providers/UkrPoshtaServiceProvider.php` registers translations from that path
   - do not move these concerns back into the shared `app/` tree unless the module architecture changes
7. Do not replace `delete()` with `truncate()` on synced tables.
   - the module has foreign key relations
   - `TRUNCATE` is unsafe here and can break the sync flow
   - the current implementation intentionally clears data with `delete()` before inserting fresh rows

## Admin flow

The main admin page is:

- `app/Filament/Pages/UkrPoshtaSyncPage.php`

That page:

- shows the current API key and delivery settings
- saves module-wide settings to global configs
- starts the queued sync flow
- polls the current sync state and progress
- shows the current database counts and last sync summary

The page does not use a separate Blade layout for the admin screen. It is built through Filament schema components.

The sync UI has two data sources:

- the live loader block reads the current queued sync state from cache and updates via polling
- the summary table reads the current database counts, so the displayed values are the actual rows stored in the database, not remote totals
- the sync service also keeps a cached last-summary snapshot in `ukr_poshta.last_sync_summary`, but the admin summary table intentionally does not trust that cache for final counts

During sync, the loader block shows:

- current stage
- current phase
- processed rows in the current stage
- a spinner/loader so the admin can see that work is still running

## Sync flow

The sync service is:

- `app/Services/UkrPoshtaSyncService.php`

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
2. districts
3. cities
4. post offices

Each stage can:

- fetch one API page at a time
- buffer rows in cache
- finalize the table rebuild only after all pages for that stage are received

Important behavior:

- the sync works in chunks so the PHP process does not keep all upstream data in memory at once
- recoverable batch errors are logged and the sync continues with the next chunk
- the queued state lives in cache and should be cleared when the run is stopped or completes
- the live counters in the admin loader are stage counters, not upstream total counters
- if the sync was interrupted previously, stale cache state can survive until cleanup, so the admin page should rely on the current database counts for final numbers

## API integration

The API client is:

- `app/Services/UkrPoshtaApiService.php`

It calls the Ukr Poshta API and normalizes the response shape before the sync service processes it.

Key points:

- language is forced to Ukrainian
- the API key is read from global configs through `UkrPoshtaConfig`
- API warnings/errors should fail the request
- do not silently ignore invalid payloads
- the client makes one request per entity stage, with the stage parameters passed as query string values

## Data model

The module uses these Eloquent models:

- `UkrPoshtaRegion`
- `UkrPoshtaDistrict`
- `UkrPoshtaCity`
- `UkrPoshtaPostOffice`

The sync logic rebuilds tables in full:

- regions are replaced after a successful regions fetch
- districts are replaced after all district pages are collected
- cities are replaced after all city pages are collected
- post offices are replaced after all post office pages are collected

The module intentionally uses `delete()` before re-inserting rows because the tables are related through foreign keys and `TRUNCATE` would be unsafe.

## Storefront flow

Customer-facing data is handled by:

- `app/Services/UkrPoshtaCheckoutDataService.php`
- `app/Support/UkrPoshtaCheckoutStateService.php`
- `app/Services/UkrPoshtaModuleDataService.php`
- `app/Http/Controllers/UkrPoshtaController.php`

The storefront side:

- loads already saved checkout state from session/cache
- renders HTML fragments for AJAX responses
- returns regions, districts, cities, and post offices as localized HTML

Public storefront endpoints:

- `GET /ukrposhta/state`
- `GET /ukrposhta/regions`
- `GET /ukrposhta/districts?region_id=...`
- `GET /ukrposhta/cities?district_id=...`
- `GET /ukrposhta/post-offices?district_id=...&city_id=...`
- `POST /ukrposhta/selection`

All data endpoints return JSON with:

- `items`
- `html`

The selection endpoint stores the current checkout choice in the module checkout state service.

## Tests and translations

This module keeps its own test suite and translation files.

- tests:
  - `Modules/UkrPoshta/tests/Feature/UkrPoshtaCheckoutDataTest.php`
  - `Modules/UkrPoshta/tests/Feature/UkrPoshtaSyncServiceTest.php`
  - `Modules/UkrPoshta/tests/Feature/UkrPoshtaConfigTest.php`
- translations:
  - `Modules/UkrPoshta/resources/lang/en/admin/modules/ukr_poshta.php`
  - `Modules/UkrPoshta/resources/lang/uk/admin/modules/ukr_poshta.php`

When changing module behavior, update the module tests and module translations in the same change.

## Files to treat with care

- `config/config.php`
  - module runtime config
  - keep API settings sourced from global configs
- `app/Support/UkrPoshtaConfig.php`
  - central config reader
  - missing API key must remain a hard failure
- `app/Filament/Pages/UkrPoshtaSyncPage.php`
  - admin sync UI and API key input
- `app/Services/UkrPoshtaSyncService.php`
  - chunked sync state machine
- `routes/web.php`
  - storefront/AJAX endpoints
- `resources/lang/`
  - module-owned translations loaded by the module provider
- `tests/`
  - module-owned regression coverage

## Maintenance checklist

Before changing the module, verify:

1. The API key still comes from global configs.
2. The module still stays singleton-only.
3. The sync page still uses dynamic Filament schema components.
4. The storefront views remain separate from admin views.
5. The sync service still rebuilds tables only after full stage collection.
6. New fields or sync stages are covered by tests.
7. Translation strings remain in the module and still resolve from the module-owned `resources/lang/` files through the module provider registration.

## Pitfalls to remember

- Do not add remote "total count" calls just to paint the admin progress UI. The current design uses the real loaded database counts and stage counters.
- Do not switch the sync to a single long-running request if the stage can be processed in chunks.
- Do not convert the queue state into an in-memory-only flow; the admin page depends on cached sync state for polling.
- Do not treat a single failed district/city/post office request as a global stop condition unless the sync service explicitly marks it as failed.
- Do not change the request language away from Ukrainian when reading or storing directory values.

## Related docs

- [Project architecture](../../docs/architecture.md)
- [Admin panel guide](../../docs/admin-panel.md)
- [Modules loading guide](../../Modules/README.md)
- [ architecture rules](../../../..//ARCHITECTURE.md)
