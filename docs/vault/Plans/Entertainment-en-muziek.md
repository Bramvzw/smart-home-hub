# Plan — Entertainment & muziek (Entertainment) module

Codex-ready spec for an AI module with three sub-domains: **film recommendations**
(cinema + Netflix/Prime, AI taste-curated), **concerts** (Hedon + all NL, AI
relevance for pushes), and **new music** (releases from Spotify-followed artists).
Front-end markup is out of scope (Claude Design later); this plan covers
functional behaviour, UI states and the data/JSON contract.

Status: implemented 2026-06-25. Build order: module 7 (after Dealtracker per roadmap; this is
the most integration-heavy module). Depends on the shared `HubNotifier`, the
shared hub AI config, **Prism**, and the existing **Spotify module**. See
[Roadmap](../Roadmap.md).

---

## 1. Functional spec

### Films
- Candidates from **TMDB**: what's **now in NL cinemas** (`now_playing`, region NL) + titles available on **Netflix** and **Prime Video** in NL (TMDB watch providers).
- Claude curates/ranks candidates against a **taste profile** the owner seeds (favorite films/genres) and refines via **thumbs up/down** feedback, with a short Dutch "waarom jij dit leuk vindt" per pick.
- No local showtimes (NL-wide "now in cinemas" only).

### Concerts
- The hub shows a **broad** list: everything at **Hedon (Zwolle)** + concerts across **NL**.
- **ntfy** fires only for **relevant** concerts: (a) artists the owner **follows on Spotify**, (b) **Hedon highlights**, (c) AI **"might like"** (acts similar to his taste).

### New music
- **New releases (albums + singles)** from artists the owner **follows on Spotify** → ntfy per the bundling rule (assumption E5).

### Delivery
- **Event-driven ntfy**: a daily check pushes new followed-artist releases and newly-found relevant concerts. Films shown in the hub (refreshed periodically); no film push unless desired later.

### UI states (functional, no markup)
- Three sections (films / concerts / music), each with loading, empty, error, and partial (one source failed) states.
- Films show the AI "why" text + thumbs controls; concerts show a relevance badge (followed / Hedon / might-like); music shows release type (album/single/EP).

---

## 2. External dependencies (research/keys at build)

- **TMDB API** (key in config) — `now_playing` (region NL), `watch/providers` for Netflix/Prime NL, details/posters.
- **Concerts** — a gig source: Ticketmaster Discovery API (NL events) and/or Bandsintown (artist-based) + **Hedon agenda** (scrape/RSS). Behind a `ConcertProvider` interface; resilient per source. (Source choice — assumption E2.)
- **Spotify** — reuse the existing Spotify module to read **followed artists** and their **recent releases**. Requires the Spotify OAuth scope `user-follow-read` (and `user-library`/`user-top-read` not needed since followed-only). **Scope extension dependency** — assumption E3.
- **Prism** (shared hub AI config) — film curation + concert "might-like" relevance.

---

## 3. Data model

Module `Modules/Entertainment` (route `/entertainment`) — confirm name (assumption E1).

### `taste_profile` (single row)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `favorite_titles` | json | seeded favorite films |
| `genres` | json | liked genres |
| `notes` | text null | free-text taste notes |
| `updated_at` | timestamp | |

### `film_feedback`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tmdb_id` | unsignedInteger | |
| `title` | string | |
| `sentiment` | enum(`up`,`down`) | |
| `created_at` | timestamp | |

### `film_recommendations`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tmdb_id` | unsignedInteger | |
| `title` | string | |
| `overview` | text null | |
| `availability` | json | e.g. `["cinema","netflix"]` |
| `poster_url` | string null | |
| `why` | text null | AI Dutch pitch |
| `score` | unsignedTinyInteger null | AI rank |
| `dismissed` | boolean | default false |
| `refreshed_at` | timestamp | |

### `concerts`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `source` | enum(`ticketmaster`,`bandsintown`,`hedon`) | |
| `external_id` | string null | dedupe |
| `artist` | string | |
| `title` | string null | |
| `venue` | string null | |
| `city` | string null | |
| `date` | datetime | |
| `url` | string null | |
| `relevance` | enum(`followed`,`hedon`,`might_like`,`none`) | `none` = browse-only, no push |
| `notified` | boolean | default false |
| `created_at`/`updated_at` | timestamps | |

- Unique (`source`, `external_id`).

### `music_releases`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `spotify_id` | string | release id |
| `artist` | string | |
| `title` | string | |
| `type` | enum(`album`,`single`,`ep`) | |
| `release_date` | date | |
| `url` | string null | |
| `image_url` | string null | |
| `notified` | boolean | default false |
| `created_at`/`updated_at` | timestamps | |

- Unique (`spotify_id`).

### Eloquent models per table under `Modules\Entertainment\Models`.

---

## 4. Services & Actions

### Clients / providers
- `Services\Tmdb\TmdbClient` — `nowPlayingNl()`, `watchProviders(int $tmdbId)`, `details(int $tmdbId)`.
- `Contracts\ConcertProvider` + `Services\Concerts\TicketmasterProvider`, `BandsintownProvider`, `HedonProvider` (each resilient, typed failure).
- `Services\Music\SpotifyReleasesService` — via the Spotify module: `followedArtists()`, `recentReleasesFor(array $artistIds, CarbonImmutable $since)`.

### AI
- `Services\EntertainmentCurator` (Prism):
  - `curateFilms(array $candidates, TasteProfile $p, Collection $feedback): array<FilmPick>` — rank + Dutch "why".
  - `concertRelevance(Concert $c, array $followedArtists, TasteProfile $p): string` — returns `followed|hedon|might_like|none`.

