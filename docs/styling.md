# Styling Architecture

This app uses small, explicit styling systems per area. The goal is consistency without introducing a heavy design-system framework.

---

## Principles

- Reusable UI belongs in Blade components, module components or named CSS classes.
- Page files may compose layout, but should not invent new colors, button styles, inputs, pills or typography.
- Inline styles are allowed only for runtime values that cannot be known in CSS.
- If a style pattern appears three times, promote it to a class or component.

---

## Web

Primary shared entry points:

- `resources/css/app.css`
- Module-local CSS under `Modules/{Module}/resources/assets/css/`

Rules:

- `resources/css/app.css` owns the app-wide Smart Home Hub tokens: `--hub-bg`, `--hub-sidebar`, `--hub-surface`, `--hub-elevated`, `--hub-card`, `--hub-line`, `--hub-text`, `--hub-muted`, `--hub-dim`, `--hub-accent` and semantic variants.
- Shared product UI should use `hub-*` component classes (`hub-shell`, `hub-sidebar`, `hub-nav-link`, `hub-card`, `hub-action`, `hub-input`, `hub-empty`) before adding new one-off styles.
- Keep shared dashboard styling in the shared CSS entry.
- Keep module-specific styling in the module asset when it does not belong to the dashboard shell.
- Prefer component classes for buttons, cards, pills, controls, empty states and modal surfaces.
- Avoid one-off utility-heavy Blade markup for product UI.
- Keep dynamic inline style fragments small and obvious.

---

## JavaScript-Owned UI

When JavaScript renders or mutates UI:

- Keep state and rendering helpers in module-local JS files.
- Do not duplicate backend business rules in JavaScript.
- Document user-visible behavior in `docs/vault/UI Specification.md` when it changes.

---

## Tasks

The active task UI is the local Kanban board in `Modules/Tasks`. Keep task styling in `Modules/Tasks/resources/assets/css/tasks.css`, but source shared surfaces, text, lines and accent values from the global `--hub-*` tokens.

---

## Migration Target

- New work should reduce one-off styling and raw colors over time.
- Repeated visual primitives should have one obvious source of truth.
