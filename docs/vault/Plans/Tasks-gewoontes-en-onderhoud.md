# Plan — Gewoontes & onderhoud in de Tasks-module

Codex-ready spec for adding **habit/streak tracking** and **recurring
maintenance tasks** to the existing `Modules/Tasks` module (not a new module).
Front-end markup is out of scope (Claude Design later); this plan covers
functional behaviour, UI states and the data/JSON contract.

Status: implemented 2026-06-25. Build order: module 3. Depends on the shared
`HubNotifier` (News plan). See [Roadmap](../Roadmap.md).

---

## 1. Context (existing Tasks model)

`task_boards` → `task_columns` → `kanban_tasks` (title, description, priority,
due_date, completed, archived_at, position) + `task_labels` (per board) +
`task_checklist_items`. Server-rendered Blade board with a JS board (`board/api.js`).
This plan ADDS to that; it does not change existing tables destructively.

---

## 2. Functional spec

Two related, recurrence-based features sharing one engine:

### Habits (gewoontes)
- Live in a **dedicated "Gewoontes" section/tab** in the Tasks module (separate from the kanban board).
- Cadence types: **X times per week** (target N, day flexible), **fixed weekdays** (e.g. Mon/Wed/Fri), **weekly**, **monthly**. (Pure "daily" is expressed as fixed weekdays = all 7.)
- Each habit tracks completions and shows **progress for the current period** + a **streak** (smart per cadence — see §5).
- Check off / undo a completion for the current period.

### Maintenance (onderhoud)
- Recurring maintenance items (printer upkeep, seasonal garden tasks, etc.).
- When **due**, a maintenance item: (a) materializes a **card on the kanban board**, and (b) sends an **ntfy** reminder.
- **Completing** the maintenance card **auto-reschedules** the next occurrence.

### UI states (functional, no markup)
- **Habits section**: list of habits with cadence label, current-period progress (e.g. "2/3 deze week"), current + best streak, check/undo affordance. Empty state when no habits.
- **Maintenance**: appears as a normal kanban card when due (with a visual "maintenance/recurring" marker); the recurrence itself is managed in a maintenance list (same section, separate tab/segment).
- **Loading / error** states for the habit endpoints.

---

## 3. Data model (new tables in `Modules/Tasks`)

### `task_recurrences`
Unified definition for habits and maintenance.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `board_id` | foreignId null | for maintenance: board the card is created on; null for habits |
| `type` | enum(`habit`,`maintenance`) | |
| `title` | string | |
| `description` | text null | |
| `cadence_type` | enum(`times_per_week`,`weekdays`,`weekly`,`monthly`,`interval`,`annual`) | habits use the first four; maintenance uses `interval` or `annual` |
| `cadence_config` | json | e.g. `{ "target": 3 }`, `{ "weekdays": [1,3,5] }`, `{ "interval": 3, "unit": "month" }`, or annual `{ "month": 3, "day": 21 }` (e.g. spring garden task) |
| `notify` | boolean | send ntfy (default true for maintenance) |
| `active` | boolean | default true |
| `next_due_on` | date null | maintenance only — next occurrence |
| `last_materialized_on` | date null | maintenance only — guards against double card creation |
| `created_at`/`updated_at` | timestamps | |

### `task_recurrence_completions`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `recurrence_id` | foreignId → task_recurrences cascadeOnDelete | |
| `completed_on` | date | the day it was completed |
| `period_key` | string | normalized period: `2026-W26` (week), `2026-06` (month), or the date (weekdays) |
| `created_at`/`updated_at` | timestamps | |

- Unique index (`recurrence_id`, `period_key`) for week/month/weekly habits (one success per period); weekday habits use the date as period_key.

### `kanban_tasks` change
- Add nullable `recurrence_id` foreignId (→ task_recurrences, nullOnDelete) so a materialized maintenance card links back to its recurrence; completing the card reschedules.
- Add a separate migration; do not alter the original migration file.

### Eloquent
- `Modules\Tasks\Models\TaskRecurrence` (+ `TaskRecurrenceBuilder`: scopes `habits()`, `maintenance()`, `active()`, `dueOn(Carbon)`).
- `Modules\Tasks\Models\TaskRecurrenceCompletion`.
- `KanbanTask` gains `recurrence()` relation.

---

## 4. Services & Actions

### `Modules\Tasks\Services\StreakCalculator`
- `progress(TaskRecurrence $r, CarbonImmutable $today): PeriodProgress` — current-period completed count vs target.
- `currentStreak(TaskRecurrence $r): int` and `bestStreak(TaskRecurrence $r): int` per §5.

### Actions (`Modules/Tasks/Actions/Recurrences/`)
- `CreateRecurrence`, `UpdateRecurrence`, `DeleteRecurrence`.
- `CompleteHabit` (`__invoke(TaskRecurrence $r, CarbonImmutable $on): TaskRecurrenceCompletion`) — idempotent per period; recomputes streak.
- `UndoHabitCompletion` (`__invoke(TaskRecurrence $r, CarbonImmutable $on): void`).
- `MaterializeDueMaintenance` (`__invoke(CarbonImmutable $today): int`) — for each active maintenance recurrence with `next_due_on <= today` and not yet materialized for that occurrence: create a `kanban_task` (linked via `recurrence_id`) on its board, send ntfy via `HubNotifier`, set `last_materialized_on`.
- `CompleteMaintenanceCard` — when a maintenance-linked card is completed, mark done and set `next_due_on = completed date + interval`; clear materialization guard.
  - Hook into the existing task-completion path (`MoveTask`/`UpdateTask`) when `recurrence_id` is set, or handle in the controller for completion.

