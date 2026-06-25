# Plan — Bambu Lab voorraad (Printing inventory) module

Codex-ready spec for a **printing supplies inventory** module: filament spools +
printer parts/consumables. The owner already has the Bambu Lab **H2D**; this is
pure stock registration — **no live print status, no MQTT, no AI, no ntfy**.
Front-end markup is out of scope (Claude Design later); this plan covers
functional behaviour, UI states and the data/JSON contract.

Status: implemented 2026-06-25. Build order: module 4. See [Roadmap](../Roadmap.md).

---

## 1. Functional spec

A `/printer` page with two inventories, both manually managed in the hub:

### Filament spools
Track per spool: **material** (PLA/PETG/ABS/TPU/…), **color** (name + optional hex), **brand**, **remaining amount** (grams, with computed %), and **purchase info** (price, store, date). Manually adjust remaining when you print, and add/remove spools.

### Parts
Two kinds (a `category` field): **spare parts** (nozzles, hotends, buildplates, belts — tracked by quantity) and **consumables** (glue stick, IPA, desiccant — quantity + unit). Manually adjust quantities.

- **No alerts** — low/empty is shown in the hub but never pushed.
- **No printer connection** — explicitly out of scope (the owner did not want live status).

### UI states (functional, no markup)
- **Lists**: filament spools + parts, each editable; show remaining (g + %) and a subtle low/empty indicator (informational only).
- **Empty**: no spools / no parts yet.
- **Loading / error** for the JSON endpoints.

---

## 2. Data model

Module `Modules/Printer`, route `/printer`, **dashboard label "3D-printer"** (the owner's chosen name; a PHP namespace cannot start with a digit, so code uses `Printer`). Leaves room for the floated print-queue feature later.

### `filament_spools`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `material` | string | PLA/PETG/ABS/TPU/… (free text or enum-ish) |
| `color_name` | string | e.g. "Galaxy Black" |
| `color_hex` | string null | optional swatch |
| `brand` | string null | |
| `diameter_mm` | decimal(3,2) | default 1.75 |
| `total_weight_g` | unsignedInteger | default 1000 |
| `remaining_g` | unsignedInteger | clamped 0..total |
| `purchase_price` | decimal(8,2) null | |
| `purchase_store` | string null | |
| `purchased_at` | date null | |
| `notes` | text null | |
| `created_at`/`updated_at` | timestamps | |

- Computed accessor `remaining_pct` = round(remaining_g / total_weight_g * 100).

### `printer_parts`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `category` | enum(`spare`,`consumable`) | |
| `name` | string | e.g. "0.4mm nozzle", "Isopropanol" |
| `quantity` | decimal(8,2) | count or amount |
| `unit` | string null | e.g. "stuks", "ml", "g" |
| `purchase_price` | decimal(8,2) null | optional |
| `purchase_store` | string null | optional |
| `notes` | text null | |
| `created_at`/`updated_at` | timestamps | |

### Eloquent
- `Modules\Printer\Models\FilamentSpool`, `Modules\Printer\Models\PrinterPart`.

---

## 3. Services & Actions

No external services. Thin actions in `Modules/Printer/Actions/`:
- Filament: `CreateSpool`, `UpdateSpool`, `DeleteSpool`, `AdjustSpoolRemaining` (`__invoke(FilamentSpool $s, int $deltaG): FilamentSpool` — clamps 0..total; negative = consume, positive = refill).
- Parts: `CreatePart`, `UpdatePart`, `DeletePart`, `AdjustPartQuantity` (`__invoke(PrinterPart $p, float $delta): PrinterPart` — clamps ≥ 0).
- `Modules\Printer\View\ViewModels\PrintingViewModel` — both inventories for the page.

---

## 4. Config (`Modules/Printer/config/config.php`)

```php
return [
    'default_spool_weight_g' => env('PRINTER_DEFAULT_SPOOL_G', 1000),
    'low_filament_pct'       => env('PRINTER_LOW_PCT', 15), // informational badge only, no alerts
];
```

---

## 5. Endpoints / data contract

Route prefix `printer.`, `/printer`.

- `GET /printer` (page) + JSON:
```json
{
  "filament": [
    {
      "id": 3,
      "material": "PLA",
      "color_name": "Galaxy Black",
      "color_hex": "#1b1b22",
      "brand": "Bambu Lab",
      "diameter_mm": 1.75,
      "total_weight_g": 1000,
      "remaining_g": 320,
      "remaining_pct": 32,
      "is_low": false,
      "purchase": { "price": 24.99, "store": "bol.com", "purchased_at": "2026-05-02" }
    }
  ],
  "parts": [
    { "id": 9, "category": "spare", "name": "0.4mm nozzle", "quantity": 3, "unit": "stuks" },
    { "id": 12, "category": "consumable", "name": "Isopropanol", "quantity": 500, "unit": "ml" }
  ]
}
```
- Filament CRUD: `POST /printer/filament`, `PATCH /printer/filament/{spool}`, `DELETE /printer/filament/{spool}`, `POST /printer/filament/{spool}/adjust` (`{ "delta_g": -150 }`).
- Parts CRUD: `POST /printer/parts`, `PATCH /printer/parts/{part}`, `DELETE /printer/parts/{part}`, `POST /printer/parts/{part}/adjust` (`{ "delta": -1 }`).

JSON via `FilamentSpoolResource` / `PrinterPartResource`. Validation via Form Requests.

---

## 6. Tests (`composer test`)

### Feature
- CRUD for spools and parts via the endpoints; validation rejects bad payloads (negative totals, unknown category).
- `AdjustSpoolRemaining` clamps to 0..total (consume below 0 → 0; refill above total → total).
- `AdjustPartQuantity` clamps ≥ 0.
- `remaining_pct` and `is_low` computed correctly relative to `low_filament_pct`.
- `GET /printer` returns the documented contract.

### Unit
- Spool percentage + low-flag computation edge cases (0, full, rounding).

---

## 7. Acceptance criteria

- [ ] Filament spools and parts can be created/edited/deleted and their amounts adjusted via the §5 endpoints.
- [ ] Remaining filament shows grams + % with an informational low badge; no notifications anywhere.
- [ ] No printer connection / live status / MQTT exists in this module.
- [ ] JSON contract matches §5.
- [ ] All new tests pass via `composer test`.

---

## 8. Confirmed decisions (signed off 2026-06-24)

- Filament fields: material + color + brand, remaining (g + %), purchase info. (No location.)
- **No alerts** — registration only; low/empty shown in-hub.
- Parts cover **spare parts + consumables** (not linked to maintenance tasks).
- Updating is **manual in the hub** (endpoints now, UI later).

## 9. Confirmed decisions (signed off 2026-06-24)

- **P1** ✅ Owner's name: **"3D-printer"**. Code uses `Modules/Printer` + route `/printer` (namespace can't start with a digit); dashboard label is "3D-printer".
- **P2** ✅ Remaining filament in **grams** + computed %, default spool **1000 g**.
- **P3** ✅ Color stored as **name + optional hex** swatch.
- **P4** ✅ Purchase fields (price/store) **optional on both** filament and parts.

## 10. Out of scope

- Blade markup / styling (Claude Design later).
- Live print status, progress, AMS monitoring, any printer/MQTT connection.
- Management UI (endpoints now; UI later).
- Print queue / per-print auto-deduction (floated separately on the roadmap).
