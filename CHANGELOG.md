# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versions follow
[Semantic Versioning](https://semver.org/) (0.x while the API is settling).
`bin/release.sh` rolls the Unreleased section into a new version on release.

## [Unreleased]

## [0.6.0] - 2026-11-10

### Changed

- Fonts are served by the hub itself instead of from the internet, so pages
  render faster on the kiosk and keep their typeface offline.
- Low-power mode (no blur, shadows or animations) now turns on automatically
  on devices with 2 GB RAM or less, such as a Raspberry Pi 3.

## [0.5.0] - 2026-11-10

### Changed

- Small screens (such as a 1024×600 Raspberry Pi kiosk) open with the compact
  icon sidebar, and the menu fits on short screens without scrolling.
- The Spotify page opens faster: the next track now loads after the page is
  shown instead of before.

## [0.4.0] - 2026-11-10

### Added

- Lighting: real white light with adjustable colour temperature for Calex and
  Govee lamps; the Bright, Cozy and Night presets now use warm or cool white
  instead of a bluish RGB imitation.
- Lighting: save your own scenes from the current state of your lamps, then
  rename, update or delete them from the scene strip. A scene changes only the
  lamps stored in it and skips lamps that are offline.
- Lighting: group endpoint `PUT /lighting/lights` to change several lamps at
  once, and `GET /lighting/state` for the live light state.

### Changed

- Lighting: the page is redesigned for the wall kiosk as a grid of lamp tiles.
  Tap a tile to switch it, drag its bottom bar to dim it, and open a control
  sheet with `⋯` for brightness, warm-to-cool white or colour with saturation.
  `Select` controls several lamps at once.
- Lighting: lamps stay usable while a command runs. Rapid slider moves are
  coalesced per lamp, failed commands roll back with a short notice, and the
  page picks up changes made in the Smart Life or Govee apps every two minutes
  without a reload.

### Fixed

- Scheduled jobs (daily briefing, news refresh, weekly recipes, price checks,
  weather alerts, maintenance tasks) now actually run in production: the
  scheduler ships as its own Docker Compose service.
- Lighting: picking a colour or moving a slider on a dimmed Calex lamp keeps
  its brightness instead of jumping to 100 %, and each change is sent as one
  command (fewer Tuya cloud calls).
- Lighting: a scene, preset or group change no longer interleaves with a
  single-lamp change on the same lamp, and a group colour change skips lamps
  that cannot show colour (listed as `skipped`) instead of just switching them
  on.
- Lighting: the two-minute state refresh no longer snaps a slider or colour
  back while you are still dragging it.
- Lighting: deleting a scene needs a second tap (`Tap again to delete`), and
  the control sheet and scene dialogs keep keyboard focus inside while open.
- Lighting: a failed group or scene change rolls back exactly the lamps that
  failed, also when several lamps share a name or a whole provider is
  unreachable; quick changes for two different selections are both sent;
  lamps that cannot show a colour are named in a notice; a scene that changed
  nothing is no longer shown as active; and a state refresh that started
  before a scene, preset or group change no longer overwrites it.
- Lighting: `Update to current` keeps the stored state of a lamp that is
  offline at that moment instead of dropping it from the scene, and applying a
  scene reports stored lamps that no longer exist (`missing_lights`).
- Lighting: a scene, preset or group change that waits for a single-lamp
  change now builds its commands from the state that change left behind, and a
  state refresh that overlaps a change no longer caches the old state for two
  minutes.

## [0.3.0] - 2026-10-11

### Changed

- Modules are reordered by dragging on the settings page instead of typing order numbers.

- News feeds, topics and keywords are now generic by default and
  configurable per install via `NEWS_KEYWORDS` and a `NEWS_FEEDS_FILE`
  JSON override, instead of shipping personal defaults.

## [0.2.0] - 2026-07-08

### Added

- Module management: hide modules and reorder the sidebar and dashboard from
  `/settings`; scheduled jobs of disabled modules are skipped at run time.
- Configurable recipes output language (`RECIPES_LANGUAGE`, default `nl`).
- Configurable recipes offer stores (`RECIPES_STORES`, default `ah,lidl`).
- Commit message convention enforced via a tracked `commit-msg` hook and a CI
  job; releases now tag a semantic version and update this changelog.

### Changed

- Calendar reads every Google calendar the user has selected (not just the
  primary one) and renders in the calendar timezone (`CALENDAR_TIMEZONE`).

## [0.1.0] - 2026-07-05

First tagged release — the hub as it runs on the NAS:

- Twelve modules: Briefing, Calendar, Deals, Entertainment, Lighting, News,
  PhonePing, Printer, Recipes, Spotify, Tasks and Weather.
- AI-composed daily briefing with per-module briefing sources.
- Hourly module health sweep with ntfy push on regressions and recoveries.
- Quality gates in CI: Pint, Rector, PHPStan level 8 with a burn-down
  baseline, PHPat architecture rules and the PHPUnit suite.
- Kiosk-oriented dashboard with a low-power mode for wall displays.

[Unreleased]: https://github.com/Bramvzw/smart-home-hub/compare/v0.6.0...HEAD
[0.6.0]: https://github.com/Bramvzw/smart-home-hub/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/Bramvzw/smart-home-hub/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/Bramvzw/smart-home-hub/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/Bramvzw/smart-home-hub/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/Bramvzw/smart-home-hub/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/Bramvzw/smart-home-hub/releases/tag/v0.1.0
