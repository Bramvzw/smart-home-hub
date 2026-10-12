# UI Specification

Document user-visible behavior here when screens, controls, states or visual semantics change.

---

## Documentation Contract

For changed UI, record:

| UI element | Document |
|---|---|
| Page/screen | Purpose, data source, actions, loading/empty/error states |
| Component | Props/data source, states, actions and out-of-scope behavior |
| Button/action | Trigger, disabled/loading behavior, success/error outcome and side effects |
| Form/input | Validation, formatting, error copy and save/cancel behavior |
| Badge/status | Meaning, color/token mapping and fallback state |
| List/card/table | Sort order, visible fields, empty state and navigation behavior |

---

## Current Screens

### Dashboard

Shared shell for module navigation. Navigation entries come from module service providers via `ModuleRegistry`.

Visual system:
- App shell uses a restrained dark dashboard style from `resources/css/app.css`.
- Global tokens use the `--hub-*` prefix for background, sidebar, surface, card, line, text and accent values.
- Sidebar navigation, module cards, primary actions, inputs and empty states should use the shared `hub-*` component classes.
- Avoid decorative gradients, glassmorphism, oversized cards and violet-only styling.

Sidebar collapse:
- The sidebar has three states: `expanded` (full menu), `rail` (centered brand and module icons only, labels and chevron hidden) and `hidden` (sidebar gone, only a floating menu button remains).
- The chevron toggle swaps between `expanded` and `rail`; the footer "Hide menu" button goes to `hidden`; the floating button restores `expanded`.
- The chosen state is persisted in the unencrypted `sidebar_state` cookie and rendered server-side, so navigation never flashes the wrong state. Valid values are owned by `App\Dashboard\SidebarState`.

Shared input behavior:
- A global touch keyboard opens when focus enters an editable text field, textarea or contenteditable surface.
- The keyboard is a full-width bottom sheet with large touch targets for small kiosk screens.
- The default letter mode omits the number row; the `123` key switches to numbers and symbols.
- The keyboard does not open for range, color, date, checkbox, select or disabled/read-only controls.
- Text keys dispatch normal `input` events so module filters, search boxes and inline editors react as if physical keys were used.
- Enter submits the active field form for single-line inputs; textarea and contenteditable fields receive a newline.
- Elements can opt out with `data-touch-keyboard="off"` on the field or an ancestor.

Dashboard module cards:
- Module cards may show a module-provided status string from `getDashboardWidget()` instead of the static fallback copy.
- News uses this to show the total unread item count on the dashboard tile.
- Briefing uses this to show whether today's briefing is ready or missing.
- Recipes uses this to show this week's recipe count or AI-unavailable state.

### Spotify

Controls playback, playlists, queue, devices and liked tracks through the Spotify module routes. Spotify UI behavior should be backed by the smaller Spotify services documented in `Architecture.md`.

Spotify uses a dark cinematic afspeelpaneel layout intended for a tablet or wall panel. The selected design direction is the handoff's Variant A, with album artwork and the playback stack centered as one composition, warm amber playback accents, soft depth, pill tabs and Dutch labels.

Layout:
- The page hides the shared dashboard header and renders only compact Spotify tab navigation above the player surface.
- `Nu aan het afspelen` shows album artwork next to a horizontally centered playback stack with an equalizer state indicator, track title, artists, album, progress, transport controls, volume/device controls and a `Volgende` pill.
- The `Volgende` pill sits below the album artwork at the same visual width as the cover. It shows next-track artwork when Spotify provides it and hides the thumbnail when there is no next track. Main player volume and device controls align to opposite edges of the playback stack width.
- `Zoeken`, `Afspeellijsten`, `Wachtrij` and `Recent` are full tab panels. Non-playing tabs include a persistent mini afspeelbalk with current track, progress, transport proxies, like and volume.
- If Spotify is connected but no track is active, the screen opens on `Zoeken` so a track, album or playlist can start playback directly through the Spotify play endpoint.
- Playlists render as cover-first tiles. Queue and recent tracks render as artwork rows with duration and contextual play/skip affordances.
- On narrow screens the artwork and centered playback stack remain constrained to the viewport and tab panels keep the mini player below the content.

Connection behavior:
- The connect account state is only shown when no usable Spotify authorization exists.
- If a refresh token exists but the short-lived access token has expired, the server refreshes the token before rendering the page so kiosk clients do not see the setup state during normal use.
- If refresh fails because authorization is missing or revoked, the connect account state is shown again.

Interaction behavior:
- Tabs toggle panel visibility without replacing the tab button class list, so visual state classes remain stable after interaction.
- The mini player proxies play/pause, previous, next, shuffle, repeat, like and volume to the same controller actions as the main controls.
- Track title, artists, album art, progress, duration, liked state, shuffle/repeat state and play/pause icons are mirrored across the main panel, current queue row and mini players.
- Search chips fill and submit the search field; search results still use the Spotify search endpoint.
- Empty and reconnect states use the same amber Spotify surface treatment.

### Lighting

Controls configured Tuya/Calex and Govee lights through `Modules/Lighting`. The page is designed for the wall-mounted kiosk: the most common action takes one tap. Visual reference: the signed-off "Lighting kiosk redesign" prototype (see `docs/vault/Plans/Lighting-verbetering.md`). All copy is English.

Layout:
- The page hides the shared hub header and renders its own: the title `Lighting`, a summary line `N of M on` (reachable lamps that are on, out of all lamps), and two 56 px buttons, `Select` and `All off`. `All off` lives only in the header, never in the scene strip; it is highlighted (`aria-pressed="true"`) while every reachable lamp is off.
- Below the header a scene strip lists the built-in presets as 72 px buttons with a colour dot, name and subtitle (e.g. `Cool white · 100%`). The strip scrolls horizontally without a visible scrollbar; its right edge fades out. The active scene carries an `Active` badge and `aria-pressed="true"`.
- The main area is a grid of lamp tiles: 4 columns on the kiosk, 3 on medium widths, 2 at phone width (≤ 640 px). A tile is 214 px high (176 px on a phone) and shows an icon, the provider label (`CALEX`, `GOVEE`), the lamp name and a state line: `70% · Warm white`, `40% · Colour`, `Off` or `Offline`.
- A tile that is on glows in its current colour (colour mode) or white temperature (white mode); the glow strength follows the brightness. Along the bottom edge a brightness bar (custom track/fill/thumb over a transparent native range input) shows the brightness; it is dimmed while the lamp is off.
- Each reachable tile has a 48 px `⋯` button (top right) that opens the control sheet.
- Unreachable lamps stay visible, muted, with an `Offline` badge and no actions (no `⋯`, no bar, toggle disabled).
- Unreachable providers are named in a short notice under the header; the other providers keep rendering.

Gestures:
- Tap a tile: toggle the lamp on/off. A lamp stored at 0 % comes on at 60 %.
- Drag the tile's bottom bar: set the brightness directly (this also turns the lamp on).
- Tap `⋯`, or long-press the tile (shortcut, ~0.55 s): open the control sheet for that lamp.
- `Select` enters select mode: the title becomes `Select lamps`, the summary `N selected`, the header shows `Select all` and `Cancel`, tiles show a check circle instead of `⋯`/bar, and tapping a tile selects it. A floating bar `N lamps selected · Adjust · Cancel` appears at the bottom; `Adjust` (disabled with nothing selected) opens the control sheet for the whole selection.
- No UI depends on hover. Escape closes the sheet; focus styles stay for keyboard use.

Control sheet (bottom sheet, 548 px high on the kiosk, nearly full height on a phone):
- Header: the lamp name (or `3 lamps` with the selected names below), a power switch (`aria-pressed`) and a close button.
- `Brightness` slider with the percentage; a `mixed` badge appears when the selected lamps differ in brightness or mode.
- A `White | Colour` segmented control, shown only when every lamp in the sheet supports both. Otherwise only the supported panel shows; a dimmable-only lamp gets brightness and power only.
- White: a warm→cool gradient slider (`color_temp` 0–100) plus `Warm` / `Neutral` / `Cool` chips (0 / 50 / 100; a chip is active within ±8). The preview disc shows the white and a label like `≈ 3,300 K · Warm white`.
- Colour: a hue ring (tap or drag; 0° at the top, clockwise) and a `Pastel ↔ Saturated` saturation slider, so pastel colours are possible. The preview label reads `Hue 229° · 100% saturation`.
- For a selection, brightness and colour temperature start at the average of the lamps (off lamps count as 0 % brightness), the mode starts at the majority (ties go to white), and the power switch is on when any lamp is on.
- `Done` closes the sheet. The scrim behind it closes it too.
- The control sheet, the scene menu and the save dialog are modal (`role="dialog"`, `aria-modal="true"`): Tab and Shift+Tab cycle through the open dialog's controls only, and Escape closes it.

Scenes:
- The scene strip lists the built-in presets (from config, read-only) first, then the user's own scenes in their saved order, then a dashed `+ Save scene` button. A user scene shows its accent dot, its name and `N lamps · your scene`, plus a 48 px `⋯` button.
- `+ Save scene` (strip) and `Save as scene…` (sheet) open the save dialog: a name field (max 60 characters), eight accent swatches (Amber, Coral, Red, Pink, Purple, Blue, Green, Ice) and a `Lamps in this scene` checklist of the reachable lamps. Lamps that are on, selected, or open in the sheet are ticked by default. `Save` stays disabled until a name is typed.
- Saving stores the **current state of each ticked lamp** (on/off, brightness, and either the colour or the white temperature). Offline lamps are skipped because their state is unknown. The new scene appears in the strip as the active scene and select mode ends.
- A user scene's `⋯` opens a menu: `Rename` (name and accent only; the stored lamp states stay), `Update to current` (re-captures the scene's own lamps from their current state; a lamp that is offline right now keeps its stored state instead of being dropped), `Delete`, `Cancel`. `Delete` needs a second tap: the first tap turns it into `Tap again to delete`; closing or reopening the menu resets it.
- Tapping a user scene changes only the lamps stored in it; other lamps are left alone. Offline lamps are skipped and named in the toast; lamps the server reports in `failed_lights` or `skipped_lights` keep their tile state (matched by provider and id), and skipped lamps are named in a short toast (`Dimmer can't show that colour — left unchanged.`). When nothing was applied (`applied: 0`, e.g. every stored lamp is gone) the scene is not marked active and a toast says `<Scene> changed nothing — its lamps are gone or can't be reached.` A scene is shown as active when every stored lamp that is present and reachable is in its stored state; built-in presets are checked first.
- Endpoints: `GET /lighting/scenes` (`{data: {presets, scenes}}`), `POST /lighting/scenes` (`201`, captures `{name, accent, lights: [{provider, id}]}`), `PUT /lighting/scenes/{scene}` (`name`, `accent` and/or `lights` to re-capture), `DELETE /lighting/scenes/{scene}` (`204`), `POST /lighting/scenes/{scene}/apply` (`{data: {applied, failed, skipped, failed_lights, skipped_lights, missing_lights}}`, same lamp lists as the group endpoint, `502` when nothing applied because lamps failed, `409` when busy). `missing_lights` (`[{provider, id}]`) lists stored lamps that no longer exist or whose provider is not configured; a scene with only such lamps is a `200` with `applied: 0`. An unknown lamp, or only offline lamps, is a `422` on the `lights` field. An unknown scene id is a `404` for update, apply and delete.

Behavior:
- No configured credentials shows the empty state `No lights connected` with the `TUYA_*` / `GOVEE_API_KEY` hint; configured providers without lamps show `No lights found`.
- Built-in presets come from config. Bright, Cozy and Night use the lamps' real white LEDs: Bright is cool white at 100 %, Cozy warm white at 70 %, Night warm white at 1 %. Lamps without tunable white only get the power and brightness. Movie and Night light keep their colours; Night light only targets lamps whose name contains `strip`.
- A tile's state line reads the mode the provider reports (white LEDs vs RGB), not whether the lamp can do colour.
- Every change updates the UI optimistically. Controls are never disabled while a command runs. Each lamp has its own queue: one request in flight at a time, and changes made meanwhile coalesce into one queued request that carries only the newest values (latest wins). A lamp with a pending command shows a `Sending…` pill and a dashed border; other lamps and scenes stay usable.
- Sheet changes for one lamp go through that lamp's queue; for a selection they go out as one `PUT /lighting/lights` group request, coalesced the same way. Group requests run one at a time; changes for the same set of lamps merge into the last queued request, while a change for a different selection queues its own request so every selection still gets its changes. When a group request fails, queued group changes are dropped and their lamps roll back too. Slider moves are sent after 250 ms of rest.
- The sheet sends colours as hex built from the hue and saturation at full value (HSV v = 100), which maps 1:1 onto the lamps' own saturation.
- If a command fails, the lamp rolls back to its last confirmed state, gets a red ring and a toast such as `Couldn't reach Calex — Living room spot was set back to its last known state.` (with a dismiss button). In a group request only the lamps listed in `failed_lights` roll back; they are matched by provider and id, because lamp names repeat (Tuya defaults) and a provider-wide failure lists the provider label in `failed`.
- A scene tapped while another is running is queued; only the newest queued scene runs after the current one.
- The active scene is recalculated after every manual change.
- The backend locks per light for single-light writes (`lighting:control:{provider}:{id}`); presets, group writes and scenes take the shared lock plus the per-light lock of every lamp they write, so a single write and a preset never interleave on one lamp. Multi-lamp writes read the cached state only after they hold those locks, so a group change that waited for a single write builds on what that write left. A busy lock returns `409`; the page retries a `409` once after 500 ms before treating it as a failure.
- The page re-reads `GET /lighting/state` every `LIGHTING_POLL_SECONDS` (default 120 s) while it is visible, and once when it becomes visible again, so changes made in the Smart Life or Govee apps appear without a reload (including a lamp going offline). Lamps with a pending or newer local command are skipped; a lamp counts as changed the moment a slider or the colour ring moves, so a poll answering during the 250 ms debounce cannot overwrite the value being dragged. A poll that started before a preset, scene or group request completed is ignored, so it cannot overwrite freshly applied tiles or clear the active scene. Hidden tabs do not poll, and leaving the page (also via Livewire navigation) removes the poll timer and visibility listener.
- The per-light endpoint accepts `color_temp` (0 warm – 100 cool) next to `power`, `brightness` and `color`; `color` and `color_temp` together are rejected with `422`. Light JSON includes `mode` (`white`/`colour`), `color_temp` and `supports_color_temp`. A single-light write is sent as one provider batch built from the cached lamp state (Calex: one command with `switch_led` folded in, no status read) and returns that cached lamp with the change applied; an unknown lamp is `422`.
- `PUT /lighting/lights` applies one change set to several lamps: `200` with `{data: {applied, failed, skipped, failed_lights, skipped_lights, missing_lights}}`. `failed` and `skipped` are display names for the toast (a provider that could not be reached appears once by its label); `failed_lights` and `skipped_lights` list every affected lamp as `{provider, id, name}`, with a provider-wide failure expanded into each targeted lamp of that provider (named after the provider); `missing_lights` is always empty here, `502` when nothing could be applied, `422` for an unknown lamp. Calex lamps in one group request are written concurrently. A lamp that cannot show the requested colour or colour temperature gets only the rest of the change set (e.g. the brightness); when nothing else is left it is not touched (not switched on) and is listed in `skipped`. Skipped lamps are not rolled back or flagged, but are named in a short toast; the next poll shows their real state.
- Low-power mode (`?lowpower=1`, persisted per device): no glow, blur or pulse; tiles that are on get a static colour fill and the sheet preview has no halo.
- Secrets and provider tokens are never rendered.

### Weather

Shows rain-alert status and the next fixed hourly forecast blocks for the configured home location.

Layout:
- The page uses the shared dashboard shell with the page header hidden, matching the Spotify-style full-height module composition.
- The module topbar shows the configured location and coordinates without secondary navigation.
- The main view follows the imported Weather Module design: a large current-weather card sits beside a rain-alert status column.
- The status column shows rain start/likely duration/intensity, or a no-alert-needed state with the active rain threshold, plus compact notification metadata.
- Today and tomorrow cards show condition, min/max temperature, rain total/probability and max wind.
- The hourly strip shows time, condition, precipitation, probability, intensity, wind and temperature. Rain-triggering blocks use a sky-blue marker and alert probability colour; wind-triggering blocks use a teal border.

Behavior:
- Default location is Amsterdam as a neutral placeholder; set your own via WEATHER_LOCATION_LABEL and the WEATHER_LATITUDE/WEATHER_LONGITUDE env vars.
- The forecast source is Open-Meteo and the page degrades to a provider-failure state if it cannot be reached.
- Rain detection checks fixed hourly blocks in the next 3 hours by default.
- A block triggers when precipitation is greater than `WEATHER_RAIN_PRECIPITATION_THRESHOLD_MM` or probability is at least `WEATHER_RAIN_PROBABILITY_THRESHOLD`.
- Rain alerts use ntfy, default to 30-minute scheduled checks, send at most once per rain period, keep a 1-hour cooldown, and include start time, minutes until start, likely duration and intensity.
- Wind alerts use ntfy, default to 30-minute scheduled checks and trigger when wind speed or gusts meet `WEATHER_WIND_ALERT_THRESHOLD_KMH`.
- The daily morning summary is scheduled at `WEATHER_DAILY_SUMMARY_TIME`, defaulting to 07:15, and includes today/tomorrow plus immediate rain/wind context.
- Alerts are only sent between `WEATHER_ALERT_START_HOUR` and `WEATHER_ALERT_END_HOUR`, defaulting to 07:00 through 23:00.
- The last sent message is shown on the dashboard for inspection.
- The dashboard refreshes the Weather content region in-place every `WEATHER_REFRESH_SECONDS`, defaulting to 15 minutes, without a full page reload.

### News

Aggregates configured RSS/Atom feeds into topic groups and exposes the same state through `/news/items`.

Layout:
- The first implementation is a functional Blade shell, not the final designed news interface.
- The page header shows the global unread count plus refresh and mark-all-read actions.
- Topics render as grouped cards with latest configured items, per-topic unread counts and per-topic mark-read actions.
- Each item shows source, relative published time, title, summary and a keyword badge when `matched_keywords` is non-empty.

Behavior:
- Initial page data comes from stored `news_items`; the page does not fetch external feeds during render.
- The empty state appears when no stored items exist and exposes a refresh action.
- Refresh calls `POST /news/refresh`, which runs the feed refresh synchronously, sends keyword ntfy alerts and reloads the page.
- Clicking an item opens the original article in a new tab and posts to `POST /news/items/{item}/read`.
- Global and per-topic mark-read actions call `POST /news/read-all`.
- The JSON state contains `topics`, `total_unread` and `last_refreshed_at`; item output is shaped by `NewsItemResource`.
- A failed feed is logged and skipped during refresh; stored items from other feeds remain available.

### Briefing

Shows the generated daily morning briefing and exposes the same payload as JSON from `/briefing`.

Layout:
- The first implementation is a functional Blade shell, not the final designed briefing interface.
- The page action is `Generate now` before today's briefing exists and `Regenerate` after it exists.
- A generated briefing shows the body, generation time, AI/fallback indicator and per-section summaries.
- The dashboard tile shows whether today's briefing is ready, fallback, or missing.

Behavior:
- Scheduled generation runs at `BRIEFING_TIME`, default 08:00, stores one briefing per date and sends ntfy.
- Manual regenerate posts to `POST /briefing/regenerate`, overwrites today's row and does not send ntfy.
- `GET /briefing` with JSON returns the `BriefingResource` contract when today's briefing exists; before generation it returns a 404 with the date and message.
- If the Anthropic/Prism path is unavailable, the briefing is built from deterministic Dutch section summaries and marked fallback.
- Weather, Calendar, Tasks and News contribute through tagged `BriefingSource` services. Empty or failing sources are skipped.

### Tasks

Task management is a local, dark, compact Kanban board rendered by `Modules/Tasks`.

Layout:
- Left sidebar lists boards and active task counts.
- Top toolbar contains editable board title, search, label filter, priority filter, deadline filter, archive toggle and `Nieuwe taak`.
- Main area is a horizontally scrolling Kanban board with configurable columns.
- Selecting a task opens a right detail panel; editing is inline rather than modal-first.

Behavior:
- Default board is `Tasks`; default columns are `Todo`, `Doing`, `Done`.
- Boards can be created, renamed and deleted.
- Columns can be created, renamed, deleted and reordered.
- Tasks can be created, dragged between columns, reordered, edited, archived and deleted.
- Dragging a task into a column named `Done` marks it completed. Moving it elsewhere marks it incomplete.
- Labels are scoped per board and can be attached from the detail panel.
- Deadline notifications are not implemented; deadlines are only visible and filterable.
- Habit and maintenance management is currently JSON-only; final Blade markup is deferred.
- `GET /tasks/habits` returns active habits with cadence config, current-period `progress`, current streak, best streak and `completed_today`.
- `POST /tasks/habits/{recurrence}/complete` checks off the current or supplied date idempotently.
- `DELETE /tasks/habits/{recurrence}/complete` removes the current or supplied date completion and returns the updated habit state.
- `GET /tasks/maintenance` returns maintenance recurrence definitions sorted by active state and next due date.
- `POST/PATCH/DELETE /tasks/recurrences` manages both habit and maintenance recurrence definitions.
- Due maintenance appears as a normal Kanban card. The board-state JSON exposes `recurrence_id` and `is_maintenance` so future UI can render a recurring/maintenance marker.
- Completing a maintenance-linked card auto-reschedules the recurrence and allows the next cycle to materialize.

Card fields:
- Title is always visible.
- Description preview, labels, deadline, checklist progress and archived state are visible when present.
- Priority is shown as a subtle left accent bar.
- Maintenance cards expose `recurrence_id` and `is_maintenance` in JSON.

### Recipes

Shows weekly recipes generated from AH and Lidl supermarket offers.

Layout:
- The first implementation is a functional Blade shell, not the final designed recipes interface.
- The page action is `Generate recipes`.
- The page header state shows the ISO week, generation time, AI-unavailable badge and failed store badges when relevant.
- Recipes render as compact cards with servings, time, estimated cost, title, description and highlighted on-offer ingredients.
- A recipe card shows a compact savings callout (e.g. "€2,00 korting op ingrediënten deze week") when its ingredients word-match this week's grocery offers with a price advantage; cards without a match show no callout.
- Offers render in a secondary list grouped visually by item with store code, product name and discount/price label.

Behavior:
- Scheduled generation runs weekly at `RECIPES_DAY`/`RECIPES_TIME`, default Friday 18:00, refetches offers and sends ntfy.
- Manual generation posts to `POST /recipes/generate`, reuses stored offers unless `refetch` is supplied, and also sends ntfy.
- `GET /recipes` with JSON returns `week_key`, `generated_at`, `is_fallback`, `stores_fetched`, `stores_failed`, `recipes`, `offers` and `recipe_deals` (matched offers and indicative savings per recipe id, `Modules\Recipes\Services\RecipeDealsMatcher`, cached briefly per week).
- `GET /recipes/{recipe}` returns full recipe details: ingredients, steps and per-recipe shopping list.
- `GET /recipes/offers` returns this week's stored offers.
- If one store fails, the other store's offers remain available and `stores_failed` names the missing source.
- If AI is unavailable, recipes are empty, offers remain shown and `is_fallback` is true.

