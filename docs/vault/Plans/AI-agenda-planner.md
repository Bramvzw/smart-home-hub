# Plan — AI agenda-planner (Planner) module

Codex-ready spec for an AI weekly planner that places flexible recurring
intentions into the week around fixed commitments and offers 1-click add to
Google Calendar. Front-end markup is out of scope (Claude Design later); this
plan covers functional behaviour, UI states and the data/JSON contract.

Status: spec ready. Build order: module 8 (last). Depends on the shared
`HubNotifier`, the shared hub AI config, **Prism**, and a new **Google Calendar
OAuth** integration. See [Roadmap](../Roadmap.md).

---

## 1. Functional spec

- Every **Sunday evening** the planner reads the coming week's busy times from
  **Google Calendar**, places the owner's flexible **intentions** into free slots,
  and proposes a weekly plan (ntfy + dashboard).
- Each proposed block has **1-click add to Google Calendar** (and "add all").
- **Intentions** (managed in the hub):
  - **Sport** 3–4×/week — weekdays **after 17:00** and weekend daytime.
  - **Visit mother** 1×/week — weekend.
  - **Date night** 1×/week — weekend (evening).
- **Hard constraint**: works weekdays **09:00–17:00** (never schedule into work).
- **When a week is tight**: place as many as possible and **report what didn't fit**.

