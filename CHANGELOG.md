# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versions follow
[Semantic Versioning](https://semver.org/) (0.x while the API is settling).
`bin/release.sh` rolls the Unreleased section into a new version on release.

## [Unreleased]

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

[Unreleased]: https://github.com/Bramvzw/smart-home-hub/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/Bramvzw/smart-home-hub/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/Bramvzw/smart-home-hub/releases/tag/v0.1.0
