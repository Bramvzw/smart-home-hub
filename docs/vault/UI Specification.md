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

Card fields:
- Title is always visible.
- Description preview, labels, deadline, checklist progress and archived state are visible when present.
- Priority is shown as a subtle left accent bar.
