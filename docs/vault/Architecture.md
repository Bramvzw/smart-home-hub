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

### Weather

Weather is a local dashboard module for rainfall monitoring at the configured home location.

- `OpenMeteoClient`: raw Open-Meteo forecast transport.
- `WeatherService`: maps forecasts, evaluates rain/wind blocks and owns alert state.
- `CheckRainForecast`: scheduled action used by the rain console command.
- `CheckWindForecast`: scheduled action used by the wind console command.
- `SendDailyWeatherSummary`: scheduled action for the morning weather summary.
- `NtfyWeatherNotifier`: module-local ntfy transport for rain alerts.
- `WeatherForecast` / `WeatherHour` / `WeatherDay` / `RainAlertResult` / `WeatherAlertResult`: typed weather and alert results.
- `WeatherViewModel`: read-side page state for `/weather`.

The default location is Herxen 17, Wijhe (`52.42632587203681`, `6.132287777181066`). Rain and wind alerts inspect fixed hourly forecast blocks in the configured window, defaulting to 3 hours. A block is rainy when precipitation is greater than the configured millimetre threshold or precipitation probability meets the configured probability threshold. A wind block triggers when wind speed or gusts meet the configured km/h threshold. Rain notifications include start time, minutes until start, likely duration and intensity. Alerts are sent through ntfy at most once per weather period, with a 1-hour default cooldown and only inside the configured alert hours. A daily summary is scheduled separately and includes today/tomorrow, rain and wind context.

### News

News is a local RSS/Atom aggregation module at `/news`.

- `FeedClient`: HTTP transport and `laminas-feed` parsing. It normalizes RSS/Atom entries into `RawFeedItem` DTOs and throws `FeedUnavailable` for request or parse failures.
- `NewsService`: iterates configured feeds, isolates failed feeds, dedupes by `(feed_key, guid)`, preserves read/notified state, stores keyword matches and prunes old items.
- `RefreshFeeds`: scheduled/manual refresh action.
- `CheckNewsKeywords`: sends one shared-hub ntfy notification per stored matching item and marks it notified only after a successful send.
- `MarkItemsRead` / `MarkAllRead`: global single-user read-state actions.
- `NewsViewModel` and `NewsItemResource`: own the `/news` page state and JSON contract.

Feeds, topics, keywords, retention, item limits and cadence are config-driven under `news.*`. Read-state is global because the hub is single-user/local. The app-level `App\Services\Ntfy\HubNotifier` is shared infrastructure for new ntfy integrations; Weather still uses its module-local notifier until a separate migration.

### Briefing

Briefing is a daily Dutch morning digest module at `/briefing`.

- `App\Contracts\BriefingSource`: shared source contract implemented by contributing modules and tagged as `briefing.source`.
- `App\Support\Briefing\BriefingSection`: structured section DTO handed to the composer.
- `BriefingSourceRegistry`: resolves tagged sources, drops null contributions, isolates source failures and sorts by priority.
- `BriefingComposer`: composes with Prism/Anthropic when `HUB_AI_ANTHROPIC_API_KEY` is configured and falls back to `TemplatedBriefingComposer` on missing key or any AI failure.
- `GenerateBriefing`: collects sections, composes, upserts one row per date, prunes old rows and optionally pushes through `HubNotifier`.
- `WeatherBriefingSource`, `CalendarBriefingSource`, `TasksBriefingSource` and `NewsBriefingSource`: module-local contributions. Learning-goal support is intentionally deferred until that module exists.

Manual regenerate refreshes the dashboard only (`push = false`) to avoid duplicate notifications. The scheduled `briefing:generate` command runs at `briefing.time`, defaults to 08:00, and sends ntfy.

### Recipes

Recipes is a weekly supermarket-offer recipe module at `/recipes`.

- `OfferProvider`: per-store contract for fetching normalized offers.
- `AlbertHeijnOfferProvider` and `LidlOfferProvider`: best-effort unofficial offer sources with tolerant JSON parsing and typed source failures.
- `OfferAggregator`: runs configured providers, catches failures per store, upserts `grocery_offers` per ISO week and records `recipe_runs` store status.
- `RecipeGenerator`: validates AI output and persists 4-5 quick Dutch recipes with per-recipe shopping lists.
- `PrismRecipeTextGenerator`: Anthropic/Prism adapter using the shared hub AI config.
- `GenerateRecipes`: fetches or reuses weekly offers, generates recipes, records AI-unavailable fallback state and pushes through `HubNotifier`.
- `RecipesViewModel`, `RecipeResource` and `OfferResource`: own the page/API read contract.

