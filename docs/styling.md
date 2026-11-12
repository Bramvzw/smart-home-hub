# Styling Architecture

This app uses small, explicit styling systems per area. The goal is consistency without introducing a heavy design-system framework.

---

## Principles

- Reusable UI belongs in Blade components, module components or named CSS classes.
- Page files may compose layout, but should not invent new colors, button styles, inputs, pills or typography.
- Inline styles are allowed only for runtime values that cannot be known in CSS.
- If a style pattern appears three times, promote it to a class or component.

---

## Web

Primary shared entry points:

- `resources/css/app.css`
- Module-local CSS under `Modules/{Module}/resources/assets/css/`

Rules:

- `resources/css/app.css` owns the app-wide Smart Home Hub tokens: `--hub-bg`, `--hub-sidebar`, `--hub-surface`, `--hub-elevated`, `--hub-card`, `--hub-line`, `--hub-text`, `--hub-muted`, `--hub-dim`, `--hub-accent` and semantic variants.
- The shared shell header and sidebar brand use `--hub-topbar-height`; keep them on the same fixed height so their bottom borders align.
- Shared product UI should use `hub-*` component classes (`hub-shell`, `hub-sidebar`, `hub-nav-link`, `hub-card`, `hub-action`, `hub-input`, `hub-empty`) before adding new one-off styles.
- Keep shared dashboard styling in the shared CSS entry.
- Keep module-specific styling in the module asset when it does not belong to the dashboard shell.
- The shared touch keyboard uses `touch-keyboard*` classes in `resources/css/app.css` because it is loaded by the app shell and applies across modules.
- Prefer component classes for buttons, cards, pills, controls, empty states and modal surfaces.
- Avoid one-off utility-heavy Blade markup for product UI.
- Keep dynamic inline style fragments small and obvious.

---

## JavaScript-Owned UI

When JavaScript renders or mutates UI:

- Keep state and rendering helpers in module-local JS files.
- Keep shared shell behavior in `resources/js/` when it must work across modules, such as the touch keyboard and the sidebar collapse logic (`resources/js/sidebar.js`).
- The sidebar collapse state (`expanded | rail | hidden`) is styled via the `body[data-sidebar]` attribute; the allowed values are owned by `App\Dashboard\SidebarState` and mirrored in `resources/js/sidebar.js`.
- Do not duplicate backend business rules in JavaScript.
- Document user-visible behavior in `docs/vault/UI Specification.md` when it changes.

---

## Tasks

The active task UI is the local Kanban board in `Modules/Tasks`. Keep task styling in `Modules/Tasks/resources/assets/css/tasks.css`, but source shared surfaces, text, lines and accent values from the global `--hub-*` tokens.

## Lighting

The Lighting kiosk uses a module-local stylesheet at `Modules/Lighting/resources/assets/css/lighting.css`. Surfaces, text, lines and the accent come from the `--hub-*` tokens; only lamp colours and a few danger/neutral tints are local. Headings use Space Grotesk (`--lighting-display`), loaded like the other kiosk modules.

