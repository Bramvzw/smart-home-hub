# Testing

Testing is one part of done. Use [Definition of Done](Definition%20of%20Done.md) for the full handoff checklist.

---

## PHP

Always run Laravel tests through Composer:

```bash
composer test
```

This clears config and runs `php artisan test` through the project script.

Expected coverage:

| Layer | Expected coverage |
|---|---|
| Actions | Unit or focused feature tests |
| Services | Unit tests with fakes/mocked clients for external behavior |
| QueryBuilders/ViewModels | Feature tests through consuming pages/endpoints, or focused unit tests when useful |
| Controllers/API | Feature tests for validation, status codes and response shape |
| Models | Unit tests for casts, relationships and important helpers |

Thin wrappers and purely presentational Blade fragments do not need direct tests unless they contain conditional behavior.

---

## JavaScript

Run Jest for JavaScript changes:

```bash
npm test
```

Use targeted specs during development when faster, then run the relevant broader check before handoff.

---

## Assets

Run a build after changes that can affect compiled assets:

```bash
npm run build
```

---

## Database Safety

Tests should use the configured test database. Never point tests at production data.

For schema changes, verify:

- Migration runs forward.
- Model fillable/casts/relationships match the schema.
- Factories/seeders still create valid records.
- Resources and feature tests expose the intended shape.

---

## Tasks

The active task manager is local. Feature tests should cover:

- Default board creation.
- Board and column creation.
- Task creation, movement and completion behavior.
- Board-scoped labels.
- Archive and delete behavior.

Run PHP coverage with:

```bash
php artisan test Modules/Tasks/tests
```

Run a frontend asset build after changing `Modules/Tasks/resources/assets/js/tasks-board.js` or `Modules/Tasks/resources/assets/css/tasks.css`:

```bash
npm run build
```