### View model
- Extend `TasksBoardViewModel` or add `HabitsViewModel` — habits with progress + streaks for the Gewoontes section.

### Briefing integration
- Provide `Modules\Tasks\Briefing\TasksBriefingSource` (implements `App\Contracts\BriefingSource` from the Briefing plan): contributes top open tasks + today's habit status. Registered tagged `briefing.source`.

---

## 5. Streak rules (smart per cadence)

- **times_per_week (target N)**: period = ISO week. Week succeeds when completions in that week ≥ N. Current streak = consecutive succeeded weeks up to now; the in-progress week does not break the streak until it ends (show `X/N deze week`).
- **weekdays [d…]**: each scheduled weekday is an occurrence. Streak = consecutive scheduled occurrences completed; missing a scheduled day breaks it; non-scheduled days are ignored.
- **weekly**: period = week, success ≥1 completion; streak = consecutive weeks.
- **monthly**: period = month, success ≥1 completion; streak = consecutive months.
- `best_streak` = max streak ever achieved.

---

## 6. Config (`Modules/Tasks/config/config.php`, extend)

```php
'recurrence' => [
    'maintenance_board' => env('TASKS_MAINTENANCE_BOARD', 'Tasks'), // board for due maintenance cards
    'maintenance_column' => env('TASKS_MAINTENANCE_COLUMN', 'Todo'),
    'notify' => true,
],
```

ntfy via the shared `HubNotifier` (shared hub topic).

---

## 7. Scheduling

- `tasks:recurrences-due` — daily (early, e.g. `dailyAt('07:00')`) → `MaterializeDueMaintenance`. Optionally a habit reminder pass for habits with `notify = true` that are behind on their weekly target. `withoutOverlapping()`.

---

## 8. Endpoints / data contract

Route prefix `tasks.`, under `/tasks`.

- `GET /tasks/habits` (JSON):
```json
{
  "habits": [
    {
      "id": 7,
      "title": "Sporten",
      "cadence_type": "times_per_week",
      "description": "",
      "cadence_config": { "times": 3 },
      "notify": true,
      "active": true,
      "next_due_on": null,
      "last_materialized_on": null,
      "progress": {
        "period_key": "2026-W26",
        "completed": 2,
        "target": 3,
        "is_complete": false,
        "percentage": 67
      },
      "current_streak": 4,
      "best_streak": 9,
      "completed_today": true
    }
  ]
}
```
- `POST /tasks/habits/{recurrence}/complete` (body optional `{ "date": "2026-06-24" }`) → updated habit object.
- `DELETE /tasks/habits/{recurrence}/complete` (today's, or optional `date`) → updated habit object.
- `GET /tasks/maintenance` (JSON) — maintenance recurrences with `next_due_on`, cadence, last completed.
- `POST /tasks/recurrences`, `PATCH /tasks/recurrences/{recurrence}`, `DELETE /tasks/recurrences/{recurrence}` — manage habits + maintenance (FE built later).
- Maintenance cards appear via the existing board state endpoint; the `TaskBoardStateResource` is extended to expose `recurrence_id` / an `is_maintenance` flag on cards.

All JSON via Resources (extend/add `HabitResource`, `RecurrenceResource`).

---

## 9. Tests (`composer test`)

### Unit (`StreakCalculator`)
- times_per_week: streak counts succeeded weeks; in-progress week doesn't break it; `2/3` progress reported.
- weekdays: consecutive scheduled occurrences; a missed scheduled day breaks the streak; non-scheduled days ignored.
- weekly / monthly streaks over consecutive periods.
- best_streak tracking.

### Feature
- `CompleteHabit` is idempotent per period (second complete same week doesn't double-count for weekly/target habits); undo reverts progress + streak.
- `MaterializeDueMaintenance` creates exactly one board card per due occurrence, links `recurrence_id`, sends one ntfy (faked `HubNotifier`), sets the materialization guard (no duplicate next run).
- Completing a maintenance card reschedules `next_due_on` by the interval and allows re-materialization next cycle.
- `GET /tasks/habits` returns the documented contract with correct progress/streak.
- Existing Tasks tests still pass (no regressions on boards/columns/tasks).

---

## 10. Acceptance criteria

- [x] A "Gewoontes" section exposes habits with per-cadence progress + smart streaks via the §8 contract.
- [x] Habits can be created/updated/deleted and completed/undone per period.
- [x] Due maintenance materializes a board card + ntfy and auto-reschedules on completion.
- [x] `TasksBriefingSource` contributes top tasks + today's habits to the Daily Briefing.
- [x] New migrations are additive; existing Tasks behaviour and tests are unaffected.
- [x] All new + existing tests pass via `composer test`.

---

## 11. Confirmed decisions (signed off 2026-06-24)

- Habits in a **dedicated section/tab**; maintenance appears as a **board card when due**.
- Cadences: **X×/week**, **fixed weekdays**, **weekly**, **monthly** (no separate "daily").
- **Smart streak** per cadence.
- Maintenance: **ntfy + auto-reschedule** on completion.

## 12. Confirmed decisions (signed off 2026-06-24)

- **T1** ✅ Maintenance supports **interval** (every N days/weeks/months) AND **seasonal/annual** (fixed month/day each year, e.g. spring garden tasks) — `interval` + `annual` cadence types.
- **T2** ✅ Due maintenance cards land on board **"Tasks"**, column **"Todo"** (configurable).
- **T3** ✅ Habits/maintenance managed via JSON endpoints now; management UI later with Claude Design.
- **T4** ✅ Undo for the **current period only** (no editing historical completions).

## 13. Out of scope

- Blade markup / styling (Claude Design later).
- The management UI (endpoints defined; UI later).
- Calendar integration for maintenance (possible later, per roadmap).
