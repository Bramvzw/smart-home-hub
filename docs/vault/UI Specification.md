# UI Specification

Document user-visible behavior here when screens, controls, states or visual semantics change.

---

## Documentation Contract

For changed UI, record:

| UI element | Document |
|---|---|
| Page/screen | Purpose, data source, actions, loading/empty/error states |
| Component | Props/data source, states, actions and out-of-scope behavior |
| Button/action | Trigger, disabled/loading behavior, success/error outcome and side effects |
| Form/input | Validation, formatting, error copy and save/cancel behavior |
| Badge/status | Meaning, color/token mapping and fallback state |
| List/card/table | Sort order, visible fields, empty state and navigation behavior |

---

## Current Screens

### Dashboard

Shared shell for module navigation. Navigation entries come from module service providers via `ModuleRegistry`.

Visual system:
- App shell uses a restrained dark dashboard style from `resources/css/app.css`.
- Global tokens use the `--hub-*` prefix for background, sidebar, surface, card, line, text and accent values.
- Sidebar navigation, module cards, primary actions, inputs and empty states should use the shared `hub-*` component classes.
- Avoid decorative gradients, glassmorphism, oversized cards and violet-only styling.

### Spotify

Controls playback, playlists, queue, devices and liked tracks through the Spotify module routes. Spotify UI behavior should be backed by the smaller Spotify services documented in `Architecture.md`.

Spotify keeps its green brand accent only for playback-specific active states and connect/progress affordances. Layout, tabs, cards, search, lists, device picker and empty states use the shared `--hub-*` surfaces and typography.

### Tasks

Task management is a local, dark, compact Kanban board rendered by `Modules/Tasks`.

Layout:
- Left sidebar lists boards and active task counts.
- Top toolbar contains editable board title, search, label filter, priority filter, deadline filter, archive toggle and `Nieuwe taak`.
- Main area is a horizontally scrolling Kanban board with configurable columns.
- Selecting a task opens a right detail panel; editing is inline rather than modal-first.

Behavior:
- Default board is `Tasks`; default columns are `Todo`, `Doing`, `Done`.
- Boards can be created, renamed and deleted.
- Columns can be created, renamed, deleted and reordered.
- Tasks can be created, dragged between columns, reordered, edited, archived and deleted.
- Dragging a task into a column named `Done` marks it completed. Moving it elsewhere marks it incomplete.
- Labels are scoped per board and can be attached from the detail panel.
- Deadline notifications are not implemented; deadlines are only visible and filterable.

Card fields:
- Title is always visible.
- Description preview, labels, deadline, checklist progress and archived state are visible when present.
- Priority is shown as a subtle left accent bar.
