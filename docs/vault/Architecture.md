# Architecture

> Canonical source: `AGENTS.md` at the repo root. When this page and `AGENTS.md` disagree, `AGENTS.md` wins.

smart-home-hub is a modular Laravel dashboard. Feature code belongs in `Modules/{Module}/`; `app/` is reserved for shared framework glue.

---

## Mental Model

```
External API / Browser / Storage
        ↓
Services (Modules/{Module}/Services)
        ↓
Actions (Modules/{Module}/Actions)       <- business use cases
        ↓
Models / Builders                         <- Eloquent and reusable filters
        ↓
ViewModels / Resources                    <- presentation and JSON contracts
        ↓
Controllers / Blade / JS                  <- thin boundaries
```

Dependency flow should stay one-way: controllers delegate inward, services do not call controllers, and reusable domain decisions should not live in Blade or JavaScript.

---

## Module Layers

| Layer | Rule |
|---|---|
| Actions | Business use cases and write orchestration. Entry point is `__invoke()` or `handle()`. |
| Services | External integrations and infrastructure wrappers. Split by responsibility before a service becomes a catch-all. |
| Data | DTOs and typed results for internal multi-field contracts. |
| Domain | Pure logic without Laravel dependencies when a rule can be isolated from storage/framework concerns. |
| Models/Builders | Eloquent records plus reusable query filters. Register builders with `newEloquentBuilder()`. |
| ViewModels | Blade read-side composition. No writes or external side effects. |
| Resources | JSON shape for model responses. Do not return raw models from new JSON endpoints. |
| Controllers | Validate, delegate to Actions/ViewModels, return views/resources. |

---

## Current Modules

### Spotify

Spotify has a compatibility facade, `Modules\Spotify\Services\SpotifyService`, that delegates to smaller services:

- `SpotifyTokenService`: OAuth state, access token and refresh token behavior.
- `SpotifyApiClient`: raw Spotify API transport, auth retry and transport error normalization.
- `SpotifyPlaybackService`: playback, queue, devices and player commands.
- `SpotifyLibraryService`: playlists, search and saved-track library behavior.

New Spotify behavior should go into the smallest matching service. Keep `SpotifyService` as a facade for compatibility unless a route or test is intentionally migrated away from it.

### Lighting

Lighting is a local dashboard module for controlling cloud-backed lights without storing device state in the database.

- `LightingService`: aggregates configured light providers, caches short-lived snapshots and isolates provider failures.
- `LightProvider`: shared provider interface for listing and controlling lights.
- `TuyaProvider`: Calex/Tuya Cloud mapping for power, brightness and colour commands.
- `GoveeProvider`: Govee Developer API mapping for power, brightness and RGB colour commands.
- `ControlLight`: write action used by the controller for per-light updates.
- `ApplyLightingPreset`: write action for applying configured presets to every reachable light across providers.
- `LightPreset` / `LightingPresetResult`: typed preset definitions and apply results.
- `LightingViewModel`: groups lights by provider and prepares page state for Blade.

Provider credentials live only in config/env (`TUYA_*`, `GOVEE_API_KEY`). Provider clients must not include credentials or signed tokens in rendered views, logs or thrown messages. One failed provider or unreadable light should mark only that provider/light unreachable; the rest of the page stays usable.

Govee model lookup is cached separately from light state so control calls do not need an extra device-list request before every command. Govee control commands are spaced slightly and retry transient API/transport failures. Preset application uses the current light snapshot to avoid commands for already-matching power, brightness and colour values. All Lighting writes run under a shared cache lock so presets and per-light controls cannot interleave provider command sequences.

### Tasks

Task management is a local Laravel Kanban module. The active module is `Modules/Tasks` and `/tasks` renders the in-app board UI.

- `TaskBoard`: user-created boards. The default board is `Tasks`.
- `TaskColumn`: per-board configurable columns. The default columns are `Todo`, `Doing`, `Done`.
- `KanbanTask`: task cards with title, description, priority, deadline, completed state, archive state and position.
- `TaskLabel`: labels scoped per board.
- `TaskChecklistItem`: simple checklist items per task.
- `TasksController`: validates board/column/task JSON requests and returns the complete board state after writes.

Dragging a task into a column named `Done` marks it completed. Moving it to any other column marks it incomplete. Vikunja has been removed; `Modules/Tasks` owns the local task manager and `/tasks` route.

---

## Forbidden Anti-Patterns

| Anti-pattern | Prefer |
|---|---|
| Business logic in controllers | Action |
| Repeated `where(...)` filter chains in controllers | Builder or ViewModel |
| Raw Eloquent model JSON from new endpoints | Resource |
| God service mixing auth, API transport, business decisions and response mapping | Smaller services plus Actions |
| Cross-module feature behavior in `app/` | Module-local class |
| Inline UI behavior docs only in code comments | `docs/vault/UI Specification.md` |