### Planning approach (correct + smart)
- Deterministic slot-finding computes feasible placements (respecting work hours, busy times, each intention's allowed windows + duration, spacing). Claude (Prism) **arranges/ranks** among feasible options and writes the friendly Dutch summary; the final plan is **validated** so no block overlaps work or busy time. AI never produces an invalid slot.

### UI states (functional, no markup)
- **Proposed plan**: week grid/list of proposed blocks with per-block accept/reject + "add all"; an "unplaceable" section listing what didn't fit and why.
- **Connect Google**: a connect/auth state when Google Calendar isn't linked yet.
- **Empty / generating / error** states.

---

## 2. Google Calendar integration (new)

- **OAuth read+write** (scope `https://www.googleapis.com/auth/calendar.events` + readonly for busy). Model the flow on the existing Spotify OAuth/token pattern (`SpotifyTokenService`).
- `Modules\Planner\Services\Google\GoogleCalendarTokenService` — auth URL, code exchange, token storage + refresh.
- `Modules\Planner\Services\Google\GoogleCalendarClient` — `busyTimes(CarbonPeriod)` (primary calendar; assumption PL5), `insertEvent(PlanItem): string` (returns Google event id).
- Tokens in `google_calendar_tokens` (access/refresh/expires).
- The existing ICS Calendar module is unchanged (it stays for display); the planner uses the Google API as the source of truth for busy/free + writing. See assumption PL5.

---

## 3. Data model (`Modules/Planner`)

Route `/planner`, label "Agenda-planner" — confirm name (assumption PL1).

### `planner_intentions`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `title` | string | e.g. "Sporten" |
| `category` | enum(`sport`,`family`,`date`,`custom`) | |
| `frequency_type` | enum(`times_per_week`,`weekly`) | |
| `target_min` | unsignedTinyInteger | e.g. sport 3 |
| `target_max` | unsignedTinyInteger | e.g. sport 4 |
| `preferred_windows` | json | e.g. `[{"days":"weekday","after":"17:00"},{"days":"weekend"}]` |
| `duration_minutes` | unsignedInteger | default per category (PL3) |
| `location` | string null | |
| `active` | boolean | default true |
| `created_at`/`updated_at` | timestamps | |

### `planner_plans`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `week_key` | string | ISO week, unique |
| `summary` | text null | AI Dutch summary |
| `status` | enum(`proposed`,`partly_accepted`,`accepted`) | |
| `is_fallback` | boolean | default false (deterministic-only, no AI summary) |
| `generated_at` | timestamp | |
| `created_at`/`updated_at` | timestamps | |

### `planner_plan_items`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `plan_id` | foreignId cascadeOnDelete | |
| `intention_id` | foreignId null | |
| `title` | string | |
| `start_at` / `end_at` | datetime null | null when unplaceable |
| `status` | enum(`proposed`,`accepted`,`rejected`,`unplaceable`) | |
| `unplaceable_reason` | string null | |
| `google_event_id` | string null | set on accept |
| `created_at`/`updated_at` | timestamps | |

### Eloquent: `PlannerIntention`, `PlannerPlan`, `PlannerPlanItem`, `GoogleCalendarToken`.

---

## 4. Services & Actions

- `Services\SlotFinder` — given busy times + work hours + an intention, returns feasible candidate slots in the target week.
- `Services\WeeklyPlanner` — orchestrates: collect intentions, find slots, place greedily honoring targets (sport min/max), produce a feasible plan + unplaceable list.
- `Services\PlanComposer` (Prism) — choose among feasible arrangements (preferences/spacing) + write the Dutch summary; output validated by `WeeklyPlanner` (reject any slot that overlaps busy/work → fallback to deterministic placement, `is_fallback = true`).
- Actions (`Modules/Planner/Actions/`):
  - `GenerateWeeklyPlan` (`__invoke(?CarbonImmutable $weekStart = null, bool $push = true): PlannerPlan`).
  - `AcceptPlanItem` (`__invoke(PlannerPlanItem $i): PlannerPlanItem` — insert into Google Calendar, store `google_event_id`, mark accepted).
  - `AcceptAllPlanItems`, `RejectPlanItem`.
  - Intention CRUD: `CreateIntention`, `UpdateIntention`, `DeleteIntention`.
- `Modules\Planner\View\ViewModels\PlannerViewModel`.

---

## 5. Config (`Modules/Planner/config/config.php`)

```php
return [
    'work_hours'    => ['days' => [1,2,3,4,5], 'start' => '09:00', 'end' => '17:00'],
    'week_starts'   => 'monday',
    'generate'      => ['day' => 'sunday', 'time' => env('PLANNER_TIME', '19:00')],
    'default_durations' => ['sport' => 90, 'family' => 150, 'date' => 180], // minutes
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT'),
        'calendar_id'   => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],
    'ai' => ['model' => env('PLANNER_MODEL', 'claude-sonnet-4-x')],
];
```

---

## 6. Scheduling

- `planner:generate` — `weeklyOn(0, '19:00')` (Sunday evening) → `GenerateWeeklyPlan` with push. `withoutOverlapping()`.

---

## 7. ntfy

Sunday-evening push via `HubNotifier`: "Je weekplan staat klaar — X blokken voorgesteld" + a note if something couldn't be placed. (Per the briefing precedent, manual regenerate does not re-push — assumption PL4.)

---

## 8. Endpoints / data contract

Route prefix `planner.`, `/planner`.

- `GET /planner` (JSON):
```json
{
  "week_key": "2026-W27",
  "status": "proposed",
  "summary": "Deze week: 3× sporten, zondag je moeder, zaterdagavond date…",
  "items": [
    { "id": 88, "title": "Sporten", "category": "sport", "start_at": "2026-06-29T18:00:00+02:00", "end_at": "2026-06-29T19:30:00+02:00", "status": "proposed" },
    { "id": 90, "title": "Date night", "category": "date", "start_at": null, "end_at": null, "status": "unplaceable", "unplaceable_reason": "Geen vrij avondblok in het weekend" }
  ]
}
```
- `POST /planner/generate` — manual regenerate.
- `POST /planner/items/{item}/accept` — insert into Google Calendar → `{ google_event_id }`.
- `POST /planner/accept-all`, `POST /planner/items/{item}/reject`.
- Intentions: `GET/POST/PATCH/DELETE /planner/intentions`.
- Google auth: `GET /planner/google/connect`, `GET /planner/google/callback`.

JSON via Resources.

---

## 9. Tests (`composer test`)

### Unit (`SlotFinder` / `WeeklyPlanner`)
- Never proposes a slot overlapping work hours (weekday 09:00–17:00) or a busy event.
- Sport placed only weekday-after-17:00 or weekend daytime; mother/date only weekend; date in an evening window.
- Places sport within target_min..target_max; a tight week falls back toward target_min and marks extras unplaceable with a reason.
- Unplaceable intentions are reported, not silently dropped.

### Feature
- `GenerateWeeklyPlan` with faked Google busy times + faked Prism produces a validated plan; an AI slot overlapping busy time is rejected → deterministic fallback (`is_fallback`).
- `AcceptPlanItem` inserts an event via a faked `GoogleCalendarClient` and stores `google_event_id`; `accept-all` accepts all proposed items.
- `GoogleCalendarTokenService` refreshes an expired token.
- Idempotent per `week_key`; regenerate overwrites the proposed (non-accepted) items.
- `GET /planner` returns the documented contract.

---

## 10. Acceptance criteria

- [ ] Sunday evening a validated weekly plan is generated from Google Calendar busy times and the intentions, then pushed via ntfy + shown in the hub.
- [ ] Proposed blocks never overlap work hours or existing events; sport/mother/date respect their windows and targets.
- [ ] Tight weeks place as many as possible and clearly report what didn't fit.
- [ ] 1-click add (and add-all) insert events into Google Calendar.
- [ ] Intentions are managed via endpoints; JSON contract matches §8; all tests pass via `composer test`.

---

## 11. Confirmed decisions (signed off 2026-06-24)

- Busy times + writing via **Google Calendar (OAuth read+write)**; enables 1-click add.
- Intentions **managed in the hub** (DB; UI later).
- **Sensible default durations** chosen by the assistant (sport 90m, mother 150m, date 180m).
- Tight weeks: **place as many as possible + report the rest**.
- Proposal **Sunday evening** via ntfy + dashboard; **propose + 1-click add**.

## 12. Confirmed decisions (signed off 2026-06-24)

- **PL1** ✅ Module/route `Modules/Planner` + `/planner`.
- **PL2** ✅ Deterministic slot-finding + AI arrangement/summary, with deterministic validation (AI can't create invalid slots).
- **PL3** ✅ Default durations sport **90m**, mother **150m**, date **180m** (evening); editable per intention.
- **PL4** ✅ Manual regenerate does **not** re-push ntfy.
- **PL5** ✅ Busy times from the **primary** Google calendar only; the ICS Calendar module stays display-only.
- **PL6** ✅ Owner provisions the Google Cloud OAuth credentials.

## 13. Out of scope

- Blade markup / styling (Claude Design later).
- Two-way sync / editing accepted events (accept inserts once; edits happen in Google).
- Travel-time / location-aware scheduling.
- Multi-calendar merging beyond the configured calendar.
