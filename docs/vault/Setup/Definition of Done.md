# Definition of Done

A change is done only when implementation, tests, behavior and documentation match the touched scope.

---

## 1. Scope Is Clear

| Changed area | Must be checked |
|---|---|
| Module business behavior | Action/service boundary, tests, architecture docs |
| JSON endpoint or response | Controller, Resource, route, feature test, API docs if present |
| Blade page | ViewModel/query ownership, UI states, styling rules |
| Database schema | Migration safety, model casts/fillable, factories/tests, docs |
| External integration | Service boundary, timeout/error handling, tests/fakes |
| JavaScript behavior | Unit specs, browser states where practical |
| Tasks Kanban behavior | Cover board/column/task behavior, JSON responses, Vite assets and UI docs |

Do not mix unrelated refactors into the same commit unless they are required to complete the change.

---

## 2. Architecture Rules

The code must follow `AGENTS.md`:

| Layer | Done means |
|---|---|
| Actions | Business use cases live in `Modules/{Module}/Actions/*` and are invoked by controllers/services. |
| Services | External integrations are split by responsibility. |
| DTOs | Important multi-field internal contracts use DTOs in `Modules/{Module}/Data/*`. |
| Builders | Reusable filters live in `Modules/{Module}/Models/Builders/*`. |
| ViewModels | Blade page data comes from `Modules/{Module}/View/ViewModels/*`. |
| Resources | New JSON model output uses `Modules/{Module}/Http/Resources/*`. |
| Controllers | Controllers validate, delegate and shape HTTP responses. |

Do not commit new code that knowingly violates these boundaries.

---

## 3. Tests And Checks

Run the cheapest relevant checks while developing, then the required checks before handoff.

| Change type | Minimum check |
|---|---|
| PHP Action/service/domain behavior | Unit or feature test covering the behavior |
| Controller/route/JSON response | Feature test covering status and response shape |
| QueryBuilder/ViewModel | Unit or feature coverage through the consuming page/endpoint |
| JavaScript behavior | `npm test` or targeted Jest spec |
| Frontend assets/styling | `npm run build` when asset output can be affected |
| Documentation-only change | Review links and examples; tests not required unless examples are executable |

Standard commands:

```bash
composer test
npm test
npm run build
```

If a relevant check cannot be run, record why in the handoff.

---

## 4. Documentation Coverage

| Code change | Documentation update |
|---|---|
| New PHP class | Add it to `docs/vault/Reference/Backend/Class Catalogue.md` when it is part of app/module architecture. |
| Changed architecture boundary | Update `AGENTS.md` and `docs/vault/Architecture.md`. |
| Changed UI behavior | Update `docs/vault/UI Specification.md`. |
| Changed styling pattern | Update `docs/styling.md`. |
| Changed test/setup flow | Update `docs/vault/Setup/Testing.md` or this file. |
| New known limitation | Add it to a relevant docs note or handoff explicitly. |

Do not treat a feature as complete while docs describe an earlier design.

---

## 5. Final Review

- `git diff` reviewed line by line.
- No unrelated dirty files are included intentionally.
- No debug `dump()`, `dd()`, `console.log()` or temporary comments remain.
- Naming follows project conventions.
- Tests/checks relevant to the touched scope have run.
- Docs match the implementation.
