# Tasks Module

Local Kanban task management for Smart Home Hub.

## Scope

- Multiple boards.
- Per-board configurable columns.
- Default board: `Tasks`.
- Default columns: `Todo`, `Doing`, `Done`.
- Board-scoped labels.
- Task priority: `low`, `normal`, `high`.
- Optional deadline, visible on cards and filterable.
- Simple checklist items.
- Archive and permanent delete.

This module is intentionally smaller than Trello, Jira, ClickUp or Vikunja. It is built for one local user and a calm dashboard workflow.

## Behavior

- `/tasks` renders the Kanban board.
- Creating the first visit ensures the default board exists.
- Dragging a task into a column named `Done` marks it completed.
- Moving a task out of `Done` marks it incomplete.
- Filters are client-side over the loaded board state: search, label, priority, deadline and show archived.
- Editing happens inline and in the right-side detail panel. The UI avoids modal-first task editing.

## Main Classes

- `TasksController`: JSON endpoints and initial page state.
- `EnsureDefaultBoard`: creates the default board and columns.
- `TaskBoard`: board model.
- `TaskColumn`: column model and `Done` detection.
- `KanbanTask`: task card model.
- `TaskLabel`: per-board label model.
- `TaskChecklistItem`: checklist item model.

## Assets

- `resources/views/index.blade.php`: app mount point with initial state and route templates.
- `resources/assets/js/tasks-board.js`: SortableJS interactions and JSON persistence.
- `resources/assets/css/tasks.css`: compact dark Kanban styling based on the design handoff.

## Checks

```bash
php artisan test Modules/Tasks/tests
npm run build
```
