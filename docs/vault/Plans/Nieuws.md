# Plan — Nieuws (News) module

Codex-ready implementation spec for the **News** module. Front-end markup is out
of scope (built later with Claude Design); this plan defines functional
behaviour, UI states and the data/JSON contract the front-end will consume.

Status: implemented 2026-06-25. Build order: module 1. See [Roadmap](../Roadmap.md).

---

## 1. Functional spec

A `/news` page that aggregates RSS/Atom feeds, grouped by topic, with read/unread
tracking and ntfy alerts on keyword matches.

- **Topics** (fixed, in config): `3d-printing` (3D-printen & making), `dev` (Dev/werk — Laravel/PHP), `fitness` (Fitness & gezondheid), `gardening` (Tuinieren/moestuin), `switch2` (Nintendo Switch 2 — games + system updates).
- Each item shows: title, short excerpt (plain-text, HTML stripped), source/feed label, relative published time, and a flag if it matched a keyword.
- Items are grouped per topic; each topic shows an unread count.
- Clicking an item opens the original URL in a new tab and marks it read.
- **Read/unread**: items start unread; user can mark a single item read, or "mark all read" (globally or per topic). "New since last visit" = unread items. The dashboard tile shows the total unread count.
- **Refresh**: a scheduled job fetches all feeds every ~30 min (config). The client polls the JSON endpoint to update the page in place.
- **Retention**: items older than 7 days (config) are pruned.
- **ntfy**: after each refresh, items whose title or summary match any configured keyword (case-insensitive substring) trigger one ntfy push, once per item.
- **Resilience**: a single failing/malformed feed must not break the page or block other feeds — log and skip it; the page renders whatever is stored.

### UI states (functional, no markup)
- **Loading**: initial fetch / poll in progress.
- **Empty**: no items stored yet (e.g. first run before the first scheduled fetch, or all feeds failed) — show an empty state with a "refresh now" affordance.
- **Error**: the JSON endpoint itself fails — show a non-blocking error; keep last-rendered items if present.
- **Per-item**: unread vs read styling; "keyword match" badge; per-topic unread badge; global unread count on the dashboard tile.

---

## 2. Data model

Module: `Modules/News`. Single-user hub → read-state is global (no per-user scoping). See assumption A1.

### Table `news_items`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `feed_key` | string | matches a feed `key` in config |
| `topic` | string | one of the topic keys |
| `guid` | string | RSS guid or, if absent, the item URL; used for dedupe |
| `title` | string | |
| `url` | string | canonical article link |
| `summary` | text | plain-text excerpt, HTML stripped, truncated (~280 chars) |
| `author` | string null | |
| `image_url` | string null | enclosure/media thumbnail if present |
| `published_at` | timestamp | from the feed; falls back to fetch time if missing |
| `is_read` | boolean | default false |
| `read_at` | timestamp null | |
| `notified` | boolean | default false; keyword-ntfy dedupe |
| `matched_keywords` | json null | keywords this item matched (for the badge) |
| `created_at` / `updated_at` | timestamps | |

- **Unique index**: (`feed_key`, `guid`) — upsert key, prevents duplicates.
- **Indexes**: (`topic`, `published_at`), (`is_read`).

### Eloquent
- `Modules\News\Models\NewsItem` with a `NewsItemBuilder` (Models/Builders/) exposing scopes: `unread()`, `forTopic(string)`, `recent(CarbonInterface $since)`, `latestPerTopic(int $limit)`.

---

## 3. Services & Actions

### `Modules\News\Services\FeedClient`
- `fetch(string $url): array<int, RawFeedItem>` — HTTP GET via `Http::timeout(config)`, parse with **laminas-feed** (`Laminas\Feed\Reader\Reader`). Normalizes each entry to a `RawFeedItem` DTO: `guid, title, url, summary (plain text), author, imageUrl, publishedAt`.
- Throws a typed `FeedUnavailable` exception on HTTP/parse failure (caller catches per-feed).

### `Modules\News\Data\RawFeedItem` (final readonly DTO)
Holds the normalized fields above before persistence.

