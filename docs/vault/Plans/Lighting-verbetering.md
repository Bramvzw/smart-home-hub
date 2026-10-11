# Plan — Lighting control improvements

> Status: **signed off** (2026-10-11). Visual reference: the Claude Design canvas
> "Lighting kiosk redesign" (https://claude.ai/artifact/6vmUNTjKF2ByGgXKRiJ2vJ).
> Primary device: the **wall-mounted kiosk** (large touchscreen, one-tap actions).
> Unlike the other plans, this one includes front-end behaviour, because the
> interaction model itself is the problem being solved.

---

## 0. Problems being solved

The owner named four pain points in the current Lighting page:

1. **No real white light.** Calex lamps are RGBCCT (separate white LEDs), but
   `TuyaProvider` always writes `work_mode: colour`. "Bright" fakes white with
   `#f5f7ff` through the RGB LEDs, which looks bluish and is dimmer.
2. **Select-then-control is unintuitive.** You pick a lamp in the left rail,
   then control it in a separate detail stage. Each lamp is controlled one at
   a time, and the selected lamp is not obvious on a kiosk.
3. **Slow and blocking.** Every command disables *all* controls until the
   cloud answers (often 1–2 s). The page never refreshes by itself, so changes
   made in the Smart Life or Govee app only show after a reload.
4. **Scenes are fixed in config.** Bright/Cozy/Movie/Night/Night light/All off
   live in `Modules/Lighting/config/config.php`. They apply one shared state
   to every lamp and cannot be created from the UI.

Verified device facts (Tuya `/v1.0/devices/{id}/functions`, 2026-10-11):

| DP | Range | Notes |
|---|---|---|
| `work_mode` | `white`, `colour`, `scene`, `music` | only `colour` is used today |
| `bright_value_v2` | 10–1000 | brightness in **white** mode |
| `temp_value_v2` | 0–1000 | 0 = warmest, 1000 = coolest |
| `colour_data_v2` | h 0–360, s 0–1000, v 0–1000 | `v` = brightness in colour mode |

**Tuya quota constraint:** the trial IoT Core plan allows **26,000 API calls
per month** (≈ 860/day, ≈ 36/hour). One device-list call returns every lamp,
so a refresh costs 1 call, but polling must stay coarse (see §3.3).

---

## Phase 1 — White mode and colour temperature (backend first)

### 1.1 `Light` DTO

Extend `Modules/Lighting/Data/Light.php`:

| Field | Type | Meaning |
|---|---|---|
| `mode` | `LightMode` enum (`white`, `colour`) | current mode as reported by the provider |
| `colorTemp` | `?int` 0–100 | 0 = warm, 100 = cool; `null` when unknown/unsupported |
| `supportsColorTemp` | `bool` | provider/device can do tunable white |

Add `Modules/Lighting/Enums/LightMode.php`. `toArray()` and `LightResource`
gain `mode`, `color_temp`, `supports_color_temp`. Existing keys stay
unchanged, so the JSON contract only grows.

### 1.2 `TuyaProvider`

- **Read:** if `work_mode === 'white'`, brightness comes from
  `bright_value_v2` (10–1000 → 1–100) and `colorTemp` from `temp_value_v2`
  (0–1000 → 0–100). Otherwise use the current `colour_data_v2` path.
  `supportsColorTemp = array_key_exists('temp_value_v2', $status)`.
- **Write:** new `setColorTemp(string $id, int $percent)`. It sends one command
  batch: `work_mode: white`, `temp_value_v2`, and the current
  `bright_value_v2`.
- `setBrightness()` writes `bright_value_v2` in white mode and
  `colour_data_v2.v` in colour mode (the mode comes from the cached state).
- `applyState()` gets a `?int $colorTemp` argument. When it is set, white mode
  wins over colour.

### 1.3 `GoveeProvider`

- Govee v1 `colorTem` command (Kelvin). Read the supported range from the
  device's `properties.colorTem.range` when present. Fallback: 2000–9000 K.
- Map 0–100 linearly onto that Kelvin range. `supportsColorTemp` is true only
  when the device lists `colorTem` in `supportCmds`.

### 1.4 Contract and actions

- `LightProvider` contract: add `setColorTemp()` and extend `applyState()`.
- `UpdateLightRequest`: accept `color_temp` (int 0–100). `color` and
  `color_temp` are **mutually exclusive** (validation error 422 if both are
  sent).
- `ControlLight` / `LightingService::control()` route `color_temp` to the
  provider.
- Presets gain an optional `color_temp` key (see §4.4 for the new defaults).

### 1.5 Tests

- Unit: Tuya read mapping for white vs colour status; write payloads for
  `setColorTemp`; brightness DP choice per mode; Govee Kelvin mapping and
  `supportCmds` detection.
- Feature: `PUT /lighting/lights/{provider}/{id}` with `color_temp` returns 200
  and the new resource fields; `color` + `color_temp` together returns 422.

---

## Phase 2 — Control pipeline: non-blocking and live

### 2.1 Finer locking (backend)

Today `LightingService::withControlLock()` takes **one global lock** for every
write. Replace it with:

- a **per-light lock** (`lighting:control:{provider}:{id}`) for single-light
  writes;
- the global lock **only** for scene/preset application and group writes that
  touch several lamps.

Govee keeps its existing pacing and retry (`command_pause_ms`,
`control_retries`).

### 2.2 Group endpoint

`PUT /lighting/lights` with a body like:

```json
{ "lights": [{ "provider": "tuya", "id": "…" }, …],
  "changes": { "power": true, "brightness": 60, "color_temp": 20 } }
```

- New action `ControlLights` (plural) loops providers and uses `applyState()`.
  Tuya commands for several devices are sent concurrently with `Http::pool`.
- Response: `{ data: { applied: n, failed: [names] } }`. A partial failure
  returns 200 with `failed` filled in. A full failure returns 502.
- The existing single-light endpoint stays (backwards compatible).

### 2.3 State endpoint and polling

- `GET /lighting/state` returns `LightResource::collection` plus
  `unreachable_providers`, read through the existing `cachedLights()` cache.
- Front-end polling:
  - only while the page is visible (Page Visibility API);
  - default every **120 s** (`LIGHTING_POLL_SECONDS`), which costs about
    720 Tuya calls/day and leaves headroom under the quota;
  - skipped while a command is pending for that lamp;
  - one extra refresh when the page becomes visible again.
- `cache_ttl` stays at 120 s, so several open screens share one provider call.

### 2.4 Front-end command queue

Replace the global `setControlsBusy()` with per-light state:

- Each lamp has its own `pending` flag and a **latest-wins** queue. While a
  command is in flight, slider moves overwrite the queued payload, and only
  the newest one is sent afterwards.
- Other lamps and scenes stay usable during a command.
- A lamp shows a subtle pending indicator, not a disabled control.
- On failure, the lamp rolls back to its last confirmed state and shows the
  danger ring with a short toast ("Couldn't reach Calex").
- `409` from the server is retried once after 500 ms.

### 2.5 Tests

- Feature: group endpoint (all succeed, partial failure, unknown light 422);
  per-light lock does not block a different lamp; state endpoint shape.
- Jest: queue coalescing (3 rapid slider values → 2 requests, last value
  wins); rollback on failure; polling paused while hidden or pending.

---

## Phase 3 — Kiosk interaction redesign

Goal: **the most common action takes one tap**, and controlling several lamps
at once is natural.

### 3.1 Layout: lamp tile grid (replaces rail + detail stage)

- **Top:** scene strip (big buttons, horizontally scrollable) + "All off".
- **Main:** a grid of large **lamp tiles** (min. 160 × 140 px; touch targets
  ≥ 56 px). Each tile shows:
  - the lamp name and a glow in its current colour or white temperature;
  - its brightness as a fill bar along the bottom edge;
  - a state line (`60% · warm white`, `40% · colour`, `off`, `unreachable`).
- **Unreachable** lamps stay visible, muted, with an "offline" badge and no
  actions.

### 3.2 Gestures

| Gesture | Effect |
|---|---|
| Tap tile | toggle on/off (primary action) |
| Horizontal drag on the tile's bottom bar | set brightness directly |
| Tap the tile's `⋯` button **or** long-press | open the control sheet for that lamp |
| Tap "Select" in the header, then tap tiles | multi-select; the control sheet controls the whole selection |

The long-press is a shortcut only. The visible `⋯` button is always there.

### 3.3 Control sheet (bottom sheet, kiosk-sized)

- Header: the lamp name, or "3 lamps" for a selection; power switch.
- **Brightness** slider (large thumb).
- Segmented control **White | Colour**:
  - *White:* a warm→cool gradient slider (`color_temp`), plus quick chips
    *Warm / Neutral / Cool*.
  - *Colour:* the existing hue ring, plus a saturation slider so pastel
    colours become possible. Today saturation is forced to 100 %.
- Hidden per lamp when unsupported (e.g. no colour temperature).
- A selection sends one group request (§2.2). For mixed selections, the
  sliders start at the average and the mode at the majority value.
- "Save as scene…" button (see Phase 4).

### 3.4 Kiosk specifics

- Respect the existing `?lowpower=1` mode: no blur, no animated glow; the
  static colour fill stays.
- No hover-dependent UI. Focus styles stay for keyboard use on the desktop.
- On phone width the grid becomes 2 columns and the sheet goes full-height.

### 3.5 Tests

- Jest: tap toggles; bar drag sends brightness; select mode builds a group
  payload; sheet hides unsupported controls; mixed-selection initial values.
- Feature: the page renders tiles for every light, including the unreachable
  and unsupported states.

---

## Phase 4 — Scenes from the UI

### 4.1 Data model

`lighting_scenes`

| Column | Type |
|---|---|
| `id` | pk |
| `name` | string(60) |
| `accent` | string(7), `#rrggbb` for the button |
| `sort_order` | unsigned int |
| timestamps | |

`lighting_scene_lights`

| Column | Type |
|---|---|
| `id` | pk |
| `lighting_scene_id` | fk, cascade delete |
| `provider` | string |
| `light_id` | string |
| `power` | bool |
| `brightness` | ?tinyint 0–100 |
| `mode` | `white` \| `colour` |
| `color` | ?string(7) |
| `color_temp` | ?tinyint 0–100 |

A scene stores a **state per lamp**, unlike the config presets, which apply
one state to all lamps. Lamps that are not in a scene are left untouched.

### 4.2 Actions and endpoints

| Method | Path | Action |
|---|---|---|
| `GET` | `/lighting/scenes` | list (built-in presets + user scenes) |
| `POST` | `/lighting/scenes` | `CreateLightingScene`, from the current state of the given lamps |
| `PUT` | `/lighting/scenes/{scene}` | `UpdateLightingScene` (rename, accent, re-capture) |
| `DELETE` | `/lighting/scenes/{scene}` | `DeleteLightingScene` |
| `POST` | `/lighting/scenes/{scene}/apply` | `ApplyLightingScene`, using the group pipeline |

Supporting code: `LightingScene` / `LightingSceneLight` models,
`LightingSceneResource`, and a `LightingSceneBuilder` (`ordered()`).

### 4.3 UI flow

- "Save as scene…" (in the control sheet and in the scene strip) opens a name
  field + accent swatches. By default it captures the lamps that are currently
  **on** or **selected**.
- The scene strip shows user scenes after the built-ins. A long-press or
  `⋯` on a user scene offers *Rename / Update to current / Delete*.
- Active-scene detection is extended to per-lamp scene states.

### 4.4 Built-in presets

Built-in presets stay in config (read-only), so the weather trigger
(`ApplyWeatherTriggeredPresets`) keeps working unchanged. Proposed new
defaults, using real white light:

| Preset | Proposed |
|---|---|
| Bright | white, 100 %, `color_temp` 100 (cool) |
| Cozy | white, 70 %, `color_temp` 0 (warm) |
| Movie | colour `#7f96ff`, 42 % (unchanged) |
| Night | white, 1 %, `color_temp` 0 |
| Night light | strip only, 1 %, `#ff8559` (unchanged) |
| All off | unchanged |

### 4.5 Tests

- Feature: scene CRUD, validation, and apply (only stored lamps change; an
  unreachable lamp is skipped and reported).
- Unit: capture from a snapshot; the builder ordering.

---

## 5. Build order and size

| Phase | Depends on | Rough size |
|---|---|---|
| 1 White mode | — | S–M |
| 2 Pipeline | — (pairs well with 1) | M |
| 3 Kiosk UI | 1 + 2 | L |
| 4 Scenes | 2 (+3 for the UI) | M |

Each phase ships on its own (`FEAT:` + CHANGELOG line). Phases 1 and 2 bring
noticeable gains even with today's UI.

---

## 6. Docs to update (Definition of Done)

- `docs/vault/UI Specification.md` — rewrite the Lighting section (tiles,
  gestures, sheet, scenes).
- `docs/styling.md` — tile, bottom-sheet and gradient-slider primitives.
- `docs/vault/Reference/Backend/Class Catalogue.md` — new enum, actions,
  models, resource, builder.
- `docs/vault/Decisions.md` — per-light locking; poll interval vs Tuya quota;
  built-in presets stay in config next to DB scenes.
- `.env.example` — `LIGHTING_POLL_SECONDS`.

---

## 7. Confirmed decisions (signed off 2026-10-11)

1. **Tap = toggle** on a tile; full controls via `⋯` (long-press is an optional shortcut).
2. **Poll interval 120 s** (`LIGHTING_POLL_SECONDS`), to stay within the Tuya quota of 26,000 calls/month.
3. **Built-in presets use real white light** as in §4.4 (Bright cool 100 %, Cozy warm 70 %, Night warm 1 %).
4. **Built-in presets stay in config** next to user scenes in the DB; the weather trigger is unchanged.
5. UI copy is **English**. "All off" lives in the header only, not in the scene strip.
6. The scene strip scrolls horizontally **without a visible scrollbar** (right-edge fade). A tile's brightness bar is **dimmed** while the lamp is off.

## 8. Out of scope

- Rooms and friendly names. Not requested now; tiles show provider names.
  A local display-name override can be added later.
- Local LAN control of Tuya lamps (would remove the cloud quota and trial
  dependency; a separate plan if ever needed).
- Schedules and automations, beyond the existing weather trigger.
- A Lighting tile on the main dashboard.