The scheduled `recipes:generate` command runs weekly on the configured day/time, default Friday 18:00. Store-source failures are isolated; one working store is enough to keep offers and AI generation available. If AI generation fails or no API key is configured, offers remain visible, recipes for that week are cleared, `recipe_runs.ai_unavailable` is set and ntfy still sends an offer summary.

### Deals

Deals is a price-watchlist module at `/deals`.

- `RetailerAdapter`: retailer contract for product search and current-price lookup.
- `BolAdapter`, `AmazonAdapter`, `TweakersAdapter`: resilient retailer implementations behind config-driven endpoints.
- `ProductMatcher`: searches configured retailers and skips/logs failed sources.
- `PriceChecker`: appends price points, updates current/lowest price and reports drops.
- `AddWatchedProduct`, `ConfirmListing`, `RemoveListing`, `CheckPrices`: own review-match and scheduled tracking workflows.

Adding a product creates unconfirmed candidate listings. Only confirmed active listings are checked by `deals:check-prices`, scheduled with `deals.check_cron` and defaulting to every three hours. Each observed drop sends one shared-hub ntfy notification.

### Entertainment

Entertainment is a film/concert/music discovery module at `/entertainment`.

- `TmdbClient`: TMDB now-playing and watch-provider transport.
- `ConcertProvider`: source contract implemented by Ticketmaster, Bandsintown and Hedon providers.
- `SpotifyReleasesService`: reads followed Spotify artists and recent albums/singles through the Spotify API client.
- `EntertainmentCurator`: AI/testable curation contract for film picks and concert relevance.
- `RefreshFilms`, `RefreshConcerts`, `RefreshMusicReleases`, `NotifyEntertainment`: scheduled workflows.

Spotify authorization now requests `user-follow-read`. Concert source failures are isolated. New music notifications are bundled; relevant concerts (`followed`, `hedon`, `might_like`) are pushed once.

### Planner

Planner is a weekly Google Calendar agenda-planning module at `/planner`.

- `GoogleCalendarTokenService`: OAuth URL, code exchange and refresh-token behavior.
- `GoogleCalendarClient`: Google Calendar free-busy and event insert transport.
- `SlotFinder`: deterministic feasible-slot generator that excludes work hours and busy events.
- `WeeklyPlanner`: places active intentions and validates any composed plan before persistence.
- `PlanComposer`: AI/testable summary/arrangement contract; invalid composed slots fall back to deterministic placement.
- `GenerateWeeklyPlan`, `AcceptPlanItem`, `AcceptAllPlanItems`, `RejectPlanItem` and intention CRUD actions own writes.

The scheduled `planner:generate` command runs weekly on the configured day/time, default Sunday 19:00. Manual generation does not push; scheduled generation sends one shared-hub ntfy summary. Accepted plan items are inserted into the configured Google Calendar and store the returned event id.

### Tasks

Task management is a local Laravel Kanban module. The active module is `Modules/Tasks` and `/tasks` renders the in-app board UI.

- `TaskBoard`: user-created boards. The default board is `Tasks`.
- `TaskColumn`: per-board configurable columns. The default columns are `Todo`, `Doing`, `Done`.
- `KanbanTask`: task cards with title, description, priority, deadline, completed state, archive state and position.
- `TaskLabel`: labels scoped per board.
- `TaskChecklistItem`: simple checklist items per task.
- `TaskRecurrence`: unified habit and maintenance recurrence definitions.
- `TaskRecurrenceCompletion`: completion history for habits and maintenance occurrences.
- `TasksController`: validates board/column/task JSON requests and returns the complete board state after writes.
- `TaskRecurrenceController`: JSON endpoints for habits, maintenance recurrence management and habit check-offs.
- `StreakCalculator`: cadence-aware progress plus current and best streak calculation.
- `MaterializeDueMaintenance`: scheduled action that creates due maintenance cards and sends shared-hub ntfy reminders.
- `CompleteMaintenanceCard`: reschedules maintenance recurrences when linked cards are completed.

Dragging a task into a column named `Done` marks it completed. Moving it to any other column marks it incomplete. Vikunja has been removed; `Modules/Tasks` owns the local task manager and `/tasks` route.

Habits are exposed through JSON under `/tasks/habits` and track `times_per_week`, `weekdays`, `weekly` and `monthly` cadences. Maintenance recurrences materialize normal Kanban cards when `next_due_on` is due; those cards carry `recurrence_id` and `is_maintenance` in the board state. Completing a linked maintenance card through either move or update flow records the occurrence, clears the materialization guard and calculates the next due date.

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
