# smart-home-hub - Architecture Guide

> AI-readable reference for agents working in this codebase.
> Last updated: 2026-06-06

---

## Project Overview

Modular Laravel smart-home dashboard.

- **Backend**: Laravel 12 with `nwidart/laravel-modules`
- **Modules**: Spotify playback control and local Tasks Kanban boards
- **Frontend**: Blade, Vite assets, module-local JavaScript/CSS
- **Tests**: PHPUnit/Pest-style Laravel tests and JavaScript unit specs

---

## Architectural Shape

This project is **module-first**. Feature code belongs in `Modules/{Module}/` unless it is truly shared by every module.

```
Modules/{Module}/
├── Actions/              # Business use cases and write orchestration
├── Data/                 # DTOs / typed result objects
├── Domain/               # Pure module business logic
├── Enums/                # Lifecycle/type values
├── Http/
│   ├── Controllers/      # Thin HTTP layer
│   ├── Requests/         # Validation
│   └── Resources/        # JSON transformers
├── Models/
│   └── Builders/         # Domain query builders
├── Services/             # External systems and technical integrations
├── View/ViewModels/      # Blade/read-side presentation models
├── resources/            # Module views and assets
├── routes/               # Module routes
└── tests/                # Module tests
```

Use `app/` only for shared framework glue such as providers, contracts, dashboard layout components, and cross-module services.

Loaded modules are declared in `bootstrap/providers.php`. Do not use a second module status registry.

---

## Required Patterns

### 1. Actions - Business Logic

Business use cases live in `Modules/{Module}/Actions/{Domain}/`.

Rules:
- Primary entry point is `__invoke()` or `handle()`.
- Dependencies are injected via the constructor or method injection.
- Controllers delegate writes and orchestration to Actions.
- Naming uses verb + noun: `CreateTask`, `MoveTask`, `PlaySpotifyTrack`.

### 2. Services - Integrations

External APIs and infrastructure wrappers live in `Modules/{Module}/Services/`.

Rules:
- Services wrap technical integrations: HTTP clients, token stores, storage APIs, cache-backed API clients.
- Services must not become catch-all business objects.
- If a service grows across multiple domains, split it by responsibility before adding more behavior.

For Spotify:
- OAuth/token behavior belongs in `SpotifyTokenService`.
- Raw Spotify API transport belongs in `SpotifyApiClient`.
- Playback behavior belongs in `SpotifyPlaybackService`.
- Library/search/playlist behavior belongs in `SpotifyLibraryService`.
- `SpotifyService` may remain as a compatibility facade, but new behavior should go into the smaller service.

For Tasks:
- Task management is an in-app local Kanban module backed by Laravel tables.
- `Modules/Tasks` owns `/tasks`, dashboard navigation, board/column/task JSON endpoints and module-local Vite assets.
- Default board is `Tasks`; default columns are `Todo`, `Doing`, `Done`.
- Labels are scoped per board. Dragging a task into a column named `Done` marks it completed; moving it out marks it incomplete.
- Vikunja has been removed. `Modules/Tasks` owns the local task manager and `/tasks` route.

### 3. DTOs - Data Objects

Use DTOs for structured multi-field internal results when the shape matters beyond a simple Eloquent update payload.

Rules:
- Prefer `final readonly class`.
- Put module DTOs in `Modules/{Module}/Data/`.
- Keep public JSON response shapes stable by using Resources at controller boundaries.

### 4. ViewModels - Presentation Reads

Blade page read models live in `Modules/{Module}/View/ViewModels/`.

Rules:
- Controllers call ViewModels for page data.
- ViewModels may perform read-side queries through model scopes/builders.
- ViewModels do not write state or trigger external side effects.

### 5. QueryBuilders - Domain Filters

Reusable Eloquent filters live in `Modules/{Module}/Models/Builders/` and are registered with `newEloquentBuilder()`.

Rules:
- Avoid repeating query clauses in controllers.
- Use builder names like `TaskBuilder`, `LaneBuilder`.
- Keep complex read grouping in ViewModels.

### 6. Controllers - Thin HTTP Layer

Controllers validate, delegate, and shape HTTP responses.

Rules:
- Use Form Requests for repeated validation.
- Use Actions for writes.
- Use ViewModels for Blade reads.
- Use Resources for model JSON.
- Avoid direct Eloquent queries in controllers unless the query is trivial and not reusable.

### 7. Resources - API Contracts

JSON model output goes through `Modules/{Module}/Http/Resources/`.

Rules:
- Do not return raw models from new JSON endpoints.
- Keep existing endpoint response shapes stable unless the user asks for a breaking API change.
- Update tests when a resource contract intentionally changes.

### 8. Events

Events describe completed facts: `TaskCreated`, `PlaybackChanged`, `TrackLiked`.

Rules:
- Dispatch events from Actions or domain services after successful state changes.
- Avoid dispatching events from controllers.

---

## Forbidden Anti-Patterns

| Anti-pattern | Prefer |
|---|---|
| God service containing unrelated API, auth, cache, business and response behavior | Split services by responsibility plus Actions |
| Business logic in controllers | Actions |
| Repeated Eloquent filters in controllers | QueryBuilders or ViewModels |
| Raw model JSON for new endpoints | Resources |
| Plain arrays for important internal result contracts | DTOs |
| Cross-module feature logic in `app/` | Module-local code |

---

## Documentation Rules

Documentation is part of done. The docs entry point is `docs/vault/README.md`.

Rules:
- `AGENTS.md` is the canonical architecture rulebook.
- `docs/vault/Architecture.md` summarizes these rules for human navigation.
- `docs/vault/Setup/Definition of Done.md` is the handoff checklist.
- `docs/vault/Setup/Testing.md` owns test command and coverage expectations.
- `docs/vault/UI Specification.md` must be updated when user-visible UI behavior changes.
- `docs/styling.md` must be updated when styling patterns or visual primitives change.
- `docs/vault/Reference/Backend/Class Catalogue.md` must be updated when first-class PHP classes are added or removed.

Do not leave docs describing an older architecture after changing boundaries, response shapes or UI behavior.

---

## Verification

Run the cheapest relevant check before finishing:

```bash
composer test
```

For JavaScript changes:

```bash
npm test
```

For frontend asset changes:

```bash
npm run build
```

Change-specific minimums:

| Change type | Minimum check |
|---|---|
| PHP Action/service/domain behavior | Unit or feature test covering the behavior |
| Controller/route/JSON response | Feature test covering status and response shape |
| QueryBuilder/ViewModel | Feature coverage through the consuming page/endpoint, or focused unit coverage |
| JavaScript behavior | `npm test` or a targeted Jest spec |
| Frontend asset/styling change | `npm run build` |
| Documentation-only change | Review links/examples; tests only if examples are executable |

If a relevant check cannot be run, state why in the handoff.
