[← AI Code Intelligence](ai-code-intelligence.md) · [Back to README](../README.md) · [Application Adapter →](ai-code-intelligence-framework-adapter.md)

# Framework-Aware Code Intelligence

This guide describes a reusable way for an AI coding agent or indexing system to understand application frameworks without treating framework conventions as ordinary function calls. It applies to monoliths, modular applications, plugin systems, and applications running in containers.

The goal is not to claim a complete runtime call graph. The goal is to combine source-level evidence with safe framework introspection, record how each relationship was discovered, and make uncertainty visible.

## Required per-application companion document

Before framework-aware investigation or indexing, the agent must check whether the target repository contains `docs/ai-code-intelligence-framework-adapter.md`.

- If it exists, read it, verify its paths/versions/commands against the current checkout, and update it when framework or runtime discovery facts have changed.
- If it does not exist, create it **using this universal guide as the source of instructions**. Do not expect to read a missing adapter file. First inspect the target repository and its deployment configuration, then document its actual framework/version, runtime/container topology, safe introspection commands, framework-specific discovery surfaces, validation approach, and known limitations.
- Never copy the app-specific values or commands from another repository's adapter. The companion document must be useful for the application being inspected and must distinguish verified facts from unverified assumptions.
- Add the new file to that repository's documentation index (README/AGENTS or equivalent) when such indexes exist.

This universal guide is the durable instruction for creating the companion. An application-specific adapter is a generated, repository-specific runbook; it is not the mechanism by which an agent discovers that the runbook is missing.

### Minimum content for a new companion

At minimum, include:

1. Application purpose, framework/version, languages, and repository roots.
2. Runtime topology: host/container/orchestrator, how to discover services, source mount paths, and the least-privileged application command context.
3. Safe read-only framework introspection commands, after verifying availability and boot side effects.
4. Static and runtime discovery sources for routes, handlers, middleware, dependency injection, events/hooks, jobs/commands, plugins/modules, UI entrypoints, and persistence boundaries relevant to that framework.
5. Test/runtime verification strategy, known dynamic behavior, parser/extractor limitations, and evidence standards.
6. Explicitly prohibited or approval-required operations for that repository, such as starting/rebuilding services, migrations, cache mutation, external calls, or database writes.

Keep general cross-framework rules in this document and concrete paths, service names, commands, versions, and project examples in the companion.

## Operating model

Use four complementary layers. Each answers a different question:

| Layer                        | Finds                                                                           | Does not prove                                                         |
|------------------------------|---------------------------------------------------------------------------------|------------------------------------------------------------------------|
| Repository/language analysis | Declarations, direct calls, imports, class inheritance, attributes/annotations  | Runtime registration or execution                                      |
| Framework static adapter     | Conventional routes, handlers, hooks, DI definitions, event declarations        | That conditional registration is active in this environment            |
| Framework runtime adapter    | Routes, services, listeners, middleware, plugins actually registered after boot | That every registered path is exercised or reachable for every request |
| Tests and runtime traces     | Observed behavior for a specific scenario/environment                           | All possible behavior in production                                    |

Do not collapse these into one unqualified `calls` edge. A route registration, a constructor dependency, a direct method call, and an observed runtime dispatch are distinct relationships.

### Recommended investigation order

1. Identify repository root, framework/version, language runtimes, package manager, deployment topology, and available test commands.
2. Search exact routes, symbols, configuration keys, hooks, and literals with `rg`.
3. Use an LSP for definitions/references, and AST tooling for syntax-level patterns. Use framework-independent extraction before framework-specific rules.
4. Determine whether the app runs directly on the host, in Docker/Podman, in Kubernetes, or in ano``````ther managed runtime.
5. If safe and available, inspect the framework's *effective registered state* from its development runtime. Keep this as a separate evidence source from source parsing.
6. Follow candidate paths through handler, validation, action/service, persistence/external adapter, response, and tests.
7. Store only evidence-backed relationships. Record unresolved/dynamic edges instead of guessing.
8. Verify important claims in current source and, when appropriate, tests or a controlled browser/runtime scenario.

## Containers and runtime introspection

Containerization changes where commands must run; it does not change the source of truth. Source files are normally inspected on the host checkout, while framework commands run inside the application's existing development service.

### Discover before executing

- Locate the repository's actual Compose, Kubernetes, or devcontainer configuration. Do not assume a conventional path or service name.
- Read the service definition and mount mappings to establish which host checkout is visible at which container path.
- Discover running services using non-mutating status/list commands. Select the application/CLI service explicitly; do not accidentally run Artisan, Composer, or framework commands in a database, web-server, or queue container.
- Prefer non-interactive execution (`exec -T` for Compose) and the project's documented unprivileged runtime user. Do not assume the container's default user is safe.
- If no application container is running, do not start, rebuild, or recreate the stack without authorization. Continue with static analysis or ask before changing runtime state.

