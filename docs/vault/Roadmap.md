# Module Roadmap

Planned and wanted modules for the smart-home hub, with the decisions made per
module. This is a living document — update it as scope changes. Personal
context that drives these choices (household, interests, hardware) is summarized
at the bottom.

Last updated: 2026-06-25.

---

## Detailed per-module plans (Codex-ready)

Each module below has a full functional + technical spec under `Plans/`, built
to be executed by Codex. Front-end is intentionally excluded (built later with
Claude Design); plans cover behaviour, data model and the JSON contract.

| Plan | Module | Status |
|---|---|---|
| [Nieuws](Plans/Nieuws.md) | News (RSS) | spec signed off |
| [Dagelijkse briefing](Plans/Dagelijkse-briefing.md) | Briefing (AI) | spec signed off |
| [Tasks: gewoontes & onderhoud](Plans/Tasks-gewoontes-en-onderhoud.md) | Tasks (extension) | spec signed off |
| [3D-printer voorraad](Plans/Bambu-voorraad.md) | Printer | spec signed off |
| [Supermarkt → recepten](Plans/Supermarkt-recepten.md) | Recipes (AI) | spec signed off |
| [Entertainment & muziek](Plans/Entertainment-en-muziek.md) | Entertainment (AI) | spec signed off |
| [Dealtracker](Plans/Dealtracker.md) | Deals | spec signed off |
| [AI agenda-planner](Plans/AI-agenda-planner.md) | Planner (AI) | spec signed off |

**Cross-cutting decisions** (apply to all plans): one shared hub ntfy topic via an
app-level `HubNotifier`; AI modules use **Prism** with one shared hub Anthropic
key; plans define the data/JSON contract only (no Blade markup).

---

## Build-ready (no new hardware needed)

### 1. Nieuws (RSS)
Editorial news feeds, grouped by topic.
- **Topics**: 3D-printing & making · Dev/work (Laravel/PHP) · Fitness & health · Gardening/moestuin · Nintendo Switch 2 (games + system updates).
- **Display**: headline + short excerpt + source + relative time; click opens the original.
- **Refresh**: ~30 min, ~6 items/topic, items older than 7 days drop off.
- **Alerts**: ntfy on keyword matches (e.g. `Bambu firmware`, `Laravel` release).
- **Tech**: `laminas-feed` for RSS/Atom; `FeedClient` + `NewsService` (aggregate, dedupe, cache) + `NewsViewModel` + `CheckNewsKeywords` action; feeds/keywords in config; `/news` tile + tests.
- **Note**: deals are NOT here — see the Dealtracker.

### 2. Bambu Lab (H2D) — filament & parts inventory
Stock tracking only; the printer is already owned.
- Filament inventory (spools: type, colour, remaining) + spare-parts/onderdelen tracking.
- **No** live print status / progress / AMS monitoring. No MQTT, no new hardware.

### 3. Dagelijkse briefing (AI — flagship)
One natural-language morning digest that ties the modules together.
- Combines weather + calendar + tasks + a recipe idea + greenhouse climate + **the news digest** + **the daily learning goal**.
- The news digest folds INTO this briefing (not a separate digest).

### 4. Supermarkt-aanbiedingen → recepten (AI)
- Fetch the weekly offers at **Lidl & Albert Heijn**, then have Claude generate recipes based on the discounted ingredients, tuned to his preferences (e.g. higher-protein for fitness), with a shopping list.

### 5. Entertainment & muziek (AI)
Three parts, AI-curated:
- **Films**: what's in cinema (bios) + on Netflix/Prime, with a "why you'll like it" pitch.
- **Concerts**: **broad** — everything at Hedon (Zwolle) and elsewhere in NL; discovery is welcome, not filtered.
- **New music**: **only** releases from his Spotify artists (followed/top). Reuses the existing Spotify module.
- Concerts via a gig API (Songkick/Bandsintown/Ticketmaster) and/or Hedon's agenda.

