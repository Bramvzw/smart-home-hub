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
