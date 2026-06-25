# Plan — Dealtracker (Deals) module

Codex-ready spec for a **price-watchlist**: the owner adds a product by name, the
module finds it at the retailers, tracks the price a few times a day, and pushes
an ntfy on every price drop. Front-end markup is out of scope (Claude Design
later); this plan covers functional behaviour, UI states and the data/JSON
contract.

Status: implemented 2026-06-25. Build order: module 6 (per roadmap). Depends on the shared
`HubNotifier`. See [Roadmap](../Roadmap.md).

---

## 1. Functional spec

- The owner adds a **product by name**. The module **searches each retailer**
  (bol.com, Amazon, Tweakers) for that name and proposes matching listings.
- Because name-matching is error-prone, the owner **reviews the proposed matches**
  (confirm/remove per retailer) before tracking starts — this prevents false
  alerts from a wrong match.
- For each confirmed listing, the module checks the price **a few times per day**
  and stores a **price history**.
- On **any price drop** vs the previous observation → **ntfy** with: product name,
  **old → new price**, whether it's the **lowest ever**, the retailer, and a
  **direct buy link**.
- **No target price** — every drop notifies.

### Retailer feasibility (phased; owner: "you pick")
- **bol.com**: official Catalog/Retailer API with **search + price** → the reliable v1 source.
- **Amazon**: PA-API (affiliate) or Keepa (paid) — best-effort, later.
- **Tweakers**: Pricewatch **search + price scrape** — best-effort, fragile.
- Each retailer sits behind one interface; a failing retailer is skipped and logged.

### UI states (functional, no markup)
- **Watchlist**: products with their per-retailer current price, lowest-ever, last-checked, and a drop indicator.
- **Add product → review matches**: candidate listings per retailer with confirm/remove.
- **Empty / loading / error / partial** (a retailer failed) states.

---

## 2. Data model

Module `Modules/Deals` (route `/deals`, label "Dealtracker") — confirm name (assumption D1).

### `watched_products`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | what the owner typed |
| `query` | string | normalized search term |
| `category` | string null | |
| `image_url` | string null | |
| `notes` | text null | |
| `created_at`/`updated_at` | timestamps | |

### `product_listings`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `watched_product_id` | foreignId cascadeOnDelete | |
| `retailer` | enum(`bol`,`amazon`,`tweakers`) | |
| `external_id` | string null | retailer product id |
| `title` | string | matched listing title (for review) |
| `url` | string | listing / buy link |
| `current_price` | decimal(10,2) null | |
| `lowest_price` | decimal(10,2) null | lowest observed |
| `confirmed` | boolean | default false — tracked only when confirmed |
| `active` | boolean | default true |
| `last_checked_at` | timestamp null | |
| `created_at`/`updated_at` | timestamps | |

- Unique (`watched_product_id`, `retailer`, `external_id`).

### `price_points`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `product_listing_id` | foreignId cascadeOnDelete | |
| `price` | decimal(10,2) | |
| `observed_at` | timestamp | |

- Index (`product_listing_id`, `observed_at`).

### Eloquent: `WatchedProduct`, `ProductListing`, `PricePoint`.

---

## 3. Services & Actions

### Retailer adapters
- `Modules\Deals\Contracts\RetailerAdapter` — `retailer(): string`, `search(string $query): array<ListingCandidate>`, `fetchPrice(ProductListing $l): ?float`.
- `Services\Retailers\BolAdapter` (official API), `AmazonAdapter` (PA-API/Keepa), `TweakersAdapter` (Pricewatch scrape). Each resilient (typed `RetailerUnavailable`).
- `Data\ListingCandidate` (DTO): retailer, externalId, title, url, price, imageUrl.

### Matching & tracking
- `Services\ProductMatcher` — `findCandidates(string $query): array<ListingCandidate>` across all adapters (catch per-retailer).
- `Services\PriceChecker` — for a listing, `fetchPrice`, append a `price_point`, update `current_price`/`lowest_price`, return whether it dropped vs the previous point.

### Actions (`Modules/Deals/Actions/`)
- `AddWatchedProduct` (`__invoke(string $name): WatchedProduct`) — create product + run matcher → create **unconfirmed** `product_listings` (candidates) for review.
- `ConfirmListing` / `RemoveListing` — confirm or drop a candidate.
- `CheckPrices` (`__invoke(): PriceCheckResult`) — for each active confirmed listing, run `PriceChecker`; collect drops; send ntfy per drop (assumption D3); record results.
- `Modules\Deals\View\ViewModels\DealsViewModel`.