### Treat framework boot as potentially effectful

A command that appears read-only (for example, listing routes or services) still boots application code. Providers, plugins, bootstrap files, and package discovery may perform network calls, write caches, touch external services, or query databases. Before runtime introspection:

1. Inspect bootstrap/provider/plugin hooks that execute during startup.
2. Use an isolated development/test environment and least-privilege credentials.
3. Prefer commands documented by that framework/version and verify their availability first.
4. Avoid commands that migrate, seed, clear caches, generate files, dispatch jobs, send mail, call payment/CRM APIs, or mutate application state.
5. Do not print environment files, credentials, full resolved container configuration, connection strings, or request/session data.
6. Use read-only database access for metadata checks; keep queries tenant/project scoped.
7. Record the container/service, environment mode, command, framework version, and timestamp for runtime-derived results, but never secrets.

If safe boot cannot be established, mark runtime extraction unavailable and use source analysis. A static result is still useful if it is clearly labeled static.

## Relationship model and evidence

Normalize framework findings to a small vocabulary while preserving the framework-specific mechanism in metadata. Reuse the index's existing relation schema where possible; add relation types only when they represent a distinct, useful fact.

| Relationship             | Example                                                           |
|--------------------------|-------------------------------------------------------------------|
| `routes_to`              | HTTP route → controller/action/closure                            |
| `middleware_applies`     | route/group → middleware                                          |
| `invokes`                | controller method → directly called action/service method         |
| `injects`                | controller/service → constructor or method dependency             |
| `registers`              | provider/configuration → route, listener, plugin, or binding      |
| `dispatches` / `handles` | event dispatch site → listener/handler                            |
| `subscribes_to`          | listener/subscriber → event/topic                                 |
| `handles`                | queue/command/message route → job/consumer/handler                |
| `mounts` / `renders`     | UI route/component → page/template/view                           |
| `observed_dispatch`      | runtime trace → actually executed handler for a captured scenario |

Every relation should retain, when available:

- project and source-symbol identity;
- target symbol identity, or an unresolved qualified name if resolution is ambiguous;
- relation type and framework mechanism;
- source path and one-based source line (or runtime registry location when no source line exists);
- evidence kind: `ast`, `lsp`, `framework_config`, `runtime_registry`, `test`, or `trace`;
- extractor/tool and relevant framework version;
- confidence, with a short evidence excerpt/reference that avoids secrets and large source dumps;
- conditions such as environment, plugin/module enabled state, feature flag, or route middleware.

Suggested confidence interpretation:

| Confidence | Meaning                                                                         |
|-----------:|---------------------------------------------------------------------------------|
|      `1.0` | Direct syntax or an exact runtime registry entry identifies both endpoints      |
| `0.8–0.99` | Strong convention/config match, with no unresolved competing target             |
| `0.5–0.79` | Plausible dynamic relationship, conditional registration, or partial resolution |
|     `<0.5` | Search candidate only; do not present as an established edge                    |

Confidence is not a substitute for evidence. Keep separate edges when static declaration and runtime observation differ. Do not overwrite static evidence with a runtime snapshot; record the environment and observation time.

## Framework adapters

Framework adapters are optional extractors layered on top of generic language analysis. Detect the framework and version from dependency manifests and bootstrap files, then inspect the installed version's official documentation and available commands before using a specific introspection interface.