### 6. Dagelijks leerdoel (AI)
- Every day a bite-sized learning goal, AI-generated, free-form but within his interest areas (3D-printing, dev/Laravel-PHP, fitness/health, gardening, gaming/Switch 2, cooking, music).
- Surfaced in the Dagelijkse briefing and/or as a trackable daily task in Tasks (ties to the habit/streak tracker).
- Open: format (nudge vs mini-lesson + resource), rotate-all vs steerable focus.

---

## Heavier / external integrations

### 7. Dealtracker — price watchlist (separate module)
A watchlist of specific products with price-drop alerts (NOT a deals feed).
- Add specific products; the module periodically fetches each product's current price and ntfy's on **every price drop** (price lower than the previous check).
- **Sources** (owner: "you choose"): bol.com + Amazon + Tweakers Pricewatch. Pragmatic phased plan — v1: bol.com (official Partner/Open API, stable) + a generic per-URL price-fetcher fallback (CSS/JSON-LD extraction) covering Tweakers/Amazon best-effort; Amazon proper via Keepa/PA-API later.
- Stores price history per product to detect drops. Scheduled price-check action → ntfy.

### 8. AI agenda-planner
Plans flexible recurring intentions into the week around fixed commitments.
- **Intentions**: gym 3-4×/week, visit mother 1×/week, date night 1×/week (placement can vary).
- **Hard constraint**: works weekdays 09:00–17:00.
- **Time prefs**: gym on weekdays after 17:00 AND weekend daytime; mother + date night preferably in the weekend.
- **Behaviour**: reads busy times from the Calendar module (ICS = read-only), Claude proposes a weekly schedule; **propose + 1-click add** per item.
- **Prerequisite**: Google Calendar write access (OAuth write scope) — the gating work. The current Calendar module only reads ICS; reuse the Spotify OAuth/token pattern (`SpotifyTokenService`) as a model.
- **Delivery**: weekly on Sunday evening via ntfy + dashboard.

---

## Integrate INTO the existing Tasks module (not separate modules)

### 9. Habit / streak tracker
- Recurring habits with streaks on the kanban board. Ties to health/routine.

### 10. Onderhoud / maintenance reminders
- Recurring maintenance tasks (printer, garden seasonal, etc.) live in Tasks. Calendar integration possibly later.

---

## Waiting on hardware

- **Moestuin (greenhouse/kas)** — Tuya temperature + humidity sensors for greenhouse climate; catalog of his plants with per-plant care instructions; seasonal sow/harvest calendar. **No** weather-tied watering advice (plants are in a greenhouse). Needs: buy a temp/humidity sensor.
- **Presence (aanwezigheid)** — mmWave presence sensor (e.g. Aqara FP2) → smarter per-room lighting. Needs: buy a sensor.

---

## Proposed build order

Nieuws → Dagelijkse briefing → Tasks (habits + maintenance) → Bambu inventory → Supermarkt/recepten → Dealtracker → Entertainment & muziek → AI agenda-planner → (Moestuin/Presence when sensors arrive).

The news feed infrastructure (HTTP client, parsing, caching, scheduled keyword checks → ntfy) is reused by the Dagelijkse briefing and the daily learning goal, which is why Nieuws goes first.

---

## Floated, not committed

AI day-planner/time-blocking · focus mode (Pomodoro + lighting/Spotify scene) · AI weekly review · 3D-print project queue · natural-language scenes.

## Dropped

Philips air-purifier module · moestuin-dokter (vision plant diagnosis) · print-fail diagnosis (vision) · afvalkalender · energieprijzen · public transport / OV.

---

## Owner context (drives the choices)

- Lives alone, has a girlfriend. Works weekdays 09:00–17:00; Laravel/PHP developer.
- Interests: sport & fitness, health & routine, cooking, music, gaming (owns a Switch 2).
- Hobbies/hardware: a greenhouse vegetable garden (moestuin in a kas); a Bambu Lab **H2D** 3D printer; smart lighting (Tuya/Calex/Govee).
- Existing modules to build on: Spotify (playback + followed artists), Tasks (kanban), Calendar (ICS feeds), Lighting, Weather, PhonePing.
