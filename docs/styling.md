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

The Lighting screen uses a module-local console stylesheet at `Modules/Lighting/resources/assets/css/lighting.css`.

- Use the `lighting-console*` classes for the Variant B master-detail layout, lamp rail, scene buttons, stage glow, switches and colour ring.
- Active preset display uses `lighting-console__scene[data-active="true"]` and `lighting-console__scene-active`.
- Runtime lamp values may be injected as inline CSS variables such as `--light-color` and `--light-brightness`.
- Keep provider/action behavior in `Modules/Lighting/resources/assets/js/lighting.js`; CSS should only describe presentation and responsive structure.

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
