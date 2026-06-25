# Plan — Dagelijkse briefing (Daily Briefing) module

Codex-ready implementation spec for the **Briefing** module: one natural-language
Dutch morning digest that aggregates the other modules. Front-end markup is out
of scope (Claude Design later); this plan covers functional behaviour, UI states
and the data/JSON contract.

Status: implemented 2026-06-25. Build order: module 2. Depends on the shared `HubNotifier`
(introduced by the [News plan](Nieuws.md)) and on Prism. See [Roadmap](../Roadmap.md).

---

## 1. Functional spec

Every morning the hub composes a short, friendly Dutch briefing from whatever
modules can contribute, sends it via ntfy at **08:00**, and shows it on the
dashboard.

- **Content** (all that have data that day): today's **weather**, today's **agenda**, top **tasks + the daily learning goal**, and a **news digest**.
- **Tone/length**: Dutch, informal (je/jij), **medium** — a short paragraph per topic.
- **Generation**: once per morning on a schedule, **cached for the day**; a **manual "regenerate"** affordance re-runs it.
- **Delivery**: both an ntfy push at 08:00 **and** a briefing section/tile on the dashboard.
- **Resilience / fallback**: if the Claude API (via Prism) is unavailable or no key is set, fall back to a **templated, non-AI** Dutch briefing built from the same data (flagged `is_fallback`).
- **Graceful degradation**: a module that has no data that day (or isn't built yet) simply contributes nothing — its section is omitted, the briefing still renders.

### UI states (functional, no markup)
- **Today's briefing present**: show the generated text + a per-section breakdown, `generated_at`, and a subtle "AI" vs "fallback" indicator.
- **Not yet generated**: show a "generate now" affordance (e.g. before the first run).
- **Generating**: in-progress state while a manual regenerate runs.
- **Fallback**: indicate the briefing was built without AI.

---

## 2. Architecture — extensible briefing-source contract

Modules contribute via a shared contract so the Briefing module never depends on
them directly (and new modules plug in without touching it).

### `App\Contracts\BriefingSource` (app-level, shared)
```php
interface BriefingSource
{
    public function key(): string;          // e.g. 'weather'
    public function label(): string;         // e.g. 'Weer'
    public function priority(): int;         // ordering in the briefing
    public function contribute(CarbonImmutable $date): ?BriefingSection; // null = nothing today
}
```

### `App\Support\Briefing\BriefingSection` (DTO)
`key`, `label`, `priority`, `summary` (short plain text for fallback/heading), `data` (array — structured facts handed to Claude).

- Each contributing module binds its implementation **tagged** `briefing.source` in its service provider.
- The Briefing module resolves all tagged sources, calls `contribute()`, drops nulls, sorts by `priority()`.

### Sources to provide (each lives in its own module)
| Source | Module | Contributes |
|---|---|---|
| `WeatherBriefingSource` | Weather (exists) | today's forecast: temp range, rain/wind, alert status |
| `CalendarBriefingSource` | Calendar (exists) | today's events (time, title) |
| `TasksBriefingSource` | Tasks (exists; habits later) | top open tasks + today's habits (when the habit tracker lands) |
| `NewsBriefingSource` | News (module 1) | top unread items per topic for the digest |
| `LearningGoalBriefingSource` | Daily learning goal (module 6) | today's learning goal (when that module lands) |

> Sequencing: build the contract + Weather/Calendar/Tasks/News sources now; the
> learning-goal source is added when module 6 is built. The briefing degrades
> gracefully until then.

---

## 3. Generation flow

1. Scheduled command `briefing:generate` runs at **08:00** (`dailyAt('08:00')->withoutOverlapping()`).
2. Collect contributions from all `briefing.source` services for "today".
3. `BriefingComposer::compose(array $sections): ComposedBriefing`:
   - **AI path**: build a structured prompt (Dutch, informal, medium length, one short paragraph per section) and call Claude via **Prism**; return the generated body.
   - **Fallback path**: on any Prism error / missing key, `TemplatedBriefingComposer` renders a deterministic Dutch text from `BriefingSection::$summary` per section; mark `is_fallback = true`.
4. Persist as today's briefing (upsert by date), then push via `HubNotifier` (title `"Goedemorgen"`, body = briefing text, truncated for ntfy if long).
5. **Manual regenerate** re-runs steps 2-4 and overwrites today's row. See assumption B2 re: whether it re-pushes ntfy.

---

## 4. Data model

Module `Modules/Briefing`. Table `briefings`:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `date` | date | unique — one briefing per day |
| `body` | text | the composed Dutch text |
| `sections` | json | the `BriefingSection` array used (key,label,summary,data) |
| `generated_at` | timestamp | |
| `model` | string null | Prism model used (null on fallback) |
| `is_fallback` | boolean | default false |
| `created_at`/`updated_at` | timestamps | |

- `Modules\Briefing\Models\Briefing`. Retention: keep last N days (config `retention_days`, default 14); prune older on generate. See assumption B3.

---

## 5. Services & Actions

- `Modules\Briefing\Services\BriefingSourceRegistry` — resolves tagged `briefing.source` services, calls `contribute()`, filters nulls, sorts by priority.
- `Modules\Briefing\Services\BriefingComposer` — AI composition via Prism; delegates to fallback on failure.
- `Modules\Briefing\Services\TemplatedBriefingComposer` — deterministic non-AI text.
- `Modules\Briefing\Data\ComposedBriefing` (DTO) — `body`, `model`, `isFallback`.
- `Modules\Briefing\Actions\GenerateBriefing` (`__invoke(?CarbonImmutable $date = null, bool $push = true): Briefing`) — orchestrates collect → compose → persist → push.
- `Modules\Briefing\View\ViewModels\BriefingViewModel` — today's briefing for the page/tile.

---

## 6. Prism integration

- `BriefingComposer` uses **Prism** to call Claude. Config (`Modules/Briefing/config/config.php`):
  - `model` (default `claude-sonnet-4-6`, verified against Anthropic model docs on 2026-06-25), `max_tokens`, `temperature`.
  - System prompt: Dutch, informal, friendly, medium length; one short paragraph per provided section; never invent data not in the input.
  - The user/content message is the structured `sections` data (JSON) + instructions.
- Prism is mockable; tests fake the LLM response. Anthropic key in env (shared hub AI config — see assumption B4).

---

## 7. Config (`Modules/Briefing/config/config.php`)

```php
return [
    'time'            => env('BRIEFING_TIME', '08:00'),
    'retention_days'  => env('BRIEFING_RETENTION_DAYS', 14),
    'language'        => 'nl',
    'tone'            => 'informal',
    'length'          => 'medium',
    'ai' => [
        'model'       => env('BRIEFING_MODEL', 'claude-sonnet-4-6'),
        'max_tokens'  => env('BRIEFING_MAX_TOKENS', 700),
        'temperature' => env('BRIEFING_TEMPERATURE', 0.5),
    ],
];
```

---

## 8. Scheduling

- `briefing:generate` — `dailyAt(config('briefing.time'))->withoutOverlapping()` → `GenerateBriefing` with push.
- Artisan command runnable manually.

---

## 9. Endpoints / data contract

Route prefix `briefing.`, `/briefing`.

- `GET /briefing` (JSON or `Accept: application/json`):
```json
{
  "date": "2026-06-24",
  "body": "Goedemorgen! Het wordt vandaag 24°C en droog…",
  "sections": [
    { "key": "weather",  "label": "Weer",         "summary": "24°C, droog, geen regenalarm" },
    { "key": "calendar", "label": "Agenda",        "summary": "2 afspraken: 10:00 standup, 14:00 tandarts" },
    { "key": "tasks",    "label": "Taken",         "summary": "Top 3 + leerdoel van vandaag" },
    { "key": "news",     "label": "Nieuws",        "summary": "3 items uitgelicht" }
  ],
  "generated_at": "2026-06-24T08:00:03+02:00",
  "is_fallback": false,
  "model": "claude-sonnet-4-x"
}
```
- `POST /briefing/regenerate` → re-runs `GenerateBriefing` (push per assumption B2), returns the new payload.

Goes through a `BriefingResource`.

---

## 10. Tests (`composer test`)

### Unit
- `BriefingSourceRegistry` collects tagged sources, drops null contributions, sorts by priority.
- `TemplatedBriefingComposer` produces deterministic Dutch text from sections.

### Feature
- `GenerateBriefing` with a faked Prism response composes + stores + pushes via a faked `HubNotifier` (one push).
- Fallback: when Prism throws / no key, the briefing is stored with `is_fallback = true` and a templated body; ntfy still sent.
- Graceful degradation: with only some sources returning data (others null/absent), the briefing renders the available sections only.
- Idempotency: `dailyAt` generation upserts one row per date; regenerate overwrites.
- `GET /briefing` returns the documented contract; `POST /briefing/regenerate` overwrites and returns the new payload.
- Each module's `BriefingSource` (Weather/Calendar/Tasks/News) returns the expected `BriefingSection` for known fixture data, and `null` when there's nothing.

---

## 11. Acceptance criteria

- [ ] At 08:00 a Dutch, informal, medium briefing is generated from available modules, stored, and pushed via ntfy.
- [ ] Dashboard endpoint returns today's briefing per the §9 contract; a regenerate endpoint overwrites it.
- [ ] When AI is unavailable, a templated fallback briefing is produced and flagged.
- [ ] Modules contribute via the `BriefingSource` contract; missing/empty sources are skipped without errors.
- [ ] All new unit + feature tests pass via `composer test`.

---

## 12. Confirmed decisions (signed off 2026-06-24)

- Delivery: **both** ntfy push **and** dashboard.
- Content: weather + agenda + tasks & learning goal + news digest.
- Length **medium**; language/tone **Dutch, informal**.
- Time **08:00**.
- Generation: **once in the morning + manual regenerate**.
- Architecture: **extensible `BriefingSource` contract** per module.
- Fallback: **templated non-AI briefing** when Claude is unavailable.

## 13. Confirmed decisions (signed off 2026-06-24)

- **B1** ✅ Module/route naming `Modules/Briefing`, `/briefing`.
- **B2** ✅ Manual regenerate **only refreshes the dashboard** — it does NOT re-send ntfy (avoids double pushes).
- **B3** ✅ Keep the last **14** days of briefings; prune older on generate.
- **B4** ✅ Anthropic API key lives in a **shared hub AI config** (env), reused by all AI modules (not per-module keys). This is the canonical AI-config decision for all future AI-module plans.

Implementation note 2026-06-25: shared AI config was added as `config/ai.php` with `HUB_AI_ANTHROPIC_API_KEY`. Manual regenerate uses `push = false`; scheduled generation uses `push = true`.

## 14. Out of scope

- Blade markup / styling (Claude Design later).
- The learning-goal source (built with module 6); the contract supports it.
- Migrating other modules' notifications — covered by the shared `HubNotifier` from the News plan.