---

## 4. Config (`Modules/Deals/config/config.php`)

```php
return [
    'check_cron'   => env('DEALS_CHECK', '0 */3 * * *'), // every 3 hours (8×/day)
    'retailers'    => ['bol', 'amazon', 'tweakers'],
    'bol'          => ['api_key' => env('BOL_API_KEY'), 'api_secret' => env('BOL_API_SECRET')],
    'amazon'       => ['enabled' => env('DEALS_AMAZON', false)],
    'tweakers'     => ['enabled' => env('DEALS_TWEAKERS', true)],
    'request_timeout' => 15,
];
```

---

## 5. Scheduling

- `deals:check-prices` — **every 3 hours** (8×/day) → `CheckPrices`. `withoutOverlapping()`.

---

## 6. ntfy (per drop)

Via `HubNotifier`. One push per dropped listing (assumption D3):

> **{product}** goedkoper bij {retailer}: €{old} → €{new} ({lowest-ever ✓/✗}). {buy-link}

---

## 7. Endpoints / data contract

Route prefix `deals.`, `/deals`.

- `GET /deals` (JSON):
```json
{
  "products": [
    {
      "id": 5,
      "name": "Bambu Lab AMS",
      "listings": [
        { "retailer": "bol", "title": "Bambu Lab AMS", "url": "https://...", "current_price": 319.00, "lowest_price": 299.00, "confirmed": true, "last_checked_at": "2026-06-24T12:00:00+02:00" }
      ]
    }
  ]
}
```
- `POST /deals/products` (`{ "name": "Bambu Lab AMS" }`) → product + proposed candidate listings to review.
- `POST /deals/listings/{listing}/confirm`, `DELETE /deals/listings/{listing}` (remove a wrong match).
- `GET /deals/products/{product}/history` — price points per listing (for a chart later).
- `POST /deals/check` — manual price check now.

JSON via Resources.

---

## 8. Tests (`composer test`)

### Unit
- Each `RetailerAdapter` parses fixtures into `ListingCandidate`s and prices.
- `PriceChecker` detects a drop vs the previous point; updates `lowest_price`; no false drop on equal/raised price.

### Feature
- `AddWatchedProduct` with faked adapters creates unconfirmed candidate listings across retailers; a failing retailer is skipped.
- `ConfirmListing` activates tracking; `RemoveListing` deletes a wrong match.
- `CheckPrices` appends price points and sends one ntfy per drop (faked `HubNotifier`), including old→new + lowest-ever + buy link; no notification when price is unchanged or higher.
- `GET /deals` and history endpoint return the documented contracts.

---

## 9. Acceptance criteria

- [x] Add a product by name → review proposed matches per retailer → confirm → tracking starts.
- [x] Prices checked a few times/day with a stored history; lowest-ever maintained.
- [x] Every drop pushes an ntfy with old→new price, lowest-ever, retailer and buy link.
- [x] Retailers are resilient; bol.com is the reliable v1 source, others best-effort.
- [x] JSON contracts match §7; all new tests pass via `composer test`.

---

## 10. Confirmed decisions (signed off 2026-06-24)

- Add a **product by name**; the module **auto-searches** retailers and the owner **reviews matches** before tracking.
- Sources: **bol.com (reliable, official API) + Amazon + Tweakers** (best-effort, phased).
- Check **a few times per day**; **no target price** — alert on **every drop**.
- ntfy contains: name, **old → new price**, **lowest-ever**, retailer, **buy link**.

## 11. Confirmed decisions (signed off 2026-06-24)

- **D1** ✅ Module/route `Modules/Deals` + `/deals` (label "Dealtracker").
- **D2** ✅ A **review-matches** step after adding — the owner confirms/removes proposed listings before tracking (avoids false alerts).
- **D3** ✅ **One ntfy per dropped listing** (not bundled).
- **D4** ✅ Check **every 3 hours** (8×/day).
- **D5** ✅ v1 ships with **bol.com** working; Amazon/Tweakers best-effort afterwards.

## 12. Out of scope

- Blade markup / styling (Claude Design later).
- Target-price / percentage-threshold alerts (every drop only).
- Guaranteed cross-retailer product matching accuracy (mitigated by the review step).
- Historical price charts UI (data is stored; charting is FE later).
