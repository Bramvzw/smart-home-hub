# Plan — Supermarkt-aanbiedingen → recepten (Recipes) module

Codex-ready spec for an AI module that fetches **Albert Heijn & Lidl** offers and
has Claude generate quick, simple recipes from the discounted ingredients, with a
shopping list, pushed every Friday evening. Front-end markup is out of scope
(Claude Design later); this plan covers functional behaviour, UI states and the
data/JSON contract.

Status: spec ready. Build order: module 5. Depends on the shared `HubNotifier`
(News plan), the shared hub AI config and **Prism**. See [Roadmap](../Roadmap.md).

---

## 1. Functional spec

- Weekly, on **Friday evening**: fetch current AH + Lidl offers, then Claude generates **4–5 recipes** built around the discounted ingredients.
- Recipes are **quick/simple**, for **1–2 persons**, **no dietary restrictions**.
- Each recipe has a **shopping list**; an ntfy push goes out Friday evening (the owner shops in the weekend).
- Recipes + offers are shown in the hub.

### Data sourcing (to research at build)
Store offers have **no official public API**; this is the fragile part. Build a per-store provider behind one interface and research the best source for each:
- **Albert Heijn**: unofficial `api.ah.nl` bonus/offers endpoints (anonymous token flow).
- **Lidl**: unofficial Lidl app/folder offers JSON.
Each provider is **resilient**: on failure, log and skip that store; the other store still works. (Owner chose "you pick the best source"; manual entry is not required but the data model does not preclude adding it later.)

### UI states (functional, no markup)
- **This week**: list of recipes (title, time, cost, on-offer ingredients highlighted) + the underlying offers; each recipe opens a detail with ingredients, steps and its shopping list.
- **Empty**: no offers fetched yet / before first run.
- **Generating**: manual regenerate in progress.
- **Partial**: one store failed — show what we have, note the missing store.
- **AI unavailable**: show the fetched offers without recipes, flagged.

---

## 2. Data model

Module `Modules/Recipes` (route `/recipes`, label "Recepten") — confirm name (assumption R1).

### `grocery_offers`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `store` | enum(`ah`,`lidl`) | |
| `external_id` | string null | store's product id, for dedupe |
| `product_name` | string | |
| `category` | string null | |
| `normal_price` | decimal(8,2) null | |
| `offer_price` | decimal(8,2) null | |
| `discount_label` | string null | e.g. "1+1 gratis", "35% korting" |
| `unit` | string null | |
| `image_url` | string null | |
| `valid_from` / `valid_to` | date null | |
| `week_key` | string | ISO week, e.g. `2026-W26` |
| `fetched_at` | timestamp | |
| `created_at`/`updated_at` | timestamps | |

- Index (`week_key`, `store`); unique (`store`, `external_id`, `week_key`) when `external_id` present.

### `recipes`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `week_key` | string | |
| `title` | string | |
| `description` | text null | |
| `servings` | unsignedInteger | default 2 (1–2 persons) |
| `time_minutes` | unsignedInteger null | |
| `estimated_cost` | decimal(8,2) null | |
| `ingredients` | json | `[{ "name": "kipfilet", "amount": "300 g", "on_offer": true, "store": "ah" }]` |
| `steps` | json | ordered array of strings |
| `shopping_list` | json | `[{ "name": "kipfilet", "amount": "300 g", "on_offer": true, "store": "ah" }]` |
| `model` | string null | Prism model (null on fallback) |
| `is_fallback` | boolean | default false |
| `created_at`/`updated_at` | timestamps | |

### Eloquent
- `Modules\Recipes\Models\GroceryOffer`, `Modules\Recipes\Models\Recipe`.

---

## 3. Services & Actions

### Offer providers
- `Modules\Recipes\Contracts\OfferProvider` — `store(): string`, `fetch(): array<OfferData>`.
- `Modules\Recipes\Services\AlbertHeijnOfferProvider`, `Modules\Recipes\Services\LidlOfferProvider` (HTTP via `Http::timeout`; throw a typed `OfferSourceUnavailable` on failure).
- `Modules\Recipes\Data\OfferData` (DTO) — normalized offer fields.
- `Modules\Recipes\Services\OfferAggregator` — runs all providers (catch per-store), upserts `grocery_offers` for the current `week_key`.

### Recipe generation
- `Modules\Recipes\Services\RecipeGenerator` — given the week's offers, call Claude via **Prism** with structured output to produce N recipes (config). Prompt: Dutch, quick/simple, 1–2 servings, no dietary restrictions, prefer discounted ingredients, include per-recipe shopping list distinguishing on-offer items + store. Returns structured data validated into `Recipe` rows.
  - **Fallback (R3)**: if Prism fails / no key, store no recipes but keep the offers, flag the week AI-unavailable, and **still push** an ntfy with the fetched offers.

