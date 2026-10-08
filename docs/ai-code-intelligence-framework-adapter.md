[← Framework-Aware Code Intelligence](framework-aware-code-intelligence.md) · [Back to README](../README.md) · [Agent Collaboration →](ai-agent-collaboration.md)

# Application Framework Adapter

This is the application-specific companion to [Framework-Aware Code Intelligence](framework-aware-code-intelligence.md). It records how an agent should discover effective framework behavior for this repository, rather than repeating universal guidance.

> **Creation rule:** In another repository, this file may not exist yet. The agent must learn that it is required from the universal guide, check for it, and create it from inspected application facts when absent. It is impossible to rely on reading this file before it has been created. This Syvak copy is an example of a completed adapter, not a source of paths or commands for other applications.

## Required agent behavior

Before a framework-aware investigation or index refresh:

1. Read the universal guide first; it defines the requirement to have this companion.
2. Check whether this file exists. If absent, inspect the repository and create it using the universal guide's minimum-content checklist. Do not attempt to read the missing file or copy another application's paths/commands.
3. If present, read this file and the universal guide. Treat this file as a local runbook that must be verified, not as permission to bypass safety rules.
4. Verify paths, container/service names, versions, and command availability against the current checkout. The documentation can become stale.
5. Update this adapter when framework, container topology, bootstrap, or discovery conventions change. Keep universal practices in the shared guide and repository-specific facts here.
6. Separate source facts, effective runtime registrations, test evidence, and unresolved questions. Do not promote an index candidate to a proven call path without source verification.

## Repository profile

| Property                       | Current repository                                                                              |
|--------------------------------|-------------------------------------------------------------------------------------------------|
| Application                    | Syvak storefront and administration                                                             |
| Framework                      | Laravel 13                                                                                      |
| Main language                  | PHP 8.5; storefront also uses TypeScript                                                        |
| Admin/UI frameworks            | Filament 5 and Livewire 4                                                                       |
| Runtime                        | Docker Compose development stack                                                                |
| Workspace root                 | `/home/kamaz/www/strateg-projects/syvak`                                                        |
| Laravel app root               | `/home/kamaz/www/strateg-projects/syvak/app-code/app`                                           |
| Documented PHP service         | `dev-syvak-php-fpm` in `.docker/dev/docker-compose.yml`                                         |
| PHP app path in that container | `/var/webroot/sites/ttter.syvak.com/app`                                                        |
| Intelligence project           | `Syvak UA`, slug `syvak-ua` (resolve its current project ID from the database for each session) |

If the Compose service name or mount path differs in a checkout, inspect the actual Compose file and running services before executing commands. Do not assume the app container's internal path from the host path.

## Safe Docker workflow

1. Use host-side `rg`, Git, Serena/LSP, and AST tools for source inspection when the checkout is available locally.
2. Inspect `.docker/dev/docker-compose.yml` and its referenced files to confirm the PHP service, mounts, user, and environment mode. Do not print expanded Compose configuration because it may contain resolved secrets.
3. Confirm the target service is already running using a non-mutating status command. Do not start/rebuild/recreate containers as part of indexing without explicit approval.
4. Run only verified, non-mutating framework introspection commands using the existing PHP service and non-interactive execution. For example, a route-list command may be useful after verifying the installed Laravel command/options and reviewing provider boot side effects. Do not run migrations, seeds, cache clear/build, queue workers, external integrations, or commands that write generated files.
5. Laravel boot can execute custom service-provider and package boot code. If safe boot is uncertain, skip runtime inspection and rely on source/config analysis with that limitation recorded.
6. Keep all AIC reads/writes scoped to slug `syvak-ua` after resolving its current project UUID. Never assume a project ID from a prior session.

## Laravel extraction checklist

### 1. Effective HTTP routes

Read route declarations and bootstrap configuration first. In this repository, storefront routes are declared in `app-code/app/routes/web.php`; module routes may be required from another file. Capture:

- HTTP method(s), URI, route name, domain, and action;
- locale/prefix and route-group inheritance;
- middleware declared on the route, group, and controller;
- whether the handler is a controller method, invokable controller, or closure;
- source file/line and runtime environment used for any effective route inventory.

Use runtime route inventory only inside the verified application container and only after assessing boot side effects. Compare the runtime listing with source declarations; differing routes may be conditional on environment, providers, modules, package discovery, or cached deployment state.

### 2. Request-to-use-case paths

For each controller/Livewire/Filament entrypoint, follow:

```text
route or UI action
→ handler method
→ FormRequest/validation and authorization
→ injected or explicitly resolved Action/Service
→ model/query or external adapter
→ response/view/event/job
→ relevant tests
```

