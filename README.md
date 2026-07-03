# Smart Home Hub

[![CI](https://github.com/Bramvzw/smart-home-hub/actions/workflows/ci.yml/badge.svg)](https://github.com/Bramvzw/smart-home-hub/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)

A modular, self-hosted smart-home dashboard built with Laravel — running on a Synology NAS, displayed on a Raspberry Pi kiosk. It controls the lights, plans the week, watches prices, curates the news, and starts every day with an AI-composed briefing.

![Dashboard](docs/screenshots/dashboard.png)

## Modules

| Module | What it does |
|---|---|
| **Briefing** | AI-composed morning briefing (Claude via [Prism](https://prismphp.com)) aggregating weather, agenda, tasks, news, deals, 3D-print stock and more — with a deterministic fallback when AI is unavailable |
| **Calendar** | Google Calendar agenda plus an AI weekly planner that proposes time blocks around your existing appointments |
| **Tasks** | Local Kanban boards with labels, recurring maintenance tasks and habit streak tracking |
| **Lighting** | Tuya (Calex) & Govee lamp control with deterministic presets and optional weather-triggered scenes (rain → cozy) |
| **Spotify** | Playback control, queue, device transfer, search and library |
| **Entertainment** | Upcoming concerts (Ticketmaster, Bandsintown, Hedon), films (TMDB) and new music releases — flagged when already in your Spotify library |
| **Recipes** | AI-generated weekly menu based on current supermarket offers, with per-recipe savings matched against tracked deals |
| **Deals** | Price tracking across bol.com, Amazon and Tweakers with ntfy push alerts on drops |
| **News** | RSS/Atom feeds grouped by topic, with keyword alerts |
| **Weather** | Open-Meteo forecast with rain, wind and daily-summary notifications |
| **Printer** | Bambu Lab filament & parts inventory with low-stock reminders |
| **PhonePing** | Ring your phone through ntfy when it's lost in the house |
| **Settings** | Central settings page — modules expose their own configuration schema |

| | |
|---|---|
| ![Daily briefing](docs/screenshots/briefing.png) | ![Calendar](docs/screenshots/calendar.png) |
| ![Tasks](docs/screenshots/tasks.png) | ![Recipes](docs/screenshots/recipes.png) |
| ![Deals](docs/screenshots/deals.png) | ![News](docs/screenshots/news.png) |
| ![Entertainment](docs/screenshots/entertainment.png) | ![3D printer](docs/screenshots/printer.png) |
| ![Settings](docs/screenshots/settings.png) | ![Mobile](docs/screenshots/dashboard-mobile.png) |

## Architecture

The project is a **modular monolith** ([nwidart/laravel-modules](https://github.com/nWidart/laravel-modules)): every feature lives in its own module under `Modules/`, with a shared contract layer in `app/` for cross-module concerns (briefing sources, schedulable goals, health checks, notifications, settings).

```
Modules/{Module}/
├── Actions/          # business use cases (writes)
├── Services/         # external integrations (HTTP clients, token stores)
├── Data/             # DTOs
├── Models/           # Eloquent + query builders
├── Http/             # thin controllers, form requests, resources
├── View/ViewModels/  # read-side page models
├── Briefing/         # this module's contribution to the morning briefing
└── tests/            # module-scoped Feature + Unit tests
```

The architecture rules live in [AGENTS.md](AGENTS.md); the documentation vault starts at [docs/vault/README.md](docs/vault/README.md).

**Stack:** Laravel 12 · Livewire · Blade · Vite · SQLite · [Prism](https://prismphp.com) (Anthropic) · Docker on Synology · Raspberry Pi kiosk frontend

## Getting started

```bash
git clone https://github.com/Bramvzw/smart-home-hub.git
cd smart-home-hub
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
composer dev   # serves app + queue + logs + vite in one command
```

Each integration (Spotify, Google Calendar, Tuya, Govee, TMDB, Anthropic, ntfy) is optional and configured through `.env` — see `.env.example`. Modules degrade gracefully when their integration isn't configured.

## Testing

```bash
composer test   # PHPUnit — all module Feature + Unit suites
npm test        # Jest — module frontend specs
```

## Deployment

Deploys to a Synology NAS via a single command (`make release`): builds assets, syncs the release over SSH and restarts the Docker stack. See [docs/vault/Setup](docs/vault/Setup) for NAS and Raspberry Pi kiosk setup.

## License

[MIT](LICENSE)