### Actions (`Modules/Recipes/Actions/`)
- `FetchOffers` (`__invoke(): OfferFetchResult`).
- `GenerateRecipes` (`__invoke(?string $weekKey = null, bool $push = true): array<Recipe>`) — fetch offers (or reuse this week's), generate, persist, ntfy via `HubNotifier`.
- `Modules\Recipes\View\ViewModels\RecipesViewModel` — this week's recipes + offers for the page.

---

## 4. Config (`Modules/Recipes/config/config.php`)

```php
return [
    'generate_day'   => env('RECIPES_DAY', 'friday'),
    'generate_time'  => env('RECIPES_TIME', '18:00'),
    'recipe_count'   => env('RECIPES_COUNT', 5),     // 4–5
    'servings'       => env('RECIPES_SERVINGS', 2),  // 1–2 persons
    'stores'         => ['ah', 'lidl'],
    'request_timeout'=> env('RECIPES_TIMEOUT', 15),
    'ai' => [
        'model'      => env('RECIPES_MODEL', 'claude-sonnet-4-x'),
        'max_tokens' => env('RECIPES_MAX_TOKENS', 2000),
    ],
];
```

Anthropic key from the shared hub AI config.

---

## 5. Scheduling

- `recipes:generate` — weekly on **Friday 18:00** (`->weeklyOn(5, '18:00')->withoutOverlapping()`) → `GenerateRecipes` with push. Artisan command runnable manually.

---

## 6. ntfy

Friday-evening push via `HubNotifier` (shared topic): title e.g. "Recepten voor het weekend", body = the recipe titles + a note that shopping lists are ready in the hub.

---

## 7. Endpoints / data contract

Route prefix `recipes.`, `/recipes`.

- `GET /recipes` (JSON):
```json
{
  "week_key": "2026-W26",
  "generated_at": "2026-06-26T18:00:05+02:00",
  "is_fallback": false,
  "stores_fetched": ["ah", "lidl"],
  "stores_failed": [],
  "recipes": [
    {
      "id": 41,
      "title": "Snelle kip-teriyaki met paprika",
      "description": "…",
      "servings": 2,
      "time_minutes": 25,
      "estimated_cost": 6.40,
      "on_offer_ingredients": ["kipfilet (AH)", "paprika (Lidl)"]
    }
  ]
}
```
- `GET /recipes/{recipe}` — full detail: ingredients, steps, shopping_list.
- `GET /recipes/offers` — this week's offers.
- `POST /recipes/generate` — manual regenerate (also re-pushes ntfy, per R4).

JSON via `RecipeResource` / `OfferResource`.

---

## 8. Tests (`composer test`)

### Unit
- Each `OfferProvider` parses a fixture response into normalized `OfferData`.
- `OfferAggregator` upserts/dedupes offers for the week.

### Feature
- `FetchOffers` with `Http::fake` stores offers; a failing store is skipped while the other stores (resilience), recorded in `stores_failed`.
- `GenerateRecipes` with a faked Prism structured response stores N recipes with shopping lists and sends one ntfy (faked `HubNotifier`).
- Fallback: Prism failure keeps offers, stores no recipes, flags AI-unavailable; ntfy reflects it (or is skipped per R3).
- Scheduled generation runs Friday and is idempotent per `week_key`.
- `GET /recipes` and `/recipes/{id}` return the documented contracts.

---

## 9. Acceptance criteria

- [ ] Every Friday 18:00, AH + Lidl offers are fetched and 4–5 quick recipes (1–2 servings) are generated with shopping lists, then pushed via ntfy.
- [ ] A failing store degrades gracefully; recipes still generate from the available offers.
- [ ] When AI is unavailable, offers are still stored and shown; the state is flagged.
- [ ] JSON contracts match §7.
- [ ] All new tests pass via `composer test`.

---

## 10. Confirmed decisions (signed off 2026-06-24)

- Data source: assistant researches the best (unofficial) source per store; resilient, phased.
- Cadence: **weekly, Friday 18:00**, ntfy in the weekend window.
- **4–5 recipes**, **1–2 servings**, **quick/simple**, **no dietary restrictions**.
- **Shopping list per recipe**.

## 11. Confirmed decisions (signed off 2026-06-24)

- **R1** ✅ Module/route `Modules/Recipes` + `/recipes` (full English), label "Recepten".
- **R2** (default, adjustable) Offers stored per ISO week (`week_key`); a regenerate within the same week reuses stored offers unless explicitly re-fetched.
- **R3** ✅ If AI is down, **still push ntfy** with the fetched offers (notify even when no recipes); offers stored/shown, state flagged.
- **R4** ✅ Manual regenerate **also re-pushes** ntfy.
- **R5** ✅ Best-effort on unofficial store endpoints; if a source breaks, fix it or fall back to the other store.

## 12. Out of scope

- Blade markup / styling (Claude Design later).
- A combined multi-recipe shopping list (per-recipe only for now; could add later).
- Inclusion in the Daily Briefing (owner kept recipes separate; the briefing has its own recipe-idea line independently if desired later).
- Pricing accuracy guarantees / official retailer partnerships.