- `lighting`, `lighting__header`, `lighting-button` (`--ghost`, `--toggle`, `--primary`, `--danger`, `--wide`) and `lighting-icon-button` form the page chrome. Buttons and touch targets are 48–56 px high.
- **Hidden-scrollbar strip:** `lighting-strip` clips a taller `lighting-strip__scroller` (extra bottom padding pushes any overlay scrollbar out of view, `scrollbar-width: none` plus `::-webkit-scrollbar { display: none }`) and fades its right edge with a `mask-image` gradient. Scene buttons are `lighting-scene`; the dot colour is the runtime variable `--scene-dot`, the active state is `aria-pressed="true"`.
- **Tile:** `lighting-tile` inside `lighting-grid`. `lighting.js` sets `--tile-rgb` (`"r g b"`), `--tile-alpha` and `--tile-flat-alpha` from the lamp colour/white temperature and brightness; CSS turns them into the radial glow (`data-on="true"`) or, under `html.low-power`, a flat fill. State variants are data attributes: `data-on`, `data-reachable`, `data-pending`, `data-failed`, `data-selected`, and `.lighting[data-mode="select"]` for select mode. Every tile renders all of its parts (⋯, check, pending pill, bar, offline badge); CSS shows the right ones per state, so a lamp that changes reachability on a poll repaints without new markup.
- **Slider primitive:** `lighting-slider` (`--big` for the sheet) is a visual track/fill/thumb over a transparent native `range` input, so touch, keyboard and screen readers keep native behaviour. JS sets `--slider-pct`, and where needed `--slider-fill`, `--slider-ring` and `--slider-track`. The tile's brightness bar dims (`opacity: 0.4`) while the lamp is off.
- **Gradient slider:** `lighting-slider--gradient` with `lighting-slider__track--temp` (warm `rgb(255 168 82)` → neutral → cool `rgb(214 228 255)`) for colour temperature; the saturation slider gets its hue gradient through `--slider-track`.
- **Bottom sheet:** `lighting-overlay` (scrim button + panel) holds `lighting-sheet`: 548 px high with 28 px top radius on the kiosk, nearly full height on a phone (where the body stacks and scrolls). Inside: `lighting-switch`, `lighting-segmented`, `lighting-chip`, the conic `lighting-ring` (handle position via `--handle-x/--handle-y`, preview colour via `--preview-rgb`) and the white `lighting-disc`.
- **Scene strip extras:** `lighting-scene--user` wraps a user scene's `lighting-scene__apply` button and its `lighting-scene__menu` (⋯); the accent is `--scene-dot`. `lighting-scene--add` is the dashed `+ Save scene` button.
- **Dialogs:** `lighting-dialog` (centred card, `--menu` for the narrow scene menu) inside a `lighting-overlay`, with `lighting-menu-item` (`--danger`), `lighting-field` / `lighting-field__input`, `lighting-swatch` (colour via `--swatch`, chosen = `aria-pressed="true"`) and `lighting-checklist__row` (dot colour via `--row-dot`).
- `lighting-selbar` (floating selection bar), `lighting-toast` (error toast with dismiss) and `lighting-empty` (empty states) complete the set.
- The scene strip (`.lighting-strip`) is the last child of `.lighting-grid`: `order: -1` makes it the full-width first row on regular screens. Under `@media (max-height: 720px)` (the 1024×600 kiosk) it moves after the last lamp and spans exactly the free cells (`:has()` + `:nth-child(4n+k):nth-last-child(2)`, 3-column variant below 1000px) as a block of scene buttons laid out on the same columns and 12px gap as the tiles (`--strip-cols`), two rows filling the tile height; `contain: size` keeps it from growing the row. Header buttons shrink, the provider label hides and tiles take half the remaining height (`clamp(138px, (100dvh - 144px) / 2, 260px)`), so two rows of lamps with their sliders fit without scrolling.
- Pending is a `Sending…` pill plus a dashed border; its dot pulses except under `html.low-power`. Failures use `data-failed="true"` (red ring) and the toast.
- Keep provider/action behaviour in `Modules/Lighting/resources/assets/js/lighting.js`; CSS only describes presentation and responsive structure.

## Weather

The Weather screen uses a module-local stylesheet at `Modules/Weather/resources/assets/css/weather.css`.

- Use the `weather-*` classes for the imported Weather Module composition: location topbar, current-weather card, status column, today/tomorrow cards, hourly strip, inline weather alerts and last-message panel.
- The palette is restrained and dashboard-focused: warm dark neutrals, sky blue for rain, green for dry state, teal for wind and amber/orange for warning context.
- Runtime weather values remain in Blade; `Modules/Weather/resources/assets/js/weather.js` only handles in-place live refresh of the weather content region.
- Keep weather-alert behavior in `Modules/Weather/Services/WeatherService` and scheduled orchestration in `Modules/Weather/Actions/`.

## Spotify

The Spotify afspeelpaneel uses a module-local stylesheet at `Modules/Spotify/resources/assets/css/player.css`, loaded as a Vite entry by the Spotify Blade view.

- Use `spotify-*` classes for the cinematic player shell, artwork stage, centered playback stack, pill tabs, transport controls, mini player, playlist tiles, track rows and connect/empty states.
- Spotify-specific visual tokens live in `player.css` and use warm amber OKLCH accents over the shared dark dashboard shell.
- Keep backend and playback behavior in module-local JS. Blade and CSS must preserve the existing DOM IDs used by the controller, such as `play-pause-btn`, `progress-bar`, `track-image`, `volume-slider`, `queue-tracks-list`, `search-input` and `recent-tracks-list`.
- Mirrored UI fragments should use data hooks such as `data-track-name`, `data-track-image`, `data-progress-fill`, `data-play-icon`, `data-like-icon` and `data-spotify-control` instead of duplicate IDs.

---

## Migration Target

- New work should reduce one-off styling and raw colors over time.
- Repeated visual primitives should have one obvious source of truth.

## Fonts and low-power mode

- Fonts are self-hosted through `@fontsource/hanken-grotesk` and `@fontsource/space-grotesk` (latin subset), imported once at the top of `resources/css/app.css`. Module stylesheets must not `@import` remote font URLs; the families are available on every page.
- `html.low-power` (see the inline script in `resources/views/components/dashboard/layout.blade.php`) disables backdrop blur, box/text shadows, animations and transitions. It is on when the device chose `?lowpower=1`, or — without a stored choice — when `navigator.deviceMemory` reports 2 GB or less (Raspberry Pi kiosks). Never rely on a shadow or animation to convey state.