### `Modules\News\Services\NewsService`
- `refresh(): NewsRefreshResult` — iterate configured feeds; for each, `FeedClient::fetch` (catch + log per-feed failures); upsert into `news_items` by (`feed_key`,`guid`) — preserve `is_read`/`notified` on existing rows; set `matched_keywords` on insert/update; prune items older than retention.
- `keywordMatches(NewsItem $item): array<string>` — case-insensitive substring match of configured keywords against `title` + `summary`.
- Returns a `NewsRefreshResult` DTO: `fetched`, `stored`, `skippedFeeds`, `failedFeeds`.

### Actions (`Modules/News/Actions/`)
- `RefreshFeeds` (`__invoke(): NewsRefreshResult`) — calls `NewsService::refresh()`.
- `CheckNewsKeywords` (`__invoke(): int`) — find stored items with `notified = false` AND non-empty `matched_keywords`, send one ntfy push each (title + source + link), mark `notified = true`. Returns count sent.
- `MarkItemsRead` (`__invoke(array $ids): int`) and `MarkAllRead` (`__invoke(?string $topic = null): int`).

### View model
- `Modules\News\View\ViewModels\NewsViewModel` — read-side: topics with their latest N items (config `items_per_topic`), per-topic unread counts, total unread, `last_refreshed_at`.

---

## 4. Config (`Modules/News/config/config.php`)

```php
return [
    'refresh_minutes' => env('NEWS_REFRESH_MINUTES', 30),
    'items_per_topic' => env('NEWS_ITEMS_PER_TOPIC', 6),
    'retention_days'  => env('NEWS_RETENTION_DAYS', 7),
    'request_timeout' => env('NEWS_REQUEST_TIMEOUT', 10),

    'topics' => [
        '3d-printing' => '3D-printen & making',
        'dev'         => 'Dev & werk',
        'fitness'     => 'Fitness & gezondheid',
        'gardening'   => 'Tuinieren / moestuin',
        'switch2'     => 'Nintendo Switch 2',
    ],

    // feed URLs to VERIFY at build time (feeds move)
    'feeds' => [
        ['key' => 'hackaday',     'topic' => '3d-printing', 'label' => 'Hackaday',       'url' => 'https://hackaday.com/blog/feed/'],
        ['key' => 'prusa-blog',   'topic' => '3d-printing', 'label' => 'Prusa Blog',     'url' => 'https://blog.prusa3d.com/feed/'],
        ['key' => 'laravel-news', 'topic' => 'dev',         'label' => 'Laravel News',   'url' => 'https://feed.laravel-news.com/'],
        ['key' => 'stitcher',     'topic' => 'dev',         'label' => 'Stitcher.io',    'url' => 'https://stitcher.io/rss'],
        ['key' => 'sbs',          'topic' => 'fitness',     'label' => 'Stronger by Science', 'url' => 'https://www.strongerbyscience.com/feed/'],
        ['key' => 'gardeners-world-nl', 'topic' => 'gardening', 'label' => 'Gardeners World NL', 'url' => 'https://www.gardenersworldmagazine.nl/feed/'],
        ['key' => 'nintendolife', 'topic' => 'switch2',     'label' => 'Nintendo Life',  'url' => 'https://www.nintendolife.com/feeds/latest'],
    ],

    'keywords' => ['Bambu', 'Bambu firmware', 'Laravel', 'PHP 8', 'Switch 2'],
];
```

---

## 5. Scheduling

Register in the module service provider or `routes/console.php` (consistent with Weather):
- `news:refresh` — runs `RefreshFeeds` then `CheckNewsKeywords`. `everyThirtyMinutes()->withoutOverlapping()`.
- Artisan command also runnable manually for testing.

---

## 6. ntfy (shared hub topic)

Owner chose **one shared hub topic** for all modules. This plan introduces a
shared app-level notifier (Weather currently has its own `NtfyWeatherNotifier`):

- New `App\Services\Ntfy\HubNotifier` with `send(string $title, string $message): void`, configured by a hub-level `config/ntfy.php` (`url`, `topic`, `token`). Mockable in tests.
- News uses `HubNotifier`. **Follow-up (separate task, not this plan):** migrate Weather to `HubNotifier`.

