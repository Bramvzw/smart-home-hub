# Plan — Moestuin (Garden) module

Codex-ready spec voor een kas-moestuinmodule. **Twee fasen**: fase 1 (nu, geen
hardware) = plant-catalogus met AI-verzorging + NL zaai-/oogstkalender; fase 2
(als de sensor er is) = live kasklimaat via een **zelfbouw ESP32** die metingen
naar de hub pusht, met ntfy bij drempels. Front-end markup is out of scope
(Claude Design later); dit plan dekt functioneel gedrag, UI-states en het
data/JSON-contract.

Status: spec ready. Build order: na de 8 kernplannen (fase 1 kan nu; fase 2 als
de ESP32 klaar is). Depends: gedeelde `HubNotifier`, gedeelde hub AI-config +
**Prism** (fase 1 verzorging). Zie [Roadmap](../Roadmap.md).

---

## 1. Functional spec

De planten staan in een **kas** → **geen weer-koppeling** (geen "sla water over
bij regen"-logica).

### Fase 1 — catalogus, verzorging, kalender (geen hardware)
- Een **catalogus** van je planten. Per plant: naam, ras/variëteit, gewas, bed/
  locatie, status (gepland/groeit/geoogst), notities, optioneel foto.
- **AI-verzorging**: bij het toevoegen van een plant genereert Claude (Prism)
  Nederlandse verzorgingsinstructies (water, zon, grond, bemesting, tips) +
  voorgestelde **zaai-/oogstmaanden** voor het NL-klimaat. Hergenereren kan;
  velden zijn handmatig overschrijfbaar.
- **Zaai-/oogstkalender**: een maand-overzicht "wat nu zaaien / oogsten" op basis
  van de zaai-/oogstvensters per plant. Maandelijkse ntfy + in de dagelijkse briefing.

### Fase 2 — kasklimaat (zelfbouw ESP32, push naar de hub)
- Een **ESP32** met temp/vocht-sensor (bv. BME280/DHT22) stuurt periodiek metingen
  naar een **ingest-endpoint** van de hub (push over de LAN, beveiligd met een token).
  De hub pollt dus NIET; de sensor pusht.
- De hub slaat de metingen op (**historie**), toont de laatste waarde + trend.
- **ntfy bij drempels** op het moment van binnenkomst: te warm / te koud / te droog
  (configureerbare grenzen), via de gedeelde `HubNotifier`.
- **Offline-detectie**: als er X minuten geen meting binnenkomt → ntfy "sensor offline".
- De ESP32-firmware zelf is out of scope; dit plan definieert het **ingest-contract**
  dat de ESP32 aanroept.

### UI states (functioneel, geen markup)
- Catalogus met plantkaarten (status, verzorging, zaai-/oogstvenster); maand-
  kalenderstrook; (fase 2) een kasklimaat-paneel met huidige temp/vocht + trend.
- Leeg (nog geen planten), laden, fout (AI-uitval), en — fase 2 — "geen recente
  meting / sensor offline".

---

## 2. Data model (`Modules/Garden`)

Route `/moestuin`, label "Moestuin".

### `garden_plants` (fase 1)
| Kolom | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | bv. "Cherrytomaat 'Sungold'" |
| `crop` | string | genormaliseerd gewas voor AI/kalender, bv. "tomaat" |
| `variety` | string null | |
| `location` | string null | bed/plek in de kas |
| `status` | enum(`planned`,`growing`,`harvested`) | default `planned` |
| `planted_on` | date null | |
| `notes` | text null | |
| `image_url` | string null | |
| `care` | json null | `{ "water": "...", "sun": "...", "soil": "...", "fertilize": "...", "tips": ["..."] }` |
| `care_is_ai` | boolean | default false — door Claude gegenereerd |
| `care_generated_at` | timestamp null | |
| `sow_from_month` / `sow_to_month` | tinyInteger null | 1-12 |
| `harvest_from_month` / `harvest_to_month` | tinyInteger null | 1-12 |
| `created_at`/`updated_at` | timestamps | |

### `garden_sensor_readings` (fase 2)
| Kolom | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `device_id` | string | door de ESP32 meegestuurd (bv. "kas-1") |
| `temperature` | decimal(4,1) null | °C |
| `humidity` | decimal(4,1) null | % |
| `recorded_at` | timestamp | tijd van binnenkomst (of door ESP32 meegegeven) |

- Index (`device_id`, `recorded_at`).

### Eloquent: `Modules\Garden\Models\GardenPlant`, `Modules\Garden\Models\GardenSensorReading`.

---

## 3. Services & Actions

### Fase 1
- `Services\PlantCareGenerator` (Prism) — `generate(string $crop, ?string $variety): PlantCare` → Nederlandse verzorging + zaai-/oogstmaanden (NL-klimaat). Mockbaar; valt netjes terug (lege/handmatige velden) als AI faalt.
- `Services\GardenCalendar` — `thisMonth(CarbonImmutable $now): array` → planten om te zaaien / te oogsten deze maand op basis van de maandvensters.
- Actions (`Modules/Garden/Actions/`): `CreatePlant` (genereert care via AI), `UpdatePlant`, `DeletePlant`, `RegeneratePlantCare`.
- `Actions\SendCalendarDigest` — maandelijkse ntfy "deze maand zaaien/oogsten".
- `Modules\Garden\View\ViewModels\GardenViewModel` — planten + maandkalender (+ laatste klimaat in fase 2).

### Fase 2 (ingest)
- `Actions\RecordReading` (`__invoke(string $deviceId, ?float $temp, ?float $humidity): GardenSensorReading`) — sla reading op; evalueer drempels → ntfy via `HubNotifier` bij overschrijding, met **dedupe** (niet blijven pushen binnen dezelfde overschrijding — bv. cooldown of "pas opnieuw na terugkeer binnen grens").
- `Http\Requests\StoreReadingRequest` — valideert device_id + numerieke temp/humidity.
- `Http\Middleware\VerifyGardenToken` (of in de controller) — controleert een gedeeld ingest-token (de ESP32 is geen ingelogde browser); de ingest-route staat buiten de `web`-auth/CSRF en accepteert het token via header.
- `Actions\CheckSensorOffline` (scheduled) — als de laatste reading ouder is dan `stale_after_minutes` → ntfy "kassensor offline" (eenmalig per offline-periode).

### Briefing-integratie
- `Modules\Garden\Briefing\GardenBriefingSource` (implements `App\Contracts\BriefingSource`) — draagt het huidige kasklimaat + "deze maand zaaien/oogsten" bij aan de [Dagelijkse briefing](Dagelijkse-briefing.md). Getagd `briefing.source`.

---

## 4. Config (`Modules/Garden/config/config.php`)

```php
return [
    'ai' => ['model' => env('GARDEN_MODEL', 'claude-sonnet-4-6')],
    'calendar_digest' => ['enabled' => true, 'day' => 1, 'time' => '08:00'],

    // fase 2 — ESP32 push-ingest
    'ingest_token' => env('GARDEN_INGEST_TOKEN'),   // ESP32 stuurt dit mee
    'stale_after_minutes' => env('GARDEN_STALE_AFTER', 60),
    'thresholds' => [
        'temp_min' => env('GARDEN_TEMP_MIN', 5),
        'temp_max' => env('GARDEN_TEMP_MAX', 35),
        'humidity_min' => env('GARDEN_HUMIDITY_MIN', 40),
    ],
    'alert_cooldown_minutes' => env('GARDEN_ALERT_COOLDOWN', 60),
];
```

---

## 5. Scheduling

- **Fase 2**: `garden:check-offline` — elke ~15 min → `CheckSensorOffline` (ntfy als er te lang geen meting binnenkwam). `withoutOverlapping()`.
- **Fase 1**: `garden:calendar-digest` — maandelijks (1e van de maand, 08:00) → `SendCalendarDigest`. (De briefing draagt dit dagelijks ook bij.)

> Geen poll-schedule voor het uitlezen: metingen komen **push** binnen van de ESP32.

---

## 6. ntfy

Via `HubNotifier` (gedeelde topic):
- **Fase 2**: bij binnenkomst-overschrijding — bv. "Kas te warm: 37°C (max 35°C)"; en "Kassensor offline — geen meting sinds HH:MM".
- **Fase 1**: maandelijkse zaai-/oogst-samenvatting.

---

## 7. Endpoints / data contract

Route prefix `garden.`, `/moestuin`.

**Ingest (fase 2, token-beveiligd, buiten web-auth/CSRF):**
- `POST /moestuin/readings` — body `{ "device_id": "kas-1", "temperature": 28.4, "humidity": 62 }`, header `X-Garden-Token: <token>`. → `{ "stored": true }` (of 401 bij fout token). De ESP32 roept dit periodiek aan.

**Hub (web):**
- `GET /moestuin` (JSON):
```json
{
  "plants": [
    {
      "id": 3, "name": "Cherrytomaat 'Sungold'", "crop": "tomaat", "variety": "Sungold",
      "location": "Bed A", "status": "growing", "planted_on": "2026-04-20",
      "care": { "water": "...", "sun": "vol licht", "soil": "...", "fertilize": "...", "tips": ["dieven"] },
      "care_is_ai": true,
      "sow": { "from": 3, "to": 4 }, "harvest": { "from": 7, "to": 10 }
    }
  ],
  "calendar": { "month": 6, "sow": ["sla", "bonen"], "harvest": ["radijs"] },
  "climate": { "temperature": 28.4, "humidity": 62, "recorded_at": "2026-06-25T12:00:00+02:00", "stale": false }
}
```
- `POST /moestuin/plants` (`{ "name", "crop", "variety?", "location?" }`) → maakt plant + genereert AI-verzorging.
- `PATCH /moestuin/plants/{plant}`, `DELETE /moestuin/plants/{plant}`.
- `POST /moestuin/plants/{plant}/regenerate-care` → hergenereert de AI-verzorging.
- `GET /moestuin/climate` (fase 2) → laatste + recente metingen.

JSON via Resources (`GardenPlantResource`).

---

## 8. Tests (`composer test`)

### Fase 1
- `CreatePlant` met gefakete Prism: slaat care + zaai-/oogstmaanden op; `care_is_ai = true`.
- AI-uitval → plant wordt toch aangemaakt, care leeg/handmatig invulbaar.
- `GardenCalendar::thisMonth` geeft de juiste zaai-/oogstplanten voor een gegeven maand.
- CRUD + `regenerate-care`; `GET /moestuin` contract.
- `GardenBriefingSource` draagt kalender (+ klimaat) bij; null als er niets is.

### Fase 2 (ingest)
- `POST /moestuin/readings` met geldig token slaat een reading op; **fout/ontbrekend token → 401**; geen reading opgeslagen.
- Ingest die een drempel overschrijdt stuurt één ntfy (gefakete `HubNotifier`); binnen de grenzen geen alert; geen herhaalde push binnen de cooldown.
- `CheckSensorOffline` stuurt ntfy als de laatste meting ouder is dan `stale_after_minutes`, en niet opnieuw zolang offline.
- `GET /moestuin/climate` contract incl. `stale`-vlag.

---

## 9. Acceptance criteria

- [ ] Planten toevoegen/bewerken/verwijderen; bij toevoegen genereert Claude NL-verzorging + zaai-/oogstvensters (overschrijfbaar).
- [ ] Maandkalender toont wat nu te zaaien/oogsten is; maandelijkse ntfy + in de briefing.
- [ ] (Fase 2) De ESP32 kan via een token-beveiligde POST metingen pushen; de hub bewaart historie, toont het klimaat, en ntfy't bij drempels + offline.
- [ ] Geen weer-koppeling (kas).
- [ ] `GardenBriefingSource` voedt de dagelijkse briefing.
- [ ] JSON-contract matcht §7; alle nieuwe tests groen via `composer test`.

---

## 10. Confirmed decisions (signed off 2026-06-25)

- **G1** ✅ Module/route `Modules/Garden` + `/moestuin`, label "Moestuin".
- **Eén plan, twee fasen** (fase 1 nu zonder hardware; fase 2 bij de ESP32).
- Verzorgingsinstructies **door AI gegenereerd** (Claude/Prism), overschrijfbaar.
- Zaai-/oogstkalender via **AI/ingebouwde NL-gids**.
- **G3** ✅ Kalender-herinnering: **maandelijkse ntfy + in de briefing**.
- **G4** ✅ Sensor = **zelfbouw ESP32 die naar de hub pusht** (token-beveiligd ingest-endpoint) — NIET Tuya, geen polling.
- **G5** ✅ Drempel-defaults: temp 5-35°C, vocht ≥40%.
- Kas → **geen weer-koppeling**.

## 11. Assumptions to confirm

- **G2**: Plantvelden zoals §2 (naam, gewas, ras, locatie, status, notities, foto). Compleet genoeg?
- **G6**: ESP32 stuurt **temp + vocht** (BME280/DHT22-achtig). Wil je later ook bodemvocht/lichtsterkte ondersteunen? (Schema is uitbreidbaar.)
- **G7**: Ingest-auth via een **gedeeld token in een header** (`X-Garden-Token`). OK, of liever per-device tokens?

## 12. Out of scope

- Blade markup / styling (Claude Design later; voeg een prompt toe aan [Frontend-prompts](Frontend-prompts.md)).
- De **ESP32-firmware** zelf (dit plan levert alleen het ingest-contract).
- Weer-gekoppeld bewateringsadvies (planten staan in een kas).
- Geautomatiseerde irrigatie/aansturing (alleen uitlezen + adviseren).
- Plant-herkenning via foto (eerder geschrapt als "moestuin-dokter").