| Framework/family             | Static sources to inspect                                                                                                                                                                                                                                             | Runtime/discovery sources and caveats                                                                                                                                                                                                                                                                                                                                            |
|------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Laravel**                  | Route files, route/controller attributes, middleware aliases/groups, service providers and container bindings, event/listener mappings, jobs/queues, notifications, policies, Livewire components/actions, Filament resources/pages/actions, package/module providers | Boot the correct app service in a safe development environment. Inspect effective routes and middleware, event/listener registrations, container bindings, and package/module discovery using commands/APIs supported by the installed version. Providers and package boot hooks can have side effects. Route-to-controller is not the same as controller-to-service invocation. |
| **Symfony**                  | Route attributes/YAML/XML/PHP config, controllers, service definitions/autowiring, event subscribers/listeners, Messenger handlers/transports, middleware/kernel configuration                                                                                        | Use the dev environment's router/container/event-dispatcher diagnostics only after checking command availability and boot effects. Account for compiled container/cache and environment-specific service definitions.                                                                                                                                                            |
| **OpenCart**                 | Storefront/admin routes and controllers, extension/module manifests, event registrations, OCMOD modifications, models, Twig templates, API routes                                                                                                                     | Effective behavior can depend on installed/enabled extensions, modification-cache state, store/admin context, and version. Inspect the effective extension/event/modified-code state without clearing or rebuilding caches as part of read-only investigation.                                                                                                                   |
| **Yii 1**                    | `CUrlManager` URL rules, application/module configuration, controller/action naming, components, behaviors, events, extensions                                                                                                                                        | Yii 1 is highly configuration-driven and legacy applications may customize dispatch. Reconstruct the configured URL manager and module/controller resolution; runtime boot can initialize components with side effects.                                                                                                                                                          |
| **Yii 2**                    | URL rules, modules, controller actions, DI definitions, behaviors, event handlers, widgets, console commands                                                                                                                                                          | Inspect the effective application configuration and registered components/events in the correct web/console app. Separate web and console configurations and environment-specific merges.                                                                                                                                                                                        |
| **Yii 3**                    | Router/middleware pipeline, DI container definitions, modules/config providers, event dispatcher, handlers                                                                                                                                                            | Treat each application composition root as authoritative. Do not assume Yii 1/2 conventions; verify the installed package versions and the actual app bootstrap.                                                                                                                                                                                                                 |
| **WordPress**                | `add_action`/`add_filter`, shortcodes, REST route registrations, rewrite rules, hooks in themes/plugins, block registrations, cron callbacks                                                                                                                          | Effective hooks depend on active plugins/theme, load order, multisite/blog context, and request type. WP-CLI or a temporary instrumented dev request may reveal registered hooks; do not activate plugins, run cron, or alter site options during read-only discovery.                                                                                                           |
| **Drupal**                   | Route YAML, services, event subscribers, plugins, hooks, forms, module/theme enablement                                                                                                                                                                               | Discover enabled modules and effective container/routes in the correct site/environment; generated container/cache state is environment-specific.                                                                                                                                                                                                                                |
| **Django / Flask / FastAPI** | URL/router declarations, decorators, blueprints/routers, dependency declarations, middleware, signals, tasks                                                                                                                                                          | Importing an app can execute module-level code. Use an isolated environment; distinguish registered routes from mounted routers and conditional imports.                                                                                                                                                                                                                         |
| **Rails**                    | Route DSL, controllers, callbacks, concerns, initializers, middleware, Active Job, event instrumentation                                                                                                                                                              | Route recognition and initializers depend on environment and mounted engines. Boot in a safe environment; do not run rake tasks that mutate schema/data.                                                                                                                                                                                                                         |
| **Express / NestJS**         | Router mounts, middleware order, decorators/modules/providers, guards/interceptors, event subscribers, queue processors                                                                                                                                               | Runtime registration can be conditional on config/import order. Do not infer order solely from directory names; capture effective module/router composition where safe.                                                                                                                                                                                                          |

This table is a starting checklist, not a promise that every application uses standard conventions. Framework versions, plugins, custom bootstraps, and generated code can alter behavior. Add an adapter-specific note when the repository deviates from convention.

## Framework-neutral fallback

When the framework is unknown, unsupported, or unsafe to boot:

1. Detect entrypoints from manifests, executable scripts, server configuration, and deployment files.
2. Trace web/CLI/message entrypoints to handlers with exact text search, LSP references, AST calls, and configuration parsing.
3. Identify dependency injection, plugin loading, hook/event registration, route tables, and middleware using explicit configuration or source patterns.
4. Label convention-based matches as candidates, not confirmed runtime behavior.
5. Use tests or an explicitly approved, isolated trace to verify dynamic registration.
6. Keep unsupported surfaces listed in the adapter document so the next agent knows what remains unknown.

## Quality checks for an adapter

Before calling an adapter useful, verify it against a small representative fixture or real application scenario:

- direct route → handler;
- route group/prefix/middleware inheritance;
- constructor and method injection;
- direct calls and interface/container resolution;
- conditional plugin/module/provider registration;
- event/hook dispatch and listener discovery;
- missing or ambiguous targets;
- environment-specific configuration;
- a negative case that must **not** produce a relation.

The adapter should be incremental, deterministic, project-scoped, restartable, and safe to run repeatedly. It should not execute user workflows or mutate the app to discover relationships.

## See Also

- [Application Framework Adapter](ai-code-intelligence-framework-adapter.md) — repository-specific discovery and runtime commands
- [AI Code Intelligence](ai-code-intelligence.md) — this project's shared index and retrieval tools
- [AI Agent Collaboration](ai-agent-collaboration.md) — boundaries for tools, containers, and delegated work