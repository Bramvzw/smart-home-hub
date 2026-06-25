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

Controls configured Tuya/Calex and Govee lights through `Modules/Lighting`.

Layout:
- Lighting uses the handoff's Variant B console layout: a left rail with provider-grouped lamps and a right master-detail stage for the selected lamp.
- The left rail shows the product mark, total on/off state, a master toggle and compact lamp rows grouped by provider label.
- Preset scene buttons sit above the detail stage and apply a shared scene to all reachable lights.
- The detail stage shows one selected lamp with a large colour/glow ring, power switch, brightness slider and colour controls when supported.
- On narrower screens the rail stacks above the detail stage.

Behavior:
- No configured credentials shows the empty setup state with `TUYA_*` and `GOVEE_API_KEY` hints.
- Provider failures show a warning naming the unreachable provider while other providers continue rendering.
- Unreadable lights remain visible but disabled and visually muted.
- Clicking a lamp row changes the focused detail panel without a full page reload.
- Presets include bright, cozy, movie, night, night light and all-off scenes. They call the preset JSON endpoint, temporarily disable the clicked preset button, update reachable light cards optimistically and skip unreachable lights.
- Presets may define target name filters. The Night light preset targets lights whose name contains `strip`, so it only turns the LED strip on softly and leaves other reachable lights unchanged.
- The active preset button uses `data-active="true"`, `aria-pressed="true"` and an `Actief` badge. Manual power, brightness or colour changes recalculate this state immediately.
- Presets skip unchanged power, brightness and colour commands from the current snapshot to reduce cloud latency, especially for Govee LED strips.
- Govee commands are paced and transient failures are retried so quick preset changes are less likely to drop a brightness or colour command.
- While a Lighting action is running, reachable switches, sliders, colour inputs and preset buttons are disabled so commands cannot overlap from the same browser.
- The backend serialises all Lighting write requests with a short cache lock. If another device submits during an active action, the JSON endpoint returns `409` with a busy message instead of interleaving commands.
- Control changes call the per-light JSON endpoint and optimistically update the UI; a failed update marks the card with the danger ring.
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
- Default location is Herxen 17, Wijhe from the Google Maps coordinates supplied by the user.
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
- Offers render in a secondary list grouped visually by item with store code, product name and discount/price label.

Behavior:
- Scheduled generation runs weekly at `RECIPES_DAY`/`RECIPES_TIME`, default Friday 18:00, refetches offers and sends ntfy.
- Manual generation posts to `POST /recipes/generate`, reuses stored offers unless `refetch` is supplied, and also sends ntfy.
- `GET /recipes` with JSON returns `week_key`, `generated_at`, `is_fallback`, `stores_fetched`, `stores_failed`, `recipes` and `offers`.
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

Behavior:
- `GET /entertainment` returns `films`, `concerts` and `music`.
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