### Actions (`Modules/Entertainment/Actions/`)
- `RefreshFilms` (weekly) — fetch TMDB candidates → curate → upsert `film_recommendations`.
- `RefreshConcerts` (daily) — fetch providers → upsert `concerts`, compute `relevance`.
- `RefreshMusicReleases` (daily) — followed artists' new releases → upsert `music_releases`.
- `NotifyEntertainment` (daily) — push unnotified music releases (bundled per E5) + unnotified relevant concerts (`relevance != none`); mark notified.
- `RecordFilmFeedback`, `UpdateTasteProfile`, `DismissFilm`.
- `Modules\Entertainment\View\ViewModels\EntertainmentViewModel`.

---

## 5. Config (`Modules/Entertainment/config/config.php`)

```php
return [
    'region' => 'NL',
    'tmdb' => ['api_key' => env('TMDB_API_KEY'), 'timeout' => 10],
    'concerts' => [
        'sources' => ['ticketmaster', 'bandsintown', 'hedon'],
        'ticketmaster_key' => env('TICKETMASTER_KEY'),
        'hedon_agenda_url' => env('HEDON_AGENDA_URL'),
    ],
    'films_refresh' => 'weekly',
    'check_time' => env('ENTERTAINMENT_CHECK_TIME', '09:00'),
    'music' => ['include' => ['album', 'single', 'ep'], 'bundle_push' => true],
    'ai' => ['model' => env('ENTERTAINMENT_MODEL', 'claude-sonnet-4-x')],
];
```

---

## 6. Scheduling

- `entertainment:refresh-concerts` + `entertainment:refresh-music` — daily (e.g. `dailyAt('09:00')`).
- `entertainment:refresh-films` — weekly.
- `entertainment:notify` — daily after refresh → `NotifyEntertainment`. `withoutOverlapping()`.

---

## 7. ntfy

Via `HubNotifier` (shared topic):
- **New music**: one push per new followed-artist release, or bundled into one daily push (assumption E5).
- **Relevant concert**: push when a concert with `relevance ∈ {followed, hedon, might_like}` is newly found.

---

## 8. Endpoints / data contract

Route prefix `entertainment.`, `/entertainment`.

- `GET /entertainment` (JSON): `{ films: [...], concerts: [...], music: [...] }` — each item with the fields from §3 (films include `why`, `availability`; concerts include `relevance` badge; music includes `type`).
- `GET /entertainment/concerts` — broad concert list (all, including `relevance: none`).
- `POST /entertainment/films/{film}/feedback` (`{ "sentiment": "up" }`).
- `POST /entertainment/films/{film}/dismiss`.
- `GET` / `PUT /entertainment/taste` — read/update the taste profile.
- `POST /entertainment/refresh` — manual refresh (no extra push unless E-equivalent).

JSON via Resources.

---

## 9. Tests (`composer test`)

### Unit
- `TmdbClient` parses fixtures (now_playing, watch providers).
- Each `ConcertProvider` parses a fixture; aggregation dedupes by (`source`,`external_id`).
- Relevance mapping: followed-artist → `followed`; Hedon show → `hedon`; else AI → `might_like`/`none`.

### Feature
- `RefreshMusicReleases` with a faked Spotify client stores new releases (albums + singles); existing ones not duplicated.
- `RefreshConcerts` resilient when one provider fails.
- `NotifyEntertainment` pushes once per new release (bundled per config) and per relevant concert via faked `HubNotifier`; never re-notifies.
- `curateFilms` with faked Prism returns ranked picks with `why`; feedback influences the prompt (assert it is included).
- `GET /entertainment` returns the documented contract; feedback + taste endpoints persist.

---

## 10. Acceptance criteria

- [x] Films section shows AI-curated, taste-aware picks across cinema/Netflix/Prime with a Dutch "why" and thumbs feedback.
- [x] Concerts list is broad (Hedon + NL); only relevant concerts (followed/Hedon/might-like) trigger ntfy.
- [x] New followed-artist releases (albums + singles) trigger ntfy per the bundling rule.
- [x] Sources are resilient; one failing provider does not break the others.
- [x] JSON contracts match §8; all new tests pass via `composer test`.

---

## 11. Confirmed decisions (signed off 2026-06-24)

- Films: cinema (NL-wide "now playing") + Netflix + Prime, **AI taste-curated**; taste **self-seeded + thumbs feedback**.
- Concerts: **broad** (Hedon + all NL) in the hub; **push** for **followed artists + Hedon highlights + AI "might like"**.
- Music: **followed Spotify artists only**; new = **albums + singles**.
- Delivery: **event-driven ntfy** (daily check), no weekly digest, not in the briefing.

## 12. Confirmed decisions (signed off 2026-06-24)

- **E1** ✅ Module/route `Modules/Entertainment` + `/entertainment`.
- **E2** ✅ Concert sources: **Ticketmaster (NL) + Bandsintown + Hedon agenda** (validated at build).
- **E3** ✅ Extend the Spotify OAuth scope with `user-follow-read` (owner re-links Spotify once).
- **E4** ✅ Daily check at **09:00** for concerts/releases; films refreshed weekly.
- **E5** ✅ New-music pushes are **bundled into one daily push**.

## 13. Out of scope

- Blade markup / styling (Claude Design later).
- Local cinema showtimes / ticket buying.
- Concert ticket purchasing.
- Music playback (the Spotify module already handles playback).