### Deals

Tracks product prices across reviewed retailer matches.

Behavior:
- `POST /deals/products` adds a watched product by name and stores proposed unconfirmed listings returned by configured retailers.
- `POST /deals/listings/{listing}/confirm` starts tracking a candidate listing.
- `DELETE /deals/listings/{listing}` removes a wrong match.
- `POST /deals/check` manually runs the same price check as the scheduled `deals:check-prices` command.
- `GET /deals` returns watched products with listing prices, lowest price, confirmation state and last checked timestamp.
- `GET /deals/products/{product}/history` returns price points per listing for future charting.
- A failed retailer is skipped and logged; other retailers remain usable.

### Entertainment

Shows film recommendations, broad concerts and followed-artist music releases.

Layout:
- A music release shows an "In library" badge on its cover art when `saved_on_spotify` is `true`.

Behavior:
- `GET /entertainment` returns `films`, `concerts` and `music`; each release includes `saved_on_spotify` (nullable boolean).
- Refreshing music releases best-effort checks each release's Spotify library status (`SpotifyLibraryService::checkSavedAlbums`); when Spotify is not linked or unreachable, `saved_on_spotify` stays `null` and the refresh does not fail.
- `GET /entertainment/concerts` returns the broad concert list, including `relevance: none`.
- `POST /entertainment/films/{film}/feedback` stores thumbs up/down taste feedback.
- `POST /entertainment/films/{film}/dismiss` hides a film recommendation.
- `GET/PUT /entertainment/taste` reads and updates the taste profile.
- `POST /entertainment/refresh` refreshes films, concerts and music and sends due notifications.
- Relevant concerts use `followed`, `hedon` or `might_like` badges; `none` remains browse-only and does not notify.

### Planner

Generates and accepts a weekly agenda plan from Google Calendar free/busy data.

Behavior:
- When Google Calendar is not connected, the page shows a connect state linking to `/planner/google/connect`.
- `POST /planner/generate` manually regenerates a plan without ntfy.
- `POST /planner/items/{item}/accept` inserts one proposed item into Google Calendar and stores `google_event_id`.
- `POST /planner/accept-all` accepts all currently proposed items on the latest plan.
- `POST /planner/items/{item}/reject` marks a proposed item rejected.
- `GET/POST/PATCH/DELETE /planner/intentions` manages active planning intentions.
- `GET /planner` returns `connected`, the latest `plan` and `intentions`.
- Unplaceable items have null `start_at`/`end_at`, status `unplaceable` and an explanatory reason.

### Settings

Per-module configuration plus module visibility and ordering, at `/settings`.

Behavior:
- The **Modules** pane lists every registered module (disabled ones included, from `ModuleRegistry::allModules()`), each row with an order number input (0–99) and an enabled checkbox; disabled module names render dimmed.
- `PUT /settings/modules` saves all rows at once; unknown slugs are ignored, out-of-range order values return validation errors under the fields. Success shows the shared "Module settings saved." status card.
- Disabling a module hides it from the sidebar, the dashboard grid and the settings panes, and skips its scheduled jobs — it does not unload routes or bindings, so deep links keep working.
- Order numbers are relative: rows are re-sorted by the stored numbers on the next render, with registration order as tie-breaker.
- Settings panes below the Modules pane are rendered per module implementing `ProvidesSettings` (currently Printer and Phone Ping); each pane is its own form posting to `PUT /settings/{module}` with validation from the module's `settingsSchema()`.
- Empty state ("No configurable modules") only appears when no enabled module provides settings.
