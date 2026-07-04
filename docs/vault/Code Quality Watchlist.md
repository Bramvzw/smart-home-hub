# Code Quality Watchlist

Use this list during reviews and refactors. A signal is not automatically a bug, but it should trigger a closer look.

---

## Structural Signals

- Controllers longer than the route boundary needs.
- Services crossing multiple domains, such as auth + API transport + cache policy + business orchestration.
- Repeated query fragments that should be builders or scopes.
- View files inventing data shape or status logic.
- Module code reaching into another module's internals instead of a shared contract.

## Contract Signals

- Repeated `isset($response['error'])` checks across layers.
- Public JSON returning raw models instead of Resources.
- Multi-field internal arrays where field names matter.
- Tests asserting only `success: true` while response fields are semantically important.

## UI Signals

- Blade files adding one-off colors, spacing, buttons or badges.
- JavaScript owning server-side business rules.
- Missing loading, empty or error states for new async UI.

## Migration Signals

- Schema changes without matching casts, factories, resources and docs.
- Destructive migrations without a data plan.
- Tests or docs relying on stale field names.

## Open Items (functional sweep 2026-07-04)

- `WeatherService` is 512 lines and owns forecast fetching, three alert flows and notification cooldowns — split by responsibility (AGENTS.md god-service rule) before adding behavior.
- `Modules/Briefing/tests/Feature/BriefingSourcesTest` (plus a few News/Tasks/Deals tests) access nullable results without narrowing — ~50 baseline errors. `phpstan/phpstan-phpunit` is installed, so adding `assertInstanceOf`/`assertNotNull` before access resolves them properly.
- Local command runs push to the real phone: the dev `.env` carries a real `PHONE_PING_NTFY_TOPIC` and Weather falls back to it. Consider an `NTFY_DRY_RUN` flag or dropping the topic from the dev `.env`.
- `news:check-keywords` prints a stray `>` instead of a summary line; most commands report a one-line result — make them all do so.
- PHPStan baseline stands at 392 (from 627); keep burning down, never add to it.