---

## 7. Endpoints / data contract

All under the `news.` route name prefix, `/news`.

- `GET /news` — page (minimal Blade shell; FE built later). Supports `Accept: application/json` (or a sibling `/news/feed.json`) returning the contract below.
- `GET /news/items` (JSON) — the feed payload:

```json
{
  "topics": [
    {
      "key": "3d-printing",
      "label": "3D-printen & making",
      "unread": 3,
      "items": [
        {
          "id": 1242,
          "title": "Bambu firmware 1.08 released",
          "url": "https://bambulab.com/...",
          "summary": "Adds AMS humidity tracking and faster first-layer calibration…",
          "source": "Hackaday",
          "topic": "3d-printing",
          "published_at": "2026-06-24T12:10:00+02:00",
          "is_read": false,
          "image_url": null,
          "matched_keywords": ["Bambu", "Bambu firmware"]
        }
      ]
    }
  ],
  "total_unread": 7,
  "last_refreshed_at": "2026-06-24T14:30:00+02:00"
}
```

- `POST /news/items/{item}/read` → `{ "is_read": true }`.
- `POST /news/read-all` (optional body `{ "topic": "dev" }`) → `{ "marked": 5 }`.
- `POST /news/refresh` → triggers `RefreshFeeds` synchronously (or queues), returns `NewsRefreshResult` summary. Used by the "refresh now" affordance.

JSON shapes go through a `Modules\News\Http\Resources\NewsItemResource` (per the Resources convention — no raw models).

---

## 8. Tests (`composer test`)

### Unit
- `FeedClient` parses a fixture RSS feed and a fixture Atom feed into normalized items; strips HTML from summary; uses URL as guid when guid is absent.
- `FeedClient` throws `FeedUnavailable` on a malformed body / non-200.
- `NewsService` dedupe: re-fetching the same guid does not create a duplicate and preserves `is_read`/`notified`.
- `NewsService` prunes items older than `retention_days`.
- `NewsService::keywordMatches` is case-insensitive and matches title or summary.

### Feature
- `RefreshFeeds` with `Http::fake` stores items grouped by topic.
- A failing feed is skipped while other feeds still store (resilience).
- `CheckNewsKeywords` sends exactly one ntfy per matching item and not again on the next run (`notified` flag). Uses a faked `HubNotifier`.
- `GET /news/items` returns the documented JSON contract with correct unread counts.
- `POST /news/items/{id}/read` and `/news/read-all` update state and counts.

---

## 9. Acceptance criteria

- [ ] `/news` renders topics with their latest items from stored data, never crashing on a bad feed.
- [ ] Scheduled `news:refresh` fetches every 30 min, upserts, prunes, and sends keyword ntfy once per item.
- [ ] Read/unread works: marking read updates counts; unread = "new".
- [ ] JSON contract matches §7 exactly.
- [ ] All new unit + feature tests pass via `composer test`.
- [ ] Feeds, keywords and cadence are config-driven.

---

## 10. Confirmed decisions (signed off 2026-06-24)

- **A1** ✅ The hub is single-user/local (no auth) → read-state is **global**, not per-user.
- **A2** ✅ Proposed feed list (§4) is a starting point the assistant curates; final URLs verified at build, owner adjusts later in config.
- **A3** ✅ Default keywords: `Bambu`, `Bambu firmware`, `Laravel`, `PHP 8`, `Switch 2`.
- **A4** ✅ Defaults: refresh 30 min, 6 items/topic, 7-day retention.
- **A5** ✅ Introduce a shared `HubNotifier` now; migrating Weather to it is a separate follow-up task (not in this plan).

Implementation note 2026-06-25: feed URLs were verified during build. All3DP returned Cloudflare `403`, `php.watch/feed/changelog` returned `404`, and the gardening feed was set to Gardeners World NL.

---

## 11. Out of scope

- Blade markup / styling (Claude Design later).
- A feed/keyword management UI (config-only for now; data model already supports adding a UI later).
- Per-user accounts / multi-user read-state.
- Deals — see the separate Dealtracker plan.
