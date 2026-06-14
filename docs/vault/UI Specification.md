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
- When the sidebar is collapsed, labels and the collapse chevron are hidden so only centered brand and module icons remain visible.

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
- Presets include bright, cozy, movie, night and all-off scenes. They call the preset JSON endpoint, temporarily disable the clicked preset button, update reachable light cards optimistically and skip unreachable lights.
- The active preset button uses `data-active="true"`, `aria-pressed="true"` and an `Actief` badge. Manual power, brightness or colour changes recalculate this state immediately.
- Presets skip unchanged power, brightness and colour commands from the current snapshot to reduce cloud latency, especially for Govee LED strips.
- Govee commands are paced and transient failures are retried so quick preset changes are less likely to drop a brightness or colour command.
- While a Lighting action is running, reachable switches, sliders, colour inputs and preset buttons are disabled so commands cannot overlap from the same browser.
- The backend serialises all Lighting write requests with a short cache lock. If another device submits during an active action, the JSON endpoint returns `409` with a busy message instead of interleaving commands.
- Control changes call the per-light JSON endpoint and optimistically update the UI; a failed update marks the card with the danger ring.
- Secrets and provider tokens are never rendered.

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