Record `routes_to`, `invokes`, `injects`, `dispatches`, and `handles` as distinct edge types. A constructor type-hint is an injection edge; it does not establish that every method on that dependency is called. A property access on a facade or service locator should not be guessed into a concrete call unless the binding/resolution is established.

### 3. Container and module bindings

Inspect:

- application and module service providers;
- interface-to-implementation and contextual bindings;
- package auto-discovery and module enablement/configuration;
- deferred providers, aliases, tags, and environment conditions;
- route, event, observer, policy, and command registration performed by providers.

Keep unresolved interface targets explicitly unresolved when multiple implementations or environment-specific bindings exist. If a runtime container diagnostic is used, record the environment and do not expose resolved secret-bearing configuration.

### 4. Events, queues, and scheduled work

Inspect event classes, dispatch sites, discovered/manual listeners, subscribers, queued listeners/jobs, commands, and schedules. Distinguish:

- event class declaration from actual dispatch;
- listener discovery from explicit registration;
- queued work from synchronous handling;
- scheduled command declaration from observed execution.

Do not start consumers, queue workers, schedules, or integrations to discover the graph. Use event/queue listings or tests only when they are safe and available in this development environment.

### 5. Livewire and Filament

Treat component/page/resource actions as application entrypoints, not just view classes:

- Livewire components: route/mount path, public actions, listeners, emitted/dispatch events, validation, and called Actions/Services;
- Filament resources: resource page routes, table/form actions, relation managers, policies/authorization, and delegated domain services;
- distinguish framework-generated actions from project-defined callbacks;
- inspect version-specific APIs and do not infer a callback's execution solely from its declaration.

### 6. Frontend-to-backend entrypoints

For storefront TypeScript and Blade, capture the browser request contract as a separate edge chain:

```text
Blade data attribute/window config or TS entrypoint
→ fetch/AJAX URL and HTTP method
→ named route/controller/FormRequest
→ Action/Service
→ JSON/view response consumed by the caller
```

Do not claim that a backend route is called by a frontend script merely because both mention the same feature. Verify the URL construction, request method, parameters, and response handling. For dynamic browser behavior, use the configured browser tools only when runtime inspection is relevant and authorized.

## Verified example: storefront category filtering

The source currently establishes these paths:

| Entry             | Evidence-backed path                                                                                                                                                        |
|-------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Category page     | localized category route → `CategoryController::show()` → `FilterProductsAction::handle()`                                                                                  |
| Live filter count | localized `/category/{slug}/filters` route → `CatalogFilterAjaxController::index()` → `FilterProductsAction::count()`                                                       |
| Load more         | localized `/category/{slug}/load-more` route → `LoadMoreProductsByAjaxController::index()` → `LoadMoreProductsByAjaxAction::handle()` → `FilterProductsAction::handle()`    |
| Browser request   | `productsFilter.ts` builds selected filter query parameters and fetches the Blade-provided filter URL; applying filters navigates to the category URL with the query string |

This example is evidence from source declarations and calls, not a captured runtime trace. Verify current line numbers and behavior against the checkout before using it as a production claim.

## Tests and verification

Use tests as corroboration, not as a replacement for discovering entrypoints. For a newly added extractor, include fixtures or focused tests for:

- localized/nested route groups and inherited middleware;
- controller, invokable, closure, and module routes;
- constructor/method injection and interface bindings;
- conventional and manually registered event listeners;
- conditional provider/module registration;
- Livewire/Filament project-defined actions;
- unresolved dynamic/service-locator calls;
- duplicate route aliases and framework-generated routes;
- container boot failure or unsafe environment fallback.

The extractor must be deterministic, incremental, project-scoped, and non-mutating. Store source evidence and runtime snapshot metadata separately. Re-run only the relevant project tests/quality commands from the app's documented container workflow.

## Known limitations to track

- Laravel providers and package boot hooks can perform side effects before an introspection command runs.
- Static analysis cannot generally prove conditional runtime registration or dynamic method dispatch.
- Runtime route/container state represents one environment and can differ from production.
- Filament/Livewire UI actions may be generated or composed through callbacks and need targeted inspection.
- Frontend request construction may be split across Blade data, TypeScript helpers, and shared fetch wrappers.
- A relation index can become stale after code/config changes; verify its indexed commit and source hashes.

## See Also

- [Framework-Aware Code Intelligence](framework-aware-code-intelligence.md) — universal framework/runtime model
- [AI Code Intelligence](ai-code-intelligence.md) — Syvak project index, tools, and database scope
- [AI Agent Collaboration](ai-agent-collaboration.md) — safe delegation and runtime ownership